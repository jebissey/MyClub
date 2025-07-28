<?php

namespace app\helpers;

use DateTime;

class AuthorizationDataHelper extends Data
{
    private $authorizations = null;

    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function getsFor($personId)
    {
        $query = $this->pdo->prepare("
            SELECT DISTINCT Authorization.Name FROM Person 
            INNER JOIN PersonGroup ON Person.Id = PersonGroup.IdPerson
            INNER JOIN `Group` ON PersonGroup.IdGroup = `Group`.Id
            INNER JOIN GroupAuthorization on `Group`.Id = GroupAuthorization.IdGroup
            INNER JOIN Authorization on GroupAuthorization.IdAuthorization = Authorization.Id 
            WHERE Person.Id = ?");
        $query->execute([$personId]);
        return $this->authorizations = array_column($query->fetchAll(), 'Name');
    }

    public function isEventManager(): bool
    {
        return in_array('EventManager', $this->authorizations ?? []);
    }

    public function isPersonManager(): bool
    {
        return in_array('PersonManager', $this->authorizations ?? []);
    }

    public function isRedactor(): bool
    {
        return in_array('Redactor', $this->authorizations ?? []);
    }

    public function isEditor(): bool
    {
        return in_array('Editor', $this->authorizations ?? []);
    }

    public function isWebmaster(): bool
    {
        return in_array('Webmaster', $this->authorizations ?? []);
    }

    public function hasAutorization(): bool
    {
        return count($this->authorizations ?? []) > 0;
    }

    public function hasOnlyOneAutorization(): bool
    {
        return count($this->authorizations ?? []) == 1;
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

    public function getArticle($id, $person)
    {
        $article = $this->fluent->from('Article')->where('Id', $id)->fetch();
        if (!$this->canReadArticle($article, $person)) {
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
    private function canReadArticle($article, $person)
    {
        if (!$article) {
            return false;
        }
        if ($person && ($article->CreatedBy == $person->Id || $this->isEditor())) {
            return true;
        }
        if ($article->PublishedBy === null) {
            return false;
        }
        if (!$person) {
            return $article->OnlyForMembers == 0 && ($article->IdGroup === null);
        }
        if ($article->OnlyForMembers == 1 && $article->IdGroup === null) {
            return true;
        }
        return $article->IdGroup === null || !empty(array_intersect([$article->IdGroup], $this->getUserGroups($person->Email)));
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
