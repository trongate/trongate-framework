<?php

namespace Orchestra\Canvas\Core;

class GeneratesCodeWithMarkdown extends GeneratesCode
{
    /**
     * Handle generating code.
     */
    protected function generatingCode(string $stub, string $name): string
    {
        $stub = parent::generatingCode($stub, $name);

        if (! empty($this->options['view'])) {
            $stub = str_replace(['DummyView', '{{ view }}', '{{view}}'], $this->options['view'], $stub);
        }

        return $stub;
    }
}
