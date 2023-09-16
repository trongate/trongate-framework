<?php

namespace Orchestra\Canvas\Core\Presets;

use Illuminate\Support\Arr;
use Symfony\Component\Console\Application;

class Laravel extends Preset
{
    /**
     * List of global generators.
     *
     * @var array<int, class-string<\Symfony\Component\Console\Command\Command>>
     */
    protected static $generators = [];

    /**
     * Add global command.
     *
     * @param  array<int, class-string<\Symfony\Component\Console\Command\Command>>  $generators
     */
    public static function commands(array $generators): void
    {
        static::$generators = array_merge(static::$generators, $generators);
    }

    /**
     * Preset name.
     */
    public function name(): string
    {
        return 'laravel';
    }

    /**
     * Get the path to the base working directory.
     */
    public function laravelPath(): string
    {
        return $this->basePath();
    }

    /**
     * Get the path to the source directory.
     */
    public function sourcePath(): string
    {
        return sprintf(
            '%s/%s',
            $this->basePath(),
            $this->config('paths.src', 'app')
        );
    }

    /**
     * Preset namespace.
     */
    public function rootNamespace(): string
    {
        return $this->config['namespace'] ?? 'App';
    }

    /**
     * Testing namespace.
     */
    public function testingNamespace(): string
    {
        return $this->config('testing.namespace', 'Tests');
    }

    /**
     * Model namespace.
     */
    public function modelNamespace(): string
    {
        return $this->config('model.namespace', $this->rootNamespace().'\Models');
    }

    /**
     * Provider namespace.
     */
    public function providerNamespace(): string
    {
        return $this->config('provider.namespace', $this->rootNamespace().'\Providers');
    }

    /**
     * Get custom stub path.
     */
    public function getCustomStubPath(): ?string
    {
        return sprintf('%s/%s', $this->basePath(), 'stubs');
    }

    /**
     * Sync commands to preset.
     */
    public function addAdditionalCommands(Application $app): void
    {
        parent::addAdditionalCommands($app);

        foreach (Arr::wrap(static::$generators) as $generator) {
            /** @var class-string<\Symfony\Component\Console\Command\Command> $generator */
            $app->add(new $generator($this));
        }
    }
}
