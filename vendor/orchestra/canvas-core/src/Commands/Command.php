<?php

namespace Orchestra\Canvas\Core\Commands;

use Illuminate\Console\Concerns\CallsCommands;
use Illuminate\Console\Concerns\ConfiguresPrompts;
use Illuminate\Console\Concerns\HasParameters;
use Illuminate\Console\Concerns\InteractsWithIO;
use Illuminate\Console\Concerns\PromptsForMissingInput;
use Illuminate\Console\OutputStyle;
use Illuminate\Console\View\Components\Factory;
use Illuminate\Container\Container;
use Orchestra\Canvas\Core\Presets\Preset;
use Orchestra\Testbench\Foundation\Application as Testbench;
use Symfony\Component\Console\Command\Command as SymfonyConsole;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class Command extends \Symfony\Component\Console\Command\Command
{
    use CallsCommands,
        ConfiguresPrompts,
        HasParameters,
        InteractsWithIO,
        PromptsForMissingInput;

    /**
     * Canvas preset.
     *
     * @var \Orchestra\Canvas\Core\Presets\Preset
     */
    protected $preset;

    /**
     * The Laravel application instance.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $laravel;

    /**
     * Construct a new generator command.
     */
    public function __construct(Preset $preset)
    {
        $this->preset = $preset;

        parent::__construct();

        $this->specifyParameters();
    }

    /**
     * Initializes the command after the input has been bound and before the input
     * is validated.
     *
     * @return void
     *
     * @phpstan-param \Symfony\Component\Console\Output\OutputInterface&\Illuminate\Console\OutputStyle  $output
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->components = new Factory($output);

        $this->configurePrompts($input);
    }

    /**
     * Run the console command.
     */
    public function run(InputInterface $input, OutputInterface $output): int
    {
        $container = Container::getInstance();

        $this->laravel = $container->bound('app')
            ? $container->get('app')
            : Testbench::create(basePath: $this->preset->laravelPath());

        return parent::run(
            $this->input = $input,
            $this->output = new OutputStyle($input, $output)
        );
    }

    /**
     * Resolve the console command instance for the given command.
     *
     * @param  \Symfony\Component\Console\Command\Command|string  $command
     * @return \Symfony\Component\Console\Command\Command
     */
    protected function resolveCommand($command)
    {
        return $this->getApplication()->find(
            $command instanceof SymfonyConsole
                ? $command->getName()
                : $command
        );
    }

    /**
     * Get the Laravel application instance.
     *
     * @return \Illuminate\Contracts\Foundation\Application
     */
    public function getLaravel()
    {
        return $this->laravel;
    }
}
