<?php

use Compose\Container\ContainerAwareInterface;
use Compose\Container\ContainerAwareTrait;
use Compose\Web\Module\User\UserServiceInterface;
use Compose\Web\Form\FormBuilder;
use Compose\Web\Form\Form;
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
        $container = $this->getContainer();
        /** @var FormBuilder $builder */
        $builder = $container->get(FormBuilder::class);

        $fields = [
            'email' => [
                'label' => 'Email',
                'type' => 'email',
                'required' => true,
                'filters' => [TrimString::class => null],
                'validators' => [EmailAddress::class => null],
            ],
            'password' => [
                'label' => 'Password',
                'type' => 'password',
                'required' => true,
                'filters' => [TrimString::class => null],
                'validators' => [StringLength::class => [6, null]],
            ],
            'confirm_password' => [
                'label' => 'Confirm password',
                'type' => 'password',
                'required' => true,
                'filters' => [TrimString::class => null],
                'validators' => [
                    StringLength::class => [6, null],
                    MatchField::class => ['password'],
                ],
            ],
        ];

        $form = $builder->build('/auth/register', $fields, Form::METHOD_POST);
        $submission = $form->processRequest($request);

        if ($submission->isValidSubmit()) {
            $values = $submission->getValues();
            try {
                /** @var UserServiceInterface $users */
                $users = $container->get(UserServiceInterface::class);

                $users->register(
                    email: $values['email'],
                    username: null,
                    plainPassword: (string) $values['password']
                );

                return new RedirectResponse('/auth/login');
            } catch (UniqueConstraintViolationException) {
                $submission = $submission->withSubmissionError('An account with that email already exists.');
            } catch (\Throwable $e) {
                $submission = $submission->withSubmissionError('Unable to register right now. Please try again.');
            }
        }

        return [
            'title' => 'Register',
            'form' => $submission,
        ];
    }
};
