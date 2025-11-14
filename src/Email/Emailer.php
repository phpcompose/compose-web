<?php

declare(strict_types=1);

namespace Compose\Web\Email;

final class Emailer
{
    /** @var callable */
    private $plugin;

    public function __construct(
        callable $plugin,
        private array $options = [],
        private ?MessageValidator $validator = null
    )
    {
        $this->setPlugin($plugin, $options);
    }

    public function createMessage(?string $subject = null, ?string $body = null): Message
    {
        return new Message($subject, $body);
    }

    public function setPlugin(callable $plugin, array $options = []): void
    {
        if (!\is_callable($plugin)) {
            throw new \InvalidArgumentException('Configured emailer plugin is not callable.');
        }

        $this->plugin = $plugin;
        $this->options = $options;
    }

    public function getPlugin(): ?callable
    {
        return $this->plugin ?? null;
    }

    public function send(Message $message): bool
    {
        $this->getAssertionValidator()->assertValid($message);

        $plugin = $this->plugin ?? null;
        if ($plugin === null) {
            throw new \RuntimeException('Emailer plugin is not configured.');
        }

        $result = $plugin($message, $this->options);

        return $this->normalizeSendResult($result);
    }

    private function normalizeSendResult(mixed $result): bool
    {
        if (\is_array($result) && array_key_exists('success', $result)) {
            return (bool) $result['success'];
        }

        if (\is_object($result) && method_exists($result, 'isSuccess')) {
            return (bool) $result->isSuccess();
        }

        return (bool) $result;
    }

    private function getAssertionValidator(): MessageValidator
    {
        return $this->validator ??= new MessageValidator();
    }
}
