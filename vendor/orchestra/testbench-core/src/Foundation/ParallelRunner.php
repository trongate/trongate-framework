<?php

namespace Orchestra\Testbench\Foundation;

use function Orchestra\Testbench\container;

class ParallelRunner extends \Illuminate\Testing\ParallelRunner
{
    /**
     * Creates the application.
     *
     * @return \Illuminate\Contracts\Foundation\Application
     */
    protected function createApplication()
    {
        if (! \defined('TESTBENCH_WORKING_PATH')) {
            \define('TESTBENCH_WORKING_PATH', $_SERVER['TESTBENCH_WORKING_PATH']);
        }

        $_ENV['APP_BASE_PATH'] = $_SERVER['APP_BASE_PATH'];

        $applicationResolver = static::$applicationResolver ?: function () {
            return container()->createApplication();
        };

        return $applicationResolver();
    }
}
