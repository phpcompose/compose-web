<?php

declare(strict_types=1);

namespace Compose\Web\Module\Contact\Page;

use Compose\Container\ContainerAwareInterface;
use Compose\Container\ContainerAwareTrait;
use Compose\Support\Configuration;
use Compose\Web\Form\Form;
use Compose\Web\Form\FormBuilder;
use Compose\Web\Module\Contact\Service\ContactService;
use Laminas\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ContactPage implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    public function __invoke(ServerRequestInterface $request, ?string $slug = null): mixed
    {
        $config = $this->getContainer()->get(Configuration::class);
        $contactConfig = $config['modules']['contact'] ?? [];
        $slug = $slug ?: 'default';

        $formConfig = $contactConfig['forms'][$slug] ?? null;
        if ($formConfig === null) {
            return $request; //
        }

        $messages = array_replace(
            $contactConfig['messages'] ?? [],
            $formConfig['messages'] ?? []
        );
        $emailSettings = array_replace(
            $contactConfig['email'] ?? [],
            $formConfig['email'] ?? []
        );

        /** @var FormBuilder $builder */
        $builder = $this->getContainer()->get(FormBuilder::class);
        $fields = $formConfig['fields'] ?? [];
        $action = $request->getUri()->getPath();
        $form = $builder->build($action, $fields, Form::METHOD_POST);
        $submission = $form->processRequest($request);

        if ($submission->isValidSubmit()) {
            /** @var ContactService $service */
            $service = $this->getContainer()->get(ContactService::class);
            $service->handleSubmission($submission, $emailSettings, $slug);
        }

        return [
            'title' => $formConfig['title'] ?? 'Contact',
            'form' => $submission,
            'messages' => $messages,
            'formConfig' => $formConfig,
            'slug' => $slug,
        ];
    }
}
