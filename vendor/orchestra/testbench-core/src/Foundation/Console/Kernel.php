<?php

namespace Orchestra\Testbench\Foundation\Console;

use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

abstract class Kernel extends ConsoleKernel
{
    /**
     * Get the bootstrap classes for the application.
     *
     * @return array
     */
    protected function bootstrappers()
    {
        return [];
    }
}
