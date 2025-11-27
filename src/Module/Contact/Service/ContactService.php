<?php

declare(strict_types=1);

namespace Compose\Web\Module\Contact\Service;

use Compose\Container\ResolvableInterface;
use Compose\Support\Configuration;
use Compose\Web\Email\Emailer;
use Compose\Web\Form\Submission;
use Compose\Web\Module\Contact\Repository\ContactEntryRepositoryInterface;

final class ContactService implements ResolvableInterface
{
    public function __construct(
        private readonly Emailer $emailer,
        private readonly Configuration $config,
        private readonly ?ContactEntryRepositoryInterface $entries = null
    ) {
    }

    /**
     * @param array<string,mixed> $emailSettings
     */
    public function handleSubmission(Submission $submission, array $emailSettings = [], ?string $formSlug = null): void
    {
        $values = $submission->getValues();
        $settings = $emailSettings ?: ($this->config['modules']['contact']['email'] ?? []);
        $slug = $formSlug ?: 'default';

        if ($this->entries !== null) {
            $this->entries->record($slug, $values);
        }

        $message = $this->emailer->createMessage(
            $this->resolveSubject($values, $settings),
            $this->buildEmailMessageBody($values)
        );

        $fromEmail = $values['email'] ?? ($settings['from'] ?? 'no-reply@example.com');
        $message->setFrom($fromEmail, $values['name'] ?? null);
        $message->addTo($this->resolveRecipient($values, $settings));

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

    private function resolveRecipient(array $values, array $settings): string
    {
        $default = $settings['to'] ?? 'admin@example.com';
        $subjectKey = $values['subject'] ?? null;
        if (!$subjectKey) {
            return $default;
        }

        $map = $settings['subject_map'] ?? [];
        return $map[$subjectKey] ?? $default;
    }

    private function resolveSubject(array $values, array $settings): string
    {
        $subject = $settings['subject'] ?? 'Contact submission received';
        if (!empty($values['subject'])) {
            return sprintf('%s: %s', $subject, (string) $values['subject']);
        }

        return $subject;
    }
}
