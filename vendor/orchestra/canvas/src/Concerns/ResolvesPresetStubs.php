<?php

namespace Orchestra\Canvas\Concerns;

use Orchestra\Canvas\Core\Presets\Preset;

trait ResolvesPresetStubs
{
    /**
     * Get the stub file for the preset.
     */
    public function getStubFileFromPresetStorage(Preset $preset, string $filename): string
    {
        $directory = $this->getPresetStorage($preset);

        if (file_exists("{$directory}/{$filename}")) {
            return "{$directory}/{$filename}";
        }

        return __DIR__."/../../storage/laravel/{$filename}";
    }

    /**
     * Get the stub storage for the preset.
     */
    public function getPresetStorage(Preset $preset): ?string
    {
        return $preset->hasCustomStubPath()
            ? $preset->getCustomStubPath()
            : $this->getDefaultPresetStorage($preset);
    }

    /**
     * Get the default stub storage for the preset.
     */
    public function getDefaultPresetStorage(Preset $preset): string
    {
        return __DIR__."/../../storage/{$preset->name()}";
    }
}
