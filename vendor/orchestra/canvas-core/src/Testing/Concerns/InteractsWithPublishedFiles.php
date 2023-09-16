<?php

namespace Orchestra\Canvas\Core\Testing\Concerns;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

trait InteractsWithPublishedFiles
{
    /**
     * The filesystem implementation.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $filesystem;

    /**
     * Setup Interacts with Published Files environment.
     */
    protected function setUpInteractsWithPublishedFiles(): void
    {
        $this->filesystem = $this->app['files'];

        $this->cleanUpFiles();
        $this->cleanUpMigrationFiles();
    }

    /**
     * Teardown Interacts with Published Files environment.
     */
    protected function tearDownInteractsWithPublishedFiles(): void
    {
        $this->cleanUpFiles();
        $this->cleanUpMigrationFiles();

        unset($this->filesystem);
    }

    /**
     * Assert file does contains data.
     *
     * @param  array<int, string>  $contains
     */
    protected function assertFileContains(array $contains, string $file, string $message = ''): void
    {
        $this->assertFilenameExists($file);

        $haystack = $this->filesystem->get(
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

        $haystack = $this->filesystem->get(
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
    protected function assertMigrationFileContains(array $contains, string $file, string $message = ''): void
    {
        $haystack = $this->filesystem->get($this->getMigrationFile($file));

        foreach ($contains as $needle) {
            $this->assertStringContainsString($needle, $haystack, $message);
        }
    }

    /**
     * Assert file doesn't contains data.
     *
     * @param  array<int, string>  $contains
     */
    protected function assertMigrationFileNotContains(array $contains, string $file, string $message = ''): void
    {
        $haystack = $this->filesystem->get($this->getMigrationFile($file));

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

        $this->assertTrue($this->filesystem->exists($appFile), "Assert file {$file} does exist");
    }

    /**
     * Assert filename not exists.
     */
    protected function assertFilenameNotExists(string $file): void
    {
        $appFile = $this->app->basePath($file);

        $this->assertTrue(! $this->filesystem->exists($appFile), "Assert file {$file} doesn't exist");
    }

    /**
     * Removes generated files.
     */
    protected function cleanUpFiles(): void
    {
        $this->filesystem->delete(
            Collection::make($this->files ?? [])
                ->transform(fn ($file) => $this->app->basePath($file))
                ->filter(fn ($file) => $this->filesystem->exists($file))
                ->all()
        );
    }

    /**
     * Removes generated migration files.
     */
    protected function getMigrationFile(string $filename): string
    {
        $migrationPath = $this->app->databasePath('migrations');

        return $this->filesystem->glob("{$migrationPath}/*{$filename}")[0];
    }

    /**
     * Removes generated migration files.
     */
    protected function cleanUpMigrationFiles(): void
    {
        $this->filesystem->delete(
            Collection::make($this->filesystem->files($this->app->databasePath('migrations')))
                ->filter(fn ($file) => Str::endsWith($file, '.php'))
                ->all()
        );
    }
}
