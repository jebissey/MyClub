<?php

declare(strict_types=1);

namespace app\modules\Common;

use Flight;
use flight\Engine;
use Latte\Engine as LatteEngine;
use app\helpers\Application;
use app\helpers\TranslationManager;
use app\models\LanguagesDataHelper;

/**
 * Socle commun : Flight/Latte et rendu de template. Aucune dépendance vers
 * DataHelper/ErrorManager au moment de la construction — utilisable même
 * pendant le bootstrap d'ErrorManager lui-même (voir EmptyController).
 */
abstract class RenderableController
{
    /** @var Engine<object> */
    protected Engine $flight;
    protected LatteEngine $latte;

    public function __construct(protected Application $application)
    {
        $this->flight = $application->getFlight();
        $this->latte = $application->getLatte();
    }

    /** @param object|array<string,mixed> $params */
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
