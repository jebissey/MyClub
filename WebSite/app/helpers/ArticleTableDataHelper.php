<?php

namespace app\helpers;

class ArticleTableDataHelper extends Data
{
    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function getQuery(ConnectedUser $connectedUser)
    {
        $query =  $this->fluent->from('Article')
            ->select('Article.Id, Article.CreatedBy, Article.Title, Article.LastUpdate')
            ->select('CASE WHEN Article.PublishedBy IS NULL THEN "non" ELSE "oui" END AS Published')
            ->select('CASE WHEN Article.OnlyForMembers = 1 THEN "oui" ELSE "non" END AS ForMembers')
            ->select('CASE WHEN Survey.IdArticle IS NULL THEN "non" ELSE "oui" END AS Pool')
            ->select('
                CASE 
                    WHEN Survey.IdArticle IS NULL THEN ""
                    ELSE 
                        (
                            CASE 
                                WHEN Survey.ClosingDate < CURRENT_DATE THEN "clos"
                                ELSE strftime("%d/%m/%Y", Survey.ClosingDate)
                            END
                            || " (" || COUNT(Reply.Id) || ") "
                            || CASE Survey.Visibility
                                WHEN "all" THEN "👁️‍🗨️👥"
                                WHEN "allAfterClosing" THEN "👁️‍🗨️👥📅"
                                WHEN "voters" THEN "👁️‍🗨️🗳️"
                                WHEN "votersAfterClosing" THEN "👁️‍🗨️🗳️📅"
                                WHEN "redactor" THEN "👁️‍🗨️📝"
                                ELSE ""
                            END
                        )
                END AS PoolDetail
            ')
            ->select('CASE WHEN Person.NickName != "" THEN Person.FirstName || " " || Person.LastName || " (" || Person.NickName || ")" ELSE Person.FirstName || " " || Person.LastName END AS PersonName')
            ->select("'Group'.Name AS GroupName")
            ->innerJoin('Person ON Article.CreatedBy = Person.Id')
            ->leftJoin('Survey ON Article.Id = Survey.IdArticle')
            ->leftJoin('Reply ON Survey.Id = Reply.IdSurvey')
            ->leftJoin("'Group' ON 'Group'.Id = Article.IdGroup")
            ->groupBy('Article.Id');

        if ($connectedUser->person ?? false) {
            if (!$connectedUser->isEditor()) {
                $query = $query->where('(Article.CreatedBy = ' . $connectedUser->person->Id . '
                    OR (Article.PublishedBy IS NOT NULL 
                        AND (Article.IdGroup IS NULL OR Article.IdGroup IN (SELECT IdGroup FROM PersonGroup WHERE IdPerson = ' . $connectedUser->person->Id . '))
                       ))');
            }
        } else $query = $query->where('(Article.IdGroup IS NULL AND Article.OnlyForMembers = 0 AND Article.PublishedBy IS NOT NULL)');
        $query = $query->orderBy('Article.LastUpdate DESC');

        return $query;
    }
}
