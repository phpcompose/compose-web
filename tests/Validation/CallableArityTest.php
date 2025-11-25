<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class CallableArityTest extends TestCase
{
    public function testClosureIgnoresExtraArg(): void
    {
        $closure = function (string $a): string {
            return $a;
        };

        $this->assertSame('hi', $closure('hi'));
        $this->assertSame('hi', $closure('hi', 'bye')); // extra arg ignored
    }

    public function testFunctionIgnoresExtraArg(): void
    {
        $this->assertSame('hi', $this->userFunction('hi'));
        $this->assertSame('hi', $this->userFunction('hi', 'bye'));
    }

    public function testInvokeMethodIgnoresExtraArg(): void
    {
        $invokable = new class {
            public function __invoke(string $a): string
            {
                return $a;
            }
        };

        $this->assertSame('hi', $invokable('hi'));
        $this->assertSame('hi', $invokable('hi', 'bye'));
    }

    public function testObjectMethodIgnoresExtraArg(): void
    {
        $obj = new class {
            public function someMethod(string $a): string
            {
                return $a;
            }
        };

        $callable = [$obj, 'someMethod'];
        $this->assertSame('hi', $callable('hi'));
        $this->assertSame('hi', $callable('hi', 'bye'));
    }

    private function userFunction(string $a): string
    {
        return $a;
    }
}
