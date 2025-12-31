<?php
declare(strict_types=1);

namespace app\models;

use DateTime;

use app\exceptions\QueryException;
use app\helpers\Application;
use app\helpers\ConnectedUser;

class AuthorizationDataHelper extends Data
{
    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function getsFor(ConnectedUser $connectedUser): array
    {
        $query = $this->pdo->prepare("
            SELECT DISTINCT Authorization.Name FROM Person 
            INNER JOIN PersonGroup ON Person.Id = PersonGroup.IdPerson
            INNER JOIN `Group` ON PersonGroup.IdGroup = `Group`.Id
            INNER JOIN GroupAuthorization on `Group`.Id = GroupAuthorization.IdGroup
            INNER JOIN Authorization on GroupAuthorization.IdAuthorization = Authorization.Id 
            WHERE Person.Id = ?");
        $query->execute([$connectedUser->person?->Id ?? 0]);
        return array_column($query->fetchAll(), 'Name');
    }

    public function canPersonReadSurveyResults(object $article, ConnectedUser $connectedUser): bool
    {
        $survey = $this->get('Survey', ['IdArticle' => $article->Id], 'ClosingDate, Visibility, Id');
        if (!$survey || !($connectedUser->person ?? false)) return false;
        $now = (new DateTime())->format('Y-m-d');
        $closingDate = $survey->ClosingDate;
        if (
            $article->CreatedBy == ($connectedUser->person?->Id ?? 0)
            || $survey->Visibility == 'all'
            || $survey->Visibility == 'allAfterClosing' && $closingDate < $now
        ) return true;
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM Reply WHERE IdSurvey = ? AND IdPerson = ?');
        $stmt->execute([$survey->Id, $connectedUser->person?->Id ?? 0]);
        $hasVoted = $stmt->fetchColumn() > 0;
        if ($hasVoted && ($survey->Visibility == 'voters' || ($survey->Visibility == 'votersAfterClosing' && $closingDate < $now)))
            return true;
        return false;
    }

    public function getArticle(int $id, ConnectedUser $connectedUser): object|false
    {
        $article = $this->get('Article', ['Id' => $id], 'CreatedBy, PublishedBy, OnlyForMembers, IdGroup');
        if ($article === false) throw new QueryException("Article {$id} doesn't exist");
        if (!$this->canReadArticle($article, $connectedUser)) return false;
        return $article;
    }

    public function getUserGroups(string $userEmail): array
    {
        $sql = '
            SELECT PersonGroup.IdGroup AS IdGroup
            FROM PersonGroup
            LEFT JOIN Person ON Person.Id = PersonGroup.IdPerson
            WHERE Person.Email COLLATE NOCASE = :email
        ';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':email' => $userEmail]);
        $rows = $stmt->fetchAll();
        return array_column($rows, 'IdGroup');
    }

    public function isUserInGroup(string $personEmail, string $groupsFilter): bool
    {
        return !empty(array_intersect($this->getGroups($groupsFilter), $this->getUserGroups($personEmail)));
    }

    #region Private functions
    private function canReadArticle($article, ConnectedUser $connectedUser): bool
    {
        if (!$article) return false;
        if (($connectedUser->person  ?? false) && ($article->CreatedBy == $connectedUser->person->Id || $connectedUser->isEditor())) return true;
        if ($article->PublishedBy === null) return false;
        if (!($connectedUser->person ?? false)) return $article->OnlyForMembers == 0 && ($article->IdGroup === null);
        if ($article->OnlyForMembers == 1 && $article->IdGroup === null) return true;
        return $article->IdGroup === null || !empty(array_intersect([$article->IdGroup], $this->getUserGroups($connectedUser->person?->Email ?? '')));
    }

    private function getGroups(string $groupsFilter): array
    {
        $groupsFilter = preg_replace('/[^\p{L}]/u', '', $groupsFilter);
        $rows = $this->gets('Group', ['Name LIKE "%' . $groupsFilter . '%"' => null]);
        return array_column($rows, 'Id');
    }
}
