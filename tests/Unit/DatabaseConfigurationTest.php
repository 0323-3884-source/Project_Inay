<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DatabaseConfigurationTest extends TestCase
{
    #[DataProvider('connectionCases')]
    public function test_hosted_database_driver_detection(array $variables, string $expected): void
    {
        $keys = ['DB_CONNECTION', 'DB_URL', 'DATABASE_URL', 'MYSQL_URL', 'MYSQLHOST', 'PGHOST'];
        $original = [];
        foreach ($keys as $key) {
            $original[$key] = [getenv($key), $_ENV[$key] ?? null, $_SERVER[$key] ?? null];
            putenv($key);
            unset($_ENV[$key], $_SERVER[$key]);
        }

        try {
            foreach ($variables as $key => $value) {
                putenv($key.'='.$value);
                $_ENV[$key] = $_SERVER[$key] = $value;
            }
            // database_path() in the config requires a booted application.
            $app = require dirname(__DIR__, 2).'/bootstrap/app.php';
            $config = require dirname(__DIR__, 2).'/config/database.php';
            $this->assertSame($expected, $config['default']);
        } finally {
            foreach ($original as $key => [$processValue, $envValue, $serverValue]) {
                putenv($processValue === false ? $key : $key.'='.$processValue);
                unset($_ENV[$key], $_SERVER[$key]);
                if ($envValue !== null) $_ENV[$key] = $envValue;
                if ($serverValue !== null) $_SERVER[$key] = $serverValue;
            }
        }
    }

    public static function connectionCases(): array
    {
        return [
            'mysql database url' => [['DATABASE_URL' => 'mysql://user:password@db:3306/inay'], 'mysql'],
            'mysql db url' => [['DB_URL' => 'mysql://user:password@db:3306/inay'], 'mysql'],
            'postgres db url' => [['DB_URL' => 'postgresql://user:password@db:5432/inay'], 'pgsql'],
            'railway mysql url' => [['MYSQL_URL' => 'mysql://user:password@db:3306/inay'], 'mysql'],
            'mysql host' => [['MYSQLHOST' => 'mysql.railway.internal'], 'mysql'],
            'postgres host' => [['PGHOST' => 'postgres.railway.internal'], 'pgsql'],
            'explicit driver wins' => [['DB_CONNECTION' => 'mysql', 'PGHOST' => 'db'], 'mysql'],
            'db url takes precedence' => [['DB_URL' => 'mysql://db/inay', 'DATABASE_URL' => 'postgres://db/inay'], 'mysql'],
            'local fallback' => [[], 'sqlite'],
        ];
    }
}
