<?php

declare(strict_types=1);

use Compose\Web\Form\FormBuilder;
use Compose\Web\Form\Form;
use Compose\Web\Form\DTO\Field;
use Compose\Web\Security\CsrfTokenProviderInterface;
use Compose\Web\Validation\Result;
use Compose\Web\Validation\Validator\StringLength;
use Compose\Web\Validation\Filter\TrimString;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\TestCase;

final class FormBuilderIntegrationTest extends TestCase
{
    public function testBuildAddsFieldsProcessorAndCsrf(): void
    {
        $csrf = new class implements CsrfTokenProviderInterface {
            public function generateToken(string $formId): string { return 'token-' . $formId; }
            public function validateToken(string $formId, ?string $token): bool { return $token === 'token-' . $formId; }
            public function getFieldName(): string { return '__CSRF__'; }
        };

        $builder = new FormBuilder($csrf);

        $fields = [
            'name' => [
                'label' => 'Name',
                'required' => true,
                'filters' => [TrimString::class => null],
                'validators' => [StringLength::class => [2, 10]],
            ],
        ];

        $form = $builder->build('/contact', $fields, Form::METHOD_POST);

        self::assertInstanceOf(Form::class, $form);
        self::assertCount(1, $form->getFields());

        $payload = [
            Form::FORM_KEY => $form->getFormIdField()['value'],
            '__CSRF__' => 'token-' . $form->getId(),
            'name' => '  Alice  ',
        ];

        $request = (new ServerRequest([], [], '/contact', 'POST'))->withParsedBody($payload);
        $submission = $form->processRequest($request);

        self::assertTrue($submission->isSubmitted());
        self::assertTrue($submission->isValidSubmit());
        self::assertSame('Alice', $submission->getValues()['name']);

        $csrfField = $submission->getCsrfField();
        self::assertNotNull($csrfField);
        self::assertSame('__CSRF__', $csrfField['name']);
        self::assertSame('token-' . $form->getId(), $csrfField['value']);
    }

    public function testRequiredFieldIsEnforcedByGeneratedProcessor(): void
    {
        $builder = new FormBuilder(null);
        $form = $builder->build('/contact', [
            'name' => ['label' => 'Name', 'required' => true],
        ]);

        $payload = [
            Form::FORM_KEY => $form->getFormIdField()['value'],
        ];

        $request = (new ServerRequest([], [], '/contact', 'POST'))->withParsedBody($payload);
        $submission = $form->processRequest($request);

        self::assertTrue($submission->isSubmitted());
        self::assertFalse($submission->isValid());
        self::assertSame(['Required'], $submission->getFieldErrors('name'));
    }
}
