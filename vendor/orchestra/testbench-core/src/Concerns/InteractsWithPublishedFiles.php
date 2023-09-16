<?php

namespace Orchestra\Testbench\Concerns;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

trait InteractsWithPublishedFiles
{
    /**
     * Determine if trait teardown has been registered.
     *
     * @var bool
     */
    protected $interactsWithPublishedFilesTeardownRegistered = false;

    /**
     * Setup Interacts with Published Files environment.
     */
    protected function setUpInteractsWithPublishedFiles(): void
    {
        $this->cleanUpFiles();
        $this->cleanUpMigrationFiles();

        $this->beforeApplicationDestroyed(function () {
            $this->tearDownInteractsWithPublishedFiles();
        });
    }

    /**
     * Teardown Interacts with Published Files environment.
     */
    protected function tearDownInteractsWithPublishedFiles(): void
    {
        if ($this->interactsWithPublishedFilesTeardownRegistered === false) {
            $this->cleanUpFiles();
            $this->cleanUpMigrationFiles();
        }

        $this->interactsWithPublishedFilesTeardownRegistered = true;
    }

    /**
     * Assert file does contains data.
     *
     * @param  array<int, string>  $contains
     */
    protected function assertFileContains(array $contains, string $file, string $message = ''): void
    {
        $this->assertFilenameExists($file);

        $haystack = $this->app['files']->get(
            $this->app->basePath($file)
        );

        foreach ($contains as $needle) {
            $this->assertStringContainsString($needle, $haystack, $message);
        }
    }

    /**
     * Assert file doesn't contains data.
     *
     * @param  array<int, string>  $contains
     */
    protected function assertFileNotContains(array $contains, string $file, string $message = ''): void
    {
        $this->assertFilenameExists($file);

        $haystack = $this->app['files']->get(
            $this->app->basePath($file)
        );

        foreach ($contains as $needle) {
            $this->assertStringNotContainsString($needle, $haystack, $message);
        }
    }

    /**
     * Assert file does contains data.
     *
     * @param  array<int, string>  $contains
     */
    protected function assertMigrationFileContains(array $contains, string $file, string $message = '', ?string $directory = null): void
    {
        $haystack = $this->app['files']->get($this->getMigrationFile($file, $directory));

        foreach ($contains as $needle) {
            $this->assertStringContainsString($needle, $haystack, $message);
        }
    }

    /**
     * Assert file doesn't contains data.
     *
     * @param  array<int, string>  $contains
     */
    protected function assertMigrationFileNotContains(array $contains, string $file, string $message = '', ?string $directory = null): void
    {
        $haystack = $this->app['files']->get($this->getMigrationFile($file, $directory));

        foreach ($contains as $needle) {
            $this->assertStringNotContainsString($needle, $haystack, $message);
        }
    }

    /**
     * Assert filename exists.
     */
    protected function assertFilenameExists(string $file): void
    {
        $appFile = $this->app->basePath($file);

        $this->assertTrue($this->app['files']->exists($appFile), "Assert file {$file} does exist");
    }

    /**
     * Assert filename not exists.
     */
    protected function assertFilenameNotExists(string $file): void
    {
        $appFile = $this->app->basePath($file);

        $this->assertTrue(! $this->app['files']->exists($appFile), "Assert file {$file} doesn't exist");
    }

    /**
     * Removes generated files.
     */
    protected function cleanUpFiles(): void
    {
        $this->app['files']->delete(
            Collection::make($this->files ?? [])
                ->transform(fn ($file) => $this->app->basePath($file))
                ->map(fn ($file) => str_contains($file, '*') ? [...$this->app['files']->glob($file)] : $file)
                ->flatten()
                ->filter(fn ($file) => $this->app['files']->exists($file))
                ->reject(fn ($file) => Str::endsWith($file, ['.gitkeep', '.gitignore']))
                ->all()
        );
    }

    /**
     * Removes generated migration files.
     */
    protected function getMigrationFile(string $filename, ?string $directory = null): string
    {
        $migrationPath = ! \is_null($directory)
            ? $this->app->basePath($directory)
            : $this->app->databasePath('migrations');

        return $this->app['files']->glob("{$migrationPath}/*{$filename}")[0];
    }

    /**
     * Removes generated migration files.
     */
    protected function cleanUpMigrationFiles(): void
    {
        $this->app['files']->delete(
            Collection::make($this->app['files']->files($this->app->databasePath('migrations')))
                ->filter(fn ($file) => Str::endsWith($file, '.php'))
                ->all()
        );
    }
}
