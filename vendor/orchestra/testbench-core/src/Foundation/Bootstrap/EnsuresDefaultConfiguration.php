<?php

namespace Orchestra\Testbench\Foundation\Bootstrap;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Collection;
use Illuminate\Support\Env;

/**
 * @internal
 */
final class EnsuresDefaultConfiguration
{
    /**
     * Bootstrap the given application.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function bootstrap(Application $app): void
    {
        if (! $this->includesDefaultConfigurations($app)) {
            return;
        }

        /** @var \Illuminate\Contracts\Config\Repository $config */
        $config = $app->make('config');

        $config->set([
            Collection::make([
                'APP_KEY' => ['app.key' => 'AckfSECXIvnK5r28GVIWUAxmbBSjTsmF'],
                'APP_DEBUG' => ['app.debug' => true],
                'DB_CONNECTION' => \defined('TESTBENCH_DUSK') ? ['database.default' => 'testing'] : null,
            ])->filter()
                ->reject(fn ($config, $key) => ! \is_null(Env::get($key)))
                ->values()
                ->all(),
        ]);
    }

    /**
     * Determine whether default migrations should be included.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return bool
     */
    protected function includesDefaultConfigurations($app): bool
    {
        return Env::get('TESTBENCH_WITHOUT_DEFAULT_VARIABLES') !== true;
    }
}
