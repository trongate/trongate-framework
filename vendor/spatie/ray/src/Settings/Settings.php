<?php

namespace Spatie\Ray\Settings;

class Settings
{
    /** @var array */
    protected $settings = [];

    /** @var bool */
    protected $loadedUsingSettingsFile = false;

    /** @var array */
    protected $defaultSettings = [
        'enable' => true,
        'host' => 'localhost',
        'port' => 23517,
        'remote_path' => null,
        'local_path' => null,
        'always_send_raw_values' => false,
    ];

    public function __construct(array $settings)
    {
        $this->settings = array_merge($this->defaultSettings, $settings);
    }

    public function markAsLoadedUsingSettingsFile()
    {
        $this->loadedUsingSettingsFile = true;

        return $this;
    }

    public function setDefaultSettings(array $defaults)
    {
        foreach ($defaults as $name => $value) {
            if ($this->wasLoadedUsingConfigFile($name)) {
                $this->settings[$name] = $value;
            }
        }

        return $this;
    }

    protected function wasLoadedUsingConfigFile($name)
    {
        if (! array_key_exists($name, $this->settings)) {
            return true;
        }

        if (! $this->loadedUsingSettingsFile) {
            return true;
        }

        return false;
    }

    public function __set(string $name, $value)
    {
        $this->settings[$name] = $value;
    }

    public function __get(string $name)
    {
        return $this->settings[$name] ?? null;
    }
}
