<?php

namespace Orchestra\Workbench\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Collection;
use Orchestra\Workbench\Contracts\RecipeManager;
use Orchestra\Workbench\Workbench;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * @phpstan-import-type TWorkbenchConfig from \Orchestra\Testbench\Foundation\Config
 */
#[AsCommand(name: 'workbench:build', description: 'Run builds for workbench')]
class BuildCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'workbench:build';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(ConsoleKernel $kernel, RecipeManager $recipes)
    {
        $commands = Collection::make($kernel->all())
            ->keys()
            ->reject(fn ($command) => ! \is_string($command))
            ->mapWithKeys(fn (string $command) => [
                str_replace(':', '-', $command) => $command,
            ]);

        /** @var array<int, string> $build */
        $build = Workbench::config('build');

        Collection::make($build)
            ->each(function (string $build) use ($kernel, $recipes, $commands) {
                if ($recipes->hasCommand($build)) {
                    $recipes->command($build)->handle($kernel, $this->output);

                    return;
                }

                $command = $commands->get($build) ?? $commands->first(fn ($name) => $build === $name);

                if (! \is_null($command)) {
                    $recipes->commandUsing($command)->handle($kernel, $this->output);
                }
            });

        return Command::SUCCESS;
    }
}
