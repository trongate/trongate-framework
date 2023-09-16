<?php

namespace Orchestra\Canvas\Commands\Database;

use Orchestra\Canvas\Commands\Generator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

/**
 * @see https://github.com/laravel/framework/blob/10.x/src/Illuminate/Foundation/Console/CastMakeCommand.php
 */
#[AsCommand(name: 'make:cast', description: 'Create a new custom Eloquent cast class')]
class Cast extends Generator
{
    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected string $type = 'Cast';

    /**
     * Get the stub file for the generator.
     */
    public function getPublishedStubFileName(): ?string
    {
        return $this->getStubFileName();
    }

    /**
     * Get the stub file name for the generator.
     */
    public function getStubFileName(): string
    {
        return $this->option('inbound') ? 'cast.inbound.stub' : 'cast.stub';
    }

    /**
     * Get the default namespace for the class.
     */
    public function getDefaultNamespace(string $rootNamespace): string
    {
        return $rootNamespace.'\Casts';
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the cast already exists'],
            ['inbound', null, InputOption::VALUE_OPTIONAL, 'Generate an inbound cast class'],
        ];
    }
}
