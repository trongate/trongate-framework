<?php

namespace Orchestra\Testbench\Concerns;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Console\Application as Artisan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bootstrap\HandleExceptions;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\WithoutEvents;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Queue\Queue;
use Illuminate\Support\Facades\ParallelTesting;
use Illuminate\Support\LazyCollection;
use Illuminate\View\Component;
use Mockery;
use PHPUnit\Framework\TestCase;
use Throwable;

trait Testing
{
    use CreatesApplication,
        HandlesAnnotations,
        HandlesDatabases,
        HandlesRoutes,
        InteractsWithMigrations,
        WithFactories;

    /**
     * The Illuminate application instance.
     *
     * @var \Illuminate\Foundation\Application|null
     */
    protected $app;

    /**
     * The callbacks that should be run after the application is created.
     *
     * @var array<int, callable():void>
     */
    protected $afterApplicationCreatedCallbacks = [];

    /**
     * The callbacks that should be run after the application is refreshed.
     *
     * @var array<int, callable():void>
     */
    protected $afterApplicationRefreshedCallbacks = [];

    /**
     * The callbacks that should be run before the application is destroyed.
     *
     * @var array<int, callable():void>
     */
    protected $beforeApplicationDestroyedCallbacks = [];

    /**
     * The exception thrown while running an application destruction callback.
     *
     * @var \Throwable|null
     */
    protected $callbackException;

    /**
     * Indicates if we have made it through the base setUp function.
     *
     * @var bool
     */
    protected $setUpHasRun = false;

    /**
     * Setup the test environment.
     *
     * @internal
     *
     * @return void
     */
    final protected function setUpTheTestEnvironment(): void
    {
        if (! $this->app) {
            $this->refreshApplication();

            $this->setUpParallelTestingCallbacks();
        }

        /** @var \Illuminate\Foundation\Application $app */
        $app = $this->app;

        foreach ($this->afterApplicationRefreshedCallbacks as $callback) {
            \call_user_func($callback);
        }

        $this->setUpTraits();

        foreach ($this->afterApplicationCreatedCallbacks as $callback) {
            \call_user_func($callback);
        }

        Model::setEventDispatcher($app['events']);

        $this->setUpHasRun = true;
    }

    /**
     * Clean up the testing environment before the next test.
     *
     * @internal
     *
     * @return void
     */
    final protected function tearDownTheTestEnvironment(): void
    {
        if ($this->app) {
            $this->callBeforeApplicationDestroyedCallbacks();

            $this->tearDownParallelTestingCallbacks();

            $this->app?->flush();

            $this->app = null;
        }

        $this->setUpHasRun = false;

        if (property_exists($this, 'serverVariables')) {
            $this->serverVariables = [];
        }

        if (property_exists($this, 'defaultHeaders')) {
            $this->defaultHeaders = [];
        }

        if (class_exists(Mockery::class)) {
            if ($container = Mockery::getContainer()) {
                $this->addToAssertionCount($container->mockery_getExpectationCount());
            }

            Mockery::close();
        }

        Carbon::setTestNow();

        if (class_exists(CarbonImmutable::class)) {
            CarbonImmutable::setTestNow();
        }

        $this->afterApplicationCreatedCallbacks = [];
        $this->beforeApplicationDestroyedCallbacks = [];

        if (property_exists($this, 'originalExceptionHandler')) {
            $this->originalExceptionHandler = null;
        }

        if (property_exists($this, 'originalDeprecationHandler')) {
            $this->originalDeprecationHandler = null;
        }

        Artisan::forgetBootstrappers();
        Component::flushCache();
        Component::forgetComponentsResolver();
        Component::forgetFactory();
        Queue::createPayloadUsing(null);
        HandleExceptions::forgetApp();

        if ($this->callbackException) {
            throw $this->callbackException;
        }
    }

