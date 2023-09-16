<?php

namespace Orchestra\Canvas\Commands;

use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'make:generator', description: 'Create a new generator command')]
class ConsoleGenerator extends \Orchestra\Canvas\Core\Commands\Generators\ConsoleGenerator
{
    //
}
