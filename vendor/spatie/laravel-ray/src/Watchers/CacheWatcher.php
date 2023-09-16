<?php

namespace Spatie\LaravelRay\Watchers;

use Illuminate\Cache\Events\CacheHit;
use Illuminate\Cache\Events\CacheMissed;
use Illuminate\Cache\Events\KeyForgotten;
use Illuminate\Cache\Events\KeyWritten;
use Spatie\LaravelRay\Payloads\CachePayload;
use Spatie\LaravelRay\Ray;
use Spatie\Ray\Settings\Settings;

class CacheWatcher extends Watcher
{
    public function register(): void
    {
        $settings = app(Settings::class);

        $this->enabled = $settings->send_cache_to_ray;

        app('events')->listen(CacheHit::class, function (CacheHit $event) {
            if (! $this->enabled()) {
                return;
            }

            $payload = new CachePayload('Hit', $event->key, $event->tags, $event->value);

            $ray = $this->ray()->sendRequest($payload);

            optional($this->rayProxy)->applyCalledMethods($ray);
        });

        app('events')->listen(CacheMissed::class, function (CacheMissed $event) {
            if (! $this->enabled()) {
                return;
            }

            $payload = new CachePayload('Missed', $event->key, $event->tags);

            $this->ray()->sendRequest($payload);
        });

        app('events')->listen(KeyWritten::class, function (KeyWritten $event) {
            if (! $this->enabled()) {
                return;
            }

            $payload = new CachePayload(
                'Key written',
                $event->key,
                $event->tags,
                $event->value,
                $this->formatExpiration($event),
            );

            $this->ray()->sendRequest($payload);
        });

        app('events')->listen(KeyForgotten::class, function (KeyForgotten $event) {
            if (! $this->enabled()) {
                return;
            }

            $payload = new CachePayload(
                'Key forgotten',
                $event->key,
                $event->tags
            );

            $this->ray()->sendRequest($payload);
        });
    }

    protected function formatExpiration(KeyWritten $event): ?int
    {
        return $event->seconds;
    }

    public function ray(): Ray
    {
        return app(Ray::class);
    }
}
