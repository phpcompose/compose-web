<?php

declare(strict_types=1);

namespace Compose\Web\Email;

final class MessageValidator
{
    public function assertValid(Message $message): void
    {
        $this->assertNotEmptyRecipients($message->tos);
        $this->assertAddressList($message->tos, 'recipient');
        $this->assertAddressList($message->ccs, 'CC');
        $this->assertAddressList($message->bccs, 'BCC');

        if ($message->hasReplyTo()) {
            $this->assertAddressList($message->getReplyTos(), 'Reply-To');
        }

        $this->assertSubject($message);
        $this->assertBody($message);
        $this->assertFrom($message);
    }

    /**
     * @param array<string,string|null> $addresses
     */
    private function assertNotEmptyRecipients(array $addresses): void
    {
        if (empty($addresses)) {
            throw new \InvalidArgumentException('Email message must have at least one recipient.');
        }
    }

    /**
     * @param array<string,string|null> $addresses
     */
    private function assertAddressList(array $addresses, string $type): void
    {
        foreach (array_keys($addresses) as $address) {
            if (!Message::isValidAddressString((string) $address)) {
                throw new \InvalidArgumentException(sprintf('Invalid %s email address: %s', $type, $address));
            }
        }
    }

    private function assertSubject(Message $message): void
    {
        if (!$message->subject) {
            throw new \InvalidArgumentException('Email message must include a subject.');
        }
    }

    private function assertBody(Message $message): void
    {
        if (!$message->body && !$message->text) {
            throw new \InvalidArgumentException('Email message must include HTML or plain text body content.');
        }
    }

    private function assertFrom(Message $message): void
    {
        if (!$message->hasFrom()) {
            throw new \InvalidArgumentException('Email message requires a From address.');
        }

        $fromAddress = $message->getFromAddress();
        if ($fromAddress === null || !Message::isValidAddressString($fromAddress)) {
            throw new \InvalidArgumentException('Email message has an invalid From address.');
        }
    }
}
