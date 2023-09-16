<?php

namespace Spatie\LaravelRay\Watchers;

use Illuminate\Support\Facades\Event;
use Spatie\LaravelRay\Payloads\EventPayload;
use Spatie\LaravelRay\Ray;

class EventWatcher extends Watcher
{
    public function register(): void
    {
        Event::listen('*', function (string $eventName, array $arguments) {
            if (! $this->enabled()) {
                return;
            }

            $payload = new EventPayload($eventName, $arguments);

            $ray = app(Ray::class)->sendRequest($payload);

            optional($this->rayProxy)->applyCalledMethods($ray);
        });
    }
}
