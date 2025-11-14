<?php

declare(strict_types=1);

namespace Compose\Web\Email;

use function filter_var;
use const FILTER_VALIDATE_EMAIL;

/**
 * Simple DTO representing an email message.
 */
final class Message
{
    public ?string $subject = null;
    public ?string $body = null;
    public ?string $text = null;
    /** @var array<string,string|null> */
    public array $tos = [];
    /** @var array<string,string|null> */
    public array $bccs = [];
    /** @var array<string,string|null> */
    public array $ccs = [];
    /** @var array<string,string|null> */
    private array $replyTos = [];
    private ?string $fromAddress = null;

    public function __construct(?string $subject = null, ?string $body = null)
    {
        $this->subject = $subject;
        $this->body = $body;
    }

    public function setFrom(string $email, ?string $name = null): self
    {
        $email = trim($email);
        $name = $name !== null ? trim($name) : null;
        return $this->setFromAddress(self::formatAddress($email, $name));
    }

    public function setFromAddress(string $address): self
    {
        $address = trim($address);
        if (!self::isValidAddressString($address)) {
            throw new \InvalidArgumentException('Invalid From address format.');
        }
        $this->fromAddress = $address;
        return $this;
    }

    public function addTo(string $email, ?string $name = null): self
    {
        $this->tos[$email] = $name;
        return $this;
    }

    public function addBcc(string $email, ?string $name = null): self
    {
        $this->bccs[$email] = $name;
        return $this;
    }

    public function addCc(string $email, ?string $name = null): self
    {
        $this->ccs[$email] = $name;
        return $this;
    }

    public function addReplyTo(string $email, ?string $name = null): self
    {
        $email = trim($email);
        if ($email === '') {
            throw new \InvalidArgumentException('Reply-To email must be provided.');
        }
        if (!self::isValidAddressString($email)) {
            throw new \InvalidArgumentException('Invalid Reply-To email address supplied.');
        }
        $this->replyTos[$email] = $name !== null ? trim($name) : null;
        return $this;
    }

    public function hasReplyTo(): bool
    {
        return !empty($this->replyTos);
    }

    /**
     * @return array<string,string|null>
     */
    public function getReplyTos(): array
    {
        return $this->replyTos;
    }

    public function hasFrom(): bool
    {
        return $this->fromAddress !== null;
    }

    public function getFromAddress(): ?string
    {
        return $this->fromAddress;
    }

    public function getHtmlBody(): string
    {
        return (string) $this->body;
    }

    public function getTextBody(): string
    {
        if ($this->text !== null) {
            return $this->text;
        }

        return trim(strip_tags($this->body ?? ''));
    }

    /**
     * @param array<string,string|null> $addresses
     */
    public static function toAddressString(array $addresses): string
    {
        $parts = [];
        foreach ($addresses as $email => $name) {
            $parts[] = $name ? "{$name} <{$email}>" : $email;
        }

        return implode(',', $parts);
    }

    public static function formatAddress(string $email, ?string $name = null): string
    {
        return $name ? "{$name} <{$email}>" : $email;
    }

    public static function isValidAddressString(string $address): bool
    {
        $address = trim($address);
        if ($address === '') {
            return false;
        }

        if (filter_var($address, FILTER_VALIDATE_EMAIL)) {
            return true;
        }

        if (preg_match('/^(.+)<([^<>]+)>$/', $address, $matches)) {
            $email = trim($matches[2]);
            return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
        }

        return false;
    }

    public function __toString(): string
    {
        $lines = [];
        $lines[] = 'Subject: ' . ($this->subject ?? '(none)');
        $lines[] = 'From: ' . ($this->fromAddress ?? '(not set)');
        $lines[] = 'To: ' . (self::toAddressString($this->tos) ?: '(not set)');

        $cc = self::toAddressString($this->ccs);
        if ($cc !== '') {
            $lines[] = 'Cc: ' . $cc;
        }

        $bcc = self::toAddressString($this->bccs);
        if ($bcc !== '') {
            $lines[] = 'Bcc: ' . $bcc;
        }

        if ($this->hasReplyTo()) {
            $lines[] = 'Reply-To: ' . self::toAddressString($this->replyTos);
        }

        if ($this->body !== null) {
            $lines[] = '--- HTML Body ---';
            $lines[] = trim($this->body) ?: '(empty)';
        }

        if ($this->text !== null) {
            $lines[] = '--- Text Body ---';
            $lines[] = trim($this->text) ?: '(empty)';
        }

        return implode(PHP_EOL, $lines);
    }
}
