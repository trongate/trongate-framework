<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Tests\Parser;

use PhpMyAdmin\SqlParser\Tests\TestCase;

class AnalyzeStatementTest extends TestCase
{
    /**
     * @dataProvider analyzeProvider
     */
    public function testAnalyze(string $test): void
    {
        $this->runParserTest($test);
    }

    /**
     * @return string[][]
     */
    public function analyzeProvider(): array
    {
        return [
            ['parser/parseAnalyzeTable'],
            ['parser/parseAnalyzeTable1'],
            ['parser/parseAnalyzeErr1'],
            ['parser/parseAnalyzeErr2'],
        ];
    }
}
