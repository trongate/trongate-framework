<?php declare(strict_types = 1);

namespace SlevomatCodingStandard\Sniffs\Classes;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use SlevomatCodingStandard\Helpers\DocCommentHelper;
use SlevomatCodingStandard\Helpers\FixerHelper;
use SlevomatCodingStandard\Helpers\IndentationHelper;
use SlevomatCodingStandard\Helpers\PropertyHelper;
use SlevomatCodingStandard\Helpers\TokenHelper;
use function count;
use function sprintf;
use function trim;
use const T_ARRAY;
use const T_AS;
use const T_COMMA;
use const T_FUNCTION;
use const T_OPEN_SHORT_ARRAY;
use const T_PRIVATE;
use const T_PROTECTED;
use const T_PUBLIC;
use const T_SEMICOLON;
use const T_VAR;
use const T_VARIABLE;

class DisallowMultiPropertyDefinitionSniff implements Sniff
{

	public const CODE_DISALLOWED_MULTI_PROPERTY_DEFINITION = 'DisallowedMultiPropertyDefinition';

	/**
	 * @return array<int, (int|string)>
	 */
	public function register(): array
	{
		return [T_VAR, T_PUBLIC, T_PROTECTED, T_PRIVATE];
	}

	/**
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
	 * @param int $visibilityPointer
	 */
	public function process(File $phpcsFile, $visibilityPointer): void
	{
		$tokens = $phpcsFile->getTokens();

		$asPointer = TokenHelper::findPreviousEffective($phpcsFile, $visibilityPointer - 1);
		if ($tokens[$asPointer]['code'] === T_AS) {
			return;
		}

		$propertyPointer = TokenHelper::findNext($phpcsFile, [T_VARIABLE, T_FUNCTION], $visibilityPointer + 1);
		if (
			$propertyPointer === null
			|| $tokens[$propertyPointer]['code'] !== T_VARIABLE
			|| !PropertyHelper::isProperty($phpcsFile, $propertyPointer)
		) {
			return;
		}

		$semicolonPointer = TokenHelper::findNext($phpcsFile, T_SEMICOLON, $propertyPointer + 1);
		$commaPointers = [];
		$nextPointer = $propertyPointer;
		do {
			$nextPointer = TokenHelper::findNext($phpcsFile, [T_COMMA, T_OPEN_SHORT_ARRAY, T_ARRAY], $nextPointer + 1, $semicolonPointer);

			if ($nextPointer === null) {
				break;
			}

			if ($tokens[$nextPointer]['code'] === T_OPEN_SHORT_ARRAY) {
				$nextPointer = $tokens[$nextPointer]['bracket_closer'];
				continue;
			}

			if ($tokens[$nextPointer]['code'] === T_ARRAY) {
				$nextPointer = $tokens[$nextPointer]['parenthesis_closer'];
				continue;
			}

			$commaPointers[] = $nextPointer;

		} while (true);

		if (count($commaPointers) === 0) {
			return;
		}

		$fix = $phpcsFile->addFixableError(
			'Use of multi property definition is disallowed.',
			$visibilityPointer,
			self::CODE_DISALLOWED_MULTI_PROPERTY_DEFINITION
		);
		if (!$fix) {
			return;
		}

		$visibility = $tokens[$visibilityPointer]['content'];

		$pointerAfterVisibility = TokenHelper::findNextEffective($phpcsFile, $visibilityPointer + 1);
		$pointerBeforeSemicolon = TokenHelper::findPreviousEffective($phpcsFile, $semicolonPointer - 1);

		$indentation = IndentationHelper::getIndentation($phpcsFile, $visibilityPointer);

		$nameTokenCodes = TokenHelper::getNameTokenCodes();

		$typeHint = null;
		$typeHintStartPointer = TokenHelper::findNext($phpcsFile, $nameTokenCodes, $visibilityPointer + 1, $propertyPointer);
		$typeHintEndPointer = null;
		$pointerAfterTypeHint = null;
		if ($typeHintStartPointer !== null) {
			$typeHintEndPointer = TokenHelper::findNextExcluding($phpcsFile, $nameTokenCodes, $typeHintStartPointer + 1) - 1;
			$typeHint = TokenHelper::getContent($phpcsFile, $typeHintStartPointer, $typeHintEndPointer);

			$pointerAfterTypeHint = TokenHelper::findNextEffective($phpcsFile, $typeHintEndPointer + 1);
		}

		$docCommentPointer = DocCommentHelper::findDocCommentOpenPointer($phpcsFile, $propertyPointer);
		$docComment = $docCommentPointer !== null
			? trim(TokenHelper::getContent($phpcsFile, $docCommentPointer, $tokens[$docCommentPointer]['comment_closer']))
			: null;

		$data = [];
		foreach ($commaPointers as $commaPointer) {
			$data[$commaPointer] = [
				'pointerBeforeComma' => TokenHelper::findPreviousEffective($phpcsFile, $commaPointer - 1),
				'pointerAfterComma' => TokenHelper::findNextEffective($phpcsFile, $commaPointer + 1),
			];
		}

		$phpcsFile->fixer->beginChangeset();

		$phpcsFile->fixer->addContent($visibilityPointer, ' ');

		FixerHelper::removeBetween($phpcsFile, $visibilityPointer, $pointerAfterVisibility);

		if ($typeHint !== null) {
			$phpcsFile->fixer->addContent($typeHintEndPointer, ' ');
			FixerHelper::removeBetween($phpcsFile, $typeHintEndPointer, $pointerAfterTypeHint);
		}

		foreach ($commaPointers as $commaPointer) {
			FixerHelper::removeBetween($phpcsFile, $data[$commaPointer]['pointerBeforeComma'], $commaPointer);

			$phpcsFile->fixer->replaceToken(
				$commaPointer,
				sprintf(
					';%s%s%s%s%s ',
					$phpcsFile->eolChar,
					$docComment !== null
						? sprintf('%s%s%s', $indentation, $docComment, $phpcsFile->eolChar)
						: '',
					$indentation,
					$visibility,
					$typeHint !== null ? sprintf(' %s', $typeHint) : ''
				)
			);

			FixerHelper::removeBetween($phpcsFile, $commaPointer, $data[$commaPointer]['pointerAfterComma']);
		}

		FixerHelper::removeBetween($phpcsFile, $pointerBeforeSemicolon, $semicolonPointer);

		$phpcsFile->fixer->endChangeset();
	}

}
