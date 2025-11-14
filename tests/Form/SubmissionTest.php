<?php

declare(strict_types=1);

use Compose\Web\Form\DTO\Field;
use Compose\Web\Form\Submission;
use Compose\Web\Validation\Result;
use PHPUnit\Framework\TestCase;

final class SubmissionTest extends TestCase
{
    public function testAccessorsExposeResultData(): void
    {
        $fields = [
            new Field('name', 'Name', value: 'Alice', errors: ['Required']),
        ];
        $result = new Result(
            raw: ['name' => ' Alice '],
            values: ['name' => 'Alice'],
            errors: ['name' => ['Required']],
        );

        $submission = new Submission(
            action: '/contact',
            method: 'POST',
            formIdField: ['name' => '__FORM_ID__', 'value' => 'abc123'],
            result: $result,
            fields: $fields,
            submitted: true,
        );

        self::assertSame('/contact', $submission->getAction());
        self::assertSame('POST', $submission->getMethod());
        self::assertSame('abc123', $submission->getFormIdField()['value']);
        self::assertTrue($submission->isSubmitted());
        self::assertFalse($submission->isValid());
        self::assertSame(['name' => 'Alice'], $submission->getValues());
        self::assertSame(['name' => ' Alice '], $submission->getRaw());
        self::assertSame(['name' => ['Required']], $submission->getErrors());
        self::assertSame(['Required'], $submission->getFieldErrors('name'));
        self::assertInstanceOf(Field::class, $submission->getField('name'));
    }
}
