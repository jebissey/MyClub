<?php

declare(strict_types=1);

namespace app\modules\Webmaster\services;

use DateTime;
use Exception;
use app\models\ArticleDataHelper;

final class SitemapBuilder
{
    public function __construct(private readonly ArticleDataHelper $articleDataHelper)
    {
    }

    /**
     * @return list<array{loc: string, lastmod: string}>
     */
    public function build(string $baseUrl): array
    {
        $articles = $this->articleDataHelper->getArticlesForAll();

        $urls = [
            ['loc' => $baseUrl, 'lastmod' => $this->formatDate($articles[0]->lastUpdate ?? null)],
        ];

        foreach ($articles as $article) {
            $urls[] = [
                'loc'     => $baseUrl . '/article/' . $article->id, // adjust to the real article route
                'lastmod' => $this->formatDate($article->lastUpdate),
            ];
        }

        return $urls;
    }

    private function formatDate(?string $date): string
    {
        try {
            return (new DateTime($date ?? 'now'))->format(DateTime::ATOM);
        } catch (Exception) {
            return (new DateTime())->format(DateTime::ATOM);
        }
    }
}
