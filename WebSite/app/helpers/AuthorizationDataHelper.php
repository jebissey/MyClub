<?php

namespace app\helpers;

use DateTime;

class AuthorizationDataHelper extends Data
{
    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function getsFor(ConnectedUser $connectedUser)
    {
        $query = $this->pdo->prepare("
            SELECT DISTINCT Authorization.Name FROM Person 
            INNER JOIN PersonGroup ON Person.Id = PersonGroup.IdPerson
            INNER JOIN `Group` ON PersonGroup.IdGroup = `Group`.Id
            INNER JOIN GroupAuthorization on `Group`.Id = GroupAuthorization.IdGroup
            INNER JOIN Authorization on GroupAuthorization.IdAuthorization = Authorization.Id 
            WHERE Person.Id = ?");
        $query->execute([$connectedUser->person->Id]);
        return array_column($query->fetchAll(), 'Name');
    }

    public function canPersonReadSurveyResults($article, $person)
    {
        $survey = $this->fluent->from('Survey')->where('IdArticle', $article->Id)->fetch();
        if (!$survey || !$person) {
            return false;
        }

        $now = (new DateTime())->format('Y-m-d');
        $closingDate = $survey->ClosingDate;

        if (
            $article->CreatedBy == $person->Id
            || $survey->Visibility == 'all'
            || $survey->Visibility == 'allAfterClosing' && $closingDate < $now
        ) {
            return true;
        }

        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM Reply WHERE IdSurvey = ? AND IdPerson = ?');
        $stmt->execute([$survey->Id, $person->Id]);
        $hasVoted = $stmt->fetchColumn() > 0;
        if ($hasVoted && ($survey->Visibility == 'voters' || ($survey->Visibility == 'votersAfterClosing' && $closingDate < $now))) {
            return true;
        }
        return false;
    }

    public function getArticle($id, $connectedUser)
    {
        $article = $this->fluent->from('Article')->where('Id', $id)->fetch();
        if (!$this->canReadArticle($article, $connectedUser)) {
            return false;
        }
        return $article;
    }

    public function getUserGroups(string $userEmail): array
    {
        $rows = $this->fluent->from('PersonGroup')
            ->select('PersonGroup.IdGroup AS IdGroup')
            ->leftJoin('Person ON Person.Id = PersonGroup.IdPerson')
            ->where('Person.Email', $userEmail)
            ->fetchAll();
        return array_column($rows, 'IdGroup');
    }

    public function isUserInGroup($personEmail, $groupsFilter)
    {
        return !empty(array_intersect($this->getGroups($groupsFilter), $this->getUserGroups($personEmail)));
    }

    #region Private functions
    private function canReadArticle($article, ConnectedUser $connectedUser)
    {
        if (!$article) return false;
        if ($connectedUser->person && ($article->CreatedBy == $connectedUser->person->Id || $connectedUser->isEditor())) return true;
        if ($article->PublishedBy === null) return false;
        if (!$connectedUser->person) return $article->OnlyForMembers == 0 && ($article->IdGroup === null);
        if ($article->OnlyForMembers == 1 && $article->IdGroup === null) return true;
        return $article->IdGroup === null || !empty(array_intersect([$article->IdGroup], $this->getUserGroups($connectedUser->person->Email)));
    }

    private function getGroups($groupsFilter): array
    {
        $rows = $this->fluent->from('"Group"')
            ->select('Id AS IdGroup')
            ->where('Name LIKE "%' . $groupsFilter . '%"')
            ->fetchAll();
        return array_column($rows, 'IdGroup');
    }
}
