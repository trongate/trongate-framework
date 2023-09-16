<?php

namespace Orchestra\Canvas\Processors;

use Illuminate\Support\Str;
use Orchestra\Canvas\Core\GeneratesCode;

/**
 * @property \Orchestra\Canvas\Commands\Policy $listener
 *
 * @see https://github.com/laravel/framework/blob/10.x/src/Illuminate/Foundation/Console/PolicyMakeCommand.php
 */
class GeneratesPolicyCode extends GeneratesCode
{
    /**
     * Handle generating code.
     */
    protected function generatingCode(string $stub, string $name): string
    {
        $stub = $this->replaceUserNamespace(
            parent::generatingCode($stub, $name)
        );

        $model = $this->options['model'];

        return ! empty($model) ? $this->replaceModel($stub, $model) : $stub;
    }

    /**
     * Replace the User model namespace.
     */
    protected function replaceUserNamespace(string $stub): string
    {
        $model = $this->userProviderModel();

        if (! $model) {
            return $stub;
        }

        return str_replace(
            $this->rootNamespace().'\User',
            $model,
            $stub
        );
    }

    /**
     * Replace the model for the given stub.
     */
    protected function replaceModel(string $stub, string $model): string
    {
        $model = str_replace('/', '\\', $model);

        if (Str::startsWith($model, '\\')) {
            $namespacedModel = trim($model, '\\');
        } else {
            $namespacedModel = $this->preset->modelNamespace().'\\'.$model;
        }

        $model = class_basename(trim($model, '\\'));

        $dummyUser = class_basename($this->userProviderModel());

        $dummyModel = Str::camel($model) === 'user' ? 'model' : $model;

        $replace = [
            'NamespacedDummyModel' => $namespacedModel,
            '{{ namespacedModel }}' => $namespacedModel,
            '{{namespacedModel}}' => $namespacedModel,
            'DummyModel' => $model,
            '{{ model }}' => $model,
            '{{model}}' => $model,
            'dummyModel' => Str::camel($dummyModel),
            '{{ modelVariable }}' => Str::camel($dummyModel),
            '{{modelVariable}}' => Str::camel($dummyModel),
            'DocDummyModel' => Str::snake($dummyModel, ' '),
            '{{ modelDoc }}' => Str::snake($dummyModel, ' '),
            '{{modelDoc}}' => Str::snake($dummyModel, ' '),
            'DocDummyPluralModel' => Str::snake(Str::pluralStudly($dummyModel), ' '),
            '{{ modelDocPlural }}' => Str::snake(Str::pluralStudly($dummyModel), ' '),
            '{{modelDocPlural}}' => Str::snake(Str::pluralStudly($dummyModel), ' '),
            'DummyUser' => $dummyUser,
            '{{ user }}' => $dummyUser,
            '{{user}}' => $dummyUser,
        ];

        $stub = str_replace(
            array_keys($replace), array_values($replace), $stub
        );

        return str_replace(
            "use {$namespacedModel};\nuse {$namespacedModel};", "use {$namespacedModel};", $stub
        );
    }
}
