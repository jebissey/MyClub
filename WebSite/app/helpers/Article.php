<?php

namespace app\helpers;

use PDO;

class Article
{
    private $fluent;

    public function __construct(PDO $pdo)
    {
        $this->fluent = new \Envms\FluentPDO\Query($pdo);
    }
    
    public function hasSurvey($id)
    {
        return $this->fluent->from('Survey')->join('Article ON Survey.IdArticle = Article.Id')->where('IdArticle', $id)->fetch();
    }
}