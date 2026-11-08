<?php

declare(strict_types=1);

namespace app\modules\Games\Karaoke;

use app\exceptions\LyricsParserException;
use app\helpers\Application;
use app\modules\Common\AbstractController;
use app\modules\Games\Karaoke\viewModels\KaraokeViewModel;

final class KaraokeController extends AbstractController
{
    private const MEDIA_PATH = __DIR__ . '/../../../../data/media/karaoke/';

    /**
     * Key used by the JavaScript => translation key.
     *
     * @var array<string, string>
     */
    private const I18N_KEYS = [
        'startError'           => 'karaoke.js.start_error',
        'loadFailed'           => 'karaoke.js.load_failed',
        'unknownError'         => 'karaoke.js.unknown_error',
        'errorPrefix'          => 'karaoke.js.error_prefix',
        'clickToPlay'          => 'karaoke.js.click_to_play',
        'syncActivationFailed' => 'karaoke.js.sync_activation_failed',
        'countdownFailed'      => 'karaoke.js.countdown_failed',
        'play'                 => 'karaoke.play',
        'pause'                => 'karaoke.pause',
        'ariaPlay'             => 'karaoke.aria.play',
        'ariaPause'            => 'karaoke.aria.pause',
        'clientsOne'           => 'karaoke.clients_connected_one',
        'clientsOther'         => 'karaoke.clients_connected_other',
        'waitingForHost'       => 'karaoke.waiting_for_host',
        'launchForAll'         => 'karaoke.launch_for_all',
        'stopForAll'           => 'karaoke.stop_for_all',
        'ariaLaunchForAll'     => 'karaoke.aria.launch_for_all',
        'ariaStopForAll'       => 'karaoke.aria.stop_for_all',
    ];

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

            $viewModel = new KaraokeViewModel(
                navItems: $this->getNavItems($connectedUser->person?->email),
                metadata: $parser->getMetadata(),
                lines: $parser->getLines(),
                audioFile: "/game/karaoke/files/{$song}",
                sessionId: session_id() ?: '',
                i18n: array_map(fn(string $key): string => $this->translate($key), self::I18N_KEYS),
                layoutParams: $this->getAllParams(['page' => $connectedUser->getPage()]),
            );

            $this->views->render('Games/Karaoke/views/karaoke.latte', $viewModel);
        } catch (LyricsParserException $e) {
            $this->renderInfo($this->translate($e->getMessage()), 10000);
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
