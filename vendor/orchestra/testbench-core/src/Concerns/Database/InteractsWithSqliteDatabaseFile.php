<?php

namespace Orchestra\Testbench\Concerns\Database;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Orchestra\Testbench\Concerns\InteractsWithPublishedFiles;

trait InteractsWithSqliteDatabaseFile
{
    use InteractsWithPublishedFiles;

    /**
     * List of generated files.
     *
     * @var array<int, string>
     */
    protected $files = [];

    /**
     * Drop Sqlite Database.
     */
    protected function withoutSqliteDatabase(callable $callback): void
    {
        $time = time();
        $filesystem = new Filesystem();

        $database = database_path('database.sqlite');

        if ($filesystem->exists($database)) {
            $filesystem->move($database, $temporary = "{$database}.backup-{$time}");

            array_push($this->files, $temporary);
        }

        value($callback);

        if (isset($temporary)) {
            $filesystem->move($temporary, $database);
        }
    }

    /**
     * Drop Sqlite Database.
     */
    protected function withSqliteDatabase(callable $callback): void
    {
        $this->withoutSqliteDatabase(function () use ($callback) {
            $filesystem = new Filesystem();

            $database = database_path('database.sqlite');
            $time = time();

            if (! $filesystem->exists($database)) {
                $filesystem->copy($example = "{$database}.example", $database);
            }

            value($callback);

            if (isset($example)) {
                $filesystem->delete($database);
            }
        });
    }

    /**
     * Tear down the Dusk test case class.
     *
     * @afterClass
     *
     * @return void
     */
    public static function cleanupBackupSqliteDatabaseFilesOnFailed()
    {
        $filesystem = new Filesystem();

        $filesystem->delete(
            Collection::make($filesystem->glob(database_path('database.sqlite.backup-*')))
                ->filter(fn ($file) => $filesystem->exists($file))
                ->all()
        );
    }
}
