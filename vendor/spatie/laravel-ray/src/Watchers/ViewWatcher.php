<?php

namespace Spatie\LaravelRay\Watchers;

use Illuminate\Support\Facades\Event;
use Spatie\LaravelRay\Ray;
use Spatie\Ray\Settings\Settings;

class ViewWatcher extends Watcher
{
    public function register(): void
    {
        $settings = app(Settings::class);

        $this->enabled = $settings->send_views_to_ray;

        Event::listen('composing:*', function ($event, $data) {
            if (! $this->enabled()) {
                return;
            }

            /** @var \Illuminate\View\View $view */
            $view = $data[0];

            $ray = app(Ray::class)->view($view);

            optional($this->rayProxy)->applyCalledMethods($ray);
        });
    }
}
