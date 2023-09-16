<?php

namespace Spatie\LaravelRay\Payloads;

use Illuminate\Queue\Jobs\Job;
use Spatie\Ray\ArgumentConverter;
use Spatie\Ray\Payloads\Payload;

class JobEventPayload extends Payload
{
    /** @var object */
    protected $event;

    /** @var object|mixed */
    protected $job;

    /** @var \Throwable|null */
    protected $exception = null;

    public function __construct(object $event)
    {
        $this->event = $event;

        // Some queue drivers use an intermediate job with the orignal job stored inside.
        // For other drivers, the job is not altered, and it can be used directly.
        $this->job = $event->job instanceof Job
            ? unserialize($event->job->payload()['data']['command'])
            : $this->job = $event->job;

        if (property_exists($event, 'exception')) {
            $this->exception = $event->exception ?? null;
        }
    }

    public function getType(): string
    {
        return 'job_event';
    }

    public function getContent(): array
    {
        return [
            'event_name' => class_basename($this->event),
            'job' => $this->job ? ArgumentConverter::convertToPrimitive($this->job) : null,
            'exception' => $this->exception ? ArgumentConverter::convertToPrimitive($this->exception) : null,
        ];
    }
}
