<?php

namespace Orchestra\Canvas\Core\Commands\Generators;

use Illuminate\Support\Str;
use Orchestra\Canvas\Core\Commands\Generator;
use Orchestra\Canvas\Core\GeneratesCommandCode;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'make:generator', description: 'Create a new generator command')]
class ConsoleGenerator extends Generator
{
    /**
     * The type of class being generated.
     */
    protected string $type = 'Generator command';

    /**
     * The type of file being generated.
     */
    protected string $fileType = 'command';

    /**
     * Generator processor.
     *
     * @var class-string<\Orchestra\Canvas\Core\GeneratesCode>
     */
    protected string $processor = GeneratesCommandCode::class;

    /**
     * Get the stub file for the generator.
     */
    public function getPublishedStubFileName(): ?string
    {
        return null;
    }

    /**
     * Get the stub file for the generator.
     */
    public function getStubFile(): string
    {
        return __DIR__.'/../../../storage/canvas/generator.stub';
    }

    /**
     * Get the default namespace for the class.
     */
    public function getDefaultNamespace(string $rootNamespace): string
    {
        return $this->preset->config('console.namespace', $rootNamespace.'\Console\Commands');
    }

    /**
     * Generator options.
     *
     * @return array<string, mixed>
     */
    public function generatorOptions(): array
    {
        /** @var string $command */
        $command = $this->option('command');

        if (! Str::startsWith($command, 'make:')) {
            $command = "make:{$command}";
        }

        return [
            'command' => $command,
            'force' => $this->option('force'),
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array<int, array>
     */
    protected function getOptions()
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the generator already exists'],
            ['command', null, InputOption::VALUE_OPTIONAL, 'The terminal command that should be assigned', 'make:name'],
        ];
    }
}
