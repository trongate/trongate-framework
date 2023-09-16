<?php

namespace Orchestra\Canvas\Commands;

use Illuminate\Console\Concerns\CreatesMatchingTest;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

/**
 * @see https://github.com/laravel/framework/blob/10.x/src/Illuminate/Foundation/Console/JobMakeCommand.php
 */
#[AsCommand(name: 'make:job', description: 'Create a new job class')]
class Job extends Generator
{
    use CreatesMatchingTest;

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected string $type = 'Job';

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
        return $this->option('sync')
            ? 'job.stub'
            : 'job.queued.stub';
    }

    /**
     * Get the default namespace for the class.
     */
    public function getDefaultNamespace(string $rootNamespace): string
    {
        return $rootNamespace.'\Jobs';
    }

    /**
     * Get the console command options.
     *
     * @return array<int, array>
     */
    protected function getOptions()
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the job already exists'],
            ['sync', null, InputOption::VALUE_NONE, 'Indicates that job should be synchronous'],
        ];
    }
}
