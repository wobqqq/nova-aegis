<?php

declare(strict_types=1);

namespace Wobqqq\Aegis\Settings;

use JsonSerializable;
use Wobqqq\Aegis\Enums\FieldType;

/**
 * One input of a settings section, drawn by the Aegis page from its JSON form.
 */
final readonly class Field implements JsonSerializable
{
    /**
     * @param array<string, string> $options
     * @param list<Field> $columns
     */
    private function __construct(
        public string $name,
        public FieldType $type,
        public string $label,
        public string $help = '',
        public array $options = [],
        public array $columns = [],
        public ?string $placeholder = null,
    ) {
    }

    public static function toggle(string $name, string $label, string $help = ''): self
    {
        return new self($name, FieldType::TOGGLE, $label, $help);
    }

    public static function number(string $name, string $label, string $help = ''): self
    {
        return new self($name, FieldType::NUMBER, $label, $help);
    }

    public static function text(string $name, string $label, string $help = '', ?string $placeholder = null): self
    {
        return new self($name, FieldType::TEXT, $label, $help, placeholder: $placeholder);
    }

    public static function textarea(string $name, string $label, string $help = ''): self
    {
        return new self($name, FieldType::TEXTAREA, $label, $help);
    }

    /**
     * @param array<string, string> $options value => label
     */
    public static function select(string $name, string $label, array $options, string $help = ''): self
    {
        return new self($name, FieldType::SELECT, $label, $help, $options);
    }

    /**
     * A list of rows, each with the given text columns.
     *
     * @param list<Field> $columns
     */
    public static function table(string $name, string $label, array $columns, string $help = ''): self
    {
        return new self($name, FieldType::TABLE, $label, $help, columns: $columns);
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return array_filter([
            'name' => $this->name,
            'type' => $this->type->value,
            'label' => $this->label,
            'help' => $this->help,
            'options' => $this->options === [] ? null : $this->options,
            'columns' => $this->columns === [] ? null : $this->columns,
            'placeholder' => $this->placeholder,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
