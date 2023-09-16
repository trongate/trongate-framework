<?php

namespace Orchestra\Canvas\Commands;

use Orchestra\Canvas\Processors\GeneratesTestingCode;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

/**
 * @see https://github.com/laravel/framework/blob/10.x/src/Illuminate/Foundation/Console/TestMakeCommand.php
 */
#[AsCommand(name: 'make:test', description: 'Create a new test class')]
class Testing extends Generator
{
    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected string $type = 'Test';

    /**
     * Generator processor.
     *
     * @var class-string<\Orchestra\Canvas\Core\GeneratesCode>
     */
    protected string $processor = GeneratesTestingCode::class;

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
        $file = $this->option('pest') ? 'pest' : 'test';

        return $this->option('unit')
            ? "{$file}.unit.stub"
            : "{$file}.stub";
    }

    /**
     * Get the default namespace for the class.
     */
    public function getDefaultNamespace(string $rootNamespace): string
    {
        if ($this->option('unit')) {
            return $rootNamespace.'\Unit';
        }

        return $rootNamespace.'\Feature';
    }

    /**
     * Generator options.
     *
     * @return array<string, mixed>
     */
    public function generatorOptions(): array
    {
        return array_merge(parent::generatorOptions(), [
            'unit' => $this->option('unit'),
            'feature' => ! $this->option('unit'),
            'pest' => $this->option('pest'),
            'force' => $this->option('force'),
        ]);
    }

    /**
     * Get the console command options.
     *
     * @return array<int, array>
     */
    protected function getOptions()
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the test already exists'],
            ['unit', 'u', InputOption::VALUE_NONE, 'Create a unit test.'],
            ['pest', 'p', InputOption::VALUE_NONE, 'Create a Pest test.'],
        ];
    }
}
