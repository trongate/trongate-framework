<?php

namespace Spatie\LaravelRay\Watchers;

use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Events\JobQueued;
use Illuminate\Support\Facades\Event;
use Spatie\LaravelRay\Payloads\JobEventPayload;
use Spatie\LaravelRay\Ray;
use Spatie\Ray\Settings\Settings;

class JobWatcher extends Watcher
{
    public function register(): void
    {
        $settings = app(Settings::class);

        $this->enabled = $settings->send_jobs_to_ray;

        Event::listen([
            JobQueued::class,
            JobProcessing::class,
            JobProcessed::class,
            JobFailed::class,
        ], function (object $event) {
            if (! $this->enabled()) {
                return;
            }

            $payload = new JobEventPayload($event);

            $ray = app(Ray::class)->sendRequest($payload);

            optional($this->rayProxy)->applyCalledMethods($ray);
        });
    }
}
