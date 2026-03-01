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

        if (!($user->isTranslator() ?? false)) {
            $this->raiseforbidden(__FILE__, __LINE__);
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

        $referenceLang = in_array($filters['ref'] ?? '', $languages, true)
            ? $filters['ref']
            : TranslationManager::DEFAULT_LANGUAGE;

        $targetLang = in_array($filters['lang'] ?? '', $languages, true)
            ? $filters['lang']
            : 'pl_PL';

        $missingOnly = (int)($filters['missing'] ?? 0);

        $translations = $this->languagesDataHelper->getTranslations(
            $referenceLang,
            $targetLang,
            $missingOnly === 1
        );

        $missingCount = $this->languagesDataHelper
            ->countMissingTranslations($targetLang);

        $this->render(
            'Translator/views/translator.latte',
            $this->getAllParams([
                'navItems'      => $this->getNavItems($user->person),
                'title'         => 'Translations',
                'page'          => $user->getPage(),
                'translations'  => $translations,
                'referenceLang' => $referenceLang,
                'targetLang'    => $targetLang,
                'missingOnly'   => $missingOnly,
                'missingCount'  => $missingCount,
                'languages'     => $languages,
            ])
        );
    }
}