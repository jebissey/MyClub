<?php

declare(strict_types=1);

namespace app\modules\Common\services;

use Flight;
use Latte\Engine as LatteEngine;

final class ViewRenderer
{
    public function __construct(private readonly LatteEngine $latte)
    {
    }

    /**
     * Render a Latte template, flush the output and stop the request.
     *
     * @param object|array<string,mixed> $params
     */
    public function render(string $template, object|array $params = []): void
    {
        echo $this->latte->renderToString($template, $params);
        if (ob_get_level()) {
            ob_end_flush();
        }
        flush();
        Flight::stop();
    }
}
