<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Tests\Builder;

use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Tests\TestCase;

class UpdateStatementTest extends TestCase
{
    public function testBuilder(): void
    {
        /* Assertion 1 */
        $parser = new Parser(
            'update user u left join user_detail ud on u.id = ud.user_id set ud.ip =\'33\' where u.id = 1'
        );
        $stmt = $parser->statements[0];
        $this->assertEquals(
            'UPDATE user AS `u` LEFT JOIN user_detail AS `ud` ON u.id = ud.user_id SET ud.ip = \'33\' WHERE u.id = 1',
            $stmt->build()
        );
        /* Assertion 2 */
        $parser = new Parser('update user u join user_detail ud on u.id = ud.user_id set ud.ip =\'33\' where u.id = 1');
        $stmt = $parser->statements[0];
        $this->assertEquals(
            'UPDATE user AS `u` JOIN user_detail AS `ud` ON u.id = ud.user_id SET ud.ip = \'33\' WHERE u.id = 1',
            $stmt->build()
        );
        /* Assertion 3 */
        $parser = new Parser(
            'update user u inner join user_detail ud on u.id = ud.user_id set ud.ip =\'33\' where u.id = 1'
        );
        $stmt = $parser->statements[0];
        $this->assertEquals(
            'UPDATE user AS `u` INNER JOIN user_detail AS `ud` ON u.id = ud.user_id SET ud.ip = \'33\' WHERE u.id = 1',
            $stmt->build()
        );
    }
}
