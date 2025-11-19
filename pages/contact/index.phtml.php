<?php

use Compose\Container\ContainerAwareInterface;
use Compose\Container\ContainerAwareTrait;
use Compose\Support\Configuration;
use Compose\Web\Form\FormBuilder;
use Psr\Http\Message\ServerRequestInterface;

return new class implements ContainerAwareInterface {
    use ContainerAwareTrait;

    public function __invoke(ServerRequestInterface $request): array {
        $config = $this->getContainer()->get(Configuration::class);
        $fields = $config['modules']['contact']['fields'] ?? [];

        /** @var FormBuilder $builder */
        $builder = $this->getContainer()->get(FormBuilder::class);
        $form = $builder->build('/contact', $fields, $request->getMethod());
        $submission = $form->processRequest($request);

        if($submission->isValidSubmit()) {
        }

        return [
            'title' => 'Contact',
            'form' => $submission,
        ];
    }
};
