<?php

declare(strict_types=1);

namespace Compose\Web;

use Compose\Config as BaseConfig;

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

        return $config;
    }
}
