<?php

namespace Spatie\Ray\Support;

use Exception;
use ReflectionFunction;
use Spatie\Ray\Ray;

class ExceptionHandler
{
    public function catch(Ray $ray, $callback): Ray
    {
        $this->executeExceptionHandlerCallback($ray, $callback);

        if (! empty(Ray::$caughtExceptions)) {
            throw array_shift(Ray::$caughtExceptions);
        }

        return $ray;
    }

    protected function executeCallableExceptionHandler(Ray $ray, $callback, $rethrow = true): Ray
    {
        $paramType = $this->getParamType(new ReflectionFunction($callback));
        $expectedClasses = $this->getExpectedClasses($paramType);

        if (count($expectedClasses)) {
            $isExpected = false;

            foreach ($expectedClasses as $class) {
                $isExpected = $this->isExpectedExceptionClass($class);

                if ($isExpected) {
                    break;
                }
            }

            if (! $isExpected && ! $rethrow) {
                return $ray;
            }

            if (! $isExpected && $rethrow) {
                throw array_shift(Ray::$caughtExceptions);
            }
        }

        $exception = array_shift(Ray::$caughtExceptions);

        $callbackResult = $callback($exception, $ray);

        return $callbackResult instanceof Ray ? $callbackResult : $ray;
    }

    protected function isExpectedExceptionClass($expectedClass): bool
    {
        foreach (Ray::$caughtExceptions as $caughtException) {
            if (is_a($caughtException, $expectedClass, true)) {
                return true;
            }
        }

        return false;
    }

    protected function sendExceptionPayload(Ray $ray): Ray
    {
        $exception = array_shift(Ray::$caughtExceptions);

        return $ray->exception($exception);
    }

    protected function executeExceptionHandlerCallback(Ray $ray, $callback, $rethrow = true): Ray
    {
        if (empty(Ray::$caughtExceptions)) {
            return $ray;
        }

        if (is_callable($callback)) {
            return $this->executeCallableExceptionHandler($ray, $callback, $rethrow);
        }

        // support arrays of both class names and callables
        if (is_array($callback)) {
            return $this->executeArrayOfExceptionHandlers($ray, $callback) ?? $ray;
        }

        return $this->sendCallbackExceptionPayload($ray, $callback);
        ;
    }

    protected function executeArrayOfExceptionHandlers(Ray $ray, array $callbacks): ?Ray
    {
        foreach ($callbacks as $item) {
            $result = $this->executeExceptionHandlerCallback($ray, $item, false);

            // the array item handled the exception
            if (empty(Ray::$caughtExceptions)) {
                return $result instanceof Ray ? $result : $ray;
            }
        }

        return $ray;
    }

    protected function sendCallbackExceptionPayload(Ray $ray, $callback): Ray
    {
        if (! $callback) {
            return $this->sendExceptionPayload($ray);
        }

        // handle class names
        foreach (Ray::$caughtExceptions as $caughtException) {
            if (is_string($callback) && is_a($caughtException, $callback, true)) {
                return $this->sendExceptionPayload($ray);
            }
        }

        return $ray;
    }

    protected function getExpectedClasses($paramType): array
    {
        if (! $paramType) {
            return [Exception::class];
        }

        $result = is_a($paramType, '\\ReflectionUnionType') ? $paramType->getTypes() : [$paramType->getName()];

        return array_map(function ($type) {
            if (is_string($type)) {
                return $type;
            }

            return method_exists($type, 'getName') ? $type->getName() : get_class($type);
        }, $result);
    }

    protected function getParamType(ReflectionFunction $reflection)
    {
        $paramType = null;

        if ($reflection->getNumberOfParameters() > 0) {
            $paramType = $reflection->getParameters()[0]->getType();
        }

        return $paramType;
    }
}
