<?php

declare(strict_types=1);

namespace Compose\Web\Email\Plugin;

use Compose\Web\Email\Message;

final class LogPlugin
{
    public function __invoke(Message $message, array $options = []): bool
    {
        error_log('[Email] ' . PHP_EOL . (string) $message);
        return true;
    }
}
