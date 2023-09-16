<?php

namespace Orchestra\Testbench\Foundation\Console\Concerns;

use Illuminate\Support\Collection;

trait HandleTerminatingConsole
{
    /**
     * The terminating callbacks.
     *
     * @var array<int, (callable(\Illuminate\Filesystem\Filesystem):void)>
     */
    protected $beforeTerminatingCallbacks = [];

    /**
     * Register a callback to be run before terminating the command.
     *
     * @param  callable(\Illuminate\Filesystem\Filesystem):void  $callback
     * @return void
     */
    protected function beforeTerminating(callable $callback): void
    {
        array_unshift($this->beforeTerminatingCallbacks, $callback);
    }

    /**
     * Handle terminating console.
     *
     * @return void
     */
    protected function handleTerminatingConsole(): void
    {
        Collection::make($this->beforeTerminatingCallbacks)
            ->each(function ($callback) {
                \call_user_func($callback);
            });
    }
}
