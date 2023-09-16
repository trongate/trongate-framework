<?php

namespace Spatie\Ray\Support;

use DateTimeImmutable;

class SystemClock implements Clock
{
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable();
    }
}
