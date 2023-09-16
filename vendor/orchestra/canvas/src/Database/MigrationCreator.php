<?php

namespace Orchestra\Canvas\Database;

use Illuminate\Support\Str;
use InvalidArgumentException;
use Orchestra\Canvas\Concerns\ResolvesPresetStubs;
use Orchestra\Canvas\Core\Presets\Laravel;
use Orchestra\Canvas\Core\Presets\Preset;

class MigrationCreator extends \Illuminate\Database\Migrations\MigrationCreator
{
    use ResolvesPresetStubs;

    /**
     * Canvas preset.
     *
     * @var \Orchestra\Canvas\Core\Presets\Preset
     */
    protected $preset;

    /**
     * Create a new migration creator instance.
     */
    public function __construct(Preset $preset)
    {
        $this->files = $preset->filesystem();
        $this->preset = $preset;
        $this->customStubPath = sprintf('%s/stubs', $this->preset->basePath());
    }

    /**
     * Create a new migration at the given path.
     *
     * @param  string  $name
     * @param  string  $path
     * @param  string|null  $table
     * @param  bool  $create
     * @return string
     *
     * @throws \Exception
     */
    public function create($name, $path, $table = null, $create = false)
    {
        $name = trim(implode('_', [Str::slug($this->preset->config('migration.prefix', ''), '_'), $name]), '_');

        if (! $this->files->isDirectory($path)) {
            if ($this->preset instanceof Laravel) {
                throw new InvalidArgumentException("Path {$path} doesn't exists.");
            }

            $this->files->makeDirectory($path, 0755, true, true);
        }

        return parent::create($name, $path, $table, $create);
    }

    /**
     * Get the migration stub file.
     *
     * @param  string|null  $table
     * @param  bool  $create
     * @return string
     */
    protected function getStub($table, $create)
    {
        if (\is_null($table)) {
            $stub = $this->files->exists($customPath = $this->customStubPath.'/migration.stub')
                ? $customPath
                : $this->getStubFileFromPresetStorage($this->preset, 'migration.stub');
        } elseif ($create) {
            $stub = $this->files->exists($customPath = $this->customStubPath.'/migration.create.stub')
                ? $customPath
                : $this->getStubFileFromPresetStorage($this->preset, 'migration.create.stub');
        } else {
            $stub = $this->files->exists($customPath = $this->customStubPath.'/migration.update.stub')
                ? $customPath
                : $this->getStubFileFromPresetStorage($this->preset, 'migration.update.stub');
        }

        return $this->files->get($stub);
    }

    /**
     * Get the path to the stubs.
     *
     * @return string
     */
    public function stubPath()
    {
        return '';
    }
}
