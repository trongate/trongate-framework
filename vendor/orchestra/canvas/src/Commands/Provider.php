<?php

namespace Orchestra\Canvas\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

/**
 * @see https://github.com/laravel/framework/blob/10.x/src/Illuminate/Foundation/Console/ProviderMakeCommand.php
 */
#[AsCommand(name: 'make:provider', description: 'Create a new service provider class')]
class Provider extends Generator
{
    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected string $type = 'Provider';

    /**
     * Get the stub file name for the generator.
     */
    public function getStubFileName(): string
    {
        return $this->option('deferred')
            ? 'provider.deferred.stub'
            : 'provider.stub';
    }

    /**
     * Get the default namespace for the class.
     */
    public function getDefaultNamespace(string $rootNamespace): string
    {
        return $this->preset->providerNamespace();
    }

    /**
     * Get the console command options.
     *
     * @return array<int, array>
     */
    protected function getOptions()
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the provider already exists'],
            ['deferred', null, InputOption::VALUE_NONE, 'Create deferrable service provider.'],
        ];
    }
}
