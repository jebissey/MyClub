<?php

declare(strict_types=1);

namespace app\modules\Article\services;

use app\helpers\WebApp;
use app\models\ArticleDataHelper;
use app\models\CarouselDataHelper;
use app\models\MessageDataHelper;
use app\models\SharedFileDataHelper;
use app\modules\Article\valueObjects\MediaListCriteria;

/**
 * Builds the list of media files displayed in the media manager,
 * enriched with the places where each file is used.
 *
 * @phpstan-import-type Candidate from MediaCatalog
 * @phpstan-type UsageMap array{
 *     article: array<string, bool>,
 *     gallery: array<string, bool>,
 *     shared: array<string, bool>,
 *     message: array<string, bool>
 * }
 * @phpstan-type FileRow array{
 *     name: string,
 *     path: string,
 *     url: string,
 *     urlRedactor: string,
 *     size: int,
 *     date: string,
 *     month: string,
 *     inGalery: bool,
 *     inArticle: bool,
 *     shared: bool,
 *     inMessage: bool
 * }
 */
final class MediaFileListBuilder
{
    public function __construct(
        private readonly MediaCatalog $catalog,
        private readonly ArticleDataHelper $articleDataHelper,
        private readonly CarouselDataHelper $carouselDataHelper,
        private readonly SharedFileDataHelper $sharedFileDataHelper,
        private readonly MessageDataHelper $messageDataHelper,
    ) {
    }

    /**
     * @return list<FileRow> Files matching the criteria, newest first.
     */
    public function build(MediaListCriteria $criteria): array
    {
        $candidates = $this->catalog->findCandidates($criteria);

        if ($candidates === []) {
            return [];
        }

        $usageMap = $this->buildUsageMap(array_column($candidates, 'path'));
        $files = $this->buildFileList($candidates, $usageMap, $criteria->unusedOnly);

        // Dates are formatted as "Y-m-d H:i:s", so a string comparison sorts chronologically.
        usort($files, fn($a, $b) => $b['date'] <=> $a['date']);

        return $files;
    }

    /**
     * @param list<string> $paths
     * @return UsageMap
     */
    private function buildUsageMap(array $paths): array
    {
        return [
            'article' => $this->articleDataHelper->getPathsUsedInArticles($paths),
            'gallery' => $this->carouselDataHelper->getPathsUsedInGalery($paths),
            'shared'  => $this->sharedFileDataHelper->getPathsShared($paths),
            'message' => $this->messageDataHelper->getPathsUsedInMessages($paths),
        ];
    }

    /**
     * @param list<Candidate> $candidates
     * @param UsageMap $usageMap
     * @return list<FileRow>
     */
    private function buildFileList(array $candidates, array $usageMap, bool $unusedOnly): array
    {
        $files = [];
        $baseUrl = WebApp::getBaseUrl();

        foreach ($candidates as $candidate) {
            $path = $candidate['path'];

            $usedInArticle = $usageMap['article'][$path] ?? false;
            $usedInGallery = $usageMap['gallery'][$path] ?? false;
            $isShared      = $usageMap['shared'][$path] ?? false;
            $usedInMessage = $usageMap['message'][$path] ?? false;

            if ($unusedOnly && ($usedInGallery || $usedInArticle || $isShared || $usedInMessage)) {
                continue;
            }

            $fullPath = $candidate['monthPath'] . $candidate['name'];

            $files[] = [
                'name'        => $candidate['name'],
                'path'        => $path,
                'url'         => $baseUrl . 'data/media/' . $path,
                'urlRedactor' => $baseUrl . 'media/' . $path,
                'size'        => filesize($fullPath) ?: 0,
                'date'        => date('Y-m-d H:i:s', filemtime($fullPath) ?: time()),
                'month'       => $candidate['month'],
                'inGalery'    => $usedInGallery,
                'inArticle'   => $usedInArticle,
                'shared'      => $isShared,
                'inMessage'   => $usedInMessage,
            ];
        }

        return $files;
    }
}
