<?php

namespace Spatie\Ray\Payloads;

use Spatie\Backtrace\Frame;
use Spatie\Ray\Concerns\RemovesRayFrames;

class CallerPayload extends Payload
{
    use RemovesRayFrames;

    /** @var array */
    protected $frames;

    public function __construct(array $frames)
    {
        $this->frames = $this->removeRayFrames($frames);
    }

    public function getType(): string
    {
        return 'caller';
    }

    public function getContent(): array
    {
        $frames = array_slice($this->frames, 1, 1);

        /** @var Frame $frame */
        $frame = array_values($frames)[0];

        return [
            'frame' => [
                'file_name' => $this->replaceRemotePathWithLocalPath($frame->file),
                'line_number' => $frame->lineNumber,
                'class' => $frame->class,
                'method' => $frame->method,
                'vendor_frame' => ! $frame->applicationFrame,
            ],
        ];
    }
}
