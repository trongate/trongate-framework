<?php

namespace Orchestra\Workbench;

use RuntimeException;

class Composer extends \Illuminate\Support\Composer
{
    /**
     * Modify composer content.
     *
     * @param  callable(array):array  $callback
     */
    public function modify(callable $callback): void
    {
        $composerFile = "{$this->workingPath}/composer.json";

        if (! file_exists($composerFile)) {
            throw new RuntimeException("Unable to locate `composer.json` file at [{$this->workingPath}].");
        }

        $composer = json_decode((string) file_get_contents($composerFile), true, 512, JSON_THROW_ON_ERROR);

        $composer = \call_user_func($callback, $composer);

        file_put_contents(
            $composerFile,
            json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );
    }
}
