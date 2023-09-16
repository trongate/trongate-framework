<?php

declare(strict_types=1);

namespace League\Flysystem;

use function array_merge;

class Config
{
    public const OPTION_VISIBILITY = 'visibility';
    public const OPTION_DIRECTORY_VISIBILITY = 'directory_visibility';

    public function __construct(private array $options = [])
    {
    }

    /**
     * @param mixed $default
     *
     * @return mixed
     */
    public function get(string $property, $default = null)
    {
        return $this->options[$property] ?? $default;
    }

    public function extend(array $options): Config
    {
        return new Config(array_merge($this->options, $options));
    }

    public function withDefaults(array $defaults): Config
    {
        return new Config($this->options + $defaults);
    }
}
