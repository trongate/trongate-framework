<?php

namespace Spatie\Ray\Support;

use Spatie\Ray\Ray;

class Counters
{
    /** @var array */
    protected $counters = [];

    public function increment(string $name): array
    {
        if (! isset($this->counters[$name])) {
            $this->counters[$name] = [ray(), 0];
        }

        [$ray, $times] = $this->counters[$name];

        $newTimes = $times + 1;

        $this->counters[$name] = [$ray, $newTimes];

        return [$ray, $newTimes];
    }

    public function get(string $name): int
    {
        if (! isset($this->counters[$name])) {
            return 0;
        }

        return $this->counters[$name][1];
    }

    public function clear(): void
    {
        $this->counters = [];
    }

    public function setRay(string $name, Ray $ray): void
    {
        $this->counters[$name][0] = $ray;
    }
}
