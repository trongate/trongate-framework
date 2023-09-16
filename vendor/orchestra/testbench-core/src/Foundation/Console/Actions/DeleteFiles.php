<?php

namespace Orchestra\Testbench\Foundation\Console\Actions;

use Illuminate\Console\View\Components\Factory as ComponentsFactory;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Str;

class DeleteFiles extends Action
{
    /**
     * Construct a new delete files instance.
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
     * @param  iterable<int, string>  $files
     * @return void
     */
    public function handle(iterable $files): void
    {
        LazyCollection::make($files)
            ->reject(fn ($file) => Str::endsWith($file, ['.gitkeep', '.gitignore']))
            ->each(function ($file) {
                if ($this->filesystem->exists($file)) {
                    $this->filesystem->delete($file);

                    $this->components?->task(
                        sprintf('File [%s] has been deleted', $this->pathLocation($file))
                    );
                } else {
                    $this->components?->twoColumnDetail(
                        sprintf('File [%s] doesn\'t exists', $this->pathLocation($file)),
                        '<fg=yellow;options=bold>SKIPPED</>'
                    );
                }
            });
    }
}
