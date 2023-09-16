<?php

namespace Laravel\Prompts\Concerns;

use Laravel\Prompts\Themes\Contracts\Scrolling;

trait ReducesScrollingToFitTerminal
{
    /**
     * Reduce the scroll property to fit the terminal height.
     */
    protected function reduceScrollingToFitTerminal(): void
    {
        $reservedLines = ($renderer = $this->getRenderer()) instanceof Scrolling ? $renderer->reservedLines() : 0;

        $this->scroll = min($this->scroll, $this->terminal()->lines() - $reservedLines);
    }
}
