<?php

declare(strict_types=1);

use Compose\Web\Email\Emailer;
use Compose\Web\Email\Message;
use Compose\Web\Module\Contact\Service\ContactService;
use Compose\Web\Form\Submission;
use Compose\Web\Form\DTO\Field;
use Compose\Web\Validation\Result;
use Compose\Support\Configuration;
use PHPUnit\Framework\TestCase;

final class ContactServiceTest extends TestCase
{
    public function testHandleSubmissionSendsEmailAndSkipsInternalFields(): void
    {
        $captor = new class {
            public ?Message $message = null;
            public array $options = [];
            public function __invoke(Message $message, array $options): bool
            {
                $this->message = $message;
                $this->options = $options;
                return true;
            }
        };

        $emailer = new Emailer($captor);

        $config = new Configuration([
            'modules' => [
                'contact' => [
                    'email' => [
                        'to' => 'default@example.com',
                        'from' => 'no-reply@example.com',
                        'subject' => 'Contact submission',
                        'subject_map' => ['sales' => 'sales@example.com'],
                    ],
                ],
            ],
        ]);

        $service = new ContactService($emailer, $config);

        $values = [
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'subject' => 'sales',
            'message' => 'Hello',
            '__FORM_ID__' => 'ignored',
            '__CSRF_TOKEN__' => 'ignored',
        ];

        $fields = [
            new Field('name', 'Name', value: 'Alice'),
            new Field('email', 'Email', value: 'alice@example.com'),
            new Field('subject', 'Subject', value: 'sales'),
            new Field('message', 'Message', value: 'Hello'),
        ];

        $submission = new Submission(
            action: '/contact',
            method: 'POST',
            formIdField: ['name' => '__FORM_ID__', 'value' => 'abc'],
            csrfField: null,
            result: new Result($values, $values, []),
            fields: $fields,
            submitted: true
        );

        $service->handleSubmission($submission);

        self::assertInstanceOf(Message::class, $captor->message);
        $sent = $captor->message;

        self::assertSame('Contact submission: sales', $sent->subject);
        self::assertSame('Alice <alice@example.com>', $sent->getFromAddress());
        self::assertArrayHasKey('sales@example.com', $sent->tos);

        $body = $sent->body;
        self::assertStringContainsString('name: Alice', $body);
        self::assertStringNotContainsString('__FORM_ID__', $body);
        self::assertStringNotContainsString('__CSRF_TOKEN__', $body);
    }
}