    /**
     * Boot the testing helper traits.
     *
     * @internal
     *
     * @param  array<class-string, class-string>  $uses
     * @return array<class-string, class-string>
     */
    final protected function setUpTheTestEnvironmentTraits(array $uses): array
    {
        if (isset($uses[WithWorkbench::class])) {
            /** @phpstan-ignore-next-line */
            $this->setUpWithWorkbench();
        }

        $this->setUpDatabaseRequirements(function () use ($uses) {
            if (isset($uses[RefreshDatabase::class])) {
                /** @phpstan-ignore-next-line */
                $this->refreshDatabase();
            }

            if (isset($uses[DatabaseMigrations::class])) {
                /** @phpstan-ignore-next-line */
                $this->runDatabaseMigrations();
            }

            if (isset($uses[DatabaseTruncation::class])) {
                /** @phpstan-ignore-next-line */
                $this->truncateDatabaseTables();
            }
        });

        if (isset($uses[DatabaseTransactions::class])) {
            /** @phpstan-ignore-next-line */
            $this->beginDatabaseTransaction();
        }

        if (isset($uses[WithoutMiddleware::class])) {
            /** @phpstan-ignore-next-line */
            $this->disableMiddlewareForAllTests();
        }

        if (isset($uses[WithoutEvents::class])) {
            /** @phpstan-ignore-next-line */
            $this->disableEventsForAllTests();
        }

        if (isset($uses[WithFaker::class])) {
            /** @phpstan-ignore-next-line */
            $this->setUpFaker();
        }

        LazyCollection::make(function () use ($uses) {
            foreach ($uses as $use) {
                yield $use;
            }
        })
            ->reject(function ($use) {
                /** @var class-string $use */
                return $this->setUpTheTestEnvironmentTraitToBeIgnored($use);
            })->map(function ($use) {
                /** @var class-string $use */
                return class_basename($use);
            })->each(function ($traitBaseName) {
                /** @var string $traitBaseName */
                if (method_exists($this, $method = 'setUp'.$traitBaseName)) {
                    $this->{$method}();
                }

                if (method_exists($this, $method = 'tearDown'.$traitBaseName)) {
                    $this->beforeApplicationDestroyed(function () use ($method) {
                        $this->{$method}();
                    });
                }
            });

        return $uses;
    }

    /**
     * Determine trait should be ignored from being autoloaded.
     *
     * @param  class-string  $use
     * @return bool
     */
    protected function setUpTheTestEnvironmentTraitToBeIgnored(string $use): bool
    {
        return false;
    }

    /**
     * Setup parallel testing callback.
     */
    protected function setUpParallelTestingCallbacks(): void
    {
        if (class_exists(ParallelTesting::class) && $this instanceof TestCase) {
            /** @phpstan-ignore-next-line */
            ParallelTesting::callSetUpTestCaseCallbacks($this);
        }
    }

    /**
     * Teardown parallel testing callback.
     */
    protected function tearDownParallelTestingCallbacks(): void
    {
        if (class_exists(ParallelTesting::class) && $this instanceof TestCase) {
            /** @phpstan-ignore-next-line */
            ParallelTesting::callTearDownTestCaseCallbacks($this);
        }
    }

    /**
     * Register a callback to be run after the application is refreshed.
     *
     * @param  callable():void  $callback
     * @return void
     */
    protected function afterApplicationRefreshed(callable $callback): void
    {
        $this->afterApplicationRefreshedCallbacks[] = $callback;

        if ($this->setUpHasRun) {
            \call_user_func($callback);
        }
    }

    /**
     * Register a callback to be run after the application is created.
     *
     * @param  callable():void  $callback
     * @return void
     */
    protected function afterApplicationCreated(callable $callback): void
    {
        $this->afterApplicationCreatedCallbacks[] = $callback;

        if ($this->setUpHasRun) {
            \call_user_func($callback);
        }
    }

    /**
     * Register a callback to be run before the application is destroyed.
     *
     * @param  callable():void  $callback
     * @return void
     */
    protected function beforeApplicationDestroyed(callable $callback): void
    {
        array_unshift($this->beforeApplicationDestroyedCallbacks, $callback);
    }

    /**
     * Execute the application's pre-destruction callbacks.
     *
     * @return void
     */
    protected function callBeforeApplicationDestroyedCallbacks()
    {
        foreach ($this->beforeApplicationDestroyedCallbacks as $callback) {
            try {
                \call_user_func($callback);
            } catch (Throwable $e) {
                if (! $this->callbackException) {
                    $this->callbackException = $e;
                }
            }
        }
    }

    /**
     * Reload the application instance with cached routes.
     */
    protected function reloadApplication(): void
    {
        $this->tearDownTheTestEnvironment();
        $this->setUpTheTestEnvironment();
    }

    /**
     * Boot the testing helper traits.
     *
     * @return array<class-string, class-string>
     */
    abstract protected function setUpTraits();

    /**
     * Refresh the application instance.
     *
     * @return void
     */
    abstract protected function refreshApplication();
}
