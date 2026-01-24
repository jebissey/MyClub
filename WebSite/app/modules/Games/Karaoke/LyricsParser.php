<?php

declare(strict_types=1);

namespace app\modules\Games\Karaoke;

use app\exceptions\LyricsParserException;
use app\helpers\Application;
use app\models\LanguagesDataHelper;

class LyricsParser
{
    private array $metadata = [];
    private array $lines = [];

    public function __construct() {}

    public function parse(string $filePath): void
    {
        if (!file_exists($filePath)) {
            throw new LyricsParserException('ErrorLyricsFileNotFound', __FILE__, __LINE__);
        }
        if (!is_readable($filePath)) {
            throw new LyricsParserException('ErrorLyricsFileNotReadable', __FILE__, __LINE__);
        }
        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new LyricsParserException('ErrorLyricsFileReadError', __FILE__, __LINE__);
        }
        $lines = explode("\n", $content);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            // Parse metadata [ti:title], [ar:artist], etc.
            if (preg_match('/^\[([a-z]+):(.+)\]$/i', $line, $matches)) {
                $this->metadata[$matches[1]] = trim($matches[2]);
            }
            // Parse timed lyrics [00:00.00]text or with word timing
            elseif (preg_match('/^\[(\d+):(\d+\.\d+)\](.*)$/', $line, $matches)) {
                $minutes = (int) $matches[1];
                $seconds = (float) $matches[2];
                $timeInSeconds = $minutes * 60 + $seconds;
                $text = trim($matches[3]);

                // Parse word-level timing <00:00.00>word
                $words = [];
                if (preg_match_all('/<(\d+):(\d+\.\d+)>([^<]+)/', $text, $wordMatches, PREG_SET_ORDER)) {
                    foreach ($wordMatches as $match) {
                        $wordMinutes = (int) $match[1];
                        $wordSeconds = (float) $match[2];
                        $wordTime = $wordMinutes * 60 + $wordSeconds;
                        $words[] = [
                            'time' => $wordTime,
                            'text' => trim($match[3])
                        ];
                    }
                }

                $this->lines[] = [
                    'time' => $timeInSeconds,
                    'text' => $text,
                    'words' => $words
                ];
            }
        }

        usort($this->lines, fn($a, $b) => $a['time'] <=> $b['time']);
    }


    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function getLines(): array
    {
        return $this->lines;
    }
}
