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
        $processor->addFilterer(static fn ($value) => trim((string) $value), 'name');
        $processor->addFilterer(static fn ($value) => strtoupper((string) $value), 'name');
        $processor->addValidator(static fn ($value) => $value === 'ALICE' ? null : 'Invalid name', 'name');
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
        $processor->addFilterer(static fn ($value) => (int) $value, 'age');
        $processor->addValidator(static fn ($value) => $value >= 18 ? null : 'Must be an adult', 'age');

        $result = $processor->process(['age' => '16']);

        self::assertInstanceOf(Result::class, $result);
        self::assertSame(['age' => '16'], $result->raw);
        self::assertSame(['age' => 16], $result->values);
        self::assertSame(['age' => ['Must be an adult']], $result->errors);
        self::assertFalse($result->isValid());
    }

    public function testGlobalFilterersAndValidatorsApplyToAllFields(): void
    {
        $processor = new Processor();
        $processor->addFilterer(static fn ($value) => is_string($value) ? trim($value) : $value);
        $processor->addValidator(static fn ($value) => $value !== '' ? null : 'Cannot be blank');

        $result = $processor->process(['first' => '  ', 'second' => ' value ']);

        self::assertInstanceOf(Result::class, $result);
        self::assertSame(['first' => '', 'second' => 'value'], $result->values);
        self::assertSame(['first' => ['Cannot be blank']], $result->errors);
    }
}
