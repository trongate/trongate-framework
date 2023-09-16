<?php

namespace Orchestra\Testbench;

use Illuminate\Foundation\Testing;
use Illuminate\Support\Str;

abstract class TestCase extends PHPUnit\TestCase implements Contracts\TestCase
{
    use Concerns\Testing,
        Testing\Concerns\InteractsWithAuthentication,
        Testing\Concerns\InteractsWithConsole,
        Testing\Concerns\InteractsWithContainer,
        Testing\Concerns\InteractsWithDatabase,
        Testing\Concerns\InteractsWithDeprecationHandling,
        Testing\Concerns\InteractsWithExceptionHandling,
        Testing\Concerns\InteractsWithSession,
        Testing\Concerns\InteractsWithTime,
        Testing\Concerns\MakesHttpRequests;

    /**
     * The base URL to use while testing the application.
     *
     * @var string
     */
    protected $baseUrl = 'http://localhost';

    /**
     * Automatically loads environment file if available.
     *
     * @var bool
     */
    protected $loadEnvironmentVariables = true;

    /**
     * Automatically enables package discoveries.
     *
     * @var bool
     */
    protected $enablesPackageDiscoveries = false;

    /**
     * Setup the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        static::$latestResponse = null;

        $this->setUpTheTestEnvironment();
    }

    /**
     * Clean up the testing environment before the next test.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $this->tearDownTheTestEnvironment();
    }

    /**
     * Boot the testing helper traits.
     *
     * @return array<class-string, class-string>
     */
    protected function setUpTraits()
    {
        return $this->setUpTheTestEnvironmentTraits(static::cachedUsesForTestCase());
    }

    /**
     * Determine trait should be ignored from being autoloaded.
     *
     * @param  class-string  $use
     * @return bool
     */
    protected function setUpTheTestEnvironmentTraitToBeIgnored(string $use): bool
    {
        return Str::startsWith($use, [
            Testing\RefreshDatabase::class,
            Testing\DatabaseMigrations::class,
            Testing\DatabaseTransactions::class,
            Testing\WithoutMiddleware::class,
            Testing\WithoutEvents::class,
            Testing\WithFaker::class,
            Testing\Concerns\InteractsWithAuthentication::class,
            Testing\Concerns\InteractsWithConsole::class,
            Testing\Concerns\InteractsWithContainer::class,
            Testing\Concerns\InteractsWithDatabase::class,
            Testing\Concerns\InteractsWithDeprecationHandling::class,
            Testing\Concerns\InteractsWithExceptionHandling::class,
            Testing\Concerns\InteractsWithSession::class,
            Testing\Concerns\InteractsWithTime::class,
            Testing\Concerns\MakesHttpRequests::class,
            Concerns\CreatesApplication::class,
            Concerns\Database\HandlesConnections::class,
            Concerns\HandlesAnnotations::class,
            Concerns\HandlesDatabases::class,
            Concerns\HandlesRoutes::class,
            Concerns\InteractsWithMigrations::class,
            Concerns\InteractsWithPHPUnit::class,
            Concerns\InteractsWithWorkbench::class,
            Concerns\Testing::class,
            Concerns\WithFactories::class,
            Concerns\WithWorkbench::class,
        ]);
    }

    /**
     * Refresh the application instance.
     *
     * @return void
     */
    protected function refreshApplication()
    {
        $this->app = $this->createApplication();
    }

    /**
     * Prepare the testing environment before the running the test case.
     *
     * @return void
     */
    public static function setUpBeforeClass(): void
    {
        static::setupBeforeClassUsingPHPUnit();
        static::setupBeforeClassUsingWorkbench();
    }

    /**
     * Clean up the testing environment before the next test case.
     *
     * @return void
     */
    public static function tearDownAfterClass(): void
    {
        static::$latestResponse = null;

        static::teardownAfterClassUsingWorkbench();
        static::teardownAfterClassUsingPHPUnit();
    }
}
