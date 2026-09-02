<?php

declare(strict_types=1);

namespace app\modules\PersonManager\viewModels;

use app\modules\Common\viewModels\LayoutViewModel;

/**
 * @phpstan-type ImportSettings array{
 *     headerRow: int,
 *     mapping: array{email: int|null, firstName: int|null, lastName: int|null, phone: int|null}
 * }
 * @phpstan-type ImportResults array{
 *     errors?: int,
 *     messages?: array<int, string>,
 *     inactivated?: int,
 *     created?: int,
 *     updated?: int,
 *     deactivated?: int,
 *     processedEmails?: array<int, string>
 * }
 */
final readonly class UsersImportViewModel extends LayoutViewModel
{
    /**
     * @param ImportSettings $importSettings
     * @param ImportResults|null $results
     * @param array<string, mixed> $layoutParams Full output of Params::getAll()
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
