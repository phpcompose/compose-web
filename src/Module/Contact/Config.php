<?php

declare(strict_types=1);

namespace Compose\Web\Module\Contact;

use Compose\Web\Validation\Validator\StringLength;

final class Config
{
    public function __invoke(): array
    {
        return [
            'modules' => [
                'contact' => [
                    'messages' => [
                        'success' => 'Thanks! We received your message.',
                        'error' => 'Please fix the highlighted fields.',
                    ],
                    'email' => [
                        'to' => 'admin@example.com',
                        'cc' => [],
                        'from' => 'no-reply@example.com',
                        'subject' => 'Website Contact',
                        'subject_map' => [
                            'sales' => 'sales@example.com',
                            'technical' => 'support@example.com',
                        ],
                    ],
                    'forms' => [
                        'default' => [
                            'title' => 'Contact',
                            'fields' => [
                                'name' => [
                                    'label' => 'Full name',
                                    'required' => true,
                                    'validators' => [StringLength::class => [10, 100]],
                                ],
                                'email' => [
                                    'label' => 'Email',
                                    'type' => 'email',
                                    'required' => true,
                                ],
                                'subject' => [
                                    'label' => 'Subject',
                                    'type' => 'select',
                                    'required' => true,
                                    'options' => [
                                        '' => 'Select a topic',
                                        'sales' => 'Sales',
                                        'technical' => 'Technical',
                                    ],
                                ],
                                'phone' => [
                                    'label' => 'Phone',
                                    'type' => 'tel',
                                    'required' => false,
                                ],
                                'message' => [
                                    'label' => 'Message',
                                    'type' => 'textarea',
                                    'required' => true,
                                    'attributes' => ['rows' => 5],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
