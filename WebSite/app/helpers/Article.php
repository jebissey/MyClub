<?php

namespace app\helpers;

use PDO;

class Article
{
    private $pdo;
    private $fluent;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->fluent = new \Envms\FluentPDO\Query($pdo);
    }

    public function hasSurvey($id)
    {
        return $this->fluent
            ->from('Survey')
            ->join('Article ON Survey.IdArticle = Article.Id')
            ->where('IdArticle', $id)
            ->where('ClosingDate <= ?', date('now'))
            ->fetch();
    }

    public function calculateTotals($crosstabData)
    {
        $totals = [
            'byAuthor' => [],
            'byAudience' => []
        ];

        foreach ($crosstabData['authors'] as $author) {
            $authorId = $author->Id;
            $total = 0;

            foreach ($crosstabData['audiences'] as $audience) {
                $audienceId = $audience['id'];
                if (isset($crosstabData['data'][$audienceId][$authorId])) {
                    $total += $crosstabData['data'][$audienceId][$authorId];
                }
            }

            $totals['byAuthor'][$authorId] = $total;
        }

        foreach ($crosstabData['audiences'] as $audience) {
            $audienceId = $audience['id'];
            $total = 0;

            if (isset($crosstabData['data'][$audienceId])) {
                foreach ($crosstabData['data'][$audienceId] as $count) {
                    $total += $count;
                }
            }

            $totals['byAudience'][$audienceId] = $total;
        }

        return $totals;
    }
}
