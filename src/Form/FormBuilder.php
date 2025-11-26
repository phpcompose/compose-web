<?php

declare(strict_types=1);

namespace Compose\Web\Form;

use Compose\Web\Form\DTO\Field;
use Compose\Web\Security\CsrfTokenProviderInterface;
use Compose\Web\Validation\Processor;

/**
 * FormBuilder
 *
 * Builds a Form from a fields definition array and wires a Processor with filters
 * and validators.
 *
 * Responsibilities
 * - Normalize field definitions (ensure 'name' exists) and create Field DTOs.
 * - Attach an optional CSRF provider to the Form.
 * - Build and attach a Processor containing filterers and validators declared
 *   on each field.
 *
 * Fields array shape:
 *   [
 *     'fieldKey' => [
 *         // optional override for the field name (defaults to the array key)
 *         'name' => 'field_name',
 *         // arbitrary metadata for consumers (type, label, etc.)
 *         'type' => 'text',
 *         'filters' => [
 *             FilterClass::class => null|['positional','args']|'singleScalar',
 *         ],
 *         'validators' => [
 *             ValidatorClass::class => null|['positional','args']|'singleScalar',
 *         ],
 *     ],
 *   ]
 *
 * Important behavior notes:
 * - Non-array field definitions are ignored.
 * - The builder expects filter/validator args to be positional:
 *   - args === null  => construct with no args
 *   - args is array  => must be a list (array_is_list), used as positional args
 *   - args is scalar => wrapped into a single-element positional array
 * - The builder instantiates via new $class(...$resolvedArgs) and requires the
 *   resulting instance to be callable (i.e. implement __invoke).
 * - Exceptions are thrown for missing classes, invalid arg shapes, or
 *   non-callable instances.
 *
 * Example:
 *   $fields = [
 *     'age' => [
 *       'filters' => [
 *         TrimFilter::class => null,
 *       ],
 *       'validators' => [
 *         \Compose\Web\Validation\Validator\NumberRange::class => [2, 10, 'must be between 2 and 10'],
 *       ],
 *     ],
 *   ];
 *
 * Note on associative (keyword) args:
 * - This builder does not support associative/keyword-style constructor args
 *   (like Python kwargs) for filters/validators. If you need that style,
 *   either provide a factory/named constructor (e.g. fromArray) on the target
 *   class or instantiate the callable yourself and pass the instance into the
 *   processor instead of declaring it here.
 */
final class FormBuilder
{
    public function __construct(
        private readonly ?CsrfTokenProviderInterface $csrfProvider = null,
    ) {
    }

    /**
     * @param array<string,array> $fields
     * @param array<string,mixed> $values optional initial values keyed by field name
     */
    public function build(string $action, array $fields, string $method = Form::METHOD_POST, array $values = []): Form
    {
        $definitions = [];
        foreach ($fields as $name => $definition) {
            if (!is_array($definition)) {
                continue;
            }

            $definition['name'] = $definition['name'] ?? (string) $name;
            if (array_key_exists($definition['name'], $values)) {
                $definition['value'] = $values[$definition['name']];
            }
            $definitions[] = $definition;
        }

        $form = new Form($action, $method);
        $form->setFields(Field::createMany($definitions));

        if ($this->csrfProvider !== null) {
            $form->setCsrfProvider($this->csrfProvider);
        }

        $form->setProcessor($this->buildProcessor($fields));

        return $form;
    }

    /**
     * @param array<string,array> $fields
     */
    private function buildProcessor(array $fields): Processor
    {
        $processor = new Processor();

        foreach ($fields as $name => $definition) {
            if (!is_array($definition)) {
                continue;
            }

            $filters = $definition['filters'] ?? [];
            foreach ($filters as $filterClass => $args) {
                $callable = $this->instantiateCallable($filterClass, $args);
                $processor->addFilterer($callable, $name);
            }

            $validators = $definition['validators'] ?? [];
            foreach ($validators as $validatorClass => $args) {
                $callable = $this->instantiateCallable($validatorClass, $args);
                $processor->addValidator($callable, $name);
            }
        }

        return $processor;
    }

    private function instantiateCallable(string $class, mixed $args): callable
    {
        if (!class_exists($class)) {
            throw new \InvalidArgumentException("Filter/validator class does not exist: {$class}");
        }

        if ($args === null) {
            $resolvedArgs = [];
        } elseif (is_array($args)) {
            if (!array_is_list($args)) {
                throw new \InvalidArgumentException("Filter/validator args must be a list array for {$class}");
            }
            $resolvedArgs = $args;
        } else {
            $resolvedArgs = [$args];
        }

        $instance = new $class(...$resolvedArgs);

        if (!is_callable($instance)) {
            throw new \InvalidArgumentException("Filter/validator must be callable: {$class}");
        }

        return $instance;
    }
}
