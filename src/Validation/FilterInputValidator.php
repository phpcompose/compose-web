<?php

declare(strict_types=1);

namespace Compose\Web\Validation;

/**
 * Runs filter_var but converts changes into validation messages.
 */
class FilterInputValidator extends FilterInputFilterer
{
    private array $typeMessages = [
        FILTER_VALIDATE_BOOLEAN => 'Boolean',
        FILTER_VALIDATE_DOMAIN => 'Domain',
        FILTER_VALIDATE_EMAIL => 'Email',
        FILTER_VALIDATE_FLOAT => 'Float',
        FILTER_VALIDATE_INT => 'Integer',
        FILTER_VALIDATE_IP => 'IP',
        FILTER_VALIDATE_MAC => 'MAC Address',
        FILTER_VALIDATE_URL => 'URL',
        FILTER_UNSAFE_RAW => 'String',
    ];

    public function __construct(int $filterType, mixed $options = null, private readonly ?string $errorMessage = null)
    {
        parent::__construct($filterType, $options ?? 0);
    }

    public function __invoke(mixed $value): ?string
    {
        $filtered = parent::__invoke($value);
        if ($filtered !== $value) {
            if ($this->errorMessage) {
                return $this->errorMessage;
            }

            $type = $this->typeMessages[$this->filterType] ?? 'value';
            return "Invalid {$type}";
        }

        return null;
    }
}
