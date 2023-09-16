<?php

namespace Spatie\Ray\Support;

use Exception;

class PlainTextDumper
{
    private static $objects;
    private static $output;
    private static $depth;

    /**
     * Converts a variable into a string representation.
     * @param mixed $var variable to be dumped
     * @param int $depth maximum depth that the dumper should go into the variable. Defaults to 10.
     * @return string the string representation of the variable
     * @throws Exception
     */
    public static function dump($var, int $depth = 5): string
    {
        self::$output = '';
        self::$objects = [];
        self::$depth = $depth;
        self::dumpInternal($var, 0);

        return self::$output;
    }

    /**
     * @throws Exception
     */
    private static function dumpInternal($var, $level): void
    {
        switch (gettype($var)) {
            case 'boolean':
                self::$output .= $var ? 'true' : 'false';

                break;

            case 'double':
            case 'integer':
                self::$output .= "$var";

                break;

            case 'string':
                self::$output .= "'" . addslashes($var) . "'";

                break;

            case 'resource':
                self::$output .= '{resource}';

                break;
            case 'NULL':
                self::$output .= "null";

                break;

            case 'unknown type':
                self::$output .= '{unknown}';

                break;

            case 'array':
                if (self::$depth <= $level) {

                    self::$output .= '[...],';
                } elseif (empty($var)) {
                    self::$output .= '[],';
                } else {
                    $keys = array_keys($var);
                    $spaces = str_repeat(' ', $level * 4);
                    self::$output .= "[" . $spaces;
                    foreach ($keys as $key) {
                        self::$output .= "\n" . $spaces . '    ';
                        self::dumpInternal($key, 0);
                        self::$output .= ' => ';
                        self::dumpInternal($var[$key], $level + 1);
                        self::$output .= ',';
                    }
                    self::$output .= "\n" . $spaces . ']';
                }

                break;

            case 'object':
                if (($id = array_search($var, self::$objects, true)) !== false) {
                    self::$output .= get_class($var) . '#' . ($id + 1) . '(...)';
                } elseif (self::$depth <= $level) {
                    self::$output .= get_class($var) . '(...)';
                } else {
                    $id = array_push(self::$objects, $var);
                    $className = get_class($var);
                    $spaces = str_repeat(' ', $level * 4);
                    self::$output .= "$className#$id\n" . $spaces . '(';
                    if ('__PHP_Incomplete_Class' !== get_class($var) && method_exists($var, '__debugInfo')) {
                        $members = $var->__debugInfo();
                        if (! is_array($members)) {
                            throw new Exception('vardumper_not_array');
                        }
                    } else {
                        $members = (array) $var;
                    }
                    foreach ($members as $key => $value) {
                        $keyDisplay = strtr(trim($key), ["\0" => ':']);
                        self::$output .= "\n" . $spaces . "    [$keyDisplay] => ";
                        self::dumpInternal($value, $level + 1);
                    }
                    self::$output .= "\n" . $spaces . ')';
                }

                break;

        }
    }
}
