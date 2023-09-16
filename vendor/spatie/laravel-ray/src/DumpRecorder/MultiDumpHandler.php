<?php

namespace Spatie\LaravelRay\DumpRecorder;

class MultiDumpHandler
{
    /** @var array */
    protected $handlers = [];

    public function dump($value)
    {
        foreach ($this->handlers as $handler) {
            $handler($value);
        }
    }

    public function addHandler(callable $callable = null): self
    {
        $this->handlers[] = $callable;

        return $this;
    }

    public function resetHandlers(): void
    {
        $this->handlers = [];
    }
}
