<?php

namespace Orchestra\Canvas\Processors;

use Illuminate\Support\Str;
use Orchestra\Canvas\Core\GeneratesCode;

/**
 * @property \Orchestra\Canvas\Commands\Database\Factory $listener
 *
 * @see https://github.com/laravel/framework/blob/10.x/src/Illuminate/Database/Console/Factories/FactoryMakeCommand.php
 */
class GeneratesFactoryCode extends GeneratesCode
{
    /**
     * Handle generating code.
     */
    protected function generatingCode(string $stub, string $name): string
    {
        $stub = parent::generatingCode($stub, $name);

        $namespaceModel = ! empty($this->options['model'])
            ? $this->qualifyClass($this->options['model'])
            : trim($this->rootNamespace(), '\\').'\\Model';

        $model = class_basename($namespaceModel);

        $factoryNamespace = $this->preset->factoryNamespace();

        if (Str::startsWith($namespaceModel, 'App\\Models')) {
            $namespace = Str::beforeLast($factoryNamespace.'\\'.Str::after($namespaceModel, 'App\\Models\\'), '\\');
        } else {
            $namespace = $factoryNamespace;
        }

        $replace = [
            '{{ factoryNamespace }}' => $namespace,
            'NamespacedDummyModel' => $namespaceModel,
            '{{ namespacedModel }}' => $namespaceModel,
            '{{namespacedModel}}' => $namespaceModel,
            'DummyModel' => $model,
            '{{ factory }}' => $model,
            '{{factory}}' => $model,
            '{{ model }}' => $model,
            '{{model}}' => $model,
        ];

        return str_replace(
            array_keys($replace), array_values($replace), $stub
        );
    }

    /**
     * Get the destination class path.
     */
    protected function getPath(string $name): string
    {
        $name = str_replace(
            ['\\', '/'], '', $this->listener->generatorName()
        );

        return $this->preset->factoryPath()."/{$name}.php";
    }
}
