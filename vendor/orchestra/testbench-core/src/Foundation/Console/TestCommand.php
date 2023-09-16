<?php

namespace Orchestra\Testbench\Foundation\Console;

use Illuminate\Support\Collection;
use Illuminate\Support\Env;
use Illuminate\Support\Str;
use NunoMaduro\Collision\Adapters\Laravel\Commands\TestCommand as Command;

use function Orchestra\Testbench\package_path;

class TestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'package:test
        {--without-tty : Disable output to TTY}
        {--compact : Indicates whether the compact printer should be used}
        {--configuration= : Read configuration from XML file}
        {--coverage : Indicates whether the coverage information should be collected}
        {--min= : Indicates the minimum threshold enforcement for coverage}
        {--p|parallel : Indicates if the tests should run in parallel}
        {--profile : Lists top 10 slowest tests}
        {--recreate-databases : Indicates if the test databases should be re-created}
        {--drop-databases : Indicates if the test databases should be dropped}
        {--without-databases : Indicates if database configuration should be performed}
        {--c|--custom-argument : Add custom env variables}
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run the package tests';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        if (! \defined('TESTBENCH_WORKING_PATH')) {
            $this->setHidden(true);
        }
    }

    /**
     * Get the PHPUnit configuration file path.
     *
     * @return string
     */
    public function phpUnitConfigurationFile()
    {
        $configurationFile = str_replace('./', '', $this->option('configuration') ?? 'phpunit.xml');

        return Collection::make([
            package_path('/'.$configurationFile),
            package_path('/'.$configurationFile.'.dist'),
        ])->filter(function ($path) {
            return file_exists($path);
        })->first() ?? './';
    }

    /**
     * Get the array of arguments for running PHPUnit.
     *
     * @param  array  $options
     * @return array
     */
    protected function phpunitArguments($options)
    {
        $file = $this->phpUnitConfigurationFile();

        return Collection::make(parent::phpunitArguments($options))
            ->reject(function ($option) {
                return Str::startsWith($option, ['--configuration=']);
            })->merge(["--configuration={$file}"])
            ->all();
    }

    /**
     * Get the array of arguments for running Paratest.
     *
     * @param  array  $options
     * @return array
     */
    protected function paratestArguments($options)
    {
        $file = $this->phpUnitConfigurationFile();

        return Collection::make(parent::paratestArguments($options))
            ->reject(function (string $option) {
                return Str::startsWith($option, ['--configuration=', '--runner=']);
            })->merge([
                "--configuration={$file}",
                "--runner=\Orchestra\Testbench\Foundation\ParallelRunner",
            ])
            ->all();
    }

    /**
     * Get the array of environment variables for running PHPUnit.
     *
     * @return array
     */
    protected function phpunitEnvironmentVariables()
    {
        return array_merge([
            'APP_KEY' => Env::get('APP_KEY'),
            'APP_DEBUG' => Env::get('APP_DEBUG'),
            'APP_ENV' => 'testing',
            'TESTBENCH_PACKAGE_TESTER' => '(true)',
            'TESTBENCH_WORKING_PATH' => TESTBENCH_WORKING_PATH,
            'APP_BASE_PATH' => $this->laravel->basePath(),
        ], parent::phpunitEnvironmentVariables());
    }

    /**
     * Get the array of environment variables for running Paratest.
     *
     * @return array
     */
    protected function paratestEnvironmentVariables()
    {
        return array_merge([
            'APP_KEY' => Env::get('APP_KEY'),
            'APP_DEBUG' => Env::get('APP_DEBUG'),
            'APP_ENV' => 'testing',
            'TESTBENCH_PACKAGE_TESTER' => '(true)',
            'TESTBENCH_WORKING_PATH' => TESTBENCH_WORKING_PATH,
            'APP_BASE_PATH' => $this->laravel->basePath(),
        ], parent::paratestEnvironmentVariables());
    }

    /**
     * Get the configuration file.
     *
     * @return string
     */
    protected function getConfigurationFile()
    {
        return $this->phpUnitConfigurationFile();
    }
}
