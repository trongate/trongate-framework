<?php

namespace Spatie\Ray\Payloads;

class ImagePayload extends Payload
{
    /** @var string */
    protected $location;

    public function __construct(string $location)
    {
        $this->location = $location;
    }

    public function getType(): string
    {
        return 'custom';
    }

    public function getContent(): array
    {
        if (file_exists($this->location)) {
            $this->location = 'file://' . $this->location;
        }

        if ($this->hasBase64Data()) {
            $this->location = $this->getLocationForBase64Data();
        }

        $location = str_replace('"', '', $this->location);

        return [
            'content' => "<img src=\"{$location}\" alt=\"\" />",
            'label' => 'Image',
        ];
    }

    protected function stripDataPrefix(string $data): string
    {
        return preg_replace('~^data:image/[a-z]+;base64,~', '', $data);
    }

    protected function hasBase64Data(): bool
    {
        $data = $this->stripDataPrefix($this->location);

        return base64_encode(base64_decode($data, true)) === $data;
    }

    protected function getLocationForBase64Data(): string
    {
        return 'data:image/png;base64,' . $this->stripDataPrefix($this->location);
    }
}
