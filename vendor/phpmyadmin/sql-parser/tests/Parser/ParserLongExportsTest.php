<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Tests\Parser;

use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statements\SetStatement;
use PhpMyAdmin\SqlParser\Statements\TransactionStatement;
use PhpMyAdmin\SqlParser\Tests\TestCase;

class ParserLongExportsTest extends TestCase
{
    public function testMysqldump(): void
    {
        $sql = <<<SQL
-- MySQL dump 10.13  Distrib 5.7.24, for Linux (x86_64)ldump e-nocleg-2-dev 01-te
--
-- Host: localhost    Database: x
-- ------------------------------------------------------
-- Server version	5.7.24

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
SQL;

        $expectedSql = [
            'SET  @OLD_CHARACTER_SET_CLIENT = @@CHARACTER_SET_CLIENT',
            'SET  @OLD_CHARACTER_SET_RESULTS = @@CHARACTER_SET_RESULTS',
            'SET  @OLD_COLLATION_CONNECTION = @@COLLATION_CONNECTION',
            'SET NAMES utf8',
            'SET  @OLD_TIME_ZONE = @@TIME_ZONE',
            "SET  TIME_ZONE = '+00:00'",
            'SET  @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 0',
            'SET  @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 0',
            "SET  @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO'",
            'SET  @OLD_SQL_NOTES = @@SQL_NOTES, SQL_NOTES = 0',
        ];

        $parser = new Parser($sql, true);
        $parser->parse();

        $sql = [];
        foreach ($parser->statements as $stmt) {
            $sql[] = $stmt->__toString();
        }

        $this->assertEquals($expectedSql, $sql);
    }

    public function testParsephpMyAdminDump(): void
    {
        $data = $this->getData('parser/parsephpMyAdminExport1');
        $parser = new Parser($data['query']);
        $collectedSetStatements = [];
        foreach ($parser->statements as $statement) {
            if ($statement instanceof TransactionStatement) {
                foreach ($statement->statements as $transactionStatement) {
                    if (! $transactionStatement instanceof SetStatement) {
                        continue;
                    }

                    $collectedSetStatements[] = $transactionStatement->build();
                }

                continue;
            }

            if (! $statement instanceof SetStatement) {
                continue;
            }

            $collectedSetStatements[] = $statement->build();
        }

        $this->assertEquals([
            'SET  SQL_MODE = "NO_AUTO_VALUE_ON_ZERO"',
            'SET  AUTOCOMMIT = 0',
            'SET  time_zone = "+00:00"',
            'SET  @OLD_CHARACTER_SET_CLIENT = @@CHARACTER_SET_CLIENT',
            'SET  @OLD_CHARACTER_SET_RESULTS = @@CHARACTER_SET_RESULTS',
            'SET  @OLD_COLLATION_CONNECTION = @@COLLATION_CONNECTION',
            'SET NAMES utf8mb4',
            'SET  CHARACTER_SET_CLIENT = @OLD_CHARACTER_SET_CLIENT',
            'SET  CHARACTER_SET_RESULTS = @OLD_CHARACTER_SET_RESULTS',
            'SET  COLLATION_CONNECTION = @OLD_COLLATION_CONNECTION',
        ], $collectedSetStatements);

        foreach ($parser->statements as $stmt) {
            // Check they all build
            $this->assertIsString($stmt->build());
        }
    }

    /**
     * @dataProvider exportFileProvider
     */
    public function testParseExport(string $test): void
    {
        $this->runParserTest($test);
    }

    /**
     * @return string[][]
     */
    public function exportFileProvider(): array
    {
        return [
            ['parser/parsephpMyAdminExport1'],
        ];
    }
}
