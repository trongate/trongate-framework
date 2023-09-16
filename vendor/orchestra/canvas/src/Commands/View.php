<?php

namespace Orchestra\Canvas\Commands;

use Illuminate\Console\Concerns\CreatesMatchingTest;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Orchestra\Canvas\Processors\GeneratesViewCode;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

/**
 * @see https://github.com/laravel/framework/blob/10.x/src/Illuminate/Foundation/Console/ViewMakeCommand.php
 */
#[AsCommand(name: 'make:view', description: 'Create a new view')]
class View extends Generator
{
    use CreatesMatchingTest;

    /**
     * The type of file being generated.
     *
     * @var string
     */
    protected string $type = 'View';

    /**
     * Generator processor.
     *
     * @var class-string<\Orchestra\Canvas\Core\GeneratesCode>
     */
    protected string $processor = GeneratesViewCode::class;

    /**
     * Handle generating code.
     */
    public function generatingCode(string $stub, string $name): string
    {
        $stub = parent::generatingCode($stub, $name);

        return str_replace(
            '{{ quote }}',
            Inspiring::quotes()->random(),
            $stub,
        );
    }

    /**
     * Get the stub file name for the generator.
     */
    public function getStubFileName(): string
    {
        return 'view.stub';
    }

    /**
     * Generator options.
     *
     * @return array<string, mixed>
     */
    public function generatorOptions(): array
    {
        return array_merge(parent::generatorOptions(), [
            'extension' => $this->option('extension'),
            'force' => $this->option('force'),
        ]);
    }

    /**
     * Get the destination test case path.
     *
     * @return string
     */
    protected function getTestPath()
    {
        return sprintf(
            '%s/%s',
            $this->preset->basePath(),
            Str::of($this->testClassFullyQualifiedName())
                ->replace('\\', '/')
                ->replaceFirst('Tests/Feature', 'tests/Feature')
                ->append('Test.php')
                ->value()
        );
    }

    /**
     * Create the matching test case if requested.
     *
     * @param  string  $path
     */
    protected function handleTestCreationUsingCanvas(string $path): bool
    {
        if (! $this->option('test') && ! $this->option('pest')) {
            return false;
        }

        $namespaceTestCase = $testCase = $this->preset->config(
            'testing.extends.feature',
            $this->preset->is('laravel') ? 'Tests\TestCase' : 'Orchestra\Testbench\TestCase'
        );

        $stub = $this->files->get($this->getTestStub());

        if (Str::startsWith($testCase, '\\')) {
            $stub = str_replace('NamespacedDummyTestCase', trim($testCase, '\\'), $stub);
        } else {
            $stub = str_replace('NamespacedDummyTestCase', $namespaceTestCase, $stub);
        }

        $contents = str_replace(
            ['{{ namespace }}', '{{ class }}', '{{ name }}', 'DummyTestCase'],
            [$this->testNamespace(), $this->testClassName(), $this->testViewName(), class_basename(trim($testCase, '\\'))],
            $stub,
        );

        $this->files->ensureDirectoryExists(\dirname($this->getTestPath()), 0755, true);

        return $this->files->put($this->getTestPath(), $contents) !== false;
    }

    /**
     * Get the namespace for the test.
     *
     * @return string
     */
    protected function testNamespace(): string
    {
        return Str::of($this->testClassFullyQualifiedName())
            ->beforeLast('\\')
            ->value();
    }

    /**
     * Get the class name for the test.
     *
     * @return string
     */
    protected function testClassName(): string
    {
        return Str::of($this->testClassFullyQualifiedName())
            ->afterLast('\\')
            ->append('Test')
            ->value();
    }

    /**
     * Get the class fully qualified name for the test.
     *
     * @return string
     */
    protected function testClassFullyQualifiedName(): string
    {
        /** @var string $extension */
        $extension = $this->option('extension');

        $name = Str::of(Str::lower($this->generatorName()))->replace('.'.$extension, '');

        $namespacedName = Str::of(
            Str::of($name)
                ->replace('/', ' ')
                ->explode(' ')
                ->map(fn ($part) => Str::of($part)->ucfirst())
                ->implode('\\')
        )
            ->replace(['-', '_'], ' ')
            ->explode(' ')
            ->map(fn ($part) => Str::of($part)->ucfirst())
            ->implode('');

        return sprintf(
            '%s\\Feature\\View\\%s',
            $this->preset->testingNamespace(),
            $namespacedName
        );
    }

    /**
     * Get the test stub file for the generator.
     *
     * @return string
     */
    protected function getTestStub(): string
    {
        return $this->getStubFileFromPresetStorage(
            $this->preset, 'view.'.($this->option('pest') ? 'pest' : 'test').'.stub'
        );
    }

    /**
     * Get the view name for the test.
     *
     * @return string
     */
    protected function testViewName(): string
    {
        return Str::of($this->generatorName())
            ->replace('/', '.')
            ->lower()
            ->value();
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['extension', null, InputOption::VALUE_OPTIONAL, 'The extension of the generated view', 'blade.php'],
            ['force', 'f', InputOption::VALUE_NONE, 'Create the view even if the view already exists'],
        ];
    }
}
