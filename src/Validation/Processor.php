<?php

declare(strict_types=1);

namespace Compose\Web\Validation;

final class Processor
{
    public ?bool $trim = null;
    public string $requiredMessage = 'Required';

    /** @var array<string, callable[]> */
    private array $filterers = [];
    /** @var array<string, callable[]> */
    private array $validators = [];
    /** @var string[] */
    private array $requiredValues = [];

    public function addFilterer(string|array $names, callable $filterer): self
    {
        $names = is_array($names) ? $names : [$names];
        foreach ($names as $name) {
            $this->filterers[$name][] = $filterer;
        }

        return $this;
    }

    public function addValidator(string|array $names, callable $validator): self
    {
        $names = is_array($names) ? $names : [$names];
        foreach ($names as $name) {
            $this->validators[$name][] = $validator;
        }

        return $this;
    }

    /**
     * @param string[] $names
     */
    public function setRequiredValues(array $names): self
    {
        $this->requiredValues = $names;
        return $this;
    }

    public function filter(array $values): array
    {
        $results = [];
        foreach ($values as $name => $value) {
            $result = $value;
            $filterers = $this->filterers[$name] ?? [];
            foreach ($filterers as $filterer) {
                $result = $filterer($result);
            }

            $results[$name] = $result;
        }

        return $results;
    }

    public function validate(array $values): ?array
    {
        $errors = [];

        foreach ($this->requiredValues as $name) {
            $value = $values[$name] ?? '';
            if (!is_array($value)) {
                $value = trim((string) $value);
            } elseif (isset($value['error']) && $value['error'] === UPLOAD_ERR_NO_FILE) {
                $value = null;
            }

            if ($value === '' || $value === null || $value === []) {
                $errors[$name][] = $this->requiredMessage;
            }
        }

        if (!$errors) {
            foreach ($values as $name => $value) {
                $validators = $this->validators[$name] ?? [];
                foreach ($validators as $validator) {
                    $error = $validator($value);
                    if ($error) {
                        $errors[$name][] = $error;
                    }
                }
            }
        }

        return $errors ?: null;
    }

    public function process(array $values): Result
    {
        $raw = $values;
        $filtered = $this->filter($values);
        $errors = $this->validate($filtered) ?? [];

        return new Result($raw, $filtered, $errors);
    }
}
