<?php

declare(strict_types=1);

namespace Compose\Web\Module\Contact\Service;

use Compose\Container\ResolvableInterface;
use Compose\Support\Configuration;
use Compose\Web\Email\Emailer;
use Compose\Web\Form\Submission;

final class ContactService implements ResolvableInterface
{
    private array $settings;

    public function __construct(
        private readonly Emailer $emailer,
        Configuration $config
    ) {
        $this->settings = $config['modules']['contact']['email'] ?? [];
    }

    public function handleSubmission(Submission $submission): void
    {
        $values = $submission->getValues();

        $message = $this->emailer->createMessage(
            $this->resolveSubject($values),
            $this->buildEmailMessageBody($values)
        );

        $fromEmail = $values['email'] ?? ($this->settings['from'] ?? 'no-reply@example.com');
        $message->setFrom($fromEmail, $values['name'] ?? null);
        $message->addTo($this->resolveRecipient($values));

        $this->emailer->send($message);
    }

    private function buildEmailMessageBody(array $values): string
    {
        $lines = [];
        foreach ($values as $key => $value) {
            if (is_string($key) && str_starts_with($key, '_')) {
                continue;
            }

            if (is_scalar($value) || $value === null) {
                $lines[] = sprintf("%s: %s", $key, (string) $value);
                continue;
            }

            $lines[] = sprintf("%s: %s", $key, json_encode($value, JSON_UNESCAPED_UNICODE));
        }

        return implode(PHP_EOL, $lines);
    }

    private function resolveRecipient(array $values): string
    {
        $default = $this->settings['to'] ?? 'admin@example.com';
        $subjectKey = $values['subject'] ?? null;
        if (!$subjectKey) {
            return $default;
        }

        $map = $this->settings['subject_map'] ?? [];
        return $map[$subjectKey] ?? $default;
    }

    private function resolveSubject(array $values): string
    {
        $subject = $this->settings['subject'] ?? 'Contact submission received';
        if (!empty($values['subject'])) {
            return sprintf('%s: %s', $subject, (string) $values['subject']);
        }

        return $subject;
    }
}
