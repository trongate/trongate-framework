<?php

namespace Spatie\LaravelRay\Watchers;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\Event;
use Spatie\LaravelRay\Payloads\ExecutedQueryPayload;
use Spatie\LaravelRay\Ray;
use Spatie\Ray\Settings\Settings;

class SlowQueryWatcher extends QueryWatcher
{
    protected $minimumTimeInMs = 500;

    public function register(): void
    {
        $settings = app(Settings::class);

        $this->enabled = $settings->send_slow_queries_to_ray ?? false;
        $this->minimumTimeInMs = $settings->slow_query_threshold_in_ms ?? $this->minimumTimeInMs;

        Event::listen(QueryExecuted::class, function (QueryExecuted $query) {
            if (! $this->enabled()) {
                return;
            }

            $ray = app(Ray::class);

            if ($query->time >= $this->minimumTimeInMs) {
                $payload = new ExecutedQueryPayload($query);

                $ray->sendRequest($payload);
            }

            optional($this->rayProxy)->applyCalledMethods($ray);
        });
    }

    public function setMinimumTimeInMilliseconds(float $milliseconds): self
    {
        $this->minimumTimeInMs = $milliseconds;

        return $this;
    }
}
