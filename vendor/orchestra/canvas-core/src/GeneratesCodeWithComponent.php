<?php

namespace Orchestra\Canvas\Core;

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Str;

/**
 * @see https://github.com/laravel/framework/blob/10.x/src/Illuminate/Foundation/Console/ComponentMakeCommand.php
 */
class GeneratesCodeWithComponent extends GeneratesCode
{
    /**
     * Handle generating code.
     */
    protected function generatingCode(string $stub, string $name): string
    {
        $stub = parent::generatingCode($stub, $name);

        if (! empty($this->options['inline'])) {
            $stub = str_replace(
                ['DummyView', '{{ view }}', '{{view}}'],
                "<<<'blade'\n<div>\n    ".Inspiring::quote()."\n</div>\nblade",
                $stub
            );
        }

        return str_replace(
            ['DummyView', '{{ view }}', '{{view}}'],
            'view(\'components.'.Str::kebab(class_basename($name)).'\')',
            $stub
        );
    }
}
