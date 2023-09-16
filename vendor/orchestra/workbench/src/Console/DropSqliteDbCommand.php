<?php

namespace Orchestra\Workbench\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'workbench:drop-sqlite-db', description: 'Drop sqlite database file')]
class DropSqliteDbCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'workbench:drop-sqlite-db';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        return $this->call('package:drop-sqlite-db');
    }
}
