<?php

declare(strict_types=1);

namespace Compose\Web;

use Compose\Config as BaseConfig;
use Compose\Web\Form\Helper\FormHelper;

/**
 * Extension point for Compose configuration; currently behaves like the core config.
 */
final class Config extends BaseConfig
{
    public function __invoke(): array
    {
        $config = parent::__invoke();

        $config['services'][\Compose\Web\Security\SessionCsrfTokenProvider::class] = \Compose\Web\Security\SessionCsrfTokenProvider::class;
        $config['services'][\Compose\Web\Email\Emailer::class] = \Compose\Web\Email\EmailerFactory::class;
        $config['services'][\Compose\Web\Form\FormBuilder::class] = \Compose\Web\Form\FormBuilder::class;
        $config['templates']['layout'] = 'layout::main';
        $config['templates']['folders']['layout'] = __DIR__ . '/../templates/layout';
        $config['templates']['helpers']['form'] = FormHelper::class;
        $contact = (new \Compose\Web\Module\Contact\Config())();
        $config = array_replace_recursive($config, $contact);

        return $config;
    }
}
