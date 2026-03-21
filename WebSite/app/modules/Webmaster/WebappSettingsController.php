<?php

declare(strict_types=1);

namespace app\modules\Webmaster;

use app\enums\FilterInputRule;
use app\helpers\Application;
use app\helpers\TranslationManager;
use app\helpers\WebApp;
use app\models\MetadataDataHelper;
use app\modules\Common\AbstractController;

class WebappSettingsController extends AbstractController
{
    private MetadataDataHelper $metadataDataHelper;
    private array $htmlSettingsKeys = [
        'Home_Header' => 'En-tête de la page d\'accueil',
        'Home_Footer' => 'Pied de page de la page d\'accueil',
    ];
    private array $numericSettingsKeys = [
        'Home_LatestArticlesCount' => [
            'label'   => 'Nombre de derniers articles à afficher',
            'default' => 10,
            'min'     => 0,
            'max'     => 50,
        ],
        'Home_FeaturedArticleId' => [
            'label'   => 'ID de l\'article mis en avant (0 = dernier article)',
            'default' => 0,
            'min'     => 0,
            'max'     => null,
        ],
    ];
    private array $imageTargets = [
        'img_home'   => ['path' => 'app/images/home.png',           'mime' => ['image/png']],
        'img_logo'   => ['path' => 'app/images/logo.png',           'mime' => ['image/png']],
        'img_banner' => ['path' => 'app/images/header-banner.jpg',  'mime' => ['image/jpeg']],
    ];

    public function __construct(Application $application)
    {
        parent::__construct($application);
        $this->metadataDataHelper = new MetadataDataHelper($application);
    }

    public function editSettings(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isHomeDesigner())) {
            return;
        }

        $lang     = TranslationManager::getCurrentLanguage();
        $settings = [];
        foreach ($this->htmlSettingsKeys as $key => $label) {
            $result = $this->dataHelper->get('Languages', ['Name' => $key], $lang);
            if ($result === false) {
                $settings[$key] = '';
            } else {
                $settings[$key] = $result->$lang ?? '';
            }
        }
        foreach ($this->numericSettingsKeys as $key => $config) {
            $result = $this->dataHelper->get('Settings', ['Name' => $key], 'Value');
            if ($result === false) {
                $this->dataHelper->set('Settings', [
                    'Name'  => $key,
                    'Value' => (string) $config['default'],
                ]);
                $settings[$key] = $config['default'];
            } else {
                $settings[$key] = (int) ($result->Value ?? $config['default']);
            }
        }

        $this->render('Webmaster/views/webappSettings.latte', $this->getAllParams([
            'navItems'            => $this->getNavItems($this->application->getConnectedUser()->person),
            'htmlSettingsKeys'    => $this->htmlSettingsKeys,
            'numericSettingsKeys' => $this->numericSettingsKeys,
            'settings'            => $settings,
            'page'                => $this->application->getConnectedUser()->getPage(),
            'supportedLanguages'  => TranslationManager::getSupportedLanguages(),
            'currentLanguage'     => $lang,
            'forcedLanguage'      => $this->metadataDataHelper->getForcedLanguage(),
        ]));
    }

    public function saveSettings(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('POST', fn($u) => $u->isHomeDesigner())) {
            return;
        }
        $schema = [
            'Home_Header'              => FilterInputRule::Html->value,
            'Home_Footer'              => FilterInputRule::Html->value,
            'Home_FeaturedArticleId'   => FilterInputRule::Int->value,
            'Home_LatestArticlesCount' => FilterInputRule::Int->value,
            'img_home'                 => FilterInputRule::DataUrl->value,
            'img_logo'                 => FilterInputRule::DataUrl->value,
            'img_banner'               => FilterInputRule::DataUrl->value,
        ];
        $input = WebApp::filterInput($schema, $this->flight->request()->data->getData());
        $lang = TranslationManager::getCurrentLanguage();
        foreach ($this->htmlSettingsKeys as $key => $label) {
            $value    = $input[$key] ?? '';
            $existing = $this->dataHelper->get('Languages', ['Name' => $key], $lang);
            if ($existing === false) {
                $this->dataHelper->set('Languages', ['Name' => $key, $lang => $value]);
            } else {
                $this->dataHelper->set('Languages', [$lang => $value], ['Name' => $key]);
            }
        }
        foreach ($this->numericSettingsKeys as $key => $config) {
            $raw      = (int) ($input[$key] ?? $config['default']);
            $value    = max($config['min'], $config['max'] !== null ? min($config['max'], $raw) : $raw);
            $existing = $this->dataHelper->get('Settings', ['Name' => $key], 'Value');
            if ($existing === false) {
                $this->dataHelper->set('Settings', ['Name' => $key, 'Value' => (string) $value]);
            } else {
                $this->dataHelper->set('Settings', ['Value' => (string) $value], ['Name' => $key]);
            }
        }
        foreach ($this->imageTargets as $field => $target) {
            $dataUrl = trim($input[$field] ?? '');
            if ($dataUrl !== '') {
                $this->saveImageFromDataUrl($dataUrl, $target['path'], $target['mime']);
            }
        }

        $this->redirect('/designer');
    }

    private function saveImageFromDataUrl(string $dataUrl, string $path, array $allowed): void
    {
        if (!str_starts_with($dataUrl, 'data:')) {
            return;
        }
        $commaPos = strpos($dataUrl, ',');
        if ($commaPos === false) {
            return;
        }
        $header  = substr($dataUrl, 5, $commaPos - 5);   // ex. "image/png;base64"
        $encoded = substr($dataUrl, $commaPos + 1);

        $parts = explode(';', $header);
        if (count($parts) !== 2 || $parts[1] !== 'base64') {
            return;
        }
        $mime = strtolower(trim($parts[0]));
        if (!in_array($mime, $allowed, true)) {
            return;
        }
        $bytes = base64_decode($encoded, strict: true);
        if ($bytes === false || strlen($bytes) < 8) {
            return;
        }
        $finfo    = new \finfo(FILEINFO_MIME_TYPE);
        $detected = $finfo->buffer($bytes);
        if (!in_array($detected, $allowed, true)) {
            return;
        }

        file_put_contents($path, $bytes);
    }

    public function saveLanguage()
    {
        if (!($this->application->getConnectedUser()->isHomeDesigner() ?? false)) {
            $this->raiseForbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $schema = [
            'lang'         => FilterInputRule::String->value,
            'use_language' => FilterInputRule::String->value,
        ];
        $requestParam = WebApp::filterInput($schema, $this->application->getFlight()->request()->query->getData());
        $language     = $requestParam['lang'] ?? '';
        $action       = $requestParam['use_language'] ?? null;
        if ($action === null || $action == 0 || $language === '') {
            $language = null;
        }
        $this->metadataDataHelper->setForcedLanguage($language);
        $this->redirect('/settings');
    }
}
