<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $app = require __DIR__ . '/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        if (PHP_OS_FAMILY === 'Windows' && env('DB_HOST') === 'mysql') {
            $host = env('DB_TEST_HOST', '127.0.0.1');
            $port = (int) env('DB_TEST_PORT', env('FORWARD_DB_PORT', env('DB_PORT', 3306)));

            config()->set('database.connections.mysql.host', $host);
            config()->set('database.connections.mysql.port', $port);
            putenv("DB_HOST={$host}");
            putenv("DB_PORT={$port}");
        }

        return $app;
    }
}
