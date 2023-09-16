<?php

namespace Spatie\LaravelRay\Watchers;

use Exception;
use Facade\FlareClient\Flare as FacadeFlare;
use Facade\FlareClient\Truncation\ReportTrimmer as FacadeReportTrimmer;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Spatie\FlareClient\Flare;
use Spatie\FlareClient\Truncation\ReportTrimmer;
use Spatie\LaravelRay\Ray;
use Spatie\Ray\Settings\Settings;
use Throwable;

class ExceptionWatcher extends Watcher
{
    public function register(): void
    {
        $settings = app(Settings::class);

        $this->enabled = $settings->send_exceptions_to_ray;

        Event::listen(MessageLogged::class, function (MessageLogged $message) {
            if (! $this->enabled()) {
                return;
            }

            if (! $this->concernsException($message)) {
                return;
            }

            $exception = $message->context['exception'];

            $meta = [];

            if ($flareReport = $this->getFlareReport($exception)) {
                $meta['flare_report'] = $flareReport;
            }

            /** @var Ray $ray */
            $ray = app(Ray::class);

            $ray->exception($exception, $meta);
        });
    }

    public function concernsException(MessageLogged $messageLogged): bool
    {
        if (! isset($messageLogged->context['exception'])) {
            return false;
        }

        if (! $messageLogged->context['exception'] instanceof Exception) {
            return false;
        }

        return true;
    }

    public function getFlareReport(Throwable $exception): ?array
    {
        if (app()->bound(Flare::class)) {
            $flare = app(Flare::class);

            $report = $flare->createReport($exception);

            return (new ReportTrimmer())->trim($report->toArray());
        }

        if (app()->bound(FacadeFlare::class)) {
            /** @var \Facade\FlareClient\Flare $flare */
            $flare = app(FacadeFlare::class);

            $report = $flare->createReport($exception);

            return (new FacadeReportTrimmer())->trim($report->toArray());
        }

        return null;
    }
}
