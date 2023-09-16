<?php

namespace Orchestra\Testbench;

use Closure;
use Illuminate\Contracts\Foundation\Application as ApplicationContract;
use Illuminate\Foundation\Application;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Testing\PendingCommand;
use Orchestra\Testbench\Foundation\Config;
use PHPUnit\Runner\Version;
use RuntimeException;

/**
 * Create Laravel application instance.
 *
 * @param  string|null  $basePath
 * @param  (callable(\Illuminate\Foundation\Application):(void))|null  $resolvingCallback
 * @param  array{extra?: array{providers?: array, dont-discover?: array, env?: array}, load_environment_variables?: bool, enabled_package_discoveries?: bool}  $options
 * @param  \Orchestra\Testbench\Foundation\Config|null  $config
 * @return \Orchestra\Testbench\Foundation\Application
 */
function container(
    ?string $basePath = null,
    ?callable $resolvingCallback = null,
    array $options = [],
    ?Config $config = null
): Foundation\Application {
    if ($config instanceof Config) {
        $hasEnvironmentFile = ! \is_null($config['laravel'])
            ? file_exists($config['laravel'].'/.env')
            : (! \is_null($basePath) && file_exists("{$basePath}/.env"));

        return (new Foundation\Application($config['laravel'] ?? $basePath, $resolvingCallback))->configure(array_merge($options, [
            'load_environment_variables' => $hasEnvironmentFile,
            'extra' => $config->getExtraAttributes(),
        ]));
    }

    return (new Foundation\Application($basePath, $resolvingCallback))->configure($options);
}

/**
 * Run artisan command.
 *
 * @param  \Orchestra\Testbench\Contracts\TestCase  $testbench
 * @param  string  $command
 * @param  array<string, mixed>  $parameters
 * @return int
 */
function artisan(Contracts\TestCase $testbench, string $command, array $parameters = []): int
{
    $command = $testbench->artisan($command, $parameters);

    return $command instanceof PendingCommand ? $command->run() : $command;
}

/**
 * Register after resolving callback.
 *
 * @param  \Illuminate\Contracts\Foundation\Application  $app
 * @param  string  $name
 * @param  (\Closure(object, \Illuminate\Contracts\Foundation\Application):(mixed))|null  $callback
 * @return void
 */
function after_resolving(ApplicationContract $app, string $name, ?Closure $callback = null): void
{
    $app->afterResolving($name, $callback);

    if ($app->resolved($name)) {
        value($callback, $app->make($name), $app);
    }
}

/**
 * Get default environment variables.
 *
 * @return array<int, string>
 *
 * @deprecated
 */
function default_environment_variables(): array
{
    return [];
}

/**
 * Get default environment variables.
 *
 * @param  iterable<string, mixed>  $variables
 * @return array<int, string>
 */
function parse_environment_variables($variables): array
{
    return Collection::make($variables)
        ->transform(function ($value, $key) {
            if (\is_bool($value) || \in_array($value, ['true', 'false'])) {
                $value = \in_array($value, [true, 'true']) ? '(true)' : '(false)';
            } elseif (\is_null($value) || \in_array($value, ['null'])) {
                $value = '(null)';
            } else {
                $value = $key === 'APP_DEBUG' ? sprintf('(%s)', Str::of($value)->ltrim('(')->rtrim(')')) : "'{$value}'";
            }

            return "{$key}={$value}";
        })->values()->all();
}

/**
 * Transform relative path.
 *
 * @param  string  $path
 * @param  string  $workingPath
 * @return string
 */
function transform_relative_path(string $path, string $workingPath): string
{
    return Str::startsWith($path, './')
        ? str_replace('./', rtrim($workingPath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR, $path)
        : $path;
}

/**
 * Get the path to the package folder.
 *
 * @param  string  $path
 * @return string
 */
function package_path(string $path = ''): string
{
    $workingPath = \defined('TESTBENCH_WORKING_PATH')
        ? TESTBENCH_WORKING_PATH
        : getcwd();

    if (Str::startsWith($path, './')) {
        return transform_relative_path($path, $workingPath);
    }

    $path != '' ? DIRECTORY_SEPARATOR.ltrim($path, DIRECTORY_SEPARATOR) : '';

    return $workingPath.DIRECTORY_SEPARATOR.$path;
}

/**
 * Get the workbench configuration.
 *
 * @return array<string, mixed>
 */
function workbench(): array
{
    /** @var \Orchestra\Testbench\Contracts\Config $config */
    $config = app()->bound(Contracts\Config::class)
        ? app()->make(Contracts\Config::class)
        : new Foundation\Config();

    return $config->getWorkbenchAttributes();
}

/**
 * Get the path to the workbench folder.
 *
 * @param  string  $path
 * @return string
 */
function workbench_path(string $path = ''): string
{
    $path != '' ? DIRECTORY_SEPARATOR.ltrim($path, DIRECTORY_SEPARATOR) : '';

    return package_path('workbench'.DIRECTORY_SEPARATOR.$path);
}

/**
 * Laravel version compare.
 *
 * @param  string  $version
 * @param  string|null  $operator
 * @return int|bool
 */
function laravel_version_compare(string $version, ?string $operator = null)
{
    /** @phpstan-ignore-next-line */
    $laravel = Application::VERSION === '10.x-dev' ? '10.0.0' : Application::VERSION;

    if (\is_null($operator)) {
        return version_compare($laravel, $version);
    }

    return version_compare($laravel, $version, $operator);
}

/**
 * PHPUnit version compare.
 *
 * @param  string  $version
 * @param  string|null  $operator
 * @return int|bool
 */
function phpunit_version_compare(string $version, ?string $operator = null)
{
    if (! class_exists(Version::class)) {
        throw new RuntimeException('Unable to verify PHPUnit version');
    }

    if (\is_null($operator)) {
        return version_compare(Version::id(), $version);
    }

    return version_compare(Version::id(), $version, $operator);
}
