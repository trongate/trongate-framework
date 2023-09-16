<?php

namespace Spatie\Ray\Support;

use Spatie\Ray\Origin\Origin;

class Limiters
{
    /** @var array */
    protected $counters = [];

    public function initialize(Origin $origin, int $limit): array
    {
        if (! isset($this->counters[$origin->fingerPrint()])) {
            $this->counters[$origin->fingerPrint()] = [0, $limit];
        }

        return $this->counters[$origin->fingerPrint()];
    }

    public function increment(Origin $origin): array
    {
        $name = $origin->fingerPrint();

        if (! isset($this->counters[$name])) {
            return [false, false];
        }

        [$times, $limit] = $this->counters[$name];

        $newTimes = $times + 1;

        $this->counters[$name] = [$newTimes, $limit];

        return [$newTimes, $limit];
    }

    public function canSendPayload(Origin $origin): bool
    {
        $name = $origin->fingerPrint();

        if (! isset($this->counters[$name])) {
            return true;
        }

        [$times, $limit] = $this->counters[$name];

        return $times < $limit || $limit <= 0;
    }
}
