<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Contexts;

use PhpMyAdmin\SqlParser\Context;
use PhpMyAdmin\SqlParser\Token;

/**
 * Context for MYSQL TEST.
 *
 * This class was auto-generated from tools/contexts/*.txt.
 * Use tools/run_generators.sh for update.
 *
 * @see https://www.phpmyadmin.net/contribute
 */
class TestContext extends Context
{
    /**
     * List of keywords.
     *
     * The value associated to each keyword represents its flags.
     *
     * @see Token::FLAG_KEYWORD_RESERVED Token::FLAG_KEYWORD_COMPOSED
     *      Token::FLAG_KEYWORD_DATA_TYPE Token::FLAG_KEYWORD_KEY
     *      Token::FLAG_KEYWORD_FUNCTION
     *
     * @var array<string,int>
     * @phpstan-var non-empty-array<non-empty-string,Token::FLAG_KEYWORD_*|int>
     */
    public static $KEYWORDS = [
        'NO_FLAG' => 1,

        'RESERVED' => 3,
        'RESERVED2' => 3, 'RESERVED3' => 3, 'RESERVED4' => 3, 'RESERVED5' => 3,

        'COMPOSED KEYWORD' => 7,

        'DATATYPE' => 9,

        'KEYWORD' => 17,

        'FUNCTION' => 33,
    ];
}
