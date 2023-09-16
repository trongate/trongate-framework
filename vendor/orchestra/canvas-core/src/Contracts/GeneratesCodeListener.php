<?php

namespace Orchestra\Canvas\Core\Contracts;

interface GeneratesCodeListener
{
    /**
     * Code already exists.
     */
    public function codeAlreadyExists(string $className): mixed;

    /**
     * Handle generating code.
     */
    public function generatingCode(string $stub, string $className): string;

    /**
     * Code successfully generated.
     */
    public function codeHasBeenGenerated(string $className): mixed;

    /**
     * Run after code successfully generated.
     */
    public function afterCodeHasBeenGenerated(string $className, string $path): void;

    /**
     * Get the stub file for the generator.
     */
    public function getPublishedStubFileName(): ?string;

    /**
     * Get the stub file for the generator.
     */
    public function getStubFile(): string;

    /**
     * Get the default namespace for the class.
     */
    public function getDefaultNamespace(string $rootNamespace): string;

    /**
     * Get the desired class name.
     */
    public function generatorName(): string;

    /**
     * Generator options.
     */
    public function generatorOptions(): array;
}
