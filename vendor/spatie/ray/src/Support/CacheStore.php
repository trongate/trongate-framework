<?php

namespace Spatie\Ray\Support;

class CacheStore
{
    /** @var array */
    protected $store = [];

    /** @var Clock */
    protected $clock;

    public function __construct(Clock $clock)
    {
        $this->clock = $clock;
    }

    public function hit(): self
    {
        $this->store[] = $this->clock->now();

        return $this;
    }

    public function clear(): self
    {
        $this->store = [];

        return $this;
    }

    public function count(): int
    {
        return count($this->store);
    }

    public function countLastSecond(): int
    {
        $amount = 0;

        $lastSecond = $this->clock->now()->modify('-1 second');

        foreach ($this->store as $key => $item) {
            if ($this->isBetween(
                $item->getTimestamp(),
                $lastSecond->getTimestamp(),
                $this->clock->now()->getTimestamp()
            )
            ) {
                $amount++;
            }
        }

        return $amount;
    }

    protected function isBetween($toCheck, $start, $end): bool
    {
        return $toCheck >= $start && $toCheck <= $end;
    }
}
