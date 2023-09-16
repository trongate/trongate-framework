<?php

namespace Spatie\Ray;

use Exception;
use Spatie\Ray\Exceptions\StopExecutionRequested;
use Spatie\Ray\Origin\Hostname;

class Client
{
    protected static $cache = [];

    /** @var int */
    protected $portNumber;

    /** @var string */
    protected $host;

    /** @var string */
    protected $fingerprint;

    public function __construct(int $portNumber = 23517, string $host = 'localhost')
    {
        $this->fingerprint = $host . ':' . $portNumber;

        $this->portNumber = $portNumber;

        $this->host = $host;
    }

    public function serverIsAvailable(): bool
    {
        // purge expired entries from the cache
        static::$cache = array_filter(static::$cache, function ($data) {
            return microtime(true) < $data[1];
        });

        if (! isset(static::$cache[$this->fingerprint])) {
            $this->performAvailabilityCheck();
        }

        return static::$cache[$this->fingerprint][0] ?? true;
    }

    public function performAvailabilityCheck(): bool
    {
        try {
            $curlHandle = $this->getCurlHandleForUrl('get', '_availability_check');

            curl_exec($curlHandle);

            $success = curl_errno($curlHandle) === CURLE_HTTP_NOT_FOUND;
            // expire the cache entry after 30 sec
            $expiresAt = microtime(true) + 30.0;

            static::$cache[$this->fingerprint] = [$success, $expiresAt];
        } finally {
            if (isset($curlHandle)) {
                curl_close($curlHandle);
            }

            return $success ?? false;
        }
    }

    public function send(Request $request): void
    {
        if (! $this->serverIsAvailable()) {
            return;
        }

        try {
            $curlHandle = $this->getCurlHandleForUrl('get', '');

            $curlError = null;

            curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $request->toJson());
            curl_exec($curlHandle);

            if (curl_errno($curlHandle)) {
                $curlError = curl_error($curlHandle);
            }

            if ($curlError) {
                // do nothing for now
            }
        } finally {
            if (isset($curlHandle)) {
                curl_close($curlHandle);
            }
        }
    }

    public function lockExists(string $lockName): bool
    {
        if (! $this->serverIsAvailable()) {
            return false;
        }

        $queryString = http_build_query([
            'hostname' => Hostname::get(),
            'project_name' => Ray::$projectName,
        ]);

        $curlHandle = $this->getCurlHandleForUrl('get', "locks/{$lockName}?{$queryString}");
        $curlError = null;

        try {
            $curlResult = curl_exec($curlHandle);

            if (curl_errno($curlHandle)) {
                $curlError = curl_error($curlHandle);
            }

            if ($curlError) {
                throw new Exception();
            }

            if (! $curlResult) {
                return false;
            }

            $response = json_decode($curlResult, true);

            if ($response['stop_execution'] ?? false) {
                throw StopExecutionRequested::make();
            }

            return $response['active'] ?? false;
        } catch (Exception $exception) {
            if ($exception instanceof StopExecutionRequested) {
                throw $exception;
            }
        } finally {
            curl_close($curlHandle);
        }

        return false;
    }

    protected function getCurlHandleForUrl(string $method, string $url)
    {
        return $this->getCurlHandle($method, "http://{$this->host}:{$this->portNumber}/{$url}");
    }

    protected function getCurlHandle(string $method, string $fullUrl)
    {
        $curlHandle = curl_init();

        curl_setopt($curlHandle, CURLOPT_URL, $fullUrl);

        curl_setopt($curlHandle, CURLOPT_HTTPHEADER, array_merge([
            'Accept: application/json',
            'Content-Type: application/json',
        ]));

        curl_setopt($curlHandle, CURLOPT_USERAGENT, 'Ray 1.0');
        curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curlHandle, CURLOPT_TIMEOUT, 2);
        curl_setopt($curlHandle, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($curlHandle, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($curlHandle, CURLOPT_ENCODING, '');
        curl_setopt($curlHandle, CURLINFO_HEADER_OUT, true);
        curl_setopt($curlHandle, CURLOPT_FAILONERROR, true);

        if ($method === 'post') {
            curl_setopt($curlHandle, CURLOPT_POST, true);
        }

        return $curlHandle;
    }
}
