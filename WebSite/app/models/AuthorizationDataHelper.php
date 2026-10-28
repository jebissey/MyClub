<?php

declare(strict_types=1);

namespace app\models;

use PDO;
use app\enums\EventAudience;
use app\helpers\Application;
use app\helpers\ConnectedUser;
use app\helpers\To;
use app\modules\Article\services\ArticleAuthorizationService;
use app\modules\Article\valueObjects\ArticleAuthorizationRow;

/**
 * @phpstan-import-type ArticleAuthorizationRowShape from ArticleAuthorizationRow
 */
final class AuthorizationDataHelper extends Data
{
    /** @var array<string, list<int>> Memoized per request, keyed by lower-cased email */
    private array $userGroupsCache = [];

    private ?ArticleAuthorizationService $articlePolicy = null;

    public function __construct(Application $application)
    {
        parent::__construct(
            $application->getPdo(),
            $application->getErrorManager(),
            $application->getPdoForLog()
        );
    }

    /**
     * @return list<string>
     */
    public function getsFor(int $memberId): array
    {
        $query = $this->pdo->prepare("
            SELECT DISTINCT Authorization.Name
            FROM Member
            INNER JOIN MemberGroup        ON Member.Id = MemberGroup.IdMember
            INNER JOIN `Group`            ON MemberGroup.IdGroup = `Group`.Id
            INNER JOIN GroupAuthorization ON `Group`.Id = GroupAuthorization.IdGroup
            INNER JOIN Authorization      ON GroupAuthorization.IdAuthorization = Authorization.Id
            WHERE Member.Id = ?
        ");
        $query->execute([$memberId]);

        /** @var list<string> */
        return array_column($query->fetchAll(), 'Name');
    }

    /**
     * Whether the person can see a media file, through the article, carousel or message that uses it.
     *
     * The article rule is NOT duplicated here: it is delegated to ArticleAuthorizationService,
     * so a media is protected exactly like the article that contains it.
     *
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
            fn(object $row): ArticleAuthorizationRow => ArticleAuthorizationRow::fromStdClass($row),
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
            if (in_array($article->id, $menuArticleIds, true)) {
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

    public function hasRepliedToSurvey(int $surveyId, int $personId): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM Reply WHERE IdSurvey = ? AND IdPerson = ? LIMIT 1');
        $stmt->execute([$surveyId, $personId]);

        return $stmt->fetchColumn() !== false;
    }

    public function hasRepliedToOrder(int $orderId, int $personId): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM OrderReply WHERE IdOrder = ? AND IdPerson = ? LIMIT 1');
        $stmt->execute([$orderId, $personId]);

        return $stmt->fetchColumn() !== false;
    }

    /**
     * @return list<int>
     */
    public function getUserGroups(string $userEmail): array
    {
        // SQLite NOCASE only folds ASCII, strtolower() does the same
        $key = strtolower($userEmail);
        if (isset($this->userGroupsCache[$key])) {
            return $this->userGroupsCache[$key];
        }

        $stmt = $this->pdo->prepare('
            SELECT MemberGroup.IdGroup
            FROM MemberGroup
            INNER JOIN Member     ON Member.Id = MemberGroup.IdMember
            INNER JOIN Individual ON Individual.Id = Member.Id
            WHERE Individual.Email COLLATE NOCASE = :email
        ');
        $stmt->execute([':email' => $userEmail]);

        return $this->userGroupsCache[$key] = $this->toIntList($stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    public function isUserInGroup(string $personEmail, string $groupsFilter): bool
    {
        return array_intersect($this->getGroups($groupsFilter), $this->getUserGroups($personEmail)) !== [];
    }


    #region Private functions
    private function articlePolicy(): ArticleAuthorizationService
    {
        // The policy only needs getUserGroups() / hasRepliedTo*() from this helper.
        // Transitional: media access should move to its own service (see review).
        return $this->articlePolicy ??= new ArticleAuthorizationService($this);
    }

    private function canReadArticle(ArticleAuthorizationRow $article, ConnectedUser $connectedUser): bool
    {
        return $this->articlePolicy()->canRead($article, $connectedUser);
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
                          SELECT IdGroup FROM MemberGroup WHERE IdMember = :personId
                      )
                  )
            ");
            $stmt->execute([
                ':id'       => $eventId,
                ':personId' => $connectedUser->person->id,
            ]);
        }
        return (bool) $stmt->fetch(PDO::FETCH_OBJ);
    }


    private function canReadMessageParent(object $message, ConnectedUser $connectedUser): bool
    {
        if (!empty($message->ArticleId)) {
            $row = $this->get('Article', ['Id' => $message->ArticleId], 'Id, CreatedBy, PublishedBy, OnlyForMembers, IdGroup');
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
            return in_array((int) $message->GroupId, $this->getUserGroups($connectedUser->person->email), true);
        }

        return false;
    }


    /**
     * @return list<int>
     */
    private function getGroups(string $groupsFilter): array
    {
        $letters = preg_replace('/[^\p{L}]/u', '', $groupsFilter) ?? '';
        if ($letters === '') {
            // An empty filter would otherwise become LIKE '%%' and match every group
            return [];
        }

        $stmt = $this->pdo->prepare('SELECT Id FROM `Group` WHERE Name LIKE :name');
        $stmt->execute([':name' => '%' . $letters . '%']);

        return $this->toIntList($stmt->fetchAll(PDO::FETCH_COLUMN));
    }


    /**
     * @param array<mixed> $values
     * @return list<int>
     */
    private function toIntList(array $values): array
    {
        return array_values(array_map(static fn(mixed $value): int => To::int($value), $values));
    }
    #endregion
}
