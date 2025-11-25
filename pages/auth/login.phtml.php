<?php

use Compose\Container\ContainerAwareInterface;
use Compose\Container\ContainerAwareTrait;
use Compose\Web\Auth\AuthService;
use Compose\Web\Auth\Credential;
use Compose\Web\Auth\Exception\InvalidCredentialsException;
use Compose\Web\Form\FormBuilder;
use Compose\Web\Form\Form;
use Compose\Web\Validation\Filter\TrimString;
use Compose\Web\Validation\Validator\EmailAddress;
use Compose\Web\Validation\Validator\StringLength;
use Compose\Web\Form\Submission;
use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

return new class implements ContainerAwareInterface {
    use ContainerAwareTrait;

    public function __invoke(ServerRequestInterface $request): array|ResponseInterface
    {
        $container = $this->getContainer();
        /** @var FormBuilder $builder */
        $builder = $container->get(FormBuilder::class);

        $fields = [
            'email' => [
                'label' => 'Email',
                'type' => 'email',
                'required' => true,
                'filters' => [
                    TrimString::class => null,
                ],
                'validators' => [
                    EmailAddress::class => null,
                ],
            ],
            'password' => [
                'label' => 'Password',
                'type' => 'password',
                'required' => true,
                'filters' => [
                    TrimString::class => null,
                ],
                'validators' => [
                    StringLength::class => [6, null],
                ],
            ],
        ];

        $form = $builder->build('/auth/login', $fields, Form::METHOD_POST);
        $submission = $form->processRequest($request);

        if ($submission->isValidSubmit()) {
            try {
                list($email, $password) = array_values($submission->getValues(['email', 'password']));
                $cred = new Credential('password', $submission->getValues()['email'], $submission->getValues()['password']);

                /** @var AuthService $auth */
                $auth = $container->get(AuthService::class);
                $auth->authenticate($cred);
                return new RedirectResponse('/user/dashboard');
            } catch (InvalidCredentialsException $e) {
                $submission = $submission->withSubmissionError('Invalid email or password.');
            }
        }

        return [
            'title' => 'Login',
            'form' => $submission,
        ];
    }

};
