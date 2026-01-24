<?php

declare(strict_types=1);

namespace app\models;

use PDO;

use app\helpers\Application;
use app\helpers\TranslationManager;

class LanguagesDataHelper extends Data
{
    private array $allowedLanguages = [];
    private string $defaultLanguage = 'fr_FR';

    public function __construct(protected Application $application)
    {
        parent::__construct($application);
        $this->initializeLanguages();
    }

    public function translate(string $key): string
    {
        if (empty($key)) return '';

        $lang = TranslationManager::getCurrentLanguage();
        if (!in_array($lang, $this->allowedLanguages, true)) $lang = $this->defaultLanguage;
        $escapedLang = '`' . str_replace('`', '``', $lang) . '`';

        $stmt = $this->pdo->prepare("SELECT $escapedLang FROM Languages WHERE Name = :key");
        $stmt->execute([':key' => $key]);
        $result = $stmt->fetchColumn();
        return $result === false ? "-- $key --" : $result;
    }

    #region Private functions
    private function initializeLanguages(): void
    {
        $stmt = $this->pdo->query("PRAGMA table_info(Languages)");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->allowedLanguages = [];
        foreach ($columns as $col) {
            if (!in_array($col['name'], ['Id', 'Name'], true)) $this->allowedLanguages[] = $col['name'];
        }
    }
}
