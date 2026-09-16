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
        $this->addLatteFilters();
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

    private function addLatteFilters(): void
    {
        // Résolution paresseuse : LanguagesDataHelper n'est construit qu'au
        // premier appel réel du filtre, jamais pendant le constructeur —
        // donc jamais pendant le bootstrap d'ErrorManager.
        $this->latte->addFilter('translate', function ($key) {
            static $languagesDataHelper = null;
            $languagesDataHelper ??= new LanguagesDataHelper($this->application, $this->application->getErrorManager());
            return $languagesDataHelper->translate($key);
        });

        $this->latte->addFilter('shortDate', fn($date) => TranslationManager::getShortDate($date));
        $this->latte->addFilter('longDate', fn($date) => TranslationManager::getLongDate($date));
        $this->latte->addFilter('longDateTime', fn($date) => TranslationManager::getLongDateTime($date));
        $this->latte->addFilter('shortDateTime', fn($date) => TranslationManager::getShortDateTime($date));
        $this->latte->addFilter('dayName', fn($date) => TranslationManager::getDayName($date));

        $this->latte->addFilter('formatFileSize', function ($bytes) {
            if ($bytes >= 1073741824) {
                return number_format($bytes / 1073741824, 2) . ' GB';
            } elseif ($bytes >= 1048576) {
                return number_format($bytes / 1048576, 2) . ' MB';
            } elseif ($bytes >= 1024) {
                return number_format($bytes / 1024, 2) . ' KB';
            } else {
                return $bytes . ' bytes';
            }
        });

        $this->latte->addFilter('readableDuration', fn($duration) => TranslationManager::getReadableDuration($duration));
    }
}
