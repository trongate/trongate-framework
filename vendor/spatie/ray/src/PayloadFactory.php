<?php

namespace Spatie\Ray;

use Carbon\CarbonInterface;
use Spatie\Ray\Payloads\BoolPayload;
use Spatie\Ray\Payloads\CarbonPayload;
use Spatie\Ray\Payloads\LogPayload;
use Spatie\Ray\Payloads\NullPayload;
use Spatie\Ray\Payloads\Payload;

class PayloadFactory
{
    /** @var array */
    protected $values;

    /** @var \Closure|null */
    protected static $payloadFinder = null;

    public static function createForValues(array $arguments): array
    {
        return (new static($arguments))->getPayloads();
    }

    public static function registerPayloadFinder(callable $callable)
    {
        self::$payloadFinder = $callable;
    }

    public function __construct(array $values)
    {
        $this->values = $values;
    }

    public function getPayloads(): array
    {
        return array_map(function ($value) {
            return $this->getPayload($value);
        }, $this->values);
    }

    protected function getPayload($value): Payload
    {
        if (self::$payloadFinder) {
            if ($payload = (static::$payloadFinder)($value)) {
                return $payload;
            }
        }

        if (is_bool($value)) {
            return new BoolPayload($value);
        }

        if (is_null($value)) {
            return new NullPayload();
        }

        if ($value instanceof CarbonInterface) {
            return new CarbonPayload($value);
        }

        $primitiveValue = ArgumentConverter::convertToPrimitive($value);

        return new LogPayload($primitiveValue, $value);
    }
}
