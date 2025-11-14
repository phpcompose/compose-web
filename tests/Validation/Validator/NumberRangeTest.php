<?php

declare(strict_types=1);

use Compose\Web\Validation\Validator\NumberRange;
use PHPUnit\Framework\TestCase;

final class NumberRangeTest extends TestCase
{
    public function testPassesWithinRange(): void
    {
        $validator = new NumberRange(min: 1, max: 10);
        self::assertNull($validator(5));
    }

    public function testFailsBelowMin(): void
    {
        $validator = new NumberRange(min: 5);
        self::assertSame('Must be at least 5', $validator(3));
    }

    public function testFailsAboveMax(): void
    {
        $validator = new NumberRange(max: 3);
        self::assertSame('Must be at most 3', $validator(5));
    }

    public function testRejectsNonNumeric(): void
    {
        $validator = new NumberRange(min: 1);
        self::assertSame('Invalid number', $validator('abc'));
    }

    public function testAllowsCustomMessage(): void
    {
        $validator = new NumberRange(min: 1, max: 2, message: 'Out of range');
        self::assertSame('Out of range', $validator(3));
    }

    public function testThrowsWhenNoBoundsProvided(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new NumberRange();
    }
}
