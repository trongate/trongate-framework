<?php declare(strict_types = 1);

namespace SlevomatCodingStandard\Helpers;

use Exception;
use function preg_last_error;
use function preg_match;

/**
 * @internal
 */
class TypeHelper
{

	/**
	 * Validates type name according to the allowed characters in type names + namespaces
	 *
	 * @link http://php.net/manual/en/language.oop5.basic.php
	 */
	public static function isTypeName(string $typeName): bool
	{
		$result = preg_match(
			'~^\\\\?([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)(\\\\[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)*$~',
			$typeName
		);
		if ($result === false) {
			// @codeCoverageIgnoreStart
			throw new Exception('PREG error ' . preg_last_error());
			// @codeCoverageIgnoreEnd
		}

		return $result !== 0;
	}

}
