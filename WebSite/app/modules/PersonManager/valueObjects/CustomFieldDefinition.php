<?php

declare(strict_types=1);

namespace app\modules\PersonManager\valueObjects;

use app\enums\CustomFieldType;

final readonly class CustomFieldDefinition
{
    public function __construct(
        public string $key,
        public string $label,
        public CustomFieldType $type,
    ) {
    }

    /** @param array<mixed> $data */
    public static function fromArray(array $data): ?self
    {
        $key = $data['key'] ?? null;
        $label = $data['label'] ?? null;
        $type = CustomFieldType::tryFrom(is_string($data['type'] ?? null) ? $data['type'] : '');
        if (!is_string($key) || $key === '' || !is_string($label) || $label === '' || $type === null) {
            return null;
        }
        return new self($key, $label, $type);
    }

    /** @return array{key: string, label: string, type: string} */
    public function toArray(): array
    {
        return ['key' => $this->key, 'label' => $this->label, 'type' => $this->type->value];
    }
}
