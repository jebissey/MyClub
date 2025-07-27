<?php

namespace app\helpers;

class News
{
    public function getNewsForPerson($person, $searchFrom): array
    {
        $news = [];
        return array_merge(
            $news,
            (new ArticleDataHelper())->getArticleNews($person, $searchFrom),
            (new SurveyDataHelper())->getSurveyNews($person, $searchFrom),
            (new EventDataHelper())->getEventNews($person, $searchFrom),
            (new MessageDataHelper())->getMessageNews($person, $searchFrom),
            (new PersonDataHelper())->getPresentationNews($person, $searchFrom)
        );
    }

    public function anyNews($person): bool
    {
        $news = $this->getNewsForPerson($person, $person->LastSignIn ?? '');
        return is_array($news) && count($news) > 0;
    }
}
