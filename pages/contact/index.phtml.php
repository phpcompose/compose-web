<?php

use Compose\Container\ContainerAwareInterface;
use Compose\Container\ContainerAwareTrait;
use Compose\Support\Configuration;
use Compose\Web\Form\Form;
use Compose\Web\Form\FormBuilder;
use Compose\Web\Module\Contact\Service\ContactService;
use Psr\Http\Message\ServerRequestInterface;

return new class implements ContainerAwareInterface {
    use ContainerAwareTrait;

    public function __invoke(ServerRequestInterface $request): array {
        $config = $this->getContainer()->get(Configuration::class);
        $fields = $config['modules']['contact']['fields'] ?? [];

        /** @var FormBuilder $builder */
        $builder = $this->getContainer()->get(FormBuilder::class);
        $form = $builder->build('/contact', $fields, Form::METHOD_POST);
        $submission = $form->processRequest($request);

        if ($submission->isValidSubmit()) {
            /** @var ContactService $service */
            $service = $this->getContainer()->get(ContactService::class);
            $service->handleSubmission($submission);
        }

        return [
            'title' => 'Contact',
            'form' => $submission,
        ];
    }
};
