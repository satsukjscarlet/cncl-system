<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    public function actingAs(\Illuminate\Contracts\Auth\Authenticatable $user, $guard = null)
    {
        $guard = $guard ?: $this->app['auth']->getDefaultDriver();
        $current = $this->app['auth']->guard($guard)->user();
        if (!$current || $current->getAuthIdentifier() !== $user->getAuthIdentifier()) {
            // Switching test identities represents a fresh login, not a reused session of another user.
            $this->app['session']->forget('password_hash_' . $guard);
        }

        return parent::actingAs($user, $guard);
    }

    protected function setUp(): void
    {
        $this->guardAgainstUnsafeTestDatabase();

        parent::setUp();
    }

    private function guardAgainstUnsafeTestDatabase(): void
    {
        $configCachePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'bootstrap'
            . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'config.php';

        if (is_file($configCachePath)) {
            throw new RuntimeException(
                'Refusing to run tests while Laravel config is cached. Run "php artisan config:clear" first.'
            );
        }

        $appEnv = $this->testEnvValue('APP_ENV');
        $dbConnection = $this->testEnvValue('DB_CONNECTION');
        $dbDatabase = $this->testEnvValue('DB_DATABASE');

        if ($appEnv !== 'testing') {
            throw new RuntimeException('Refusing to run tests because APP_ENV is not "testing".');
        }

        if ($dbConnection !== 'sqlite' || $dbDatabase !== ':memory:') {
            throw new RuntimeException(
                'Refusing to run tests because the test database is not sqlite/:memory:. '
                . 'This protects real MySQL databases from RefreshDatabase.'
            );
        }
    }

    private function testEnvValue(string $key): ?string
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        return $value === false ? null : (string) $value;
    }
}
