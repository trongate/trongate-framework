<?php

namespace Orchestra\Testbench\Concerns;

trait WithLaravelMigrations
{
    use InteractsWithWorkbench;

    /**
     * Bootstrap with laravel migrations.
     *
     * @return void
     */
    protected function setUpWithLaravelMigrations(): void
    {
        /** @var bool $loadLaravelMigrations */
        $loadLaravelMigrations = static::$cachedConfigurationForWorkbench?->getWorkbenchAttributes()['install'] ?? false;

        if (! ($loadLaravelMigrations && static::usesTestingConcern(WithWorkbench::class))) {
            $this->loadLaravelMigrations();
        }
    }
}
