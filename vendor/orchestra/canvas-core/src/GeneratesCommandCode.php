<?php

namespace Orchestra\Canvas\Core;

use Illuminate\Support\Str;

/**
 * @see https://github.com/laravel/framework/blob/10.x/src/Illuminate/Foundation/Console/ConsoleMakeCommand.php
 */
class GeneratesCommandCode extends GeneratesCode
{
    /**
     * Handle generating code.
     */
    protected function generatingCode(string $stub, string $name): string
    {
        $stub = parent::generatingCode($stub, $name);

        $command = $this->options['command'] ?: 'app:'.Str::of($name)->classBasename()->kebab()->value();

        return str_replace(['dummy:command', '{{ command }}', '{{command}}'], $command, $stub);
    }
}
