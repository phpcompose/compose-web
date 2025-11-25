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
                \Doctrine\DBAL\Connection::class => static function () {
                    $url = $_ENV['DB_URL'] ?? null;
                    if ($url) {
                        return \Doctrine\DBAL\DriverManager::getConnection(['url' => $url]);
                    }

                    return \Doctrine\DBAL\DriverManager::getConnection([
                        'driver' => $_ENV['DB_DRIVER'] ?? 'pdo_mysql',
                        'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
                        'port' => $_ENV['DB_PORT'] ?? null,
                        'dbname' => $_ENV['DB_NAME'] ?? null,
                        'user' => $_ENV['DB_USER'] ?? null,
                        'password' => $_ENV['DB_PASSWORD'] ?? null,
                    ]);
                },
                \Compose\Web\Auth\AuthStorageInterface::class => \Compose\Web\Auth\SessionAuthStorage::class,
                \Compose\Web\Auth\PasswordHasherInterface::class => \Compose\Web\Auth\PasswordHasher::class,
                \Compose\Web\Auth\AuthenticatorInterface::class => \Compose\Web\Auth\PasswordAuthenticator::class,
                \Compose\Web\Auth\AuthService::class => \Compose\Web\Auth\AuthServiceFactory::class,
                \Compose\Web\Module\User\UserServiceInterface::class => \Compose\Web\Module\User\UserService::class,
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
            'auth' => [
                'storage' => \Compose\Web\Auth\SessionAuthStorage::class,
                'authenticators' => [
                    \Compose\Web\Auth\PasswordAuthenticator::class,
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
