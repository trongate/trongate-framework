<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Tools;

use ReflectionClass;
use ReflectionException;
use Zumba\JsonSerializer\JsonSerializer;

use function in_array;

/**
 * Used for .out files generation
 */
class CustomJsonSerializer extends JsonSerializer
{
    public const SKIP_PROPERTIES = [
        'ALLOWED_KEYWORDS',
        'GROUP_OPTIONS',
        'END_OPTIONS',
        'KEYWORD_PARSERS',
        'STATEMENT_PARSERS',
        'KEYWORD_NAME_INDICATORS',
        'OPERATOR_NAME_INDICATORS',
        'DEFAULT_DELIMITER',
        'PARSER_METHODS',
        'OPTIONS',
        'CLAUSES',
        'DB_OPTIONS',
        'DELIMITERS',
        'JOINS',
        'FIELDS_OPTIONS',
        'LINES_OPTIONS',
        'TRIGGER_OPTIONS',
        'FUNC_OPTIONS',
        'TABLE_OPTIONS',
        'FIELD_OPTIONS',
        'DATA_TYPE_OPTIONS',
        'REFERENCES_OPTIONS',
        'KEY_OPTIONS',
        'VIEW_OPTIONS',
        'EVENT_OPTIONS',
        'USER_OPTIONS',
        'asciiMap',
    ];

    /**
     * Extract the object data
     *
     * @param  object          $value
     * @param  ReflectionClass $ref
     * @param  string[]        $properties
     *
     * @return array<string,mixed>
     */
    protected function extractObjectData($value, $ref, $properties)
    {
        $data = [];
        foreach ($properties as $property) {
            if (in_array($property, self::SKIP_PROPERTIES, true)) {
                continue;
            }

            try {
                $propRef = $ref->getProperty($property);
                $propRef->setAccessible(true);
                $data[$property] = $propRef->getValue($value);
            } catch (ReflectionException $e) {
                $data[$property] = $value->$property;
            }
        }

        return $data;
    }
}
