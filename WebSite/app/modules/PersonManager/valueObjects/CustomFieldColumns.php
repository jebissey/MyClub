<?php

declare(strict_types=1);

namespace app\modules\PersonManager\valueObjects;

final readonly class CustomFieldColumns
{
    /**
     * @param list<CustomFieldDefinition> $definitions
     * @param array<string, int> $mapping Column index of each custom field, indexed by field key
     */
    public function __construct(
        public array $definitions,
        public array $mapping,
    ) {
    }
}
