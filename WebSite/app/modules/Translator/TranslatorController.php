<?php

declare(strict_types=1);

namespace app\modules\Translator;

use app\enums\FilterInputRule;
use app\helpers\Application;
use app\helpers\TranslationManager;
use app\helpers\WebApp;
use app\modules\Common\AbstractController;

class TranslatorController extends AbstractController
{
    public function __construct(
        Application $application,
    ) {
        parent::__construct($application);
    }

    public function index(): void
    {
        $user = $this->application->getConnectedUser();

        if (!$user->isTranslator()) {
            $this->raiseForbidden(__FILE__, __LINE__);
            return;
        }

        $languages = TranslationManager::getSupportedLanguages();

        $schema = [
            'ref'     => FilterInputRule::Content->value,
            'lang'    => FilterInputRule::Content->value,
            'missing' => FilterInputRule::Int->value,
        ];

        $filters = WebApp::filterInput(
            $schema,
            $this->flight->request()->query->getData()
        );

        $referenceLang = WebApp::toStr($filters['ref'] ?? '');
        $referenceLang = in_array($referenceLang, $languages, true)
            ? $referenceLang
            : TranslationManager::DEFAULT_LANGUAGE;

        $targetLang = WebApp::toStr($filters['lang'] ?? '');
        $targetLang = in_array($targetLang, $languages, true)
            ? $targetLang
            : TranslationManager::DEFAULT_LANGUAGE;

        $missingOnly = WebApp::toInt($filters['missing'] ?? 0);

        $translations = $this->languagesDataHelper->getTranslations(
            $referenceLang,
            $targetLang,
            $missingOnly === 1
        );

        $this->render('Translator/views/translator.latte', $this->getAllParams([
            'navItems'      => $this->getNavItems($user->person),
            'title'         => 'Translations',
            'page'          => $user->getPage(),
            'i18n'  => $translations,
            'referenceLang' => $referenceLang,
            'targetLang'    => $targetLang,
            'missingOnly'   => $missingOnly,
            'missingCount'  => $this->languagesDataHelper->countMissingTranslations($targetLang),
            'languages'     => $languages,
            'btn_HistoryBack' => true,
            'btn_Parent'      => "/designer",
        ]));
    }
}
