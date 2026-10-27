<?php

declare(strict_types=1);

namespace app\modules\Games\Karaoke;

use app\exceptions\LyricsParserException;
use app\helpers\Application;
use app\helpers\WebApp;
use app\modules\Common\AbstractController;

final class KaraokeController extends AbstractController
{
    private const MEDIA_PATH =  __DIR__ . '/../../../../data/media/karaoke/';

    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function play(string $song): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isAnybody(), __FILE__, __LINE__)) {
            return;
        }

        try {
            $parser = new LyricsParser();
            $parser->parse(self::MEDIA_PATH . "{$song}.lrc");
            $connectedUser = $this->application->getConnectedUser();

            $this->views->render('Games/Karaoke/views/karaoke.latte', $this->getAllParams([
                'navItems' => $this->getNavItems($connectedUser->person?->Email),
                'page' => $this->application->getConnectedUser()->getPage(),
                'metadata' => $parser->getMetadata(),
                'lines' => $parser->getLines(),
                'audioFile' => "/game/karaoke/files/{$song}",
                'sessionId' => session_id()
            ]));
        } catch (LyricsParserException $e) {
            $content = ($this->t)($e->getMessage())
                ?? ($this->t)('Error500');

            $this->views->render('Common/views/info.latte', $this->getAllParams([
                'content' => $content,
                'hasAuthorization' => $this->application->getConnectedUser()->hasAutorization(),
                'currentVersion' => Application::VERSION,
                'timer' => 10000,
                'previousPage' => false,
                'page' => $this->application->getConnectedUser()->getPage(),
            ]));
        }
    }

    public function files(string $name): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isAnybody(), __FILE__, __LINE__)) {
            return;
        }

        $filePath = rtrim(self::MEDIA_PATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $name . '.mp3';
        if (!file_exists($filePath) || !is_readable($filePath)) {
            $this->errors->badRequest("Files {$name} not found", __FILE__, __LINE__);
            return;
        }

        $mimeType = mime_content_type($filePath);
        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . filesize($filePath));
        header('Accept-Ranges: bytes');
        readfile($filePath);
    }
}
