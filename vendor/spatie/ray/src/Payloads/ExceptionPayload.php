<?php

namespace Spatie\Ray\Payloads;

use Spatie\Backtrace\Backtrace;
use Spatie\Backtrace\Frame;
use Throwable;

class ExceptionPayload extends Payload
{
    /** @var \Throwable */
    protected $exception;

    /** @var array */
    protected $meta = [];

    public function __construct(Throwable $exception, array $meta = [])
    {
        $this->exception = $exception;

        $this->meta = $meta;
    }

    public function getType(): string
    {
        return 'exception';
    }

    public function getContent(): array
    {
        Backtrace::createForThrowable($this->exception);

        return [
            'class' => get_class($this->exception),
            'message' => $this->exception->getMessage(),
            'frames' => $this->getFrames(),
            'meta' => $this->meta,
        ];
    }

    protected function getFrames(): array
    {
        $frames = Backtrace::createForThrowable($this->exception)->frames();

        return array_map(function (Frame $frame) {
            return [
                'file_name' => $this->replaceRemotePathWithLocalPath($frame->file),
                'line_number' => $frame->lineNumber,
                'class' => $frame->class,
                'method' => $frame->method,
                'vendor_frame' => ! $frame->applicationFrame,
                'snippet' => $frame->getSnippetProperties(12),
            ];
        }, $frames);
    }
}
