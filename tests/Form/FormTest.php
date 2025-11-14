<?php

declare(strict_types=1);

use Compose\Web\Form\DTO\Field;
use Compose\Web\Form\Form;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\TestCase;

final class FormTest extends TestCase
{
    public function testGeneratesUniqueIdAndHiddenField(): void
    {
        $form = new Form('/contact');
        $another = new Form('/contact');

        self::assertNotSame($form->getId(), $another->getId());
        self::assertSame(Form::FORM_KEY, $form->getFormIdField()['name']);
    }

    public function testProcessRequestPopulatesFieldsAndDetectsSubmission(): void
    {
        $form = (new Form('/contact'))
            ->addField(new Field('name', 'Name'))
            ->addField(new Field('email', 'Email'));

        $request = (new ServerRequest([], [], '/contact', 'POST'))
            ->withParsedBody([
                Form::FORM_KEY => $form->getFormIdField()['value'],
                'name' => 'Alice',
                'email' => 'alice@example.com',
            ]);

        self::assertTrue($form->isSubmitted($request));

        $submission = $form->processRequest($request);

        self::assertTrue($submission->isSubmitted());
        self::assertTrue($submission->isValid());
        self::assertSame('Alice', $submission->getValues()['name']);
        self::assertSame('Alice', $submission->getField('name')?->value);

        $getRequest = (new ServerRequest([], [], '/contact', 'GET'))
            ->withQueryParams([
                Form::FORM_KEY => $form->getFormIdField()['value'],
            ]);

        self::assertFalse($form->isSubmitted($getRequest));
        $getSubmission = $form->processRequest($getRequest);
        self::assertFalse($getSubmission->isSubmitted());
    }

    public function testRequiredFieldsAreEnforcedFromFieldDefinitions(): void
    {
        $form = (new Form('/contact'))
            ->addField(new Field('name', 'Name', required: true));

        $request = (new ServerRequest([], [], '/contact', 'POST'))
            ->withParsedBody([
                Form::FORM_KEY => $form->getFormIdField()['value'],
            ]);

        $submission = $form->processRequest($request);

        self::assertTrue($submission->isSubmitted());
        self::assertFalse($submission->isValid());
        self::assertSame(['Required'], $submission->getFieldErrors('name'));
    }
}
