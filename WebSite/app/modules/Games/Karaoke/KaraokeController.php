<?php

declare(strict_types=1);

namespace app\modules\Games\Karaoke;

use app\exceptions\LyricsParserException;
use app\helpers\Application;
use app\helpers\Params;
use app\modules\Common\AbstractController;

class KaraokeController extends AbstractController
{
    private const MEDIA_PATH =  __DIR__ . '/../../../../data/media/karaoke/';

    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function play(string $song): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        try {
            $parser = new LyricsParser($this->application, $this->languagesDataHelper);
            $parser->parse(self::MEDIA_PATH . "{$song}.lrc");
            $connectedUser = $this->application->getConnectedUser();

            $this->render('Games/Karaoke/views/karaoke.latte', Params::getAll([
                'navItems' => $this->getNavItems($connectedUser->person),
                'page' => $this->application->getConnectedUser()->getPage(),
                'metadata' => $parser->getMetadata(),
                'lines' => $parser->getLines(),
                'audioFile' => "/game/karaoke/files/{$song}",
            ]));
        } catch (LyricsParserException $e) {
            $content = $this->languagesDataHelper->translate($e->getMessage())
                ?? $this->languagesDataHelper->translate('Error500');

            $this->render('Common/views/info.latte', [
                'content' => $content,
                'hasAuthorization' => $this->application->getConnectedUser()?->hasAutorization() ?? false,
                'currentVersion' => Application::VERSION,
                'timer' => 10000,
                'previousPage' => false,
                'page' => $this->application->getConnectedUser()?->getPage(),
            ]);
        }
    }

    public function files(string $name): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }

        $filePath = rtrim(self::MEDIA_PATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $name . '.mp3';
        if (!file_exists($filePath) || !is_readable($filePath)) {
            $this->raiseBadRequest("Files {$name} not found", __FILE__, __LINE__);
            return;
        }

        $mimeType = mime_content_type($filePath);
        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . filesize($filePath));
        header('Accept-Ranges: bytes');
        readfile($filePath);
    }
}
