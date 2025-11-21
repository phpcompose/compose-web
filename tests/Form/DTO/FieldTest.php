<?php

declare(strict_types=1);

use Compose\Web\Form\DTO\Field;
use PHPUnit\Framework\TestCase;

final class FieldTest extends TestCase
{
    public function testCreatesFromArray(): void
    {
        $field = Field::fromArray([
            'name' => 'email',
            'label' => 'Email',
            'value' => 'foo@example.com',
            'required' => true,
            'errors' => ['Invalid'],
            'attributes' => ['placeholder' => 'you@example.com'],
        ]);

        self::assertSame('email', $field->name);
        self::assertTrue($field->required);
        self::assertSame(['Invalid'], $field->errors);
        self::assertSame('you@example.com', $field->attributes['placeholder']);
    }

    public function testToArrayAndWithProduceExpectedShape(): void
    {
        $field = new Field('name', 'Label', value: 'foo');
        $tweaked = $field->with(['value' => 'bar']);

        self::assertSame('bar', $tweaked->value);
        self::assertSame(
            [
                'name' => 'name',
                'label' => 'Label',
                'type' => 'text',
                'value' => 'bar',
                'required' => false,
                'errors' => [],
                'help' => null,
                'options' => [],
                'attributes' => [],
            ],
            $tweaked->toArray()
        );
    }

    public function testWithAllowsExplicitNullValues(): void
    {
        $field = new Field('name', 'Label', value: 'foo');
        $tweaked = $field->with(['value' => null]);

        self::assertNull($tweaked->value);
    }

    public function testCreateManyBuildsFieldsFromDefinitions(): void
    {
        $definitions = [
            ['name' => 'name', 'label' => 'Name'],
            ['name' => 'email', 'label' => 'Email', 'type' => 'email'],
        ];

        $fields = Field::createMany($definitions);

        self::assertCount(2, $fields);
        self::assertSame('name', $fields[0]->name);
        self::assertSame('email', $fields[1]->name);
        self::assertSame('email', $fields[1]->type);
    }
}
