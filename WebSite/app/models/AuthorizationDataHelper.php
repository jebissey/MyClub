<?php

declare(strict_types=1);

namespace app\models;

use DateTime;
use PDO;
use app\enums\EventAudience;
use app\exceptions\QueryException;
use app\helpers\Application;
use app\helpers\ConnectedUser;
use app\valueObjects\ArticleAuthorizationRow;
use app\valueObjects\ArticleRow;
use app\valueObjects\ClosingVisibilityRow;

/**
 * @phpstan-import-type ArticleAuthorizationRowShape from ArticleAuthorizationRow
 * @phpstan-import-type ArticleRowShape from ArticleRow
 * @phpstan-import-type ClosingVisibilityRowShape from ClosingVisibilityRow
 */
class AuthorizationDataHelper extends Data
{
    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    /**
     * @return array<int, string>
     */
    public function getsFor(ConnectedUser $connectedUser): array
    {
        $query = $this->pdo->prepare("
            SELECT DISTINCT Authorization.Name FROM Person 
            INNER JOIN PersonGroup ON Person.Id = PersonGroup.IdPerson
            INNER JOIN `Group` ON PersonGroup.IdGroup = `Group`.Id
            INNER JOIN GroupAuthorization on `Group`.Id = GroupAuthorization.IdGroup
            INNER JOIN Authorization on GroupAuthorization.IdAuthorization = Authorization.Id 
            WHERE Person.Id = ?");
        $query->execute([$connectedUser->person->Id ?? 0]);
        return array_column($query->fetchAll(), 'Name');
    }

    /**
     * @param array<int, object{Url?: string}> $navItems
     */
    public function personCanReadMediaFile(int $year, int $month, string $filename, ConnectedUser $connectedUser, array $navItems): bool
    {
        $path = sprintf('%04d/%02d/%s', $year, $month, $filename);

        $stmt = $this->pdo->prepare(
            "
            SELECT a.CreatedBy, a.PublishedBy, a.OnlyForMembers, a.IdGroup, a.Id
            FROM Article a
            WHERE a.Content LIKE :pattern

            UNION

            SELECT a.CreatedBy, a.PublishedBy, a.OnlyForMembers, a.IdGroup, a.Id
            FROM Article a
            INNER JOIN Carousel c ON c.IdArticle = a.Id
            WHERE c.Item LIKE :pattern"
        );
        $stmt->execute([':pattern' => '%' . $path . '%']);
        /** @var list<ArticleAuthorizationRowShape> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_OBJ);
        $articles = array_map(
            fn (object $row): ArticleAuthorizationRow => ArticleAuthorizationRow::fromStdClass($row),
            $rows
        );

        $menuArticleIds = [];
        foreach ($navItems as $navItem) {
            if (preg_match('#/menu/show/article/(\d+)$#', $navItem->Url ?? '', $matches)) {
                $menuArticleIds[] = (int)$matches[1];
            }
        }
        foreach ($articles as $article) {
            if ($this->canReadArticle($article, $connectedUser)) {
                return true;
            }
            if (in_array($article->Id, $menuArticleIds, true)) {
                return true;
            }
        }

        $stmt = $this->pdo->prepare(
            "
            SELECT ArticleId, EventId, GroupId
            FROM Message
            WHERE ImagePath LIKE :pattern"
        );
        $stmt->execute([':pattern' => '%' . $path . '%']);
        $messages = $stmt->fetchAll(PDO::FETCH_OBJ);

        foreach ($messages as $message) {
            if ($this->canReadMessageParent($message, $connectedUser)) {
                return true;
            }
        }

        return false;
    }

    public function canPersonReadOrderResults(ArticleAuthorizationRow $article, ConnectedUser $connectedUser): bool
    {
        $row = $this->get('Order', ['IdArticle' => $article->Id], 'ClosingDate, Visibility, Id');
        if (!$row || !($connectedUser->person ?? false)) {
            return false;
        }
        /** @var ClosingVisibilityRowShape $row */
        $order = ClosingVisibilityRow::fromStdClass($row);
        $now = (new DateTime())->format('Y-m-d');
        $closingDate = $order->ClosingDate;
        if (
            $article->CreatedBy === $connectedUser->person->Id
            || $order->Visibility === 'all'
            || ($order->Visibility === 'allAfterClosing' && $closingDate < $now)
        ) {
            return true;
        }
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM OrderReply WHERE IdOrder = ? AND IdPerson = ?');
        $stmt->execute([$order->Id, $connectedUser->person->Id]);
        $hasOrdered = $stmt->fetchColumn() > 0;
        if ($hasOrdered && ($order->Visibility === 'orderers' || ($order->Visibility === 'orderersAfterClosing' && $closingDate < $now))) {
            return true;
        }
        return false;
    }

    public function canPersonReadSurveyResults(ArticleAuthorizationRow $article, ConnectedUser $connectedUser): bool
    {
        $row = $this->get('Survey', ['IdArticle' => $article->Id], 'ClosingDate, Visibility, Id');
        if (!$row || !($connectedUser->person ?? false)) {
            return false;
        }
        /** @var ClosingVisibilityRowShape $row */
        $survey = ClosingVisibilityRow::fromStdClass($row);
        $now = (new DateTime())->format('Y-m-d');
        $closingDate = $survey->ClosingDate;
        if (
            $article->CreatedBy === $connectedUser->person->Id
            || $survey->Visibility === 'all'
            || ($survey->Visibility === 'allAfterClosing' && $closingDate < $now)
        ) {
            return true;
        }
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM Reply WHERE IdSurvey = ? AND IdPerson = ?');
        $stmt->execute([$survey->Id, $connectedUser->person->Id]);
        $hasVoted = $stmt->fetchColumn() > 0;
        if ($hasVoted && ($survey->Visibility === 'voters' || ($survey->Visibility === 'votersAfterClosing' && $closingDate < $now))) {
            return true;
        }
        return false;
    }

    public function getArticle(int $id, ConnectedUser $connectedUser): ArticleAuthorizationRow|false
    {
        $row = $this->get('Article', ['Id' => $id], 'Id, CreatedBy, PublishedBy, OnlyForMembers, IdGroup, Title, Content, LastUpdate, Timestamp');
        if ($row === false) {
            throw new QueryException("Article {$id} doesn't exist");
        }
        /** @var ArticleRowShape $row */
        $article = ArticleAuthorizationRow::fromArticleRow(ArticleRow::fromStdClass($row));
        if (!$this->canReadArticle($article, $connectedUser)) {
            return false;
        }
        return $article;
    }

    /**
     * @return array<int, int>
     */
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
        $rows = $stmt->fetchAll(PDO::FETCH_OBJ);
        return array_column($rows, 'IdGroup');
    }

