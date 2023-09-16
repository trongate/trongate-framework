<?php declare(strict_types = 1);

namespace SlevomatCodingStandard\Sniffs\Classes;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use SlevomatCodingStandard\Helpers\ClassHelper;
use SlevomatCodingStandard\Helpers\FixerHelper;
use SlevomatCodingStandard\Helpers\SniffSettingsHelper;
use SlevomatCodingStandard\Helpers\TokenHelper;
use function assert;
use function in_array;
use function str_repeat;
use const T_ATTRIBUTE;
use const T_COMMENT;
use const T_CONST;
use const T_DOC_COMMENT_OPEN_TAG;
use const T_ENUM_CASE;
use const T_FUNCTION;
use const T_PRIVATE;
use const T_PROTECTED;
use const T_PUBLIC;
use const T_READONLY;
use const T_SEMICOLON;
use const T_STATIC;
use const T_USE;
use const T_VAR;
use const T_VARIABLE;

/**
 * @internal
 */
abstract class AbstractPropertyConstantAndEnumCaseSpacing implements Sniff
{

	/** @var int */
	public $minLinesCountBeforeWithComment = 1;

	/** @var int */
	public $maxLinesCountBeforeWithComment = 1;

	/** @var int */
	public $minLinesCountBeforeWithoutComment = 0;

	/** @var int */
	public $maxLinesCountBeforeWithoutComment = 1;

	abstract protected function isNextMemberValid(File $phpcsFile, int $pointer): bool;

	abstract protected function addError(File $phpcsFile, int $pointer, int $min, int $max, int $found): bool;

	/**
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
	 * @param int $pointer
	 */
	public function process(File $phpcsFile, $pointer): int
	{
		$this->minLinesCountBeforeWithComment = SniffSettingsHelper::normalizeInteger($this->minLinesCountBeforeWithComment);
		$this->maxLinesCountBeforeWithComment = SniffSettingsHelper::normalizeInteger($this->maxLinesCountBeforeWithComment);
		$this->minLinesCountBeforeWithoutComment = SniffSettingsHelper::normalizeInteger($this->minLinesCountBeforeWithoutComment);
		$this->maxLinesCountBeforeWithoutComment = SniffSettingsHelper::normalizeInteger($this->maxLinesCountBeforeWithoutComment);

		$tokens = $phpcsFile->getTokens();

		$classPointer = ClassHelper::getClassPointer($phpcsFile, $pointer);

		$semicolonPointer = TokenHelper::findNext($phpcsFile, [T_SEMICOLON], $pointer + 1);
		assert($semicolonPointer !== null);

		$firstOnLinePointer = TokenHelper::findFirstTokenOnNextLine($phpcsFile, $semicolonPointer);
		assert($firstOnLinePointer !== null);

		$nextFunctionPointer = TokenHelper::findNext(
			$phpcsFile,
			[T_FUNCTION, T_ENUM_CASE, T_CONST, T_VARIABLE, T_USE],
			$firstOnLinePointer + 1
		);
		if (
			$nextFunctionPointer === null
			|| $tokens[$nextFunctionPointer]['code'] === T_FUNCTION
			|| $tokens[$nextFunctionPointer]['conditions'] !== $tokens[$pointer]['conditions']
		) {
			return $nextFunctionPointer ?? $firstOnLinePointer;
		}

		$types = [T_COMMENT, T_DOC_COMMENT_OPEN_TAG, T_ATTRIBUTE, T_ENUM_CASE, T_CONST, T_VAR, T_PUBLIC, T_PROTECTED, T_PRIVATE, T_READONLY, T_STATIC, T_USE];
		$nextPointer = TokenHelper::findNext($phpcsFile, $types, $firstOnLinePointer + 1, $tokens[$classPointer]['scope_closer']);

		if (!$this->isNextMemberValid($phpcsFile, $nextPointer)) {
			return $nextPointer;
		}

		$linesBetween = $tokens[$nextPointer]['line'] - $tokens[$semicolonPointer]['line'] - 1;
		if (in_array($tokens[$nextPointer]['code'], [T_DOC_COMMENT_OPEN_TAG, T_COMMENT, T_ATTRIBUTE], true)) {
			$minExpectedLines = $this->minLinesCountBeforeWithComment;
			$maxExpectedLines = $this->maxLinesCountBeforeWithComment;
		} else {
			$minExpectedLines = $this->minLinesCountBeforeWithoutComment;
			$maxExpectedLines = $this->maxLinesCountBeforeWithoutComment;
		}

		if ($linesBetween >= $minExpectedLines && $linesBetween <= $maxExpectedLines) {
			return $firstOnLinePointer;
		}

		$fix = $this->addError($phpcsFile, $pointer, $minExpectedLines, $maxExpectedLines, $linesBetween);
		if (!$fix) {
			return $firstOnLinePointer;
		}

		if ($linesBetween > $maxExpectedLines) {
			$lastPointerOnLine = TokenHelper::findLastTokenOnLine($phpcsFile, $semicolonPointer);
			$firstPointerOnNextLine = TokenHelper::findFirstTokenOnLine($phpcsFile, $nextPointer);

			$phpcsFile->fixer->beginChangeset();

			if ($maxExpectedLines > 0) {
				$phpcsFile->fixer->addContent($lastPointerOnLine, str_repeat($phpcsFile->eolChar, $maxExpectedLines));
			}

			FixerHelper::removeBetween($phpcsFile, $lastPointerOnLine, $firstPointerOnNextLine);

			$phpcsFile->fixer->endChangeset();
		} else {
			$phpcsFile->fixer->beginChangeset();

			for ($i = 0; $i < $minExpectedLines; $i++) {
				$phpcsFile->fixer->addNewlineBefore($firstOnLinePointer);
			}

			$phpcsFile->fixer->endChangeset();
		}

		return $firstOnLinePointer;
	}

}
