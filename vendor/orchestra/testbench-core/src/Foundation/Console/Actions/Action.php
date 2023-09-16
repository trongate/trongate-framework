<?php

namespace Orchestra\Testbench\Foundation\Console\Actions;

use function Orchestra\Testbench\package_path;

abstract class Action
{
    /**
     * Working path for the action.
     *
     * @var string|null
     */
    public $workingPath;

    /**
     * Normalise file location.
     *
     * @param  string  $path
     * @return string
     */
    protected function pathLocation(string $path): string
    {
        if (! \is_null($this->workingPath)) {
            $path = str_replace(rtrim($this->workingPath, '/').'/', '', $path);
        }

        $path = str_replace(package_path(), '', $path);

        return $path;
    }
}