    public function isUserInGroup(string $personEmail, string $groupsFilter): bool
    {
        return !empty(array_intersect($this->getGroups($groupsFilter), $this->getUserGroups($personEmail)));
    }

    #region Private functions
    private function canReadArticle(ArticleAuthorizationRow $article, ConnectedUser $connectedUser): bool
    {
        if (($connectedUser->person ?? false) && ($article->CreatedBy === $connectedUser->person->Id || $connectedUser->isEditor())) {
            return true;
        }
        if ($article->PublishedBy === null) {
            return false;
        }
        if ($connectedUser->person !== null && $article->IdGroup === null) {
            return true;
        }
        return $article->IdGroup === null || !empty(array_intersect(
            [$article->IdGroup],
            $this->getUserGroups($connectedUser->person->Email ?? '')
        ));
    }

    private function canReadEventById(int $eventId, ConnectedUser $connectedUser): bool
    {
        if (!($connectedUser->person ?? false)) {
            $stmt = $this->pdo->prepare("
                SELECT e.Id FROM Event e
                JOIN EventType et ON et.Id = e.IdEventType
                WHERE e.Id = :id
                  AND et.Inactivated = 0
                  AND et.IdGroup IS NULL
                  AND e.Audience = :audience
            ");
            $stmt->execute([
                ':id'       => $eventId,
                ':audience' => EventAudience::ForAll->value,
            ]);
        } else {
            $stmt = $this->pdo->prepare("
                SELECT e.Id FROM Event e
                JOIN EventType et ON et.Id = e.IdEventType
                WHERE e.Id = :id
                  AND et.Inactivated = 0
                  AND (
                      et.IdGroup IS NULL
                      OR et.IdGroup IN (
                          SELECT IdGroup FROM PersonGroup WHERE IdPerson = :personId
                      )
                  )
            ");
            $stmt->execute([
                ':id'       => $eventId,
                ':personId' => $connectedUser->person->Id,
            ]);
        }
        return (bool) $stmt->fetch(PDO::FETCH_OBJ);
    }

    private function canReadMessageParent(object $message, ConnectedUser $connectedUser): bool
    {
        if (!empty($message->ArticleId)) {
            $row = $this->get('Article', ['Id' => $message->ArticleId], 'CreatedBy, PublishedBy, OnlyForMembers, IdGroup');
            if ($row === false) {
                return false;
            }
            /** @var ArticleAuthorizationRowShape $row */
            return $this->canReadArticle(ArticleAuthorizationRow::fromStdClass($row), $connectedUser);
        }

        if (!empty($message->EventId)) {
            return $this->canReadEventById((int) $message->EventId, $connectedUser);
        }

        if (!empty($message->GroupId)) {
            if (!($connectedUser->person ?? false)) {
                return false;
            }
            return in_array($message->GroupId, $this->getUserGroups($connectedUser->person->Email), true);
        }

        return false;
    }

    /**
     * @return array<int, int>
     */
    private function getGroups(string $groupsFilter): array
    {
        $groupsFilter = preg_replace('/[^\p{L}]/u', '', $groupsFilter);
        $rows = $this->gets('Group', ['Name LIKE "%' . $groupsFilter . '%"' => null]);
        return array_column($rows, 'Id');
    }
}
