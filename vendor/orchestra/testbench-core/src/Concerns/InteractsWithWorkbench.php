<?php

namespace Orchestra\Testbench\Concerns;

use Illuminate\Support\Arr;
use Orchestra\Testbench\Foundation\Config;

trait InteractsWithWorkbench
{
    use InteractsWithPHPUnit;

    /**
     * The cached test case configuration.
     *
     * @var \Orchestra\Testbench\Contracts\Config|null
     */
    protected static $cachedConfigurationForWorkbench;

    /**
     * Get Application's base path.
     *
     * @return string|null
     */
    public static function applicationBasePathUsingWorkbench()
    {
        if (! static::usesTestingConcern()) {
            return $_ENV['APP_BASE_PATH'] ?? null;
        }

        return $_ENV['APP_BASE_PATH'] ?? $_ENV['TESTBENCH_APP_BASE_PATH'] ?? null;
    }

    /**
     * Ignore package discovery from.
     *
     * @return array<int, string>|null
     */
    public function ignorePackageDiscoveriesFromUsingWorkbench()
    {
        if (property_exists($this, 'enablesPackageDiscoveries') && \is_bool($this->enablesPackageDiscoveries)) {
            return $this->enablesPackageDiscoveries === false ? ['*'] : [];
        }

        return static::usesTestingConcern(WithWorkbench::class)
            ? (static::$cachedConfigurationForWorkbench?->getExtraAttributes()['dont-discover'] ?? [])
            : null;
    }

    /**
     * Get package bootstrapper.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<int, class-string>|null
     */
    protected function getPackageBootstrappersUsingWorkbench($app)
    {
        if (empty($bootstrappers = (static::$cachedConfigurationForWorkbench?->getExtraAttributes()['bootstrappers'] ?? null))) {
            return null;
        }

        return static::usesTestingConcern(WithWorkbench::class)
            ? Arr::wrap($bootstrappers)
            : [];
    }

    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<int, class-string>|null
     */
    protected function getPackageProvidersUsingWorkbench($app)
    {
        if (empty($providers = (static::$cachedConfigurationForWorkbench?->getExtraAttributes()['providers'] ?? null))) {
            return null;
        }

        return static::usesTestingConcern(WithWorkbench::class)
            ? Arr::wrap($providers)
            : [];
    }

    /**
     * Prepare the testing environment before the running the test case.
     *
     * @return void
     */
    public static function setupBeforeClassUsingWorkbench(): void
    {
        $workingPath = \defined('TESTBENCH_WORKING_PATH')
            ? TESTBENCH_WORKING_PATH
            : getcwd();

        $config = Config::loadFromYaml($workingPath);

        if (
            ! \is_null($config['laravel'])
            && isset(static::$cachedTestCaseUses[WithWorkbench::class])
        ) {
            $_ENV['TESTBENCH_APP_BASE_PATH'] = $config['laravel'];
        }

        static::$cachedConfigurationForWorkbench = $config;
    }

    /**
     * Clean up the testing environment before the next test case.
     *
     * @return void
     */
    public static function teardownAfterClassUsingWorkbench(): void
    {
        static::$cachedConfigurationForWorkbench = null;

        unset($_ENV['TESTBENCH_APP_BASE_PATH']);
    }
}
