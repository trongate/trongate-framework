<?php

namespace Orchestra\Canvas\Processors;

use Illuminate\Support\Str;
use Orchestra\Canvas\Core\GeneratesCode;

/**
 * @property \Orchestra\Canvas\Commands\Listener $listener
 *
 * @see https://github.com/laravel/framework/blob/10.x/src/Illuminate/Foundation/Console/ListenerMakeCommand.php
 */
class GeneratesListenerCode extends GeneratesCode
{
    /**
     * Handle generating code.
     */
    protected function generatingCode(string $stub, string $name): string
    {
        $stub = parent::generatingCode($stub, $name);

        $event = $this->options['event'];

        if (\is_null($event) || ! Str::startsWith($event, [
            $this->preset->rootNamespace(),
            'Illuminate',
            '\\',
        ])) {
            $event = $this->preset->rootNamespace().'\\Events\\'.$event;
        }

        $stub = str_replace(
            ['DummyEvent', '{{ event }}', '{{event}}'],
            class_basename($event),
            $stub
        );

        return str_replace(
            ['DummyFullEvent', '{{ eventNamespace }}', '{{eventNamespace}}'],
            trim($event, '\\'),
            $stub
        );
    }

    /**
     * Determine if the class already exists.
     */
    protected function alreadyExists(string $rawName): bool
    {
        return class_exists($rawName);
    }
}
