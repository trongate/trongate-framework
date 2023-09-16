<?php

namespace Orchestra\Canvas\Core\Commands\Generators;

use Orchestra\Canvas\Core\Commands\Generator;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'make:class', description: 'Create a new class')]
class Code extends Generator
{
    /**
     * The type of class being generated.
     */
    protected string $type = 'Class';

    /**
     * Get the stub file for the generator.
     */
    public function getStubFile(): string
    {
        return $this->getStubFileName();
    }

    /**
     * Get the stub file name for the generator.
     */
    public function getStubFileName(): string
    {
        return __DIR__.'/../../../storage/canvas/code.stub';
    }
}
