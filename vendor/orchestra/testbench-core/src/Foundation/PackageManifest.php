<?php

namespace Orchestra\Testbench\Foundation;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\PackageManifest as IlluminatePackageManifest;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

/**
 * @internal
 */
class PackageManifest extends IlluminatePackageManifest
{
    /**
     * Testbench Class.
     *
     * @var \Orchestra\Testbench\Contracts\TestCase|object|null
     */
    protected $testbench;

    /**
     * List of required packages.
     *
     * @var array<int, string>
     */
    protected $requiredPackages = [
        'laravel/dusk',
        'spatie/laravel-ray',
    ];

    /**
     * Create a new package manifest instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @param  string  $basePath
     * @param  string  $manifestPath
     * @param  \Orchestra\Testbench\Contracts\TestCase|object|null  $testbench
     */
    public function __construct(Filesystem $files, $basePath, $manifestPath, $testbench = null)
    {
        parent::__construct($files, $basePath, $manifestPath);

        $this->setTestbench($testbench);
    }

    /**
     * Create a new package manifest instance from base.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @param  object|null  $testbench
     * @return void
     */
    public static function swap($app, $testbench = null)
    {
        /** @var \Illuminate\Foundation\PackageManifest $base */
        $base = $app->make(IlluminatePackageManifest::class);

        $app->instance(
            IlluminatePackageManifest::class,
            new static(
                $base->files, $base->basePath, $base->manifestPath, $testbench
            )
        );
    }

    /**
     * Set Testbench instance.
     *
     * @param  object|null  $testbench
     * @return void
     */
    public function setTestbench($testbench): void
    {
        $this->testbench = \is_object($testbench) ? $testbench : null;
    }

    /**
     * Requires packages.
     *
     * @param  string[]  $packages
     * @return $this
     */
    public function requires(...$packages)
    {
        $this->requiredPackages = array_merge($this->requiredPackages, Arr::wrap($packages));

        return $this;
    }

    /**
     * Get the current package manifest.
     *
     * @return array
     */
    protected function getManifest()
    {
        $ignore = ! \is_null($this->testbench) && method_exists($this->testbench, 'ignorePackageDiscoveriesFrom')
                ? ($this->testbench->ignorePackageDiscoveriesFrom() ?? [])
                : [];

        $ignoreAll = \in_array('*', $ignore);

        return Collection::make(parent::getManifest())
            ->reject(function ($configuration, $package) use ($ignore, $ignoreAll) {
                return ($ignoreAll && ! \in_array($package, $this->requiredPackages))
                    || \in_array($package, $ignore);
            })->map(static function ($configuration, $key) {
                foreach ($configuration['providers'] ?? [] as $provider) {
                    if (! class_exists($provider)) {
                        return null;
                    }
                }

                return $configuration;
            })->filter()->all();
    }

    /**
     * Get all of the package names that should be ignored.
     *
     * @return array
     */
    protected function packagesToIgnore()
    {
        return [];
    }

    /**
     * Get all of the package names from root.
     *
     * @return array
     */
    protected function providersFromRoot()
    {
        $package = $this->providersFromTestbench();

        return \is_array($package) ? [
            $this->format($package['name']) => $package['extra']['laravel'] ?? [],
        ] : [];
    }

    /**
     * Get testbench root composer file.
     *
     * @return array{name: string, extra?: array{laravel?: array}}|null
     */
    protected function providersFromTestbench()
    {
        if (\defined('TESTBENCH_WORKING_PATH') && is_file(TESTBENCH_WORKING_PATH.'/composer.json')) {
            /** @var array{name: string, extra?: array{laravel?: array}} $composer */
            $composer = $this->files->json(TESTBENCH_WORKING_PATH.'/composer.json');

            return $composer;
        }

        return null;
    }

    /**
     * Write the given manifest array to disk.
     *
     * @param  array  $manifest
     * @return void
     *
     * @throws \Exception
     */
    protected function write(array $manifest)
    {
        parent::write(
            Collection::make($manifest)->merge($this->providersFromRoot())->filter()->all()
        );
    }
}
