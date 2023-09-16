<?php

namespace Orchestra\Testbench\PHPUnit;

use Orchestra\Testbench\Exceptions\DeprecatedException;
use Throwable;

use function Orchestra\Testbench\phpunit_version_compare;

if (phpunit_version_compare('10.1', '>=')) {
    class TestCase extends \PHPUnit\Framework\TestCase
    {
        /**
         * {@inheritdoc}
         */
        protected function transformException(Throwable $error): Throwable
        {
            /** @var \Illuminate\Testing\TestResponse|null $response */
            $response = static::$latestResponse ?? null;

            if (! \is_null($response)) {
                $response->transformNotSuccessfulException($error);
            }

            return $error;
        }
    }
} else {
    class TestCase extends \PHPUnit\Framework\TestCase
    {
        /**
         * {@inheritdoc}
         */
        protected function runTest(): mixed
        {
            $result = null;

            /** @var \Illuminate\Testing\TestResponse|null $response */
            $response = static::$latestResponse ?? null;

            try {
                $result = parent::runTest();
            } catch (DeprecatedException $error) {
                throw $error;
            } catch (Throwable $error) {
                if (! \is_null($response)) {
                    $response->transformNotSuccessfulException($error);
                }

                throw $error;
            }

            return $result;
        }
    }
}
