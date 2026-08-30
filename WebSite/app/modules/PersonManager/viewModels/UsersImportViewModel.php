<?php

declare(strict_types=1);

namespace app\modules\PersonManager\viewModels;

use app\modules\Common\viewModels\LayoutViewModel;

final readonly class UsersImportViewModel extends LayoutViewModel
{
    /**
     * @param array{headerRow: int, mapping: array{email: int|null, firstName: int|null, lastName: int|null, phone: int|null}} $importSettings
     * @param array{errors?: int, messages?: array<int, string>, inactivated?: int, created?: int, updated?: int, deactivated?: int, processedEmails?: array<int, string>}|null $results
     * @param array<string, mixed> $layoutParams Full output of Params::getAll().
     */
    public function __construct(
        public array $importSettings,
        public ?array $results,
        public string $layout,
        array $layoutParams = []
    ) {
        parent::__construct(
            ...self::baseArgsFrom($layoutParams),
        );
    }
}
