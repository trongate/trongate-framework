<?php

namespace Orchestra\Testbench\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
use Orchestra\Testbench\Contracts\Config as ConfigContract;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'package:purge-skeleton', description: 'Purge skeleton folder to original state')]
class PurgeSkeletonCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'package:purge-skeleton';

    /**
     * Execute the console command.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $filesystem
     * @return int
     */
    public function handle(Filesystem $filesystem, ConfigContract $config)
    {
        $this->call('config:clear');
        $this->call('event:clear');
        $this->call('route:clear');
        $this->call('view:clear');

        ['files' => $files, 'directories' => $directories] = $config->getPurgeAttributes();

        $workingPath = $this->laravel->basePath();

        (new Actions\DeleteFiles(
            filesystem: $filesystem,
            workingPath: $workingPath,
        ))->handle(
            Collection::make([
                '.env',
                'testbench.yaml',
            ])->map(fn ($file) => $this->laravel->basePath($file))
        );

        (new Actions\DeleteFiles(
            filesystem: $filesystem,
            workingPath: $workingPath,
        ))->handle(
            LazyCollection::make(function () use ($filesystem) {
                yield $this->laravel->basePath('database/database.sqlite');
                yield $filesystem->glob($this->laravel->basePath('routes/testbench-*.php'));
                yield $filesystem->glob($this->laravel->basePath('storage/app/public/*'));
                yield $filesystem->glob($this->laravel->basePath('storage/app/*'));
                yield $filesystem->glob($this->laravel->basePath('storage/framework/sessions/*'));
            })->flatten()
        );

        (new Actions\DeleteFiles(
            filesystem: $filesystem,
            components: $this->components,
            workingPath: $workingPath,
        ))->handle(
            LazyCollection::make($files)
                ->map(fn ($file) => $this->laravel->basePath($file))
                ->map(function ($file) use ($filesystem) {
                    return str_contains($file, '*')
                        ? [...$filesystem->glob($file)]
                        : $file;
                })->flatten()
                ->reject(fn ($file) => str_contains($file, '*'))
        );

        (new Actions\DeleteDirectories(
            filesystem: $filesystem,
            components: $this->components,
            workingPath: $workingPath,
        ))->handle(
            Collection::make($directories)
                ->map(fn ($directory) => $this->laravel->basePath($directory))
                ->map(function ($directory) use ($filesystem) {
                    return str_contains($directory, '*')
                        ? [...$filesystem->glob($directory)]
                        : $directory;
                })->flatten()
                ->reject(fn ($directory) => str_contains($directory, '*'))
        );

        return Command::SUCCESS;
    }
}
