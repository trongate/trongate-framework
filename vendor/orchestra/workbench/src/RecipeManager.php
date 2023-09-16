<?php

namespace Orchestra\Workbench;

use Illuminate\Support\Manager;
use Illuminate\Support\Str;

class RecipeManager extends Manager implements Contracts\RecipeManager
{
    /**
     * Create "asset-publish" driver.
     */
    public function createAssetPublishDriver(): Contracts\Recipe
    {
        return new Recipes\AssetPublishCommand();
    }

    /**
     * Create "create-sqlite-db" driver.
     */
    public function createCreateSqliteDbDriver(): Contracts\Recipe
    {
        return new Recipes\Command('workbench:create-sqlite-db', callback: function () {
            if (config('database.default') === 'testing') {
                config(['database.default' => 'sqlite']);
            }
        });
    }

    /**
     * Create "drop-sqlite-db" driver.
     */
    public function createDropSqliteDbDriver(): Contracts\Recipe
    {
        return new Recipes\Command('workbench:drop-sqlite-db', callback: function () {
            if (config('database.default') === 'sqlite') {
                config(['database.default' => 'testing']);
            }
        });
    }

    /**
     * Create anonymous command driver.
     */
    public function commandUsing(string $command): Contracts\Recipe
    {
        return new Recipes\Command($command);
    }

    /**
     * Run the recipe by name.
     */
    public function command(string $driver): Contracts\Recipe
    {
        return $this->driver($driver);
    }

    /**
     * Determine recipe is available by name.
     */
    public function hasCommand(string $driver): bool
    {
        if (isset($this->customCreators[$driver])) {
            return true;
        }

        $method = 'create'.Str::studly($driver).'Driver';

        return method_exists($this, $method);
    }

    /**
     * Get the default driver name.
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return 'asset-publish';
    }
}
