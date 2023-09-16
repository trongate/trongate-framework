<?php

namespace Orchestra\Canvas\Processors;

use Illuminate\Support\Str;
use InvalidArgumentException;
use Orchestra\Canvas\Core\GeneratesCode;

/**
 * @property \Orchestra\Canvas\Commands\Routing\Controller $listener
 *
 * @see https://github.com/laravel/framework/blob/10.x/src/Illuminate/Routing/Console/ControllerMakeCommand.php
 */
class GeneratesControllerCode extends GeneratesCode
{
    /**
     * Handle generating code.
     */
    protected function generatingCode(string $stub, string $name): string
    {
        $stub = parent::generatingCode($stub, $name);

        $controllerNamespace = $this->getNamespace($name);

        $rootNamespace = $this->rootNamespace();

        $replace = [];

        if ($this->options['parent']) {
            $replace = $this->buildParentReplacements();
        }

        if ($this->options['model']) {
            $replace = $this->buildModelReplacements($replace);
        }

        // Remove the base controller import if we are already in base namespace.
        $replace = array_merge($replace, [
            "use {$controllerNamespace}\Controller;\n" => '',
            "use {$rootNamespace}\Http\Controllers\Controller;" => "use {$rootNamespace}Http\Controllers\Controller;",
        ]);

        return str_replace(
            array_keys($replace), array_values($replace), $stub
        );
    }

    /**
     * Build the replacements for a parent controller.
     *
     * @return array<string, string>
     */
    protected function buildParentReplacements(): array
    {
        $parentModelClass = $this->parseModel($this->options['parent']);

        if (! class_exists($parentModelClass) && method_exists($this->listener, 'createModel')) {
            $this->listener->createModel($parentModelClass);
        }

        return [
            'ParentDummyFullModelClass' => $parentModelClass,
            '{{ namespacedParentModel }}' => $parentModelClass,
            '{{namespacedParentModel}}' => $parentModelClass,
            'ParentDummyModelClass' => class_basename($parentModelClass),
            '{{ parentModel }}' => class_basename($parentModelClass),
            '{{parentModel}}' => class_basename($parentModelClass),
            'ParentDummyModelVariable' => lcfirst(class_basename($parentModelClass)),
            '{{ parentModelVariable }}' => lcfirst(class_basename($parentModelClass)),
            '{{parentModelVariable}}' => lcfirst(class_basename($parentModelClass)),
        ];
    }

    /**
     * Build the model replacement values.
     *
     * @param  array<string, string>  $replace
     * @return array<string, string>
     */
    protected function buildModelReplacements(array $replace): array
    {
        $modelClass = $this->parseModel($this->options['model']);

        if (! class_exists($modelClass) && method_exists($this->listener, 'createModel')) {
            $this->listener->createModel($modelClass);
        }

        $replace = $this->buildFormRequestReplacements($replace, $modelClass);

        return array_merge($replace, [
            'DummyFullModelClass' => $modelClass,
            '{{ namespacedModel }}' => $modelClass,
            '{{namespacedModel}}' => $modelClass,
            'DummyModelClass' => class_basename($modelClass),
            '{{ model }}' => class_basename($modelClass),
            '{{model}}' => class_basename($modelClass),
            'DummyModelVariable' => lcfirst(class_basename($modelClass)),
            '{{ modelVariable }}' => lcfirst(class_basename($modelClass)),
            '{{modelVariable}}' => lcfirst(class_basename($modelClass)),
        ]);
    }

    /**
     * Get the fully-qualified model class name.
     *
     * @throws \InvalidArgumentException
     */
    protected function parseModel(string $model): string
    {
        if (preg_match('([^A-Za-z0-9_/\\\\])', $model)) {
            throw new InvalidArgumentException('Model name contains invalid characters.');
        }

        $model = trim(str_replace('/', '\\', $model), '\\');

        if (! Str::startsWith($model, $rootNamespace = $this->preset->modelNamespace())) {
            $model = $rootNamespace.'\\'.$model;
        }

        return $model;
    }

    /**
     * Build the model replacement values.
     *
     * @param  string  $modelClass
     * @return array<string, string>
     */
    protected function buildFormRequestReplacements(array $replace, $modelClass)
    {
        [$namespace, $storeRequestClass, $updateRequestClass] = [
            'Illuminate\\Http', 'Request', 'Request',
        ];

        if ($this->options['requests']) {
            $namespace = 'App\\Http\\Requests';

            [$storeRequestClass, $updateRequestClass] = $this->generateFormRequests(
                $modelClass, $storeRequestClass, $updateRequestClass
            );
        }

        $namespacedRequests = $namespace.'\\'.$storeRequestClass.';';

        if ($storeRequestClass !== $updateRequestClass) {
            $namespacedRequests .= PHP_EOL.'use '.$namespace.'\\'.$updateRequestClass.';';
        }

        return array_merge($replace, [
            '{{ storeRequest }}' => $storeRequestClass,
            '{{storeRequest}}' => $storeRequestClass,
            '{{ updateRequest }}' => $updateRequestClass,
            '{{updateRequest}}' => $updateRequestClass,
            '{{ namespacedStoreRequest }}' => $namespace.'\\'.$storeRequestClass,
            '{{namespacedStoreRequest}}' => $namespace.'\\'.$storeRequestClass,
            '{{ namespacedUpdateRequest }}' => $namespace.'\\'.$updateRequestClass,
            '{{namespacedUpdateRequest}}' => $namespace.'\\'.$updateRequestClass,
            '{{ namespacedRequests }}' => $namespacedRequests,
            '{{namespacedRequests}}' => $namespacedRequests,
        ]);
    }

    /**
     * Generate the form requests for the given model and classes.
     *
     * @param  string  $modelClass
     * @param  string  $storeRequestClass
     * @param  string  $updateRequestClass
     * @return array<int, string>
     */
    protected function generateFormRequests($modelClass, $storeRequestClass, $updateRequestClass)
    {
        $storeRequestClass = 'Store'.class_basename($modelClass).'Request';
        $updateRequestClass = 'Update'.class_basename($modelClass).'Request';

        $this->listener->createRequest($storeRequestClass);
        $this->listener->createRequest($updateRequestClass);

        return [$storeRequestClass, $updateRequestClass];
    }
}
