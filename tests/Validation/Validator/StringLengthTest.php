<?php

declare(strict_types=1);

use Compose\Web\Validation\Validator\StringLength;
use PHPUnit\Framework\TestCase;

final class StringLengthTest extends TestCase
{
    public function testValidWhenWithinBounds(): void
    {
        $validator = new StringLength(min: 2, max: 5);
        self::assertNull($validator('four'));
    }

    public function testFailsBelowMinimum(): void
    {
        $validator = new StringLength(min: 3);
        self::assertSame('Must be at least 3 characters', $validator('hi'));
    }

    public function testFailsAboveMaximum(): void
    {
        $validator = new StringLength(max: 3);
        self::assertSame('Must be at most 3 characters', $validator('hello'));
    }

    public function testCustomMessageOverridesDefaults(): void
    {
        $validator = new StringLength(min: 5, max: 10, message: 'Length out of bounds');
        self::assertSame('Length out of bounds', $validator('tiny'));
    }

    public function testThrowsWhenNoBoundsProvided(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new StringLength();
    }
}
