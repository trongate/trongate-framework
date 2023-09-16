<?php

namespace Orchestra\Canvas\Commands\Database;

use Illuminate\Support\Composer;
use Orchestra\Canvas\Commands\Generator;
use Orchestra\Canvas\Processors\GeneratesSeederCode;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * @see https://github.com/laravel/framework/blob/10.x/src/Illuminate/Database/Console/Seeds/SeederMakeCommand.php
 */
#[AsCommand(name: 'make:seeder', description: 'Create a new seeder class')]
class Seeder extends Generator
{
    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected string $type = 'Seeder';

    /**
     * Generator processor.
     *
     * @var class-string<\Orchestra\Canvas\Core\GeneratesCode>
     */
    protected string $processor = GeneratesSeederCode::class;

    /**
     * The Composer instance.
     *
     * @var \Illuminate\Support\Composer
     */
    protected $composer;

    /**
     * Configure the command options.
     *
     * @return void
     */
    protected function configure()
    {
        parent::configure();

        $this->composer = new Composer($this->preset->filesystem(), $this->preset->basePath());
    }

    /**
     * Run after code successfully generated.
     */
    public function afterCodeHasBeenGenerated(string $className, string $path): void
    {
        $this->composer->dumpAutoloads();

        parent::afterCodeHasBeenGenerated($className, $path);
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
        return 'seeder.stub';
    }
}
