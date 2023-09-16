<?php

namespace Orchestra\Canvas\Core;

use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class LaravelServiceProvider extends ServiceProvider implements DeferrableProvider
{
    use CommandsProvider;

    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(Presets\Preset::class, fn (Container $app) => $this->presetForLaravel($app));
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<int, class-string|string>
     */
    public function provides(): array
    {
        return [
            Presets\Preset::class,
        ];
    }
}
