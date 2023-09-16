<?php

namespace Orchestra\Canvas\Core;

trait CodeGenerator
{
    /**
     * Canvas preset.
     *
     * @var \Orchestra\Canvas\Core\Presets\Preset
     */
    protected $preset;

    /**
     * Set Preset for generator.
     *
     * @return $this
     */
    public function setPreset(Presets\Preset $preset)
    {
        $this->preset = $preset;

        return $this;
    }

    /**
     * Generate code.
     *
     * @return int
     */
    public function generateCode(bool $force = false)
    {
        return $this->resolveGeneratesCodeProcessor()($force);
    }

    /**
     * Code already exists.
     */
    public function codeAlreadyExists(string $className): int
    {
        $this->components->error(sprintf('%s [%s] already exists!', $this->type, $className));

        return static::FAILURE;
    }

    /**
     * Code successfully generated.
     */
    public function codeHasBeenGenerated(string $className): int
    {
        $this->components->info(sprintf('%s [%s] created successfully.', $this->type, $className));

        return static::SUCCESS;
    }

    /**
     * Get the default namespace for the class.
     */
    public function getDefaultNamespace(string $rootNamespace): string
    {
        return $rootNamespace;
    }

    /**
     * Generator options.
     *
     * @return array<string, mixed>
     */
    public function generatorOptions(): array
    {
        return [
            'name' => $this->generatorName(),
        ];
    }

    /**
     * Resolve generates code processor.
     */
    protected function resolveGeneratesCodeProcessor(): GeneratesCode
    {
        /** @var \Orchestra\Canvas\Core\GeneratesCode $class */
        $class = property_exists($this, 'processor')
            ? $this->processor
            : GeneratesCode::class;

        return new $class($this->preset, $this);
    }
}
