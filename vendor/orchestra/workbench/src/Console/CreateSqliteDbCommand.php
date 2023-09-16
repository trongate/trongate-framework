<?php

namespace Orchestra\Workbench\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'workbench:create-sqlite-db', description: 'Create sqlite database file')]
class CreateSqliteDbCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'workbench:create-sqlite-db {--force : Overwrite the database file}';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        return $this->call('package:create-sqlite-db', ['--force' => $this->option('force')]);
    }
}
