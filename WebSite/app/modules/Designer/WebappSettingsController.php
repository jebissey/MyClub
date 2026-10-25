<?php

declare(strict_types=1);

namespace app\modules\Designer;

use app\enums\FilterInputRule;
use app\helpers\Application;
use app\helpers\TranslationManager;
use app\helpers\To;
use app\helpers\WebApp;
use app\models\MetadataDataHelper;
use app\modules\Common\AbstractController;

final class WebappSettingsController extends AbstractController
{
    private readonly MetadataDataHelper $metadataDataHelper;

    /**
     * Setting name => translation key of its label.
     *
     * @var array<string, string>
     */
    private array $htmlSettingsKeys = [
        'Home_Header' => 'designer.home_settings.header',
        'Home_Footer' => 'designer.home_settings.footer',
    ];

    /**
     * The optional "label" is a translation key.
     *
     * @var array<string, array{
     *     label?: string,
     *     default: int,
     *     min?: int,
     *     max?: int|null
     * }>
     */
    private array $numericSettingsKeys = [
        'Home_LatestArticlesCount' => [
            'label'   => 'designer.home_settings.latest_articles_count',
            'default' => 10,
            'min'     => 0,
            'max'     => 50,
        ],
        'Home_FeaturedArticleId' => [
            'label'   => 'designer.home_settings.featured_article_id',
            'default' => 0,
            'min'     => 0,
            'max'     => null,
        ],
        'Home_FeaturedArticleParagraphs' => [
            'label'   => 'designer.home_settings.featured_article_paragraphs',
            'default' => 0,
            'min'     => 0,
            'max'     => null,
        ],
        'Home_FooterArticleId' => ['default' => 0],
    ];

    /**
     * @var array<string, array{
     *     path: string,
     *     mime: list<string>
     * }>
     */
    private array $imageTargets = [
        'img_home'   => ['path' => 'app/images/home.png',           'mime' => ['image/png']],
        'img_logo'   => ['path' => 'app/images/logo.png',           'mime' => ['image/png']],
        'img_banner' => ['path' => 'app/images/header-banner.jpg',  'mime' => ['image/jpeg']],
    ];

    /**
     * @var array<string, array{
     *     default: string
     * }>
     */
    private array $colorSettingsKeys = [
        'Navbar_BgColor'   => ['default' => '#212529'],
        'Navbar_InkColor'  => ['default' => '#ffffff'],
        'Navbar_IconColor' => ['default' => '#ffc107'],
    ];

    public function __construct(Application $application)
    {
        parent::__construct($application);
        $this->metadataDataHelper = new MetadataDataHelper($application);
    }

