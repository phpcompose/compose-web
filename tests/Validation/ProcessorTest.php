<?php

declare(strict_types=1);

use Compose\Web\Validation\Processor;
use Compose\Web\Validation\Result;
use PHPUnit\Framework\TestCase;

final class ProcessorTest extends TestCase
{
    public function testFiltersAndValidatesInput(): void
    {
        $processor = new Processor();
        $processor->addFilterer('name', static fn ($value) => trim((string) $value));
        $processor->addFilterer('name', static fn ($value) => strtoupper((string) $value));
        $processor->addValidator('name', static fn ($value) => $value === 'ALICE' ? null : 'Invalid name');
        $processor->setRequiredValues(['name']);

        $result = $processor->process(['name' => '  alice ']);

        self::assertTrue($result->isValid());
        self::assertSame('ALICE', $result->values['name']);
    }

    public function testRequiredErrorsBubbleUp(): void
    {
        $processor = new Processor();
        $processor->setRequiredValues(['email']);

        $values = ['email' => ''];
        $errors = $processor->validate($values);

        self::assertSame(['email' => ['Required']], $errors);
    }

    public function testProcessResultKeepsRawValues(): void
    {
        $processor = new Processor();
        $processor->addFilterer('age', static fn ($value) => (int) $value);
        $processor->addValidator('age', static fn ($value) => $value >= 18 ? null : 'Must be an adult');

        $result = $processor->process(['age' => '16']);

        self::assertInstanceOf(Result::class, $result);
        self::assertSame(['age' => '16'], $result->raw);
        self::assertSame(['age' => 16], $result->values);
        self::assertSame(['age' => ['Must be an adult']], $result->errors);
        self::assertFalse($result->isValid());
    }
}
