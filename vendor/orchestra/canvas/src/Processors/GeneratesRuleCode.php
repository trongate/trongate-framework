<?php

namespace Orchestra\Canvas\Processors;

use Orchestra\Canvas\Core\GeneratesCode;

/**
 * @property \Orchestra\Canvas\Commands\Rule $listener
 *
 * @see https://github.com/laravel/framework/blob/10.x/src/Illuminate/Foundation/Console/RuleMakeCommand.php
 */
class GeneratesRuleCode extends GeneratesCode
{
    /**
     * Handle generating code.
     */
    protected function generatingCode(string $stub, string $name): string
    {
        $stub = parent::generatingCode($stub, $name);

        return str_replace(
            '{{ ruleType }}', $this->options['implicit'] ? 'ImplicitRule' : 'Rule', $stub
        );
    }
}
