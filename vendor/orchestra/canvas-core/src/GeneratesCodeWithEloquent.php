<?php

namespace Orchestra\Canvas\Core;

use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * @see https://github.com/laravel/framework/blob/10.x/src/Illuminate/Foundation/Console/ObserverMakeCommand.php
 */
class GeneratesCodeWithEloquent extends GeneratesCode
{
    /**
     * Handle generating code.
     */
    protected function generatingCode(string $stub, string $name): string
    {
        $stub = parent::generatingCode($stub, $name);

        $model = $this->options['model'];

        return ! empty($model) ? $this->replaceModel($stub, $model) : $stub;
    }

    /**
     * Replace the model for the given stub.
     */
    protected function replaceModel(string $stub, string $model): string
    {
        $modelClass = $this->parseModel($model);

        $replace = [
            'DummyFullModelClass' => $modelClass,
            '{{ namespacedModel }}' => $modelClass,
            '{{namespacedModel}}' => $modelClass,
            'DummyModelClass' => class_basename($modelClass),
            '{{ model }}' => class_basename($modelClass),
            '{{model}}' => class_basename($modelClass),
            'DummyModelVariable' => lcfirst(class_basename($modelClass)),
            '{{ modelVariable }}' => lcfirst(class_basename($modelClass)),
            '{{modelVariable}}' => lcfirst(class_basename($modelClass)),
        ];

        return str_replace(
            array_keys($replace), array_values($replace), $stub
        );
    }

    /**
     * Get the fully-qualified model class name.
     *
     * @param  string  $model
     *
     * @throws \InvalidArgumentException
     */
    protected function parseModel($model): string
    {
        if (preg_match('([^A-Za-z0-9_/\\\\])', $model)) {
            throw new InvalidArgumentException('Model name contains invalid characters.');
        }

        return $this->qualifyModel($model);
    }

    /**
     * Qualify the given model class base name.
     */
    protected function qualifyModel(string $model): string
    {
        $model = ltrim($model, '\\/');

        $model = str_replace('/', '\\', $model);

        $rootNamespace = $this->rootNamespace();
        $namespaceModel = $this->preset->modelNamespace().'\\'.$model;

        if (Str::startsWith($model, $namespaceModel)) {
            return $model;
        } elseif (! \is_null($this->preset->config('model.namespace'))) {
            return $namespaceModel;
        }

        return is_dir($this->preset->sourcePath().'/Models')
                    ? $rootNamespace.'Models\\'.$model
                    : $namespaceModel;
    }
}
