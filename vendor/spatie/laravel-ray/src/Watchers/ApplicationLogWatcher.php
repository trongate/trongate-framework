<?php

namespace Spatie\LaravelRay\Watchers;

use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Spatie\LaravelRay\Ray;
use Spatie\Ray\Payloads\ApplicationLogPayload;

class ApplicationLogWatcher extends Watcher
{
    public function register(): void
    {
        /** @var \Spatie\LaravelRay\Ray $ray */
        $ray = app(Ray::class);

        $this->enabled = $ray->settings->send_log_calls_to_ray;

        Event::listen(MessageLogged::class, function (MessageLogged $message) {
            if (! $this->shouldLogMessage($message)) {
                return;
            }

            if (! class_exists('Spatie\Ray\Payloads\ApplicationLogPayload')) {
                return;
            }

            $payload = new ApplicationLogPayload($message->message, $message->context);

            /** @var Ray $ray */
            $ray = app(Ray::class);

            $ray->sendRequest($payload);

            switch ($message->level) {
                case 'error':
                case 'critical':
                case 'alert':
                case 'emergency':
                    $ray->color('red');

                    break;
                case 'warning':
                    $ray->color('orange');

                    break;
            }
        });
    }

    protected function shouldLogMessage(MessageLogged $message): bool
    {
        if (! $this->enabled()) {
            return false;
        }

        if (is_null($message->message)) {
            return false;
        }

        /** @var Ray $ray */
        $ray = app(Ray::class);

        if (! $ray->settings->send_log_calls_to_ray) {
            return false;
        }

        if ((new ExceptionWatcher())->concernsException($message)) {
            return false;
        }

        if ((new LoggedMailWatcher())->concernsLoggedMail($message)) {
            return false;
        }

        if ((new DeprecatedNoticeWatcher())->concernsDeprecatedNotice($message)) {
            return false;
        }

        return true;
    }
}
