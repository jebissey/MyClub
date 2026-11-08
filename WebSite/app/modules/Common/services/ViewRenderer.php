<?php

declare(strict_types=1);

namespace app\modules\Common\services;

use Flight;
use Latte\Engine as LatteEngine;
use app\modules\Common\viewModels\LayoutViewModel;

final class ViewRenderer
{
    public function __construct(private readonly LatteEngine $latte)
    {
    }

    /**
     * Render a Latte template, flush the output and stop the request.
     *
     * @phpstan-latte-ignore
     */
    public function render(string $template, LayoutViewModel $viewModel): void
    {
        echo $this->latte->renderToString($template, $viewModel);
        if (ob_get_level()) {
            ob_end_flush();
        }
        flush();
        Flight::stop();
    }
}
