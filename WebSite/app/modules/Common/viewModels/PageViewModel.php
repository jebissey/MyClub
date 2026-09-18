<?php

declare(strict_types=1);

namespace app\modules\Common\viewModels;

/**
 * Pour les vues qui n'ont besoin d'aucune donnée propre au-delà
 * du contexte de layout (page, navbar, i18n, etc.).
 */
final readonly class PageViewModel extends LayoutViewModel
{
    /** @param array<string, mixed> $layoutParams */
    public function __construct(array $layoutParams = [])
    {
        parent::__construct(...self::baseArgsFrom($layoutParams));
    }
}
