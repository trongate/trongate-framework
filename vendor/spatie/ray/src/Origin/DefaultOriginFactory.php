<?php

namespace Spatie\Ray\Origin;

use Spatie\Backtrace\Backtrace;
use Spatie\Backtrace\Frame;
use Spatie\Ray\Ray;

class DefaultOriginFactory implements OriginFactory
{
    public function getOrigin(): Origin
    {
        $frame = $this->getFrame();

        return new Origin(
            $frame ? $frame->file : null,
            $frame ? $frame->lineNumber : null,
            Hostname::get()
        );
    }

    /**
     * @return \Spatie\Backtrace\Frame|null
     */
    protected function getFrame()
    {
        $frames = $this->getAllFrames();

        $indexOfRay = $this->getIndexOfRayFrame($frames);

        return $frames[$indexOfRay] ?? null;
    }

    protected function getAllFrames(): array
    {
        $frames = Backtrace::create()->frames();

        return array_reverse($frames, true);
    }

    /**
     * @param array $frames
     *
     * @return int|null
     */
    protected function getIndexOfRayFrame(array $frames)
    {
        if ($this->isUsingGlobalRay($frames)) {
            return $this->getIndexOfGlobalRayFrame($frames) + 1;
        }

        $index = $this->search(function (Frame $frame) {
            if ($frame->class === Ray::class) {
                return true;
            }

            if ($this->startsWith($frame->file, dirname(__DIR__))) {
                return true;
            }

            return false;
        }, $frames);

        return $index + 1;
    }

    public function startsWith(string $hayStack, string $needle): bool
    {
        return strpos($hayStack, $needle) === 0;
    }

    public function isUsingGlobalRay(array $frames)
    {
        return $this->getIndexOfGlobalRayFrame($frames) !== null;
    }

    /**
     * @param array<int, Frame> $frames
     *
     * @return int|null
     */
    protected function getIndexOfGlobalRayFrame(array $frames)
    {
        return $this->search(function (Frame $frame) {
            if (! $this->startsWith($frame->file, 'phar:')) {
                return false;
            }

            if (strpos($frame->file, 'global-ray/ray-phars') === false) {
                return false;
            }

            return true;
        }, $frames);
    }

    /**
     * @param callable $callable
     * @param array $items
     *
     * @return int|null
     */
    protected function search(callable $callable, array $items)
    {
        foreach ($items as $key => $item) {
            if ($callable($item, $key)) {
                return $key;
            }
        }

        return null;
    }
}
