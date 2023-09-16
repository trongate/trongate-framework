<?php

namespace Orchestra\Canvas\Commands;

use Orchestra\Canvas\Concerns\ResolvesPresetStubs;

abstract class Generator extends \Orchestra\Canvas\Core\Commands\Generator
{
    use ResolvesPresetStubs;

    /**
     * Get the stub file for the generator.
     */
    public function getStubFile(): string
    {
        return $this->getStubFileFromPresetStorage($this->preset, $this->getStubFileName());
    }

    /**
     * Get the stub file name for the generator.
     */
    abstract public function getStubFileName(): string;
}
