<?php

declare(strict_types=1);

use Compose\Web\Form\Form;
use PHPUnit\Framework\TestCase;

final class FormTest extends TestCase
{
    public function testGeneratesUniqueIdAndHiddenField(): void
    {
        $form = new Form('/contact');
        $another = new Form('/contact');

        self::assertNotSame($form->getId(), $another->getId());
        self::assertSame(Form::FORM_KEY, $form->hiddenField()['name']);
    }

    public function testDetectsSubmissionByPayload(): void
    {
        $form = new Form('/contact', Form::METHOD_POST);
        $payload = [
            Form::FORM_KEY => $form->getId(),
            'name' => 'Alice',
        ];

        self::assertTrue($form->isSubmitted($payload));
        self::assertTrue($form->isSubmitted($payload, 'name'));
        self::assertFalse($form->isSubmitted($payload, 'missing'));
        self::assertSame($payload, $form->getValues());
    }
}
