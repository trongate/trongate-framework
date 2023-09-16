<?php

namespace Orchestra\Workbench\Listeners;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Orchestra\Testbench\Contracts\Config as ConfigContract;
use Orchestra\Testbench\Foundation\Events\ServeCommandStarted;
use Orchestra\Workbench\Workbench;

class AddAssetSymlinkFolders
{
    /**
     * Construct a new event listener.
     */
    public function __construct(
        public ConfigContract $config,
        public Filesystem $files
    ) {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(ServeCommandStarted $event): void
    {
        /** @var array<int, array{from: string, to: string}> $sync */
        $sync = Workbench::config('sync');

        Collection::make($sync)
            ->map(function ($pair) {
                /** @var string $from */
                $from = Workbench::packagePath($pair['from']);

                /** @var string $to */
                $to = Workbench::laravelPath($pair['to']);

                return $this->files->isDirectory($from)
                    ? ['from' => $from, 'to' => $to]
                    : null;
            })->filter()
            ->each(function ($pair) {
                /** @var array{from: string, to: string} $pair */

                /** @var string $from */
                $from = $pair['from'];

                /** @var string $to */
                $to = $pair['to'];

                if (is_link($to)) {
                    $this->files->delete($to);
                } elseif ($this->files->isDirectory($to)) {
                    $this->files->deleteDirectory($to);
                }

                /** @var string $rootDirectory */
                $rootDirectory = Str::beforeLast($to, '/');

                if (! $this->files->isDirectory($rootDirectory)) {
                    $this->files->ensureDirectoryExists($rootDirectory);
                }

                $this->files->link($from, $to);
            });
    }
}
