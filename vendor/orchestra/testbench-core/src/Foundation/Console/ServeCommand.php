<?php

namespace Orchestra\Testbench\Foundation\Console;

use Composer\Config as ComposerConfig;
use Illuminate\Foundation\Console\ServeCommand as Command;
use Orchestra\Testbench\Foundation\Events\ServeCommandEnded;
use Orchestra\Testbench\Foundation\Events\ServeCommandStarted;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ServeCommand extends Command
{
    /**
     * Execute the console command.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (
            class_exists(ComposerConfig::class, false)
            && method_exists(ComposerConfig::class, 'disableProcessTimeout') // @phpstan-ignore-line
        ) {
            ComposerConfig::disableProcessTimeout();
        }

        /** @phpstan-ignore-next-line */
        $_ENV['TESTBENCH_WORKING_PATH'] = TESTBENCH_WORKING_PATH;

        static::$passthroughVariables[] = 'TESTBENCH_WORKING_PATH';

        event(new ServeCommandStarted($input, $output, $this->components));

        return tap(parent::execute($input, $output), function ($exitCode) use ($input, $output) {
            event(new ServeCommandEnded($input, $output, $this->components, $exitCode));
        });
    }

    /**
     * Get the value of a command option.
     *
     * @param  string|null  $key
     * @return string|array|bool|null
     */
    public function option($key = null)
    {
        $value = parent::option($key);

        if ($key === 'no-reload' && $value !== true) {
            return true;
        }

        return $value;
    }
}
