<?php

namespace Orchestra\Canvas\Presets;

use Orchestra\Canvas\Core\Presets\Package as Preset;

use function Orchestra\Testbench\workbench_path;

class PackageWorkbench extends Preset
{
    /**
     * Preset name.
     */
    public function name(): string
    {
        return 'workbench';
    }

    /**
     * Get the path to the base working directory.
     */
    public function laravelPath(): string
    {
        return app()->basePath();
    }

    /**
     * Get the path to the source directory.
     */
    public function sourcePath(): string
    {
        return workbench_path('app');
    }

    /**
     * Preset namespace.
     */
    public function rootNamespace(): string
    {
        return 'Workbench\App';
    }

    /**
     * Model namespace.
     */
    public function modelNamespace(): string
    {
        return 'Workbench\App\Models';
    }

    /**
     * Provider namespace.
     */
    public function providerNamespace(): string
    {
        return 'Workbench\App\Providers';
    }

    /**
     * Databases namespace.
     */
    public function factoryNamespace(): string
    {
        return 'Workbench\Database\Factories';
    }

    /**
     * Databases namespace.
     */
    public function seederNamespace(): string
    {
        return 'Workbench\Database\Seeders';
    }

    /**
     * Get the path to the resource directory.
     */
    public function resourcePath(): string
    {
        return workbench_path('resources');
    }

    /**
     * Get the path to the factory directory.
     */
    public function factoryPath(): string
    {
        return workbench_path('database/factories');
    }

    /**
     * Get the path to the migration directory.
     */
    public function migrationPath(): string
    {
        return workbench_path('database/migrations');
    }

    /**
     * Get the path to the seeder directory.
     */
    public function seederPath(): string
    {
        return workbench_path('database/seeders');
    }

    /**
     * Get custom stub path.
     */
    public function getCustomStubPath(): ?string
    {
        return null;
    }
}
