<?php

namespace Tests\Support;

use Illuminate\Contracts\Foundation\Application;
use RuntimeException;

final class TestDatabaseSafety
{
    private const TEST_CONNECTION = 'mysql';

    private const TEST_DATABASE = 'mrj_test_db';

    private const FORBIDDEN_DATABASES = ['mrj_prod_db'];

    /**
     * This is deliberately invoked while Laravel boots the test case, before
     * RefreshDatabase can issue migrate:fresh.
     */
    public static function assertSafe(Application $app): void
    {
        $configuration = self::configuration($app);
        $violations = [];

        if ($configuration['app_env'] !== 'testing') {
            $violations[] = 'APP_ENV must equal testing';
        }
        if ($configuration['connection'] !== self::TEST_CONNECTION) {
            $violations[] = 'DB_CONNECTION must equal '.self::TEST_CONNECTION;
        }
        if ($configuration['database'] !== self::TEST_DATABASE) {
            $violations[] = 'DB_DATABASE must equal '.self::TEST_DATABASE;
        }
        if (in_array($configuration['database'], self::FORBIDDEN_DATABASES, true)) {
            $violations[] = 'DB_DATABASE is a forbidden production database';
        }

        if ($violations !== []) {
            throw new RuntimeException('Unsafe test database configuration: '.implode('; ', $violations).'. Refusing to start tests before RefreshDatabase/migrate:fresh.');
        }
    }

    /** @return array{app_env: string, connection: string, host: ?string, port: ?string, database: string} */
    public static function configuration(Application $app): array
    {
        $connection = (string) $app['config']->get('database.default');
        $settings = $app['config']->get("database.connections.{$connection}", []);

        return [
            'app_env' => (string) $app->environment(),
            'connection' => $connection,
            'host' => isset($settings['host']) ? (string) $settings['host'] : null,
            'port' => isset($settings['port']) ? (string) $settings['port'] : null,
            'database' => (string) ($settings['database'] ?? ''),
        ];
    }
}
