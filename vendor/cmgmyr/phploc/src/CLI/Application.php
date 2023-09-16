<?php declare(strict_types=1);
/*
 * This file is part of PHPLOC.
 *
 * (c) Chris Gmyr <cmgmyr@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Cmgmyr\PHPLOC;

use const PHP_EOL;
use function printf;
use Cmgmyr\PHPLOC\Log\Csv as CsvPrinter;
use Cmgmyr\PHPLOC\Log\Json as JsonPrinter;
use Cmgmyr\PHPLOC\Log\Text as TextPrinter;
use Cmgmyr\PHPLOC\Log\Xml as XmlPrinter;
use SebastianBergmann\FileIterator\Facade;

final class Application
{
    private const VERSION = '8.0.3';

    public function run(array $argv): int
    {
        $this->printVersion();

        try {
            $arguments = (new ArgumentsBuilder)->build($argv);
        } catch (Exception $e) {
            print PHP_EOL . $e->getMessage() . PHP_EOL;

            return 1;
        }

        if ($arguments->version()) {
            return 0;
        }

        print PHP_EOL;

        if ($arguments->help()) {
            $this->help();

            return 0;
        }

        $files = [];

        foreach ($arguments->directories() as $directory) {
            $newFiles = (new Facade)->getFilesAsArray(
                $directory,
                $arguments->suffixes(),
                '',
                $arguments->exclude()
            );
            $files = $files + $newFiles;
        }

        if (empty($files)) {
            print 'No files found to scan' . PHP_EOL;

            return 1;
        }

        $result = (new Analyser)->countFiles($files, $arguments->countTests());

        (new TextPrinter)->printResult($result, $arguments->countTests());

        if ($arguments->csvLogfile()) {
            $printer = new CsvPrinter;

            $printer->printResult($arguments->csvLogfile(), $result);
        }

        if ($arguments->jsonLogfile()) {
            $printer = new JsonPrinter;

            $printer->printResult($arguments->jsonLogfile(), $result);
        }

        if ($arguments->xmlLogfile()) {
            $printer = new XmlPrinter;

            $printer->printResult($arguments->xmlLogfile(), $result);
        }

        return 0;
    }

    private function printVersion(): void
    {
        printf(
            'phploc %s by Chris Gmyr.' . PHP_EOL,
            self::VERSION
        );
    }

    private function help(): void
    {
        print <<<'EOT'
Usage:
  phploc [options] <directory>

Options for selecting files:

  --suffix <suffix> Include files with names ending in <suffix> in the analysis
                    (default: .php; can be given multiple times)
  --exclude <path>  Exclude files with <path> in their path from the analysis
                    (can be given multiple times)

Options for analysing files:

  --count-tests     Count PHPUnit test case classes and test methods

Options for report generation:

  --log-csv <file>  Write results in CSV format to <file>
  --log-json <file> Write results in JSON format to <file>
  --log-xml <file>  Write results in XML format to <file>

EOT;
    }
}
