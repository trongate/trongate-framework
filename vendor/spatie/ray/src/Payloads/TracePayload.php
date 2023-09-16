<?php

namespace Spatie\Ray\Payloads;

use Spatie\Backtrace\Frame;
use Spatie\Ray\Concerns\RemovesRayFrames;

class TracePayload extends Payload
{
    use RemovesRayFrames;

    /** @var array */
    protected $frames;

    /** @var int|null */
    protected $startFromIndex = null;

    /** @var int|null */
    protected $limit = null;

    public function __construct(array $frames)
    {
        $this->frames = $this->removeRayFrames($frames);
    }

    public function startFromIndex(int $index): self
    {
        $this->startFromIndex = $index;

        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    public function getType(): string
    {
        return 'trace';
    }

    public function getContent(): array
    {
        $frames = array_map(function (Frame $frame) {
            return [
                'file_name' => $this->replaceRemotePathWithLocalPath($frame->file),
                'line_number' => $frame->lineNumber,
                'class' => $frame->class,
                'method' => $frame->method,
                'vendor_frame' => ! $frame->applicationFrame,
            ];
        }, $this->frames);

        if (! is_null($this->limit)) {
            $frames = array_slice($frames, $this->startFromIndex ?? 0, $this->limit);
        }

        return compact('frames');
    }
}
