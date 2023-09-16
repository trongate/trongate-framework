<?php

namespace Orchestra\Testbench\Foundation\Console\Actions;

use Illuminate\Console\View\Components\Factory as ComponentsFactory;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\LazyCollection;

class DeleteDirectories extends Action
{
    /**
     * Construct a new delete directories instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $filesystem
     * @param  \Illuminate\Console\View\Components\Factory  $components
     * @param  string|null  $workingPath
     */
    public function __construct(
        public Filesystem $filesystem,
        public ?ComponentsFactory $components = null,
        ?string $workingPath = null
    ) {
        $this->workingPath = $workingPath;
    }

    /**
     * Handle the action.
     *
     * @param  iterable<int, string>  $directories
     * @return void
     */
    public function handle(iterable $directories): void
    {
        LazyCollection::make($directories)
            ->each(function ($directory) {
                if ($this->filesystem->isDirectory($directory)) {
                    $this->filesystem->deleteDirectory($directory);

                    $this->components?->task(
                        sprintf('Directory [%s] has been deleted', $this->pathLocation($directory))
                    );
                } else {
                    $this->components?->twoColumnDetail(
                        sprintf('Directory [%s] doesn\'t exists', $this->pathLocation($directory)),
                        '<fg=yellow;options=bold>SKIPPED</>'
                    );
                }
            });
    }
}
