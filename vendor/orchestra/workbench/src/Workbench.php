<?php

namespace Orchestra\Workbench;

/**
 * @phpstan-import-type TWorkbenchConfig from \Orchestra\Testbench\Foundation\Config
 */
class Workbench
{
    /**
     * Get the path to the laravel folder.
     */
    public static function laravelPath(string $path = ''): string
    {
        return app()->basePath($path);
    }

    /**
     * Get the path to the package folder.
     */
    public static function packagePath(string $path = ''): string
    {
        return \Orchestra\Testbench\package_path($path);
    }

    /**
     * Get the path to the workbench folder.
     */
    public static function path(string $path = ''): string
    {
        return \Orchestra\Testbench\workbench_path($path);
    }

    /**
     * Get the availale configuration.
     *
     * @param  string|null  $key
     * @return mixed|array<string, mixed>
     *
     * @phpstan-return ($key is null ? TWorkbenchConfig : mixed)
     */
    public static function config($key = null)
    {
        $workbench = \Orchestra\Testbench\workbench();

        if (! \is_null($key)) {
            return $workbench[$key] ?? null;
        }

        return $workbench;
    }
}
