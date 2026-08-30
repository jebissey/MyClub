<?php

declare(strict_types=1);

namespace app\modules\Translator;

use app\enums\FilterInputRule;
use app\helpers\Application;
use app\helpers\TranslationManager;
use app\helpers\To;
use app\helpers\WebApp;
use app\modules\Common\AbstractController;
use app\modules\Translator\viewModels\TranslatorViewModel;

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

        $referenceLang = To::str($filters['ref'] ?? '');
        $referenceLang = in_array($referenceLang, $languages, true)
            ? $referenceLang
            : TranslationManager::DEFAULT_LANGUAGE;

        $targetLang = To::str($filters['lang'] ?? '');
        $targetLang = in_array($targetLang, $languages, true)
            ? $targetLang
            : TranslationManager::DEFAULT_LANGUAGE;

        $missingOnly = To::int($filters['missing'] ?? 0);

        $translations = $this->languagesDataHelper->getTranslations(
            $referenceLang,
            $targetLang,
            $missingOnly === 1
        );

        $viewModel = new TranslatorViewModel(
            navItems: $this->getNavItems($user->person),
            title: 'Translations',
            i18n: array_values($translations),
            referenceLang: $referenceLang,
            targetLang: $targetLang,
            missingOnly: $missingOnly,
            missingCount: $this->languagesDataHelper->countMissingTranslations($targetLang),
            languages: $languages,
            layoutParams: $this->getAllParams([]),
        );

        $this->render('Translator/views/translator.latte', $viewModel->toArray());
    }
}
