<?php

namespace app\helpers;

use app\helpers\TranslationManager;

class LanguagesDataHelper extends Data
{
    public function translate($key)
    {
        $translation = $this->fluent->from('Languages')->where('Name', $key)->fetch(TranslationManager::getCurrentLanguage());
        return !$translation ? "-- $key --" : $translation;
    }
}
