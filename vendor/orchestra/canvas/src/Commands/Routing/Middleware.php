<?php

namespace Orchestra\Canvas\Commands\Routing;

use Illuminate\Console\Concerns\CreatesMatchingTest;
use Orchestra\Canvas\Commands\Generator;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * @see https://github.com/laravel/framework/blob/10.x/src/Illuminate/Routing/Console/MiddlewareMakeCommand.php
 */
#[AsCommand(name: 'make:middleware', description: 'Create a new middleware class')]
class Middleware extends Generator
{
    use CreatesMatchingTest;

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected string $type = 'Middleware';

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
        return 'middleware.stub';
    }

    /**
     * Get the default namespace for the class.
     */
    public function getDefaultNamespace(string $rootNamespace): string
    {
        return $rootNamespace.'\Http\Middleware';
    }
}
