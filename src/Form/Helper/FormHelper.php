<?php

declare(strict_types=1);

namespace Compose\Web\Form\Helper;

use Compose\Template\Helper\HelperRegistryAwareInterface;
use Compose\Template\Helper\HelperRegistryInterface;
use Compose\Template\Helper\TagHelper;
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
        $tagHelper = null;

        try {
            $tagHelper = $registry->get(TagHelper::class);
        } catch (Throwable) {
            try {
                $tagHelper = $registry->get('tag');
            } catch (Throwable) {
                $tagHelper = null;
            }
        }

        $this->tag = $tagHelper instanceof TagHelper ? $tagHelper : new TagHelper();
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

        return $this->wrapControl($field, $selectHtml);
    }

    public function formCheck(Field $field, array $attributes = []): string
    {
        $attributes = $this->inputAttributes($field, $attributes, false);
        $attributes['type'] = $field->type === 'radio' ? 'radio' : 'checkbox';
        $attributes['class'] = trim(($attributes['class'] ?? '') . ' form-check-input');

        if (!empty($field->value)) {
            $attributes['checked'] = 'checked';
        }

        $input = $this->tag()->tag('input', null, $this->inputElementAttributes($field, $attributes));

        $labelAttrib = [
            'for' => $attributes['id'] ?? $field->name,
            'class' => 'form-check-label',
        ];
        $label = $this->tag()->tag('label', $field->label, $labelAttrib);

        $body = $input . $label;
        if ($field->hasErrors()) {
            $body .= $this->errorFeedback($field);
        } elseif ($field->help) {
            $body .= $this->helpText($field->help);
        }

        return $this->tag()->tag('div', $body, [
            'class' => trim('form-check ' . ($field->wrapperAttributes['class'] ?? '')),
        ]);
    }

    private function wrapControl(Field $field, string $inputHtml): string
    {
        $id = $field->attributes['id'] ?? $field->name;
        $label = $this->tag()->tag('label', $this->labelText($field), [
            'for' => $id,
            'class' => 'form-label',
        ]);

        $content = $label . $inputHtml;

        if ($field->hasErrors()) {
            $content .= $this->errorFeedback($field);
        } elseif ($field->help) {
            $content .= $this->helpText($field->help);
        }

        $wrapperAttrib = $field->wrapperAttributes;
        $wrapperAttrib['class'] = trim(($wrapperAttrib['class'] ?? '') . ' mb-3');

        return $this->tag()->tag('div', $content, $wrapperAttrib);
    }

    private function inputAttributes(Field $field, array $overrides = [], bool $withControlClass = true): array
    {
        $attributes = array_merge($field->attributes, $overrides);
        $attributes['name'] = $field->name;
        $attributes['id'] = $attributes['id'] ?? $field->name;

        $classes = $attributes['class'] ?? '';
        if ($withControlClass) {
            $classes = trim($classes . ' form-control');
        }
        if ($field->hasErrors()) {
            $classes = trim($classes . ' is-invalid');
        }
        $attributes['class'] = trim($classes);

        if ($field->required) {
            $attributes['required'] = 'required';
        }

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

    private function errorFeedback(Field $field): string
    {
        return $this->tag()->tag('div', implode(' ', $field->errors), ['class' => 'invalid-feedback']);
    }

    private function helpText(string $help): string
    {
        return $this->tag()->tag('div', $help, ['class' => 'form-text']);
    }

    private function labelText(Field $field): string
    {
        return $field->required ? $field->label . ' <span class="text-danger">*</span>' : $field->label;
    }

    private function tag(): TagHelper
    {
        if ($this->tag === null) {
            $this->tag = new TagHelper();
        }

        return $this->tag;
    }
}
