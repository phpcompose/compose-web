<?php

declare(strict_types=1);

use Compose\Web\Validation\FilterInputFilterer;
use Compose\Web\Validation\FilterInputValidator;
use PHPUnit\Framework\TestCase;

final class FilterInputValidatorTest extends TestCase
{
    public function testFiltererTransformsValue(): void
    {
        $filterer = new FilterInputFilterer(FILTER_SANITIZE_NUMBER_INT);
        self::assertSame('123', $filterer->__invoke('abc123def'));
    }

    public function testValidatorReturnsErrorOnChange(): void
    {
        $validator = new FilterInputValidator(FILTER_VALIDATE_EMAIL);
        $error = $validator->__invoke('not-an-email');

        self::assertSame('Invalid Email', $error);
    }

    public function testValidatorAllowsCustomMessage(): void
    {
        $validator = new FilterInputValidator(FILTER_VALIDATE_INT, null, 'Numbers only');
        $error = $validator->__invoke('ten');

        self::assertSame('Numbers only', $error);
    }
}
