<?php

namespace Orchestra\Canvas\Processors;

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Str;
use Orchestra\Canvas\Core\GeneratesCode;

/**
 * @property \Orchestra\Canvas\Commands\Database\Cast $listener
 *
 * @see https://github.com/laravel/framework/blob/10.x/src/Illuminate/Foundation/Console/ComponentMakeCommand.php
 */
class GeneratesCodeWithComponent extends GeneratesCode
{
    /**
     * Build the class with the given name.
     */
    protected function buildClass(string $name): string
    {
        $class = parent::buildClass($name);

        if (! empty($this->options['inline'])) {
            $class = str_replace(
                ['DummyView', '{{ view }}', '{{view}}'],
                "<<<'blade'\n<div>\n    ".Inspiring::quote()."\n</div>\nblade",
                $class
            );
        }

        return str_replace(
            ['DummyView', '{{ view }}', '{{view}}'],
            'view(\'components.'.Str::kebab(class_basename($name)).'\')',
            $class
        );
    }
}
