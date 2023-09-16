<?php

namespace Orchestra\Canvas\Commands\Database;

use Illuminate\Console\Concerns\CreatesMatchingTest;
use Illuminate\Support\Str;
use Orchestra\Canvas\Commands\Generator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

/**
 * @see https://github.com/laravel/framework/blob/10.x/src/Illuminate/Foundation/Console/ModelMakeCommand.php
 */
#[AsCommand(name: 'make:model', description: 'Create a new Eloquent model class')]
class Eloquent extends Generator
{
    use CreatesMatchingTest;

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected string $type = 'Model';

    /**
     * Run after code successfully generated.
     */
    public function afterCodeHasBeenGenerated(string $className, string $path): void
    {
        if ($this->option('all')) {
            $this->input->setOption('factory', true);
            $this->input->setOption('seed', true);
            $this->input->setOption('migration', true);
            $this->input->setOption('controller', true);
            $this->input->setOption('resource', true);
        }

        if ($this->option('factory')) {
            $this->createFactory($className);
        }

        if ($this->option('migration')) {
            $this->createMigration($className);
        }

        if ($this->option('seed')) {
            $this->createSeeder($className);
        }

        if ($this->option('controller') || $this->option('resource') || $this->option('api')) {
            $this->createController($className);
        }

        parent::afterCodeHasBeenGenerated($className, $path);
    }

    /**
     * Create a model factory for the model.
     */
    protected function createFactory(string $eloquentClassName): void
    {
        /** @var string $name */
        $name = $this->argument('name');

        $factory = Str::studly(class_basename($name));

        $this->call('make:factory', [
            'name' => "{$factory}Factory",
            '--model' => $eloquentClassName,
        ]);
    }

    /**
     * Create a migration file for the model.
     */
    protected function createMigration(string $eloquentClassName): void
    {
        /** @var string $name */
        $name = $this->argument('name');

        $table = Str::snake(Str::pluralStudly(class_basename($name)));

        if ($this->option('pivot')) {
            $table = Str::singular($table);
        }

        $this->call('make:migration', [
            'name' => "create_{$table}_table",
            '--create' => $table,
        ]);
    }

    /**
     * Create a seeder file for the model.
     */
    protected function createSeeder(string $eloquentClassName): void
    {
        /** @var string $name */
        $name = $this->argument('name');

        $seeder = Str::studly(class_basename($name));

        $this->call('make:seed', [
            'name' => "{$seeder}Seeder",
        ]);
    }

    /**
     * Create a controller for the model.
     */
    protected function createController(string $eloquentClassName): void
    {
        /** @var string $name */
        $name = $this->argument('name');

        $controller = Str::studly(class_basename($name));

        $this->call('make:controller', array_filter([
            'name' => "{$controller}Controller",
            '--model' => $this->option('resource') || $this->option('api') ? $eloquentClassName : null,
            '--api' => $this->option('api'),
            '--requests' => $this->option('requests') || $this->option('all'),
        ]));
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
        if ($this->option('pivot')) {
            return 'model.pivot.stub';
        }

        if ($this->option('morph-pivot')) {
            return 'model.morph-pivot.stub';
        }

        return 'model.stub';
    }

    /**
     * Get the default namespace for the class.
     */
    public function getDefaultNamespace(string $rootNamespace): string
    {
        return $this->preset->modelNamespace();
    }

    /**
     * Get the console command options.
     *
     * @return array<int, array>
     */
    protected function getOptions()
    {
        return [
            ['all', 'a', InputOption::VALUE_NONE, 'Generate a migration, seeder, factory, and resource controller for the model'],
            ['controller', 'c', InputOption::VALUE_NONE, 'Create a new controller for the model'],
            ['factory', 'f', InputOption::VALUE_NONE, 'Create a new factory for the model'],
            ['force', null, InputOption::VALUE_NONE, 'Create the class even if the model already exists'],
            ['migration', 'm', InputOption::VALUE_NONE, 'Create a new migration file for the model'],
            ['morph-pivot', null, InputOption::VALUE_NONE, 'Indicates if the generated model should be a custom polymorphic intermediate table model'],
            ['seed', 's', InputOption::VALUE_NONE, 'Create a new seeder file for the model'],
            ['pivot', 'p', InputOption::VALUE_NONE, 'Indicates if the generated model should be a custom intermediate table model'],
            ['resource', 'r', InputOption::VALUE_NONE, 'Indicates if the generated controller should be a resource controller'],
            ['api', null, InputOption::VALUE_NONE, 'Indicates if the generated controller should be an api controller'],
            ['requests', 'R', InputOption::VALUE_NONE, 'Create new form request classes and use them in the resource controller'],
        ];
    }
}
