<?php

namespace app\helpers;

use app\helpers\TranslationManager;

class LanguagesDataHelper extends Data
{
    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function translate($key)
    {
        $lang = TranslationManager::getCurrentLanguage();
        $stmt = $this->pdo->prepare("SELECT `$lang` FROM Languages WHERE Name = :key");
        $stmt->execute([':key' => $key]);
        $result = $stmt->fetchColumn();
        return $result === false ? "-- $key --" : $result;
    }
}
