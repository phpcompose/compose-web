<?php

declare(strict_types=1);

namespace Compose\Web\Validation;

final class Processor
{
    public ?bool $trim = null;
    public string $requiredMessage = 'Required';

    /** @var callable[] */
    private array $globalFilterers = [];
    /** @var array<string, callable[]> */
    private array $filterers = [];
    /** @var callable[] */
    private array $globalValidators = [];
    /** @var array<string, callable[]> */
    private array $validators = [];
    /** @var string[] */
    private array $requiredValues = [];

    public function addFilterer(callable $filterer, string|array|null $names = null): self
    {
        if ($names === null) {
            $this->globalFilterers[] = $filterer;
            return $this;
        }

        $names = is_array($names) ? $names : [$names];
        foreach ($names as $name) {
            $this->filterers[$name][] = $filterer;
        }

        return $this;
    }

    public function addValidator(callable $validator, string|array|null $names = null): self
    {
        if ($names === null) {
            $this->globalValidators[] = $validator;
            return $this;
        }

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
            $filterers = [...$this->globalFilterers, ...($this->filterers[$name] ?? [])];
            foreach ($filterers as $filterer) {
                $result = $filterer($result, $values);
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
                $validators = [...$this->globalValidators, ...($this->validators[$name] ?? [])];
                foreach ($validators as $validator) {
                    $error = $validator($value, $values);
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
