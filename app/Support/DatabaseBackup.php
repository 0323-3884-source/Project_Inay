<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DatabaseBackup
{
    private const HEADER = "-- Project INAY signed database records backup v1\n";

    private function reject(string $message): never
    {
        throw ValidationException::withMessages(['backup' => $message]);
    }

    public function supported(): bool
    {
        return in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb', 'sqlite'], true);
    }

    private function structure(): array
    {
        if (! $this->supported()) $this->reject('SQL backup and recovery currently support MySQL, MariaDB and SQLite only.');
        $schema = DB::connection()->getSchemaBuilder();
        $excluded = array_filter(array_merge(config('backups.excluded_tables'), [config('session.table'), config('cache.stores.database.table'), config('cache.stores.database.lock_table')]));
        $tables = [];
        // Laravel 12 getTables() without a schema can enumerate every database
        // visible to the MySQL user. Never back up or inspect other databases.
        $database = DB::connection()->getDriverName() === 'sqlite' ? 'main' : DB::connection()->getDatabaseName();
        if (! $database) $this->reject('The active database could not be identified.');
        foreach ($schema->getTables($database) as $table) {
            $name = $table['name'];
            if (in_array($name, $excluded, true) || str_starts_with($name, 'sqlite_')) continue;
            if (! preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/D', $name)) $this->reject('Unsupported database table identifier.');
            if (in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true) && strtolower($table['engine'] ?? '') !== 'innodb') {
                $this->reject('Backup recovery requires InnoDB for table '.$name.' (engine: '.($table['engine'] ?? 'not reported').'). No data was changed.');
            }
            $columns = $schema->getColumns($name);
            $tables[$name] = [
                'columns' => array_values(array_map(fn ($column) => $column['name'], array_filter($columns, fn ($column) => empty($column['generation'])))),
                'fingerprint' => hash('sha256', json_encode([$columns, $schema->getForeignKeys($name)], JSON_THROW_ON_ERROR)),
            ];
        }
        ksort($tables);
        return $tables;
    }

    private function wrap(string $identifier): string
    {
        return DB::connection()->getQueryGrammar()->wrap($identifier);
    }

    private function wrapper(): array
    {
        return DB::connection()->getDriverName() === 'sqlite'
            ? ["PRAGMA foreign_keys=OFF;\nBEGIN TRANSACTION;\n", "COMMIT;\nPRAGMA foreign_keys=ON;\n"]
            : ["SET FOREIGN_KEY_CHECKS=0;\nSTART TRANSACTION;\n", "COMMIT;\nSET FOREIGN_KEY_CHECKS=1;\n"];
    }

    private function value(mixed $value): string
    {
        if ($value === null) return 'NULL';
        // Hex encoding keeps newlines, quotes, Unicode and SQL-like text inert.
        $hex = bin2hex((string) $value);
        return DB::connection()->getDriverName() === 'sqlite' ? "CAST(X'{$hex}' AS TEXT)" : "CONVERT(X'{$hex}' USING utf8mb4)";
    }

    public function create(): string
    {
        if (! config('app.key')) $this->reject('An application key is required before creating signed backups.');
        $tables = $this->structure();
        $driver = DB::connection()->getDriverName();
        $meta = ['version' => 1, 'driver' => $driver, 'created_at' => now()->toIso8601String(), 'tables' => $tables];
        [$prefix, $suffix] = $this->wrapper();
        $body = '-- Metadata: '.base64_encode(json_encode($meta, JSON_THROW_ON_ERROR))."\n".$prefix;
        $append = function (string $sql) use (&$body): void {
            $body .= '-- Statement: '.strlen($sql)."\n".$sql."\n";
            if (strlen($body) > config('backups.max_bytes') - 1024) $this->reject('The database exceeds the web backup size limit. Use an operator-managed database backup.');
        };
        if ($driver !== 'sqlite' && DB::connection()->transactionLevel() === 0) {
            DB::statement('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
        }
        DB::transaction(function () use ($tables, $append): void {
            foreach ($tables as $table => $definition) $append('DELETE FROM '.$this->wrap($table).';');
            foreach ($tables as $table => $definition) {
                $columns = $definition['columns'];
                $insert = 'INSERT INTO '.$this->wrap($table).' ('.implode(', ', array_map($this->wrap(...), $columns)).') VALUES ';
                $batch = [];
                foreach (DB::table($table)->select($columns)->cursor() as $row) {
                    $values = array_map(fn ($column) => $this->value($row->$column), $columns);
                    $batch[] = '('.implode(', ', $values).')';
                    if (count($batch) >= 100) {
                        $append($insert.implode(', ', $batch).';');
                        $batch = [];
                    }
                }
                if ($batch) $append($insert.implode(', ', $batch).';');
            }
        });
        $body .= $suffix;
        $signature = hash_hmac('sha256', $body, (string) config('app.key'));
        $contents = self::HEADER.'-- Signature: '.$signature."\n".$body;
        $path = 'database-backups/'.now()->format('Ymd-His').'-'.Str::uuid().'.sql';
        if (! Storage::disk('local')->put($path, $contents)) $this->reject('Could not save the private backup. No data was changed.');
        return $path;
    }

    public function inspect(string $contents): array
    {
        if (strlen($contents) > config('backups.max_bytes') || ! str_starts_with($contents, self::HEADER)) $this->reject('Select an unmodified Project INAY SQL backup within the size limit.');
        $rest = substr($contents, strlen(self::HEADER));
        $end = strpos($rest, "\n");
        if ($end === false) $this->reject('Invalid backup header.');
        $signature = substr($rest, 0, $end);
        $body = substr($rest, $end + 1);
        if (! preg_match('/^-- Signature: ([a-f0-9]{64})$/D', $signature, $match)
            || ! hash_equals(hash_hmac('sha256', $body, (string) config('app.key')), $match[1])) {
            $this->reject('Backup signature is invalid. Only unmodified backups from this installation are accepted.');
        }
        $metaEnd = strpos($body, "\n");
        if ($metaEnd === false || ! str_starts_with($body, '-- Metadata: ')) $this->reject('Missing backup metadata.');
        try {
            $meta = json_decode(base64_decode(substr($body, 13, $metaEnd - 13), true), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) { $this->reject('Invalid backup metadata.'); }
        if (($meta['version'] ?? null) !== 1 || ($meta['driver'] ?? null) !== DB::connection()->getDriverName() || ($meta['tables'] ?? null) !== $this->structure()) {
            $this->reject('The backup database type or schema does not match this installation. No data was changed.');
        }
        [$prefix, $suffix] = $this->wrapper();
        $sql = substr($body, $metaEnd + 1);
        if (! str_starts_with($sql, $prefix) || ! str_ends_with($sql, $suffix)) $this->reject('Invalid SQL backup envelope.');
        $sql = substr($sql, strlen($prefix), -strlen($suffix));
        $statements = [];
        $offset = 0;
        while ($offset < strlen($sql)) {
            $lineEnd = strpos($sql, "\n", $offset);
            if ($lineEnd === false || ! preg_match('/^-- Statement: ([0-9]+)$/D', substr($sql, $offset, $lineEnd - $offset), $length)) $this->reject('Invalid SQL statement boundary.');
            $count = (int) $length[1];
            if ($count < 1 || $count > strlen($sql) - $lineEnd - 2 || substr($sql, $lineEnd + 1 + $count, 1) !== "\n") $this->reject('Truncated SQL statement.');
            $statements[] = substr($sql, $lineEnd + 1, $count);
            $offset = $lineEnd + 2 + $count;
        }
        return ['metadata' => $meta, 'statements' => $statements];
    }

    public function restore(array $verified, ?\Closure $beforeCommit = null): void
    {
        $sqlite = DB::connection()->getDriverName() === 'sqlite';
        // Deferred constraints work even inside the synthetic-test transaction.
        if ($sqlite) DB::statement('PRAGMA defer_foreign_keys=ON');
        else DB::statement('SET FOREIGN_KEY_CHECKS=0');
        try {
            DB::transaction(function () use ($verified, $sqlite, $beforeCommit): void {
                foreach ($verified['statements'] as $statement) DB::unprepared($statement);
                if ($sqlite && DB::select('PRAGMA foreign_key_check') !== []) $this->reject('Restored data violates record relationships. Recovery was rolled back.');
                if (! $sqlite) {
                    // MySQL does not validate existing rows when checks are re-enabled.
                    foreach (array_keys($verified['metadata']['tables']) as $table) {
                        foreach (DB::connection()->getSchemaBuilder()->getForeignKeys($table) as $fk) {
                            $query = DB::table($table.' as child')->leftJoin($fk['foreign_table'].' as parent', function ($join) use ($fk) {
                                foreach ($fk['columns'] as $index => $column) $join->on('child.'.$column, '=', 'parent.'.$fk['foreign_columns'][$index]);
                            });
                            foreach ($fk['columns'] as $column) $query->whereNotNull('child.'.$column);
                            if ($query->whereNull('parent.'.$fk['foreign_columns'][0])->exists()) $this->reject('Restored data violates record relationships. Recovery was rolled back.');
                        }
                    }
                }
                if (! DB::table('admin_users')->whereIn('role', ['admin', 'admin_cho', 'admin_dswd'])->exists()) {
                    $this->reject('The backup has no usable administrator role. Recovery was rolled back.');
                }
                if ($beforeCommit) $beforeCommit();
            });
        } finally {
            if ($sqlite) DB::statement('PRAGMA defer_foreign_keys=OFF');
            else DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }
}
