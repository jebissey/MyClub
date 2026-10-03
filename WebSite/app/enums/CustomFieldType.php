<?php

declare(strict_types=1);

namespace app\enums;

enum CustomFieldType: string
{
    case Text = 'string';
    case Number = 'number';
    case Date = 'date';

    public function inputType(): string
    {
        return match ($this) {
            self::Text => 'text',
            self::Number => 'number',
            self::Date => 'date',
        };
    }

    public function translationKey(): string
    {
        return 'custom_fields.type.' . $this->value;
    }
}
