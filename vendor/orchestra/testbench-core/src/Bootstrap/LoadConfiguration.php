<?php

namespace Orchestra\Testbench\Bootstrap;

use Generator;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Config\Repository as RepositoryContract;
use Illuminate\Contracts\Foundation\Application;
use Symfony\Component\Finder\Finder;

/**
 * @internal
 *
 * @phpstan-type TLaravel \Illuminate\Contracts\Foundation\Application
 */
final class LoadConfiguration
{
    /**
     * Bootstrap the given application.
     *
     * @param  TLaravel  $app
     * @return void
     */
    public function bootstrap(Application $app): void
    {
        $app->instance('config', $config = new Repository([]));

        $this->loadConfigurationFiles($app, $config);

        if (\is_null($config->get('database.connections.testing'))) {
            $config->set('database.connections.testing', [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'foreign_key_constraints' => env('DB_FOREIGN_KEYS', false),
            ]);
        }

        if ($config->get('database.default') === 'sqlite' && ! file_exists($config->get('database.connections.sqlite.database'))) {
            $config->set('database.default', 'testing');
        }

        mb_internal_encoding('UTF-8');
    }

    /**
     * Load the configuration items from all of the files.
     *
     * @param  TLaravel  $app
     * @param  \Illuminate\Contracts\Config\Repository  $config
     * @return void
     */
    private function loadConfigurationFiles(Application $app, RepositoryContract $config): void
    {
        foreach ($this->getConfigurationFiles($app) as $key => $path) {
            $config->set($key, require $path);
        }
    }

    /**
     * Get all of the configuration files for the application.
     *
     * @param  TLaravel  $app
     * @return \Generator<string, mixed>
     */
    private function getConfigurationFiles(Application $app): Generator
    {
        $path = is_dir($app->basePath('config'))
            ? $app->basePath('config')
            : realpath(__DIR__.'/../../laravel/config');

        if (\is_string($path)) {
            foreach (Finder::create()->files()->name('*.php')->in($path) as $file) {
                yield basename($file->getRealPath(), '.php') => $file->getRealPath();
            }
        }
    }
}
