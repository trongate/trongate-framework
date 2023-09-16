<?php declare(strict_types = 1);

namespace SlevomatCodingStandard\Sniffs\Functions;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;
use SlevomatCodingStandard\Helpers\FunctionHelper;
use SlevomatCodingStandard\Helpers\TokenHelper;
use const T_FUNCTION;
use const T_WHITESPACE;

class DisallowEmptyFunctionSniff implements Sniff
{

	public const CODE_EMPTY_FUNCTION = 'EmptyFunction';

	/**
	 * @return array<int, (int|string)>
	 */
	public function register(): array
	{
		return [T_FUNCTION];
	}

	/**
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
	 * @param int $functionPointer
	 */
	public function process(File $phpcsFile, $functionPointer): void
	{
		$tokens = $phpcsFile->getTokens();

		if (FunctionHelper::isAbstract($phpcsFile, $functionPointer)) {
			return;
		}

		if (FunctionHelper::getName($phpcsFile, $functionPointer) === '__construct') {
			$propertyPromotion = TokenHelper::findNext(
				$phpcsFile,
				Tokens::$scopeModifiers,
				$tokens[$functionPointer]['parenthesis_opener'] + 1,
				$tokens[$functionPointer]['parenthesis_closer']
			);

			if ($propertyPromotion !== null) {
				return;
			}
		}

		$firstContent = TokenHelper::findNextExcluding(
			$phpcsFile,
			T_WHITESPACE,
			$tokens[$functionPointer]['scope_opener'] + 1,
			$tokens[$functionPointer]['scope_closer']
		);

		if ($firstContent !== null) {
			return;
		}

		$phpcsFile->addError(
			'Empty function body must have at least a comment to explain why is empty.',
			$functionPointer,
			self::CODE_EMPTY_FUNCTION
		);
	}

}
