<?php

namespace Orchestra\Testbench\Concerns;

use Orchestra\Testbench\Foundation\Bootstrap\LoadMigrationsFromArray;
use Orchestra\Testbench\Foundation\Bootstrap\StartWorkbench;
use Orchestra\Testbench\Foundation\Config;

trait WithWorkbench
{
    use InteractsWithWorkbench;

    /**
     * Bootstrap with Workbench.
     *
     * @return void
     */
    protected function setUpWithWorkbench(): void
    {
        /** @var \Illuminate\Contracts\Foundation\Application $app */
        $app = $this->app;

        /** @var \Orchestra\Testbench\Contracts\Config $config */
        $config = static::$cachedConfigurationForWorkbench ?? new Config();

        (new StartWorkbench(config: $config, loadWorkbenchProviders: false))->bootstrap($app);

        (new LoadMigrationsFromArray(
            $config['migrations'] ?? [], $config['seeders'] ?? false,
        ))->bootstrap($app);
    }
}
