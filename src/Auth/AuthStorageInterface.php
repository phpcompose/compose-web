<?php

declare(strict_types=1);

namespace Compose\Web\Auth;

interface AuthStorageInterface
{
    public function store(Identity $identity): void;

    public function clear(): void;

    public function load(): ?Identity;
}
