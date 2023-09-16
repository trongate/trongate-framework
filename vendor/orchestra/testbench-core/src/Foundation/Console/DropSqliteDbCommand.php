<?php

namespace Orchestra\Testbench\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'package:drop-sqlite-db', description: 'Drop sqlite database file')]
class DropSqliteDbCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'package:drop-sqlite-db';

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

        (new Actions\DeleteFiles(
            filesystem: $filesystem,
            components: $this->components,
            workingPath: $workingPath,
        ))->handle(["{$databasePath}/database.sqlite"]);

        return Command::SUCCESS;
    }
}
