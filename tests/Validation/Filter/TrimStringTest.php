<?php

declare(strict_types=1);

use Compose\Web\Validation\Filter\TrimString;
use PHPUnit\Framework\TestCase;

final class TrimStringTest extends TestCase
{
    public function testTrimsWhitespace(): void
    {
        $filter = new TrimString();
        self::assertSame('value', $filter('  value  '));
    }

    public function testLeavesNullUntouched(): void
    {
        $filter = new TrimString();
        self::assertNull($filter(null));
    }

    public function testTrimsStringableObject(): void
    {
        $filter = new TrimString();
        $stringable = new class {
            public function __toString(): string
            {
                return '  foo  ';
            }
        };

        self::assertSame('foo', $filter($stringable));
    }
}
