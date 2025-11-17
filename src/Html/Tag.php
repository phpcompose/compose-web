<?php

declare(strict_types=1);

namespace Compose\Web\Html;

use Compose\Template\Template;

/**
 * Minimal mutable HTML tag wrapper for rendering helpers.
 */
final class Tag
{
    private static array $voidTags = [
        'area','base','br','col','embed','hr','img','input','link','meta','param','source','track','wbr'
    ];

    private string $name;
    /** @var string|array|null */
    private string|array|null $content;
    private array $attributes;
    private bool $void;

    public function __construct(
        string $name,
        string|array|null $content = null,
        array $attributes = [],
        bool $void = false
    ) {
        $this->name = $name;
        $this->content = $content;
        $this->attributes = $attributes;
        $this->void = $void;
    }

    public function setAttribute(string $name, mixed $value): self
    {
        $this->attributes[$name] = $value;
        return $this;
    }

    public function addClass(string $class): self
    {
        $current = $this->attributes['class'] ?? '';
        $this->attributes['class'] = trim($current . ' ' . $class);
        return $this;
    }

    public function addClassIf(bool $condition, string $class): self
    {
        return $condition ? $this->addClass($class) : $this;
    }

    public function setAttributeIf(bool $condition, string $name, mixed $value): self
    {
        return $condition ? $this->setAttribute($name, $value) : $this;
    }

    public function setContent(string|array|null $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function append(string|array|self $content): self
    {
        if ($this->content === null) {
            $this->content = [];
        }

        if (is_array($this->content)) {
            $this->content[] = $content;
        } else {
            $this->content = [$this->content, $content];
        }

        return $this;
    }

    public function setVoid(bool $void = true): self
    {
        $this->void = $void;
        return $this;
    }

    public function __toString(): string
    {
        $inner = $this->renderContent();
        return self::open($this->name, $this->attributes, $this->void) .
            $inner .
            self::close($this->name, $this->void);
    }

    public static function open(string $name, array $attributes = [], bool $void = false): string
    {
        if ($name === '') {
            return '';
        }

        $isVoid = $void || self::isVoid($name);
        return '<' . $name . self::attributeString($attributes) . ($isVoid ? '>' : '>');
    }

    public static function close(string $name, bool $void = false): string
    {
        $isVoid = $void || self::isVoid($name);
        return ($name !== '' && !$isVoid) ? '</' . $name . '>' : '';
    }

    public static function attributeString(array $attributes = []): string
    {
        if (empty($attributes)) {
            return '';
        }

        $str = '';
        foreach ($attributes as $key => $value) {
            if ($value === null || $value === false) {
                continue;
            }

            if ($value === true) {
                $str .= $key . ' ';
                continue;
            }

            if (!is_scalar($value)) {
                trigger_error('Value for attribute key {' . $key . '} must be scalar data type', E_USER_WARNING);
                continue;
            }

            $escaped = Template::escape((string) $value);
            $str .= "{$key}=\"{$escaped}\" ";
        }

        return ' ' . trim($str);
    }

    private function renderContent(): string
    {
        if ($this->void) {
            return '';
        }

        $content = $this->content;
        if ($content === null) {
            return '';
        }

        if (is_array($content)) {
            $buffer = '';
            foreach ($content as $item) {
                $buffer .= (string) $item;
            }
            return $buffer;
        }

        return (string) $content;
    }

    private static function isVoid(string $name): bool
    {
        return in_array(strtolower($name), self::$voidTags, true);
    }
}
