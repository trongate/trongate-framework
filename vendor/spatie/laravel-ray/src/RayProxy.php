<?php

namespace Spatie\LaravelRay;

use Spatie\Ray\Ray;

class RayProxy
{
    /** @var array */
    protected $methodsCalled = [];

    public function __call($method, $arguments)
    {
        $this->methodsCalled[] = compact('method', 'arguments');
    }

    public function applyCalledMethods(Ray $ray)
    {
        foreach ($this->methodsCalled as $methodCalled) {
            call_user_func_array([$ray, $methodCalled['method']], $methodCalled['arguments']);
        }
    }
}
