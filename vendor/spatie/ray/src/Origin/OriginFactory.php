<?php

namespace Spatie\Ray\Origin;

interface OriginFactory
{
    public function getOrigin(): Origin;
}
