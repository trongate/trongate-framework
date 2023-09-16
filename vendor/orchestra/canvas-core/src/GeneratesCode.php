<?php

namespace Orchestra\Canvas\Core;

use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Str;

class GeneratesCode
{
    /**
     * The filesystem implementation.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Processor options.
     *
     * @var array<string, mixed>
     */
    protected $options = [];

    /**
     * Construct a new processor.
     */
    public function __construct(
        protected Presets\Preset $preset,
        protected Contracts\GeneratesCodeListener $listener
    ) {
        $this->files = $preset->filesystem();
        $this->options = $listener->generatorOptions();
    }

    /**
     * Execute generates code processor.
     *
     * @return int
     */
    public function __invoke(bool $force = false)
    {
        $name = $this->listener->generatorName();

        $className = $this->qualifyClass($name);

        $path = $this->getPath($className);

        // First we will check to see if the class already exists. If it does, we don't want
        // to create the class and overwrite the user's code. So, we will bail out so the
        // code is untouched. Otherwise, we will continue generating this class' files.
        if (! $force && $this->alreadyExists($name)) {
            return $this->listener->codeAlreadyExists($className);
        }

        // Next, we will generate the path to the location where this class' file should get
        // written. Then, we will build the class and make the proper replacements on the
        // stub files so that it gets the correctly formatted namespace and class name.
        $this->makeDirectory($path);

        $this->files->put($path, $this->sortImports($this->buildClass($className)));

        return tap($this->listener->codeHasBeenGenerated($className), function ($exitCode) use ($className, $path) {
            $this->listener->afterCodeHasBeenGenerated($className, Str::of($path)->after($this->preset->sourcePath()));
        });
    }

    /**
     * Parse the class name and format according to the root namespace.
     */
    protected function qualifyClass(string $name): string
    {
        $name = ltrim($name, '\\/');

        $rootNamespace = $this->rootNamespace();

        if (Str::startsWith($name, $rootNamespace)) {
            return $name;
        }

        $name = str_replace('/', '\\', $name);

        return $this->qualifyClass(
            $this->getDefaultNamespace(trim($rootNamespace, '\\')).'\\'.$name
        );
    }

    /**
     * Get the default namespace for the class.
     */
    protected function getDefaultNamespace(string $rootNamespace): string
    {
        return $this->listener->getDefaultNamespace($rootNamespace);
    }

    /**
     * Determine if the class already exists.
     */
    protected function alreadyExists(string $rawName): bool
    {
        return $this->files->exists($this->getPath($this->qualifyClass($rawName)));
    }

    /**
     * Get the destination class path.
     */
    protected function getPath(string $name): string
    {
        $name = Str::replaceFirst($this->rootNamespace(), '', $name);

        return $this->preset->sourcePath().'/'.str_replace('\\', '/', $name).'.php';
    }

    /**
     * Build the directory for the class if necessary.
     */
    protected function makeDirectory(string $path): string
    {
        if (! $this->files->isDirectory(\dirname($path))) {
            $this->files->makeDirectory(\dirname($path), 0777, true, true);
        }

        return $path;
    }

    /**
     * Build the class with the given name.
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function buildClass(string $name): string
    {
        return $this->generatingCode(
            $this->files->get($this->getListenerStubFile()), $name
        );
    }

    /**
     * Handle generating code.
     */
    protected function generatingCode(string $stub, string $name): string
    {
        return $this->listener->generatingCode(
            $this->replaceClass(
                $this->replaceNamespace($stub, $name), $name
            ), $name
        );
    }

    /**
     * Get generator stub file.
     */
    protected function getListenerStubFile(): string
    {
        if (\is_null($publishedStubFile = $this->listener->getPublishedStubFileName())) {
            return $this->listener->getStubFile();
        }

        $stubFile = sprintf(
            '%s/stubs/%s', $this->preset->basePath(), $publishedStubFile
        );

        if (! $this->files->exists($stubFile)) {
            return $this->listener->getStubFile();
        }

        return $stubFile;
    }

    /**
     * Replace the namespace for the given stub.
     */
    protected function replaceNamespace(string $stub, string $name): string
    {
        $stub = str_replace(
            ['DummyRootNamespace\\', '{{ rootNamespace }}\\', '{{rootNamespace}}\\'],
            '{{rootNamespace}}',
            $stub
        );

        $searches = [
            ['DummyNamespace', 'DummyRootNamespace', 'NamespacedDummyUserModel'],
            ['{{ namespace }}', '{{ rootNamespace }}', '{{ namespacedUserModel }}'],
            ['{{namespace}}', '{{rootNamespace}}', '{{namespacedUserModel}}'],
        ];

        foreach ($searches as $search) {
            $stub = str_replace(
                $search,
                [$this->getNamespace($name), $this->rootNamespace().'\\', $this->userProviderModel()],
                $stub
            );
        }

        return $stub;
    }

    /**
     * Replace the class name for the given stub.
     */
    protected function replaceClass(string $stub, string $name): string
    {
        $class = str_replace($this->getNamespace($name).'\\', '', $name);

        $stub = str_replace(
            ['DummyClass', '{{ class }}', '{{class}}'], $class, $stub
        );

        return str_replace(
            ['DummyUser', '{{ userModel }}', '{{userModel}}'],
            class_basename($this->userProviderModel()),
            $stub
        );
    }

    /**
     * Get the full namespace for a given class, without the class name.
     */
    protected function getNamespace(string $name): string
    {
        return trim(implode('\\', \array_slice(explode('\\', $name), 0, -1)), '\\');
    }

    /**
     * Alphabetically sorts the imports for the given stub.
     */
    protected function sortImports(string $stub): string
    {
        if (preg_match('/(?P<imports>(?:use [^;]+;$\n?)+)/m', $stub, $match)) {
            $imports = explode("\n", trim($match['imports']));

            sort($imports);

            return str_replace(trim($match['imports']), implode("\n", $imports), $stub);
        }

        return $stub;
    }

    /**
     * Get the root namespace for the class.
     */
    protected function rootNamespace(): string
    {
        return $this->preset->rootNamespace().'\\';
    }

    /**
     * Get the model for the default guard's user provider.
     */
    protected function userProviderModel(): string
    {
        return $this->preset->config('user-auth-provider', User::class);
    }
}
