<?php

namespace Orchestra\Canvas\Core\Testing;

class TestCase extends \Orchestra\Testbench\TestCase
{
    use Concerns\InteractsWithPublishedFiles;

    /**
     * Stubs files.
     *
     * @var array<int, string>|null
     */
    protected $files = [];
}
