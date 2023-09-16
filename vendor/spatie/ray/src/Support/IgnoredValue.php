<?php

namespace Spatie\Ray\Support;

class IgnoredValue
{
    public static function make(): self
    {
        return new static();
    }
}
