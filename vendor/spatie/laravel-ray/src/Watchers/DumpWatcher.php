<?php

namespace Spatie\LaravelRay\Watchers;

use Spatie\LaravelRay\DumpRecorder\DumpRecorder;
use Spatie\Ray\Settings\Settings;

class DumpWatcher extends Watcher
{
    public function register(): void
    {
        $settings = app(Settings::class);

        $this->enabled = $settings->send_dumps_to_ray;

        app(DumpRecorder::class)->register();
    }
}
