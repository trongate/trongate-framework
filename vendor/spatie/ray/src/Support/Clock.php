<?php

namespace Spatie\Ray\Support;

use DateTimeImmutable;

interface Clock
{
    public function now(): DateTimeImmutable;
}
