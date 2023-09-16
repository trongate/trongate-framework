<?php

namespace Orchestra\Workbench\Listeners;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Orchestra\Testbench\Contracts\Config as ConfigContract;
use Orchestra\Testbench\Foundation\Events\ServeCommandEnded;
use Orchestra\Workbench\Workbench;

class RemoveAssetSymlinkFolders
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
    public function handle(ServeCommandEnded $event): void
    {
        /** @var array<int, array{from: string, to: string}> $sync */
        $sync = Workbench::config('sync');

        Collection::make($sync)
            ->map(function ($pair) {
                /** @var string $from */
                $from = Workbench::packagePath($pair['from']);

                /** @var string $to */
                $to = Workbench::laravelPath($pair['to']);

                return is_link($to) ? $to : null;
            })->filter()
            ->each(function ($to) {
                /** @var string $to */
                $this->files->delete($to);
            });
    }
}
