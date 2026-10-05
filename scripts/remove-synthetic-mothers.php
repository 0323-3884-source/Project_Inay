<?php

// Local-only cleanup of the exact 20 synthetic accounts. Preview by default.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Mother;

$connection = DB::connection();
if (! $app->environment('local') || $connection->getDriverName() !== 'mysql'
    || ! in_array($connection->getConfig('host'), ['localhost', '127.0.0.1'], true)) {
    throw new RuntimeException('Cleanup is limited to the local MySQL database.');
}
$emails = array_map(fn ($i) => sprintf('synthetic.mother.%03d@example.test', $i), range(1, 20));
$targets = Mother::whereIn('email', $emails)->orderBy('id')->get(['id', 'first_name', 'last_name', 'email']);
foreach ($targets as $mother) {
    if (! preg_match('/^Synthetic\d{3}$/', $mother->last_name) || $mother->id === 1) {
        throw new RuntimeException('Unexpected identity; no cleanup performed.');
    }
}
echo json_encode(['database'=>$connection->getDatabaseName(), 'count'=>$targets->count(), 'targets'=>$targets->toArray()], JSON_PRETTY_PRINT).PHP_EOL;
if (! in_array('--apply', $argv, true)) exit(0);
if ($targets->count() !== 20) throw new RuntimeException('Expected exactly 20 synthetic accounts.');

$backupDirectory = storage_path('app/private/database-backups');
if (! is_dir($backupDirectory) && ! mkdir($backupDirectory, 0700, true)) throw new RuntimeException('Cannot create backup directory.');
$backupPath = $backupDirectory.'/before-synthetic-cleanup-'.date('Ymd-His').'.json';
$connection->transaction(function () use ($connection, $targets, $backupPath) {
    $backup = ['database'=>$connection->getDatabaseName(), 'tables'=>[]];
    foreach ($connection->select('SHOW FULL TABLES WHERE Table_type = ?', ['BASE TABLE']) as $table) {
        $name = array_values((array) $table)[0];
        $quoted = '`'.str_replace('`', '``', $name).'`';
        $backup['tables'][$name] = [
            'schema'=>array_values((array) $connection->selectOne('SHOW CREATE TABLE '.$quoted))[1],
            'rows'=>$connection->table($name)->get()->toArray(),
        ];
    }
    if (file_put_contents($backupPath, json_encode($backup, JSON_THROW_ON_ERROR), LOCK_EX) === false) {
        throw new RuntimeException('Backup failed; nothing deleted.');
    }
    // Keep uploaded files on disk for recovery; database-owned dependents
    // are removed by the application's existing foreign-key cascades.
    $deleted = Mother::whereIn('id', $targets->modelKeys())->delete();
    if ($deleted !== 20 || ! Mother::where('id', 1)->where('first_name', 'Martha')->exists()
        || ! $connection->table('infants')->where('id', 1)->where('mother_id', 1)->exists()) {
        throw new RuntimeException('Preservation check failed; rolling back.');
    }
    echo 'Deleted '.$deleted.' synthetic mother accounts. Martha and James Juan preserved.'.PHP_EOL;
});
echo 'Full database schema/data backup: '.$backupPath.PHP_EOL;
