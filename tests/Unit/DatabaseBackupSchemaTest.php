<?php

namespace Tests\Unit;

use App\Support\DatabaseBackup;
use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DatabaseBackupSchemaTest extends TestCase
{
    private function schema(string $engine): array
    {
        $connection = \Mockery::mock(Connection::class);
        $schema = \Mockery::mock(Builder::class);
        DB::shouldReceive('connection')->andReturn($connection);
        $connection->shouldReceive('getDriverName')->andReturn('mysql');
        $connection->shouldReceive('getDatabaseName')->once()->andReturn('synthetic_inay');
        $connection->shouldReceive('getSchemaBuilder')->andReturn($schema);
        // An unscoped call would expose unrelated schemas and must fail this test.
        $schema->shouldReceive('getTables')->once()->with('synthetic_inay')->andReturn([['name' => 'mothers', 'engine' => $engine]]);
        if ($engine === 'InnoDB') {
            $schema->shouldReceive('getColumns')->with('mothers')->andReturn([['name' => 'id', 'generation' => null]]);
            $schema->shouldReceive('getForeignKeys')->with('mothers')->andReturn([]);
        }
        $method = new \ReflectionMethod(DatabaseBackup::class, 'structure');
        return $method->invoke(new DatabaseBackup);
    }

    public function test_mysql_backup_table_scan_is_scoped_to_the_current_database(): void
    {
        $this->assertSame(['mothers'], array_keys($this->schema('InnoDB')));
    }

    public function test_real_nontransactional_tables_are_still_rejected(): void
    {
        $this->expectException(ValidationException::class);
        $this->schema('MyISAM');
    }
}
