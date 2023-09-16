<?php

namespace Orchestra\Canvas\Commands;

use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @see https://github.com/laravel/framework/blob/10.x/src/Illuminate/Foundation/Console/ResourceMakeCommand.php
 */
#[AsCommand(name: 'make:resource', description: 'Create a new resource')]
class Resource extends Generator
{
    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected string $type = 'Resource';

    /**
     * Execute the command.
     *
     * @return int 0 if everything went fine, or an exit code
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($this->collection()) {
            $this->type = 'Resource collection';
        }

        return parent::execute($input, $output);
    }

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
        return $this->collection()
            ? 'resource-collection.stub'
            : 'resource.stub';
    }

    /**
     * Get the default namespace for the class.
     */
    public function getDefaultNamespace(string $rootNamespace): string
    {
        return $rootNamespace.'\Http\Resources';
    }

    /**
     * Determine if the command is generating a resource collection.
     *
     * @return bool
     */
    protected function collection()
    {
        /** @var string $name */
        $name = $this->argument('name');

        return $this->option('collection') ||
            Str::endsWith($name, 'Collection');
    }

    /**
     * Get the console command options.
     *
     * @return array<int, array>
     */
    protected function getOptions()
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the resource already exists'],
            ['collection', 'c', InputOption::VALUE_NONE, 'Create a resource collection'],
        ];
    }
}
