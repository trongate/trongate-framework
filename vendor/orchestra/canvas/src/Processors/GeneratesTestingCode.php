<?php

namespace Orchestra\Canvas\Processors;

use Illuminate\Support\Str;
use Orchestra\Canvas\Core\GeneratesCode;
use Orchestra\Canvas\Core\Presets\Laravel;

/**
 * @property \Orchestra\Canvas\Commands\Testing $listener
 *
 * @see https://github.com/laravel/framework/blob/10.x/src/Illuminate/Foundation/Console/TestMakeCommand.php
 */
class GeneratesTestingCode extends GeneratesCode
{
    /**
     * Handle generating code.
     */
    protected function generatingCode(string $stub, string $name): string
    {
        $stub = parent::generatingCode($stub, $name);

        $testCase = $this->options['unit']
            ? $this->preset->config('testing.extends.unit', 'PHPUnit\Framework\TestCase')
            : $this->preset->config(
                'testing.extends.feature',
                $this->preset->is('laravel') ? 'Tests\TestCase' : 'Orchestra\Testbench\TestCase'
            );

        return $this->replaceTestCase($stub, $testCase);
    }

    /**
     * Replace the model for the given stub.
     */
    protected function replaceTestCase(string $stub, string $testCase): string
    {
        $namespaceTestCase = $testCase = str_replace('/', '\\', $testCase);

        if (Str::startsWith($testCase, '\\')) {
            $stub = str_replace('NamespacedDummyTestCase', trim($testCase, '\\'), $stub);
        } else {
            $stub = str_replace('NamespacedDummyTestCase', $namespaceTestCase, $stub);
        }

        $stub = str_replace(
            "use {$namespaceTestCase};\nuse {$namespaceTestCase};", "use {$namespaceTestCase};", $stub
        );

        $testCase = class_basename(trim($testCase, '\\'));

        return str_replace('DummyTestCase', $testCase, $stub);
    }

    /**
     * Get the destination class path.
     */
    protected function getPath(string $name): string
    {
        $name = Str::replaceFirst($this->rootNamespace(), '', $name);

        return sprintf(
            '%s/tests/%s',
            $this->preset->basePath(),
            str_replace('\\', '/', $name).'.php'
        );
    }

    /**
     * Get the root namespace for the class.
     */
    protected function rootNamespace(): string
    {
        return $this->preset->testingNamespace();
    }
}
