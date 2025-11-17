<?php

declare(strict_types=1);

namespace Compose\Web\Form\Helper;

use Compose\Template\Helper\HelperRegistryAwareInterface;
use Compose\Template\Helper\HelperRegistryInterface;
use Compose\Template\Helper\TagHelper;
use Compose\Web\Form\Submission;
use Compose\Web\Form\DTO\Field;
use Throwable;

/**
 * Minimal Bootstrap form helper backed by Field DTOs.
 */
final class FormHelper implements HelperRegistryAwareInterface
{
    private ?TagHelper $tag = null;

    public function setHelperRegistry(HelperRegistryInterface $registry): void
    {
        $this->tag = $registry->get(TagHelper::class);
    }

    public function __invoke(): static
    {
        return $this;
    }



    public function formControl(Field $field, array $attributes = []): string
    {
        $attributes = $this->inputAttributes($field, $attributes, $field->type !== 'select');
        $inputHtml = match ($field->type) {
            'textarea' => $this->tag()->tag('textarea', (string) ($field->value ?? ''), $attributes),
            'select' => $this->formSelectElement($field, $attributes),
            default => $this->tag()->tag('input', null, $this->inputElementAttributes($field, $attributes)),
        };

        return $this->wrapControl($field, $inputHtml);
    }

    public function formSelect(Field $field, array $attributes = []): string
    {
        $attributes = $this->inputAttributes($field, $attributes, false);
        $selectHtml = $this->formSelectElement($field, $attributes);

        return $this->wrapControl($field, inputHtml: $selectHtml);
    }

    public function formCheck(Field $field, array $attributes = []): string
    {
        $attributes = $this->inputAttributes($field, $attributes, false);
        $attributes['type'] = $field->type === 'radio' ? 'radio' : 'checkbox';
        $attributes = $this->appendClass($attributes, 'form-check-input');

        if (!empty($field->value)) {
            $attributes['checked'] = 'checked';
        }

        $input = $this->tag()->tag('input', null, $this->inputElementAttributes($field, $attributes));

        $label = $this->buildLabel($field, $attributes['id'] ?? $field->name, 'form-check-label');

        $body = $input . $label . $this->appendFeedback($field);

        return $this->tag()->tag('div', $body, [
            'class' => trim('form-check ' . ($field->wrapperAttributes['class'] ?? '')),
        ]);
    }

    private function wrapControl(Field $field, string $inputHtml): string
    {
        $id = $field->attributes['id'] ?? $field->name;
        $label = $this->buildLabel($field, $id, 'form-label');

        $content = $label . $inputHtml . $this->appendFeedback($field);
        $wrapperAttrib = $this->appendClass($field->wrapperAttributes, 'mb-3');

        return $this->tag()->tag('div', $content, $wrapperAttrib);
    }

    private function inputAttributes(Field $field, array $overrides = [], bool $withControlClass = true): array
    {
        $attributes = array_merge($field->attributes, $overrides);
        $attributes['name'] = $field->name;
        $attributes['id'] = $attributes['id'] ?? $field->name;

        $classes = $attributes['class'] ?? '';
        $attributes['class'] = trim($classes);

        if ($withControlClass) {
            $attributes = $this->appendClass($attributes, 'form-control');
        }
        if ($field->hasErrors()) {
            $attributes = $this->appendClass($attributes, 'is-invalid');
        }

        if ($field->required) {
            $attributes['required'] = 'required';
        }

        return $attributes;
    }

    private function appendClass(array $attributes, string $class): array
    {
        $current = $attributes['class'] ?? '';
        $attributes['class'] = trim($current . ' ' . $class);

        return $attributes;
    }

    private function inputElementAttributes(Field $field, array $attributes): array
    {
        $attributes['type'] = $field->type;
        if (!array_key_exists('value', $attributes)) {
            $attributes['value'] = $field->value ?? '';
        }

        return $attributes;
    }

    private function formSelectElement(Field $field, array $attributes): string
    {
        $attributes['class'] = trim(($attributes['class'] ?? '') . ' form-select');

        $optionsHtml = '';
        foreach ($field->options as $value => $label) {
            if (is_array($label)) {
                $groupOptions = '';
                foreach ($label as $optionValue => $optionLabel) {
                    $groupOptions .= $this->optionTag($field, $optionValue, $optionLabel);
                }
                $optionsHtml .= $this->tag()->tag('optgroup', $groupOptions, ['label' => (string) $value]);
                continue;
            }

            $optionsHtml .= $this->optionTag($field, $value, $label);
        }

        return $this->tag()->tag('select', $optionsHtml, $attributes);
    }

    private function optionTag(Field $field, string|int $value, mixed $label): string
    {
        $attributes = ['value' => (string) $value];
        if ((string) $field->value === (string) $value) {
            $attributes['selected'] = 'selected';
        }

        return $this->tag()->tag('option', (string) $label, $attributes);
    }

    private function appendFeedback(Field $field): string
    {
        $html = '';

        if ($field->hasErrors()) {
            $html .= $this->tag()->tag('div', implode(' ', $field->errors), ['class' => 'invalid-feedback']);
        }

        if ($field->help) {
            $html .= $this->tag()->tag('div', $field->help, ['class' => 'form-text']);
        }

        return $html;
    }

    private function buildLabel(Field $field, string $for, string $class): string
    {
        $labelText = $field->required
            ? $field->label . ' <span class="text-danger">*</span>'
            : $field->label;

        return $this->tag()->tag('label', $labelText, [
            'for' => $for,
            'class' => $class,
        ]);
    }

    private function tag(): TagHelper
    {
        if ($this->tag === null) {
            $this->tag = new TagHelper();
        }

        return $this->tag;
    }

    public function render(Field|Submission $formOrField): string
    {
        if ($formOrField instanceof Submission) {
            return $this->renderForm($formOrField);
        }
        if ($formOrField instanceof Field) {
            return $this->renderField($formOrField);
        }
        throw new \InvalidArgumentException('Argument must be instance of Field or Submission');
    }

    public function renderForm(Submission $form, array $attributes = []): string
    {
        $attributes['action'] ??= $form->getAction();
        $attributes['method'] ??= strtolower($form->getMethod());

        $inner = '';

        $formId = $form->getFormIdField();
        $inner .= $this->tag()->tag('input', null, [
            'type' => 'hidden',
            'name' => $formId['name'],
            'value' => $formId['value'],
        ]);

        if ($csrf = $form->getCsrfField()) {
            $inner .= $this->tag()->tag('input', null, [
                'type' => 'hidden',
                'name' => $csrf['name'],
                'value' => $csrf['value'],
            ]);
        }

        foreach ($form->getFields() as $field) {
            $inner .= $this->renderField($field);
        }

        return $this->tag()->tag('form', $inner, $attributes);
    }

    public function renderField(Field $field, array $attributes = []): string
    {
        return match ($field->type) {
            'checkbox', 'radio' => $this->formCheck($field, $attributes),
            'select' => $this->formSelect($field, $attributes),
            default => $this->formControl($field, $attributes),
        };
    }

    public function render_form(Submission $form, array $attributes = []) : string
    {
        return $this->renderForm($form, $attributes);
    }

    public function render_field(Field $field, array $attributes = []) : string
    {
        return $this->renderField($field, $attributes);
    }
}
