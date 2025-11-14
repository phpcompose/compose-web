<?php

declare(strict_types=1);

namespace Compose\Web\Email;

use Compose\Container\ServiceFactoryInterface;
use Compose\Support\Configuration;
use Psr\Container\ContainerInterface;

/**
 * Builds Emailer instances from configuration.
 */
final class EmailerFactory implements ServiceFactoryInterface
{
    public static function create(ContainerInterface $container, string $name): Emailer
    {
        /** @var Configuration $config */
        $config = $container->get(Configuration::class);
        $settings = $config['email'] ?? $config['emailer'] ?? [];

        $pluginSpec = $settings['plugin'] ?? null;
        if ($pluginSpec === null) {
            throw new \RuntimeException('Emailer plugin must be configured.');
        }

        $baseOptions = isset($settings['options']) && \is_array($settings['options'])
            ? $settings['options']
            : [];

        [$callable, $pluginOptions] = self::resolvePlugin($container, $pluginSpec, $settings);
        $options = self::mergeOptions($baseOptions, $pluginOptions);

        return new Emailer($callable, $options);
    }

    private static function resolvePlugin(ContainerInterface $container, mixed $pluginSpec, array $settings): array
    {
        $pluginOptions = [];

        if (\is_string($pluginSpec)) {
            $callable = $container->get($pluginSpec);
            if (isset($settings[$pluginSpec]) && \is_array($settings[$pluginSpec])) {
                $pluginOptions = $settings[$pluginSpec];
            }
        } else {
            $callable = $pluginSpec;
        }

        if (!\is_callable($callable)) {
            throw new \InvalidArgumentException('Configured emailer plugin must be callable.');
        }

        return [$callable, $pluginOptions];
    }

    private static function mergeOptions(array $baseOptions, array $pluginOptions): array
    {
        if (empty($baseOptions)) {
            return $pluginOptions;
        }

        if (empty($pluginOptions)) {
            return $baseOptions;
        }

        return array_replace($baseOptions, $pluginOptions);
    }
}
