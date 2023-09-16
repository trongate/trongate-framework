<?php

namespace Orchestra\Canvas\Commands;

use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'make:class', description: 'Create a new class')]
class Code extends \Orchestra\Canvas\Core\Commands\Generators\Code
{
    //
}
