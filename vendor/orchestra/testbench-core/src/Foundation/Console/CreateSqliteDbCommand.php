<?php

namespace Orchestra\Testbench\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'package:create-sqlite-db', description: 'Create sqlite database file')]
class CreateSqliteDbCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'package:create-sqlite-db {--force : Overwrite the database file}';

    /**
     * Execute the console command.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $filesystem
     * @return int
     */
    public function handle(Filesystem $filesystem)
    {
        $workingPath = $this->laravel->basePath();
        $databasePath = $this->laravel->databasePath();

        /** @var bool $force */
        $force = $this->option('force');

        $from = $filesystem->exists("{$databasePath}/database.sqlite.example")
            ? "{$databasePath}/database.sqlite.example"
            : (string) realpath(__DIR__.'/stubs/database.sqlite.example');

        $to = "{$databasePath}/database.sqlite";

        (new Actions\GeneratesFile(
            filesystem: $filesystem,
            components: $this->components,
            force: $force,
            workingPath: $workingPath,
        ))->handle($from, $to);

        return Command::SUCCESS;
    }
}
