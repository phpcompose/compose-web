<?php

use Compose\Container\ContainerAwareInterface;
use Compose\Container\ContainerAwareTrait;
use Compose\Web\Auth\AuthService;
use Compose\Web\Form\Form;
use Compose\Web\Form\FormBuilder;
use Compose\Web\Module\User\UserServiceInterface;
use Compose\Web\Validation\Filter\TrimString;
use Compose\Web\Validation\Validator\EmailAddress;
use Compose\Web\Validation\Validator\MatchField;
use Compose\Web\Validation\Validator\StringLength;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ServerRequestInterface;

return new class implements ContainerAwareInterface {
    use ContainerAwareTrait;

    public function __invoke(ServerRequestInterface $request): array|RedirectResponse
    {
        /** @var AuthService $auth */
        $auth = $this->getContainer()->get(AuthService::class);
        $identity = $auth->currentIdentity();
        if ($identity === null) {
            return new RedirectResponse('/auth/login');
        }

        $container = $this->getContainer();
        /** @var FormBuilder $builder */
        $builder = $container->get(FormBuilder::class);

        $fields = [
            'email' => [
                'label' => 'Email',
                'type' => 'email',
                'required' => true,
                'value' => $identity->getEmail(),
                'filters' => [TrimString::class => null],
                'validators' => [EmailAddress::class => null],
            ],
            'current_password' => [
                'label' => 'Current password',
                'type' => 'password',
                'required' => true,
                'filters' => [TrimString::class => null],
            ],
            'new_password' => [
                'label' => 'New password',
                'type' => 'password',
                'required' => true,
                'filters' => [TrimString::class => null],
                'validators' => [StringLength::class => [6, null]],
            ],
            'confirm_password' => [
                'label' => 'Confirm new password',
                'type' => 'password',
                'required' => true,
                'filters' => [TrimString::class => null],
                'validators' => [
                    StringLength::class => [6, null],
                    MatchField::class => ['new_password'],
                ],
            ],
        ];

        $form = $builder->build('/user/settings', $fields, Form::METHOD_POST);
        $submission = $form->processRequest($request);

        if ($submission->isValidSubmit()) {
            $values = $submission->getValues();

            try {
                /** @var UserServiceInterface $users */
                $users = $container->get(UserServiceInterface::class);
                $user = $users->getById($identity->getId());
                if ($user === null) {
                    return new RedirectResponse('/auth/login');
                }

                // verify current password
                $hasher = $this->getContainer()->get(\Compose\Web\Auth\PasswordHasherInterface::class);
                if (!$hasher->verify((string) ($values['current_password'] ?? ''), $user->getPasswordHash())) {
                    $submission = $submission->withSubmissionError('Current password is incorrect.');
                } else {
                    // update email/password
                    $users->updateEmail($user->getId(), $values['email']);
                    $users->updatePassword($user->getId(), (string) $values['new_password']);
                    return new RedirectResponse('/user/');
                }
            } catch (UniqueConstraintViolationException) {
                $submission = $submission->withSubmissionError('That email is already in use.');
            } catch (\Throwable $e) {
                $submission = $submission->withSubmissionError('Unable to update account right now. Please try again.');
            }
        }

        return [
            'title' => 'Account settings',
            'form' => $submission,
        ];
    }
};
