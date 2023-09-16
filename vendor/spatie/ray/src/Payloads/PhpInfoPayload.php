<?php

namespace Spatie\Ray\Payloads;

class PhpInfoPayload extends Payload
{
    /** @var array */
    protected $properties = [];

    public function __construct(string ...$properties)
    {
        $this->properties = $properties;
    }

    public function getType(): string
    {
        return 'table';
    }

    public function getContent(): array
    {
        $values = array_flip($this->properties);

        foreach ($values as $property => $value) {
            $values[$property] = ini_get($property);
        }

        if (empty($values)) {
            $values = [
                'PHP version' => phpversion(),
                'Memory limit' => ini_get('memory_limit'),
                'Max file upload size' => ini_get('max_file_uploads'),
                'Max post size' => ini_get('post_max_size'),
                'PHP ini file' => php_ini_loaded_file(),
                "PHP scanned ini file" => php_ini_scanned_files(),
                'Extensions' => implode(', ', get_loaded_extensions()),
            ];
        }

        return [
            'values' => $values,
            'label' => 'PHPInfo',
        ];
    }
}
