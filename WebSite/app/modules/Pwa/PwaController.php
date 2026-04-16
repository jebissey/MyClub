<?php

declare(strict_types=1);

namespace app\modules\Pwa;


use app\helpers\Application;
use app\helpers\WebApp;
use app\modules\Common\AbstractController;
use app\modules\Common\services\ArticleService;

class PwaController extends AbstractController
{
    public function __construct(
        Application $application,
        private ArticleService $articleService,
    ) {
        parent::__construct($application);
    }

    public function manifest(): void
    {
        header('Content-Type: application/json');
        header('Cache-Control: no-store');

        echo json_encode([
            "id" => WebApp::getBaseUrl(),

            "name" => $this->dataHelper->getSetting('PWA_Name', 'MyClub'),
            "short_name" => $this->dataHelper->getSetting('PWA_ShortName', 'MyClub'),

            "start_url" => "/?source=pwa",
            "scope" => "/",

            "display" => "standalone",

            "theme_color" => $this->dataHelper->getSetting('PWA_ThemeColor', '#0d6efd'),
            "background_color" => $this->dataHelper->getSetting('PWA_BackgroundColor', '#ffffff'),

            "icons" => [
                [
                    "src" => "/app/images/logo.png",
                    "sizes" => "192x192",
                    "type" => "image/png"
                ],
                [
                    "src" => "/app/images/logo.png",
                    "sizes" => "512x512",
                    "type" => "image/png"
                ]
            ],

            "share_target" => [
                "action" => "/share-target",
                "method" => "POST",
                "enctype" => "multipart/form-data",
                "params" => [
                    "title" => "title",
                    "text"  => "text",
                    "files" => [
                        [
                            "name" => "files",
                            "accept" => ["image/*"]
                        ]
                    ]
                ]
            ]

        ], JSON_UNESCAPED_SLASHES);
    }

    public function handleShare(): void
    {
        $user = $this->application->getConnectedUser();

        if (!($user->isRedactor() ?? false)) {
            $this->raiseForbidden(__FILE__, __LINE__);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }

        $title = $_POST['title'] ?? '';
        $text  = $_POST['text'] ?? '';

        $articleId = $this->articleService->createWithMedia(
            $user->person->Id,
            $_FILES['files'] ?? null,
            $title,
            $text
        );

        $this->redirect('/article/edit/' . $articleId);
    }
}
