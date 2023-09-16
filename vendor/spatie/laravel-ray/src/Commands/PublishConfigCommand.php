<?php

namespace Spatie\LaravelRay\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class PublishConfigCommand extends Command
{
    protected $signature = 'ray:publish-config {--homestead : Indicates that Homestead is being used}
                                               {--docker : Indicates that Docker is being used}';

    protected $description = 'Create the Laravel Ray config file in project root.';

    public function handle()
    {
        if ((new Filesystem())->exists('ray.php')) {
            $this->error('ray.php already exists in the project root');

            return;
        }

        copy(__DIR__ . '/../../stub/ray.php', base_path('ray.php'));

        if ($this->option('docker')) {
            file_put_contents(
                base_path('ray.php'),
                str_replace(
                    "'host' => env('RAY_HOST', 'localhost')",
                    "'host' => env('RAY_HOST', 'host.docker.internal')",
                    file_get_contents(base_path('ray.php'))
                )
            );
        }

        if ($this->option('homestead')) {
            file_put_contents(
                base_path('ray.php'),
                str_replace(
                    "'host' => env('RAY_HOST', 'localhost')",
                    "'host' => env('RAY_HOST', '10.0.2.2')",
                    file_get_contents(base_path('ray.php'))
                )
            );
        }

        $this->info('`ray.php` created in the project base directory');
    }
}
