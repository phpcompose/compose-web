<?php

declare(strict_types=1);

namespace Compose\Web\Email\Plugin;

use Compose\Container\ResolvableInterface;
use Compose\Web\Email\Message;
use PHPMailer\PHPMailer\PHPMailer;

/**
 * SMTP/PHPMailer transport plugin for the Emailer service.
 */
final class PhpMailerPlugin implements ResolvableInterface
{
    /**
     * @param array{
     *     smtp?:bool,
     *     host?:string,
     *     port?:int,
     *     username?:string,
     *     password?:string,
     *     secure?:string
     * } $options
     */
    public function __invoke(Message $message, array $options): bool
    {
        $mailer = new PHPMailer(true);

        if (!empty($options['smtp'])) {
            $mailer->isSMTP();
            $mailer->Host = $options['host'] ?? '';
            $mailer->SMTPAuth = true;
            $mailer->SMTPSecure = $options['secure'] ?? PHPMailer::ENCRYPTION_STARTTLS;
            $mailer->Port = (int) ($options['port'] ?? 587);
            $mailer->Username = $options['username'] ?? '';
            $mailer->Password = $options['password'] ?? '';
        }

        $fromAddress = $message->getFromAddress();
        if ($fromAddress === null) {
            throw new \InvalidArgumentException('PHPMailer plugin requires a From address.');
        }

        [$fromEmail, $fromName] = $this->splitAddress($fromAddress);
        $mailer->setFrom($fromEmail, $fromName);

        if ($message->hasReplyTo()) {
            foreach ($message->getReplyTos() as $email => $name) {
                $mailer->addReplyTo($email, $name ?? '');
            }
        } else {
            $mailer->addReplyTo($fromEmail, $fromName);
        }

        $mailer->Subject = $message->subject ?? '';

        $bodySet = false;
        if ($message->body !== null && $message->body !== '') {
            $mailer->isHTML(true);
            $mailer->Body = $message->body;
            $bodySet = true;
        }

        if ($message->text !== null && $message->text !== '') {
            $mailer->AltBody = $message->text;
            if (!$bodySet) {
                $mailer->isHTML(false);
                $mailer->Body = $message->text;
            }
        } elseif (!$bodySet) {
            $mailer->isHTML(false);
            $mailer->Body = '';
        }

        foreach ($message->tos as $email => $name) {
            $mailer->addAddress($email, $name ?? '');
        }

        foreach ($message->ccs as $email => $name) {
            $mailer->addCC($email, $name ?? '');
        }

        foreach ($message->bccs as $email => $name) {
            $mailer->addBCC($email, $name ?? '');
        }

        return $mailer->send();
    }

    /**
     * @return array{0:string,1:string}
     */
    private function splitAddress(string $address): array
    {
        $address = trim($address);

        if (preg_match('/^(.+)<([^<>]+)>$/', $address, $matches)) {
            $email = trim($matches[2]);
            $name = trim($matches[1], "\"' \t\n\r\0\x0B");
            return [$email, $name !== '' ? $name : ''];
        }

        return [$address, ''];
    }
}
