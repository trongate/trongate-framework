<?php

namespace Orchestra\Canvas\Processors;

use Orchestra\Canvas\Core\GeneratesCode;

/**
 * @property \Orchestra\Canvas\Commands\Event $listener
 *
 * @see https://github.com/laravel/framework/blob/10.x/src/Illuminate/Foundation/Console/EventMakeCommand.php
 */
class GeneratesEventCode extends GeneratesCode
{
    /**
     * Determine if the class already exists.
     */
    protected function alreadyExists(string $rawName): bool
    {
        return class_exists($rawName);
    }
}
