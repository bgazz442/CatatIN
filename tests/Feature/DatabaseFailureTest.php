<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class DatabaseFailureTest extends TestCase
{
    public function test_database_failure_returns_safe_message_and_is_reported_to_log(): void
    {
        $this->assertFalse(config('app.debug'));

        config([
            'app.debug' => false,
            'database.default' => 'qa_e07_failure',
            'database.connections.qa_e07_failure' => [
                'driver' => 'mysql',
                'host' => '127.0.0.1',
                'port' => '1',
                'database' => 'qa_e07_isolated_database',
                'username' => 'qa_e07_test_user',
                'password' => 'QA_E07_SECRET_MARKER',
                'unix_socket' => '',
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'prefix_indexes' => true,
                'strict' => true,
                'engine' => null,
            ],
        ]);
        $testLogPath = storage_path('logs/qa-e07-isolated-test.log');
        if (file_exists($testLogPath)) {
            unlink($testLogPath);
        }

        config([
            'logging.default' => 'qa_e07_test',
            'logging.channels.qa_e07_test' => [
                'driver' => 'single',
                'path' => $testLogPath,
                'level' => 'debug',
                'replace_placeholders' => true,
            ],
        ]);
        Log::setDefaultDriver('qa_e07_test');
        DB::purge('qa_e07_failure');

        try {
            $response = $this->get('/');

            $response->assertStatus(500);
            $response->assertSeeText('CatatIn sedang mengalami gangguan sementara');
            $response->assertSeeText('Data Anda tetap aman. Silakan coba kembali beberapa saat lagi.');

            foreach ([
                'SQLSTATE',
                'qa_e07_isolated_database',
                'qa_e07_test_user',
                'QA_E07_SECRET_MARKER',
                'select count',
                'Stack trace',
                'PDOException',
            ] as $sensitiveDetail) {
                $response->assertDontSee($sensitiveDetail, false);
            }

            $this->assertFileExists($testLogPath);
            $logContents = file_get_contents($testLogPath);
            $this->assertStringContainsString('SQLSTATE', $logContents);
            $this->assertStringContainsString('qa_e07_failure', $logContents);
            $this->assertStringContainsString('select count(*) as aggregate', $logContents);
            $this->assertStringNotContainsString('QA_E07_SECRET_MARKER', $logContents);
        } finally {
            if (file_exists($testLogPath)) {
                unlink($testLogPath);
            }
        }
    }
}