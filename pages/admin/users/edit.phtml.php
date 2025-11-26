<?php

use Compose\Container\ContainerAwareInterface;
use Compose\Container\ContainerAwareTrait;
use Compose\Web\Auth\AuthService;
use Compose\Web\Form\Form;
use Compose\Web\Form\FormBuilder;
use Compose\Web\Validation\Filter\TrimString;
use Compose\Web\Validation\Validator\EmailAddress;
use Compose\Web\Validation\Validator\MatchField;
use Compose\Web\Validation\Validator\StringLength;
use Compose\Web\Module\User\Repository\DbalUserRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ServerRequestInterface;

return new class implements ContainerAwareInterface {
    use ContainerAwareTrait;

    public function __invoke(ServerRequestInterface $request, int $id): array|RedirectResponse
    {
        $identity = $this->getContainer()->get(AuthService::class)->currentIdentity();
        if ($identity === null) {
            return new RedirectResponse('/auth/login');
        }

        /** @var DbalUserRepository $repo */
        $repo = $this->getContainer()->get(DbalUserRepository::class);
        $user = $repo->findById($id);
        if ($user === null) {
            return new RedirectResponse('/admin/users');
        }

        /** @var FormBuilder $builder */
        $builder = $this->getContainer()->get(FormBuilder::class);

        $fields = [
            'email' => [
                'label' => 'Email',
                'type' => 'email',
                'required' => true,
                'value' => $user->getEmail(),
                'filters' => [TrimString::class => null],
                'validators' => [EmailAddress::class => null],
            ],
            'username' => [
                'label' => 'Username',
                'type' => 'text',
                'required' => false,
                'value' => $user->getUsername(),
                'filters' => [TrimString::class => null],
            ],
            'status' => [
                'label' => 'Status',
                'type' => 'select',
                'required' => true,
                'options' => [
                    '1' => 'Active',
                    '0' => 'Disabled',
                ],
                'value' => (string) $user->getStatus(),
            ],
            'profile_json' => [
                'label' => 'Profile (JSON)',
                'type' => 'textarea',
                'required' => false,
                'value' => json_encode($user->getProfile(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                'attributes' => ['rows' => 5],
            ],
            'preferences_json' => [
                'label' => 'Preferences (JSON)',
                'type' => 'textarea',
                'required' => false,
                'value' => json_encode($user->getPreferences(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                'attributes' => ['rows' => 5],
            ],
            'new_password' => [
                'label' => 'New password',
                'type' => 'password',
                'required' => false,
                'filters' => [TrimString::class => null],
                'validators' => [StringLength::class => [6, null]],
            ],
            'confirm_password' => [
                'label' => 'Confirm password',
                'type' => 'password',
                'required' => false,
                'filters' => [TrimString::class => null],
                'validators' => [
                    MatchField::class => ['new_password'],
                ],
            ],
        ];

        $form = $builder->build('/admin/users/edit/' . $id, $fields, Form::METHOD_POST);
        $submission = $form->processRequest($request);

        if ($submission->isValidSubmit()) {
            $values = $submission->getValues();
            $profile = $this->decodeJsonField($values['profile_json'] ?? '', 'Profile');
            $preferences = $this->decodeJsonField($values['preferences_json'] ?? '', 'Preferences');

            if (is_string($profile)) {
                $submission = $submission->withSubmissionError($profile);
            } elseif (is_string($preferences)) {
                $submission = $submission->withSubmissionError($preferences);
            } else {
                try {
                    $password = $values['new_password'] ?? null;
                    $password = ($password === '' || $password === null) ? null : (string) $password;

                    $repo->updateAdminUser(
                        userId: $user->getId(),
                        email: (string) $values['email'],
                        username: $values['username'] !== '' ? (string) $values['username'] : null,
                        status: (int) ($values['status'] ?? 1),
                        profile: $profile,
                        preferences: $preferences,
                        passwordHash: $password ? $this->getContainer()->get(\Compose\Web\Auth\PasswordHasherInterface::class)->hash($password) : null
                    );

                    return new RedirectResponse('/admin/users');
                } catch (UniqueConstraintViolationException) {
                    $submission = $submission->withSubmissionError('Email is already in use.');
                } catch (\Throwable $e) {
                    $submission = $submission->withSubmissionError('Unable to update user right now.');
                }
            }
        }

        return [
            'title' => 'Edit User',
            'form' => $submission,
        ];
    }

    private function decodeJsonField(string $json, string $label): array|string
    {
        if ($json === '') {
            return [];
        }
        $decoded = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            return $label . ' JSON is invalid.';
        }
        return $decoded;
    }
};
