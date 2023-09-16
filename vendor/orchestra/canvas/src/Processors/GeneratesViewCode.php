<?php

namespace Orchestra\Canvas\Processors;

use Orchestra\Canvas\Core\GeneratesCode;

class GeneratesViewCode extends GeneratesCode
{
    /**
     * Get the destination class path.
     */
    protected function getPath(string $name): string
    {
        return sprintf(
            '%s/views/%s.%s',
            $this->preset->resourcePath(),
            $this->options['name'],
            $this->options['extension'],
        );
    }
}
