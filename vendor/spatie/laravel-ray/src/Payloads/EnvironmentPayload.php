<?php

namespace Spatie\LaravelRay\Payloads;

use Dotenv\Dotenv;
use Illuminate\Support\Env;
use Spatie\Ray\ArgumentConverter;
use Spatie\Ray\Payloads\Payload;

class EnvironmentPayload extends Payload
{
    /** @var array */
    protected $values;

    /** @var string */
    protected $path;

    /** @var string */
    protected $filename;

    /**
     * @param string[]|array|null $onlyShowNames
     * @param string|null $filename
     */
    public function __construct(?array $onlyShowNames = null, ?string $filename = null)
    {
        $filename = $filename ?? app()->environmentFilePath();

        $this->path = dirname($filename);

        $this->filename = basename($filename);

        $this->values = $this->getDotEnvValues($onlyShowNames);
    }

    public function getType(): string
    {
        return 'table';
    }

    public function getContent(): array
    {
        $values = array_map(function ($value) {
            $value = $this->decorateSpecialValues($value);

            return ArgumentConverter::convertToPrimitive($value);
        }, $this->values);

        return [
            'values' => $values,
            'label' => '.env',
        ];
    }

    protected function decorateSpecialValues($value)
    {
        if ($value === '') {
            return '<div class="text-gray-400">(empty)</div>';
        }

        if ($value === 'null' || $value === 'NULL') {
            return '<div class="text-gray-400">NULL</div>';
        }

        if ($value === 'true' || $value === 'false') {
            $color = $value === 'true' ? 'green' : 'red';

            return "<div class=\"text-{$color}-600\">{$value}</div>";
        }

        if (preg_match('~^https?://~', $value) === 1) {
            return "<a href=\"{$value}\" class=\"text-blue-600 hover:underline\">{$value}</a>";
        }

        if (strpos($value, 'base64:') === 0) {
            return "<div class=\"text-gray-400\">{$value}</div>";
        }

        // ip addresses
        if (preg_match('~(\d{1,3}\.){3}\d{1,3}~', $value) === 1) {
            return "<div href=\"{$value}\" class=\"text-indigo-700\">{$value}</div>";
        }

        return $value;
    }

    protected function loadDotEnv(): array
    {
        return Dotenv::create(
            Env::getRepository(),
            $this->path,
            $this->filename
        )->safeLoad();
    }

    protected function getDotEnvValues(?array $filterNames): array
    {
        $values = $this->loadDotEnv();

        if (! $filterNames) {
            return $values;
        }

        return array_filter($values, function ($value) use ($filterNames) {
            return in_array($value, $filterNames, true);
        }, ARRAY_FILTER_USE_KEY);
    }
}
