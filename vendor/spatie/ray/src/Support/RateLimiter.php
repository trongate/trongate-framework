<?php

namespace Spatie\Ray\Support;

class RateLimiter
{
    /** @var int|null */
    protected $maxCalls;

    /** @var int|null */
    protected $maxPerSecond;

    /** @var CacheStore */
    protected $cache;

    /** @var bool */
    protected $notified;

    private function __construct(?int $maxCalls, ?int $maxPerSecond)
    {
        $this->maxCalls = $maxCalls;
        $this->maxPerSecond = $maxPerSecond;
        $this->cache = new CacheStore(new SystemClock());
    }

    public static function disabled(): self
    {
        return new self(null, null);
    }

    public function hit(): self
    {
        $this->cache()->hit();

        return $this;
    }

    public function max(?int $maxCalls): self
    {
        $this->maxCalls = $maxCalls;

        return $this;
    }

    public function perSecond(?int $callsPerSecond): self
    {
        $this->maxPerSecond = $callsPerSecond;

        return $this;
    }

    public function isMaxReached(): bool
    {
        if ($this->maxCalls === null) {
            return false;
        }

        $reached = $this->cache()->count() >= $this->maxCalls;

        if ($reached === false) {
            $this->notified = false;
        }

        return $reached;
    }

    public function isMaxPerSecondReached(): bool
    {
        if ($this->maxPerSecond === null) {
            return false;
        }

        $reached = $this->cache()->countLastSecond() >= $this->maxPerSecond;

        if ($reached === false) {
            $this->notified = false;
        }

        return $reached;
    }

    public function clear(): self
    {
        $this->maxCalls = null;
        $this->maxPerSecond = null;

        $this->cache()->clear();

        return $this;
    }

    public function isNotified(): bool
    {
        return $this->notified;
    }

    public function notify(): void
    {
        $this->notified = true;
    }

    public function cache(): CacheStore
    {
        return $this->cache;
    }
}
