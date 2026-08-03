<?php

declare(strict_types=1);

namespace app\models;

use PDO;
use RuntimeException;
use app\helpers\Application;
use app\helpers\TranslationManager;

class LanguagesDataHelper extends Data
{
    /** @var array<int, string> */
    private array $allowedLanguages = [];
    private string $defaultLanguage = 'fr_FR';

    public function __construct(protected Application $application)
    {
        parent::__construct($application);
        $this->initializeLanguages();
    }

    #region Public - Front translation

    public function translate(string $key): string
    {
        if ($key === '') {
            return '';
        }
        $lang = TranslationManager::getCurrentLanguage();
        if (!in_array($lang, $this->allowedLanguages, true)) {
            $lang = $this->defaultLanguage;
        }
        $escapedLang = $this->escapeColumn($lang);
        $stmt = $this->pdo->prepare(
            "SELECT $escapedLang FROM Languages WHERE Name = :key"
        );

        $stmt->execute([':key' => $key]);
        $result = $stmt->fetchColumn();
        return $result === false || $result === '' ? "-- $key --" : (string)$result;
    }

    #endregion

    #region Public - Translator screen

    /**
     * @return array<int, string>
     */
    public function getAllowedLanguages(): array
    {
        return $this->allowedLanguages;
    }

    /**
     * @return array<int, object{Id: int, Name: string, ref_value: string|null, target_value: string|null}>
     */
    public function getTranslations(
        string $referenceLang,
        string $targetLang,
        bool $missingOnly = false
    ): array {
        if (
            !in_array($referenceLang, $this->allowedLanguages, true) ||
            !in_array($targetLang, $this->allowedLanguages, true)
        ) {
            return [];
        }

        $ref = $this->escapeColumn($referenceLang);
        $target = $this->escapeColumn($targetLang);

        $sql = "SELECT Id, Name, $ref AS ref_value, $target AS target_value
                FROM Languages";

        if ($missingOnly) {
            $sql .= " WHERE $target = '' OR $target IS NULL";
        }

        $sql .= " ORDER BY Name";

        $stmt = $this->pdo->query($sql);
        if ($stmt === false) {
            throw new RuntimeException('Failed to query Languages table');
        }

        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function countMissingTranslations(string $targetLang): int
    {
        if (!in_array($targetLang, $this->allowedLanguages, true)) {
            return 0;
        }
        $target = $this->escapeColumn($targetLang);
        $stmt = $this->pdo->query(
            "SELECT COUNT(*) 
            FROM Languages 
            WHERE $target = '' OR $target IS NULL"
        );
        if ($stmt === false) {
            return 0;
        }
        return (int)$stmt->fetchColumn();
    }

    public function updateTranslation(
        int $id,
        string $targetLang,
        string $value
    ): bool {
        if (!in_array($targetLang, $this->allowedLanguages, true)) {
            return false;
        }

        $target = $this->escapeColumn($targetLang);

        $stmt = $this->pdo->prepare(
            "UPDATE Languages
             SET $target = :value
             WHERE Id = :id"
        );

        return $stmt->execute([
            ':value' => $value,
            ':id' => $id,
        ]);
    }

    #endregion

    #region Private helpers
    private function initializeLanguages(): void
    {
        $stmt = $this->pdo->query("PRAGMA table_info(Languages)");
        if ($stmt === false) {
            throw new \RuntimeException('Failed to query Languages table schema');
        }
        /** @var array<int, array{name: string}> $columns */
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->allowedLanguages = [];
        foreach ($columns as $col) {
            if (!in_array($col['name'], ['Id', 'Name'], true)) {
                $this->allowedLanguages[] = $col['name'];
            }
        }
    }

    private function escapeColumn(string $column): string
    {
        return '`' . str_replace('`', '``', $column) . '`';
    }

    #endregion
}
