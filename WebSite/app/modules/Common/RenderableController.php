<?php

declare(strict_types=1);

namespace app\modules\Common;

use Flight;
use flight\Engine;
use Latte\Engine as LatteEngine;
use app\helpers\Application;

abstract class RenderableController
{
    protected Engine $flight;
    protected LatteEngine $latte;

    public function __construct(protected Application $application)
    {
        $this->flight = $application->getFlight();
        $this->latte = $application->getLatte();
    }

    /**
     * @phpstan-latte-ignore
     * @param object|array<string,mixed> $params
     */
    public function render(string $templateLatteName, object|array $params = []): void
    {
        $content = $this->latte->renderToString($templateLatteName, $params);
        echo $content;
        if (ob_get_level()) {
            ob_end_flush();
        }
        flush();
        Flight::stop();
    }
}
