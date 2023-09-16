<?php

namespace Orchestra\Canvas;

use Illuminate\Console\Application as Artisan;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Env;
use Illuminate\Support\ServiceProvider;
use Orchestra\Canvas\Core\Presets\Preset;
use Orchestra\Workbench\Workbench;
use Symfony\Component\Yaml\Yaml;

use function Orchestra\Testbench\package_path;

class LaravelServiceProvider extends ServiceProvider implements DeferrableProvider
{
    use Core\CommandsProvider;

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('orchestra.canvas', function (Application $app) {
            $filesystem = $app->make('files');

            if (\defined('TESTBENCH_WORKING_PATH') && class_exists(Workbench::class)) {
                return $this->registerCanvasForWorkbench($filesystem);
            }

            $config = ['preset' => 'laravel'];

            if ($filesystem->exists($app->basePath('canvas.yaml'))) {
                $config = Yaml::parseFile($app->basePath('canvas.yaml'));
            } else {
                Arr::set($config, 'testing.extends', [
                    'unit' => 'PHPUnit\Framework\TestCase',
                    'feature' => 'Tests\TestCase',
                ]);

                $config['namespace'] = rescue(fn () => trim($this->app->getNamespace(), '\\'), null, false);
            }

            $config['user-auth-provider'] = $this->userProviderModel();

            return Canvas::preset($config, $app->basePath(), $filesystem);
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        Artisan::starting(static function ($artisan) {
            $artisan->getLaravel()->booted(static function ($app) use ($artisan) {
                /**
                 * @var \Illuminate\Contracts\Foundation\Application $app
                 * @var \Illuminate\Console\Application $artisan
                 */
                $preset = $app->make('orchestra.canvas');

                if (
                    \defined('TESTBENCH_WORKING_PATH')
                    || Env::get('CANVAS_FOR_LARAVEL') === true
                    || file_exists($app->basePath('canvas.yaml'))
                ) {
                    $artisan->add(new Commands\Channel($preset));
                    $artisan->add(new Commands\Component($preset));
                    $artisan->add(new Commands\Console($preset));
                    $artisan->add(new Commands\Database\Cast($preset));
                    $artisan->add(new Commands\Database\Eloquent($preset));
                    $artisan->add(new Commands\Database\Factory($preset));
                    $artisan->add(new Commands\Database\Migration($preset));
                    $artisan->add(new Commands\Database\Observer($preset));
                    $artisan->add(new Commands\Database\Seeder($preset));
                    $artisan->add(new Commands\Event($preset));
                    $artisan->add(new Commands\Exception($preset));
                    $artisan->add(new Commands\Job($preset));
                    $artisan->add(new Commands\Listener($preset));
                    $artisan->add(new Commands\Mail($preset));
                    $artisan->add(new Commands\Notification($preset));
                    $artisan->add(new Commands\Policy($preset));
                    $artisan->add(new Commands\Provider($preset));
                    $artisan->add(new Commands\Request($preset));
                    $artisan->add(new Commands\Resource($preset));
                    $artisan->add(new Commands\Routing\Controller($preset));
                    $artisan->add(new Commands\Routing\Middleware($preset));
                    $artisan->add(new Commands\Rule($preset));
                    $artisan->add(new Commands\StubPublish($preset));
                    $artisan->add(new Commands\Testing($preset));
                    $artisan->add(new Commands\View($preset));
                }

                $preset->addAdditionalCommands($artisan);
            });
        });
    }

    /**
     * Regiseter canvas for workbench.
     */
    protected function registerCanvasForWorkbench(Filesystem $filesystem): Preset
    {
        $config = ['preset' => Presets\PackageWorkbench::class];

        if ($filesystem->exists(package_path('canvas.yaml'))) {
            $yaml = Yaml::parseFile(package_path('canvas.yaml'));

            $config['generators'] = $yaml['generators'] ?? [];
        }

        return Canvas::preset(
            $config, rtrim(package_path(), DIRECTORY_SEPARATOR), $filesystem
        );
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<int, string>
     */
    public function provides()
    {
        return ['orchestra.canvas'];
    }
}
