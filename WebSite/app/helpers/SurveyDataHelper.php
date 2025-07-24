<?php

namespace app\helpers;

class SurveyDataHelper extends Data
{
    public function articleHasSurvey($articleId)
    {
        return $this->fluent
            ->from('Survey')
            ->join('Article ON Survey.IdArticle = Article.Id')
            ->where('IdArticle', $articleId)
            ->where('ClosingDate <= ?', date('now'))
            ->fetch();
    }

    public function getWithCreator($articleId)
    {
        return $this->fluent->from('Survey')->join('Article ON Survey.IdArticle = Article.Id')->where('IdArticle', $articleId)->select('Article.CreatedBy')->fetch();
    }
}
