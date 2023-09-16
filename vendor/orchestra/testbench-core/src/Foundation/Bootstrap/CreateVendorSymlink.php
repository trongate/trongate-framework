<?php

namespace Orchestra\Testbench\Foundation\Bootstrap;

use ErrorException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;

/**
 * @internal
 */
final class CreateVendorSymlink
{
    /**
     * The project working path.
     *
     * @var string
     */
    public $workingPath;

    /**
     * Construct a new Create Vendor Symlink bootstrapper.
     *
     * @param  string  $workingPath
     */
    public function __construct(string $workingPath)
    {
        $this->workingPath = $workingPath;
    }

    /**
     * Bootstrap the given application.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function bootstrap(Application $app): void
    {
        $filesystem = new Filesystem();

        $appVendorPath = $app->basePath('vendor');

        if (
            ! $filesystem->isFile("{$appVendorPath}/autoload.php") ||
            $filesystem->hash("{$appVendorPath}/autoload.php") !== $filesystem->hash("{$this->workingPath}/autoload.php")
        ) {
            if ($filesystem->exists($app->bootstrapPath('cache/packages.php'))) {
                $filesystem->delete($app->bootstrapPath('cache/packages.php'));
            }

            if (is_link($appVendorPath)) {
                $filesystem->delete($appVendorPath);
            }

            try {
                $filesystem->link($this->workingPath, $appVendorPath);
            } catch (ErrorException) {
                //
            }
        }

        $app->flush();
    }
}
