<?php

namespace Orchestra\Testbench\Concerns\Database;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * @internal
 */
trait HandlesConnections
{
    /**
     * Allow to use database connections environment variables.
     */
    final protected function usesDatabaseConnectionsEnvironmentVariables(Repository $config, string $driver, string $keyword): void
    {
        $keyword = Str::upper($keyword);

        $options = [
            'url' => 'URL',
            'host' => 'HOST',
            'port' => 'PORT',
            'database' => ['DB', 'DATABASE'],
            'username' => ['USER', 'USERNAME'],
            'password' => 'PASSWORD',
        ];

        $config->set(
            Collection::make($options)
                ->mapWithKeys(function ($value, $key) use ($driver, $keyword, $config) {
                    $name = "database.connections.{$driver}.{$key}";

                    /** @var mixed $configuration */
                    $configuration = Collection::make(Arr::wrap($value))
                        ->transform(fn ($value) => env("{$keyword}_{$value}"))
                        ->first(fn ($value) => ! \is_null($value)) ?? $config->get($name);

                    return [
                        "{$name}" => $configuration,
                    ];
                })->all()
        );
    }
}
