<?php

declare(strict_types=1);

use Compose\Web\Validation\Result;
use PHPUnit\Framework\TestCase;

final class ResultTest extends TestCase
{
    public function testAddErrorMarksResultInvalid(): void
    {
        $result = new Result(['foo' => 'bar'], ['foo' => 'bar']);
        self::assertTrue($result->isValid());

        $result->addError('Oops', 'foo');
        self::assertFalse($result->isValid());
        self::assertSame(['foo' => ['Oops']], $result->errors);
    }
}
