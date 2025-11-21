<?php

declare(strict_types=1);

namespace Compose\Web;

use Compose\Config as BaseConfig;
use Compose\Web\Form\Helper\FormHelper;
use Compose\Web\Support\Env;

/**
 * Extension point for Compose configuration; currently behaves like the core config.
 */
final class Config extends BaseConfig
{
    public function __invoke(): array
    {
        $config = parent::__invoke();

        $pluginChoice = Env::get('EMAIL_PLUGIN');
        $defaultPlugin = \Compose\Web\Email\Plugin\PhpMailerPlugin::class;
        if ($pluginChoice !== null && $pluginChoice !== '') {
            if (!str_contains($pluginChoice, '\\')) {
                $normalized = strtolower($pluginChoice);
                if ($normalized === 'log') {
                    $defaultPlugin = \Compose\Web\Email\Plugin\LogPlugin::class;
                } elseif (in_array($normalized, ['phpmailer', 'smtp'], true)) {
                    $defaultPlugin = \Compose\Web\Email\Plugin\PhpMailerPlugin::class;
                } else {
                    $defaultPlugin = $pluginChoice;
                }
            } else {
                $defaultPlugin = $pluginChoice;
            }
        }

        $defaults = [
            'services' => [
                \Compose\Web\Security\SessionCsrfTokenProvider::class => \Compose\Web\Security\SessionCsrfTokenProvider::class,
                \Compose\Web\Email\Emailer::class => \Compose\Web\Email\EmailerFactory::class,
                \Compose\Web\Email\Plugin\LogPlugin::class => \Compose\Web\Email\Plugin\LogPlugin::class,
                \Compose\Web\Email\Plugin\PhpMailerPlugin::class => \Compose\Web\Email\Plugin\PhpMailerPlugin::class,
            ],
            'templates' => [
                'layout' => 'layout::main',
                'folders' => [
                    'layout' => __DIR__ . '/../templates/layout',
                ],
                'helpers' => [
                    'form' => FormHelper::class,
                ],
            ],
            'email' => [
                'plugin' => $defaultPlugin,
                'options' => [],
                \Compose\Web\Email\Plugin\PhpMailerPlugin::class => [
                    'smtp' => Env::bool('SMTP_ENABLED', true),
                    'host' => Env::get('SMTP_HOST', 'smtp.example.com'),
                    'port' => Env::int('SMTP_PORT', 587),
                    'username' => Env::get('SMTP_USERNAME', 'smtp-user@example.com'),
                    'password' => Env::get('SMTP_PASSWORD', 'app-password'),
                    'secure' => Env::get('SMTP_SECURE', 'tls'),
                ],
            ],
        ];

        $defaults['services'][\Compose\Web\Form\FormBuilder::class] = static function ($container) {
            return new \Compose\Web\Form\FormBuilder(
                $container->get(\Compose\Web\Security\SessionCsrfTokenProvider::class)
            );
        };

        $config = array_replace_recursive($config, $defaults);

        $contact = (new \Compose\Web\Module\Contact\Config())();
        $config = array_replace_recursive($config, $contact);

        if (empty($config['email']['plugin'])) {
            $config['email']['plugin'] = \Compose\Web\Email\Plugin\LogPlugin::class;
        }

        return $config;
    }
}
