<?php

declare(strict_types=1);

namespace Compose\Web\Template\Helper;

use Compose\Container\ResolvableInterface;
use Compose\Support\Configuration;
use Compose\Template\Helper\HelperRegistryAwareInterface;
use Compose\Template\Helper\HelperRegistryInterface;

final class AdminHelper implements HelperRegistryAwareInterface, ResolvableInterface
{
    private ?HelperRegistryInterface $helpers = null;

    public function __construct(private readonly Configuration $config)
    {
    }

    public function setHelperRegistry(HelperRegistryInterface $registry): void
    {
        $this->helpers = $registry;
    }

    public function __invoke(): static
    {
        return $this;
    }

    /**
     * @return array<string,mixed>
     */
    public function modules(): array
    {
        return $this->config['admin']['modules'] ?? [];
    }
}
