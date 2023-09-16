<?php

namespace Orchestra\Canvas\Core\Presets;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Symfony\Component\Console\Application;

abstract class Preset
{
    /**
     * Construct a new preset.
     *
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        protected array $config,
        protected string $basePath,
        protected Filesystem $files
    ) {
        //
    }

    /**
     * Check if preset name equal to $name.
     */
    public function is(string $name): bool
    {
        return $this->name() === $name;
    }

    /**
     * Get configuration.
     *
     * @param  mixed|null  $default
     */
    public function config(?string $key = null, $default = null)
    {
        if (\is_null($key)) {
            return $this->config;
        }

        return Arr::get($this->config, $key, $default);
    }

    /**
     * Get the filesystem instance.
     */
    public function filesystem(): Filesystem
    {
        return $this->files;
    }

    /**
     * Get the path to the base working directory.
     */
    public function basePath(): string
    {
        return $this->basePath;
    }

    /**
     * Get the path to the testing directory.
     */
    public function testingPath(): string
    {
        return "{$this->basePath}/tests";
    }

    /**
     * Get the path to the vendor directory.
     */
    public function vendorPath(): string
    {
        return "{$this->basePath}/vendor";
    }

    /**
     * Get the path to the resource directory.
     */
    public function resourcePath(): string
    {
        return sprintf(
            '%s/%s',
            $this->basePath(),
            $this->config('paths.resource', 'resources')
        );
    }

    /**
     * Get the path to the factory directory.
     */
    public function factoryPath(): string
    {
        return sprintf(
            '%s/%s',
            $this->basePath(),
            $this->config('factory.path', 'database/factories')
        );
    }

    /**
     * Get the path to the migration directory.
     */
    public function migrationPath(): string
    {
        return sprintf(
            '%s/%s',
            $this->basePath(),
            $this->config('migration.path', 'database/migrations')
        );
    }

    /**
     * Get the path to the seeder directory.
     */
    public function seederPath(): string
    {
        return sprintf(
            '%s/%s',
            $this->basePath(),
            $this->config('seeder.path', 'database/seeders')
        );
    }

    /**
     * Database factory namespace.
     */
    public function factoryNamespace(): string
    {
        return $this->config('factory.namespace', 'Database\Factories');
    }

    /**
     * Database seeder namespace.
     */
    public function seederNamespace(): string
    {
        return $this->config('seeder.path', 'Database\Seeders');
    }

    /**
     * Sync commands to preset.
     */
    public function addAdditionalCommands(Application $app): void
    {
        tap($this->config('generators') ?? [], function ($generators) use ($app) {
            foreach (Arr::wrap($generators) as $generator) {
                /** @var class-string<\Symfony\Component\Console\Command\Command> $generator */
                $app->add(new $generator($this));
            }
        });
    }

    /**
     * Preset has custom stub path.
     */
    public function hasCustomStubPath(): bool
    {
        return ! \is_null($this->getCustomStubPath());
    }

    /**
     * Preset name.
     */
    abstract public function name(): string;

    /**
     * Get the path to the base working directory.
     */
    abstract public function laravelPath(): string;

    /**
     * Get the path to the source directory.
     */
    abstract public function sourcePath(): string;

    /**
     * Preset namespace.
     */
    abstract public function rootNamespace(): string;

    /**
     * Testing namespace.
     */
    abstract public function testingNamespace(): string;

    /**
     * Model namespace.
     */
    abstract public function modelNamespace(): string;

    /**
     * Provider namespace.
     */
    abstract public function providerNamespace(): string;

    /**
     * Get custom stub path.
     */
    abstract public function getCustomStubPath(): ?string;
}
