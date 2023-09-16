<?php

namespace Orchestra\Workbench\Contracts;

interface RecipeManager
{
    /**
     * Create anonymous command driver.
     */
    public function commandUsing(string $command): Recipe;

    /**
     * Run the recipe by name.
     */
    public function command(string $driver): Recipe;

    /**
     * Determine recipe is available by name.
     */
    public function hasCommand(string $driver): bool;
}
