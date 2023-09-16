<?php

namespace Orchestra\Testbench\Foundation\Console\Concerns;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\LazyCollection;

trait CopyTestbenchFiles
{
    use HandleTerminatingConsole;

    /**
     * Copy the "testbench.yaml" file.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @param  \Illuminate\Filesystem\Filesystem  $filesystem
     * @param  string  $workingPath
     * @return void
     */
    protected function copyTestbenchConfigurationFile(Application $app, Filesystem $filesystem, string $workingPath): void
    {
        $configurationFile = LazyCollection::make(function () {
            yield 'testbench.yaml';
            yield 'testbench.yaml.example';
            yield 'testbench.yaml.dist';
        })->map(fn ($file) => "{$workingPath}/{$file}")
            ->filter(fn ($file) => $filesystem->exists($file))
            ->first();

        $testbenchFile = $app->basePath('testbench.yaml');

        if ($filesystem->exists($testbenchFile)) {
            $filesystem->copy($testbenchFile, "{$testbenchFile}.backup");

            $this->beforeTerminating(function () use ($filesystem, $testbenchFile) {
                if ($filesystem->exists("{$testbenchFile}.backup")) {
                    $filesystem->move("{$testbenchFile}.backup", $testbenchFile);
                }
            });
        }

        if (! \is_null($configurationFile)) {
            $filesystem->copy($configurationFile, $testbenchFile);

            $this->beforeTerminating(function () use ($filesystem, $testbenchFile) {
                if ($filesystem->exists($testbenchFile)) {
                    $filesystem->delete($testbenchFile);
                }
            });
        }
    }

    /**
     * Copy the ".env" file.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @param  \Illuminate\Filesystem\Filesystem  $filesystem
     * @param  string  $workingPath
     * @return void
     */
    protected function copyTestbenchDotEnvFile(Application $app, Filesystem $filesystem, string $workingPath): void
    {
        $workingPath = $filesystem->isDirectory("{$workingPath}/workbench")
            ? "{$workingPath}/workbench"
            : $workingPath;

        $configurationFile = LazyCollection::make(function () {
            yield $this->environmentFile;
            yield "{$this->environmentFile}.example";
            yield "{$this->environmentFile}.dist";
        })->map(fn ($file) => "{$workingPath}/{$file}")
            ->filter(fn ($file) => $filesystem->exists($file))
            ->first();

        if (\is_null($configurationFile) && $filesystem->exists($app->basePath('.env.example'))) {
            $configurationFile = $app->basePath('.env.example');
        }

        $environmentFile = $app->basePath('.env');

        if ($filesystem->exists($environmentFile)) {
            $filesystem->copy($environmentFile, "{$this->environmentFile}.backup");

            $this->beforeTerminating(function () use ($filesystem, $environmentFile) {
                $filesystem->move("{$this->environmentFile}.backup", $environmentFile);
            });
        }

        if (! \is_null($configurationFile) && ! $filesystem->exists($environmentFile)) {
            $filesystem->copy($configurationFile, $environmentFile);

            $this->beforeTerminating(function () use ($filesystem, $environmentFile) {
                $filesystem->delete($environmentFile);
            });
        }
    }
}