    public function editSettings(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isHomeDesigner(), __FILE__, __LINE__)) {
            return;
        }

        $lang     = TranslationManager::getCurrentLanguage();
        $settings = [];
        foreach (array_keys($this->htmlSettingsKeys) as $key) {
            $result = $this->dataHelper->get('Languages', ['Name' => $key], $lang);
            $settings[$key] = $result === false ? '' : ($result->$lang ?? '');
        }
        foreach ($this->numericSettingsKeys as $key => $config) {
            $settings[$key] = (int) $this->dataHelper->getSetting($key, (string) $config['default']);
        }
        foreach ($this->colorSettingsKeys as $key => $config) {
            $settings[$key] = $this->dataHelper->getSetting($key, $config['default']);
        }

        $htmlSettingsLabels = array_map(
            fn(string $labelKey): string => ($this->t)($labelKey),
            $this->htmlSettingsKeys
        );
        $numericSettings = array_map(
            function (array $config): array {
                if (isset($config['label'])) {
                    $config['label'] = ($this->t)($config['label']);
                }

                return $config;
            },
            $this->numericSettingsKeys
        );

        $this->views->render('Designer/views/webappSettings.latte', $this->getAllParams([
            'navItems'            => $this->getNavItems($this->application->getConnectedUser()->person),
            'htmlSettingsKeys'    => $htmlSettingsLabels,
            'numericSettingsKeys' => $numericSettings,
            'settings'            => $settings,
            'page'                => $this->application->getConnectedUser()->getPage(),
            'supportedLanguages'  => TranslationManager::getSupportedLanguages(),
            'currentLanguage'     => $lang,
            'forcedLanguage'      => $this->metadataDataHelper->getForcedLanguage(),
            'btn_HistoryBack' => true,
            'btn_Parent'      => "/designer",
            'i18n' => [
                'imageProcessing' => ($this->t)('designer.home_settings.image_processing'),
                'imageToSave'     => ($this->t)('designer.home_settings.image_to_save'),
                'imageReadError'  => ($this->t)('designer.home_settings.image_read_error'),
            ],
        ]));
    }

    public function saveSettings(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('POST', fn($u) => $u->isHomeDesigner(), __FILE__, __LINE__)) {
            return;
        }
        $schema = [
            'Home_Header'                    => FilterInputRule::Html->value,
            'Home_Footer'                    => FilterInputRule::Html->value,
            'Home_FeaturedArticleId'         => FilterInputRule::Int->value,
            'Home_FeaturedArticleParagraphs' => FilterInputRule::Int->value,
            'Home_LatestArticlesCount'       => FilterInputRule::Int->value,
            'Home_FooterArticleId'           => FilterInputRule::Int->value,
            'img_home'   => FilterInputRule::DataUrl->value,
            'img_logo'   => FilterInputRule::DataUrl->value,
            'img_banner' => FilterInputRule::DataUrl->value,
            'Navbar_BgColor'   => FilterInputRule::Color->value,
            'Navbar_InkColor'  => FilterInputRule::Color->value,
            'Navbar_IconColor' => FilterInputRule::Color->value,
        ];
        $input = WebApp::filterInput($schema, $this->flight->request()->data->getData());
        $lang = TranslationManager::getCurrentLanguage();
        foreach (array_keys($this->htmlSettingsKeys) as $key) {
            $value    = $input[$key] ?? '';
            $existing = $this->dataHelper->get('Languages', ['Name' => $key], $lang);
            if ($existing === false) {
                $this->dataHelper->set('Languages', ['Name' => $key, $lang => $value]);
            } else {
                $this->dataHelper->set('Languages', [$lang => $value], ['Name' => $key]);
            }
        }
        foreach ($this->numericSettingsKeys as $key => $config) {
            $this->dataHelper->setSetting($key, To::str($input[$key] ?? $config['default']));
        }
        foreach ($this->colorSettingsKeys as $key => $config) {
            $raw   = $input[$key] ?? $config['default'];
            $value = (is_string($raw) && preg_match('/^#[0-9a-fA-F]{6}$/', $raw) === 1) ? $raw : $config['default'];
            $this->dataHelper->setSetting($key, $value);
        }
        foreach ($this->imageTargets as $field => $target) {
            $dataUrl = trim(To::str($input[$field] ?? ''));
            if ($dataUrl !== '') {
                $this->saveImageFromDataUrl($dataUrl, $target['path'], $target['mime']);
            }
        }

        $this->redirect('/designer');
    }

    /**
     * @param list<string> $allowed
     */
    private function saveImageFromDataUrl(string $dataUrl, string $path, array $allowed): void
    {
        if (!str_starts_with($dataUrl, 'data:')) {
            return;
        }
        $commaPos = strpos($dataUrl, ',');
        if ($commaPos === false) {
            return;
        }
        $header  = substr($dataUrl, 5, $commaPos - 5);   // e.g. "image/png;base64"
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

    public function saveLanguage(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isHomeDesigner(), __FILE__, __LINE__)) {
            return;
        }

        $schema = [
            'lang'         => FilterInputRule::String->value,
            'use_language' => FilterInputRule::String->value,
        ];
        $requestParam = WebApp::filterInput($schema, $this->application->getFlight()->request()->query->getData());
        $language     = To::str($requestParam['lang'] ?? '');
        $action       = $requestParam['use_language'] ?? null;
        if ($action === null || $action == 0 || $language === '') {
            $language = null;
        }
        $this->metadataDataHelper->setForcedLanguage($language);
        $this->redirect('/settings');
    }
}
