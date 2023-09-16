<?php

namespace Orchestra\Canvas\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

/**
 * @see https://github.com/laravel/framework/blob/10.x/src/Illuminate/Foundation/Console/ChannelMakeCommand.php
 */
#[AsCommand(name: 'make:channel', description: 'Create a new channel class')]
class Channel extends Generator
{
    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected string $type = 'Channel';

    /**
     * Get the stub file name for the generator.
     */
    public function getStubFileName(): string
    {
        return 'channel.stub';
    }

    /**
     * Get the default namespace for the class.
     */
    public function getDefaultNamespace(string $rootNamespace): string
    {
        return $rootNamespace.'\Broadcasting';
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the channel already exists'],
        ];
    }
}
