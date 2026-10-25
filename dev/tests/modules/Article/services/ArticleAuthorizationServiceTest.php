<?php

declare(strict_types=1);

namespace tests\modules\Article\services;

use PDO;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;
use app\enums\ArticleAccess;
use app\enums\Authorization;
use app\helpers\ConnectedUser;
use app\models\AuthorizationDataHelper;
use app\models\Data;
use app\modules\Article\services\ArticleAuthorizationService;
use app\modules\Article\valueObjects\ArticleAuthorizationRow;
use app\modules\Article\valueObjects\ArticleRow;
use app\modules\Article\valueObjects\Result;
use app\modules\Common\valueObjects\ClosingVisibilityRow;
use app\modules\Common\valueObjects\ConnectedUser as ConnectedUserVO;
use app\modules\Common\valueObjects\Person;

final class ArticleAuthorizationServiceTest extends TestCase
{
    private const PAST_DATE = '2000-01-01';
    private const FUTURE_DATE = '2999-12-31';

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * AuthorizationDataHelper is final, so it cannot be mocked, stubbed or subclassed.
     * The real class is used instead, on top of an in-memory SQLite database.
     *
     * The constructor is bypassed (it needs a full Application) and only the
     * inherited $pdo property is set.
     */
    private function makeHelper(?PDO $pdo = null): AuthorizationDataHelper
    {
        /** @var AuthorizationDataHelper $helper */
        $helper = (new ReflectionClass(AuthorizationDataHelper::class))->newInstanceWithoutConstructor();
        (new ReflectionProperty(Data::class, 'pdo'))->setValue($helper, $pdo ?? $this->makeSchemaPdo());

        return $helper;
    }

    /**
     * A helper whose database has no table at all: any query throws a PDOException.
     * Replaces `expects($this->never())`: if the service queries the database, the test fails.
     */
    private function makeForbiddingHelper(): AuthorizationDataHelper
    {
        return $this->makeHelper($this->makeEmptyPdo());
    }

    private function makeEmptyPdo(): PDO
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return $pdo;
    }

    /**
     * Minimal schema with the tables used by the helper methods called from the service.
     * Empty tables behave like a default stub: no groups, no replies.
     */
    private function makeSchemaPdo(): PDO
    {
        $pdo = $this->makeEmptyPdo();
        $pdo->exec('
            CREATE TABLE Individual  (Id INTEGER PRIMARY KEY, Email TEXT);
            CREATE TABLE Member      (Id INTEGER PRIMARY KEY);
            CREATE TABLE MemberGroup (IdMember INTEGER, IdGroup INTEGER);
            CREATE TABLE Reply       (IdSurvey INTEGER, IdPerson INTEGER);
            CREATE TABLE OrderReply  (IdOrder INTEGER, IdPerson INTEGER);
        ');

        return $pdo;
    }

    /**
     * The email follows the same convention as makePerson().
     *
     * @param list<int> $groupIds
     */
    private function givenMemberInGroups(PDO $pdo, int $personId, array $groupIds): void
    {
        $pdo->prepare('INSERT INTO Individual (Id, Email) VALUES (?, ?)')
            ->execute([$personId, "person{$personId}@example.test"]);
        $pdo->prepare('INSERT INTO Member (Id) VALUES (?)')
            ->execute([$personId]);

        foreach ($groupIds as $groupId) {
            $pdo->prepare('INSERT INTO MemberGroup (IdMember, IdGroup) VALUES (?, ?)')
                ->execute([$personId, $groupId]);
        }
    }

    private function givenSurveyReply(PDO $pdo, int $surveyId, int $personId): void
    {
        $pdo->prepare('INSERT INTO Reply (IdSurvey, IdPerson) VALUES (?, ?)')
            ->execute([$surveyId, $personId]);
    }

    private function givenOrderReply(PDO $pdo, int $orderId, int $personId): void
    {
        $pdo->prepare('INSERT INTO OrderReply (IdOrder, IdPerson) VALUES (?, ?)')
            ->execute([$orderId, $personId]);
    }

    private function makeService(?AuthorizationDataHelper $helper = null): ArticleAuthorizationService
    {
        return new ArticleAuthorizationService($helper ?? $this->makeHelper());
    }

    private function makeArticle(
        int $createdBy = 10,
        ?int $publishedBy = 1,
        ?int $idGroup = null,
        bool $onlyForMembers = false,
    ): ArticleRow {
        return new ArticleRow(
            Id: 1,
            Title: 'Title',
            Content: 'Content',
            CreatedBy: $createdBy,
            PublishedBy: $publishedBy,
            IdGroup: $idGroup,
            OnlyForMembers: $onlyForMembers,
            LastUpdate: '2026-01-01 00:00:00',
            Timestamp: '',
        );
    }

    private function makeClosing(string $visibility, string $closingDate): ClosingVisibilityRow
    {
        return new ClosingVisibilityRow(Id: 1, ClosingDate: $closingDate, Visibility: $visibility);
    }

    /**
     * Builds a real ConnectedUser (final) without its constructor and assigns
     * the public $person / $user properties directly.
     *
     * In production $person and $user are always set together; here they can be
     * decoupled on purpose (no person but editor) to isolate one branch of the
     * service. That state is not reachable in production but tests the || correctly.
     */
    private function makeConnectedUser(?Person $person = null, bool $isEditor = false): ConnectedUser
    {
        $ref = new ReflectionClass(ConnectedUser::class);
        /** @var ConnectedUser $helper */
        $helper = $ref->newInstanceWithoutConstructor();

        $helper->person = $person;

        $authorizations = $isEditor ? [Authorization::Editor->value] : [];
        // ConnectedUserVO requires a non-null Person: use a dummy one when $person is null
        $helper->user = new ConnectedUserVO($person ?? $this->makePerson(0), $authorizations);

        return $helper;
    }

    private function makePerson(int $id): Person
    {
        return new Person(Id: $id, Email: "person{$id}@example.test");
    }

    /**
     * @param Result<ArticleRow> $result
     */
    private function denialOf(Result $result): ?ArticleAccess
    {
        return $result->match(
            onOk: static fn(mixed $value): ?ArticleAccess => null,
            onFail: static fn(ArticleAccess $reason): ArticleAccess => $reason,
        );
    }

    private function denial(
        ArticleRow $article,
        ConnectedUser $user,
        ?AuthorizationDataHelper $helper = null
    ): ?ArticleAccess {
        return $this->denialOf($this->makeService($helper)->authorizeRead($article, $user));
    }

    // -------------------------------------------------------------------------
    // isOwner / canEdit / canDelete
    // -------------------------------------------------------------------------

    public function testIsOwnerReturnsFalseWhenUserHasNoPerson(): void
    {
        $service = $this->makeService();

        $this->assertFalse($service->isOwner($this->makeArticle(createdBy: 42), $this->makeConnectedUser(person: null)));
    }

    public function testIsOwnerWorksWithAnArticleAuthorizationRow(): void
    {
        $row = new ArticleAuthorizationRow(
            Id: 1,
            CreatedBy: 42,
            PublishedBy: null,
            IdGroup: null,
            OnlyForMembers: false,
        );

        $this->assertTrue($this->makeService()->isOwner($row, $this->makeConnectedUser($this->makePerson(42))));
        $this->assertFalse($this->makeService()->isOwner($row, $this->makeConnectedUser($this->makePerson(43))));
    }

    public function testCanEditReturnsFalseWhenUserHasNoPerson(): void
    {
        $result = $this->makeService()->canEdit($this->makeArticle(), $this->makeConnectedUser(person: null));

        $this->assertFalse($result);
    }

    public function testCanEditReturnsTrueWhenUserIsAuthor(): void
    {
        $result = $this->makeService()->canEdit(
            $this->makeArticle(createdBy: 42),
            $this->makeConnectedUser($this->makePerson(42))
        );

        $this->assertTrue($result);
    }

    public function testCanEditReturnsFalseWhenUserIsNotAuthor(): void
    {
        $result = $this->makeService()->canEdit(
            $this->makeArticle(createdBy: 10),
            $this->makeConnectedUser($this->makePerson(42))
        );

        $this->assertFalse($result);
    }

    public function testCanDeleteDelegatesToCanEdit(): void
    {
        $service = $this->makeService();
        $article = $this->makeArticle(createdBy: 42);

        $this->assertTrue($service->canDelete($article, $this->makeConnectedUser($this->makePerson(42))));
        $this->assertFalse($service->canDelete($article, $this->makeConnectedUser($this->makePerson(99))));
    }

    // -------------------------------------------------------------------------
    // canPublish / canChangeOwner
    // -------------------------------------------------------------------------

    public function testCanPublishReturnsTrueWhenUserIsAuthor(): void
    {
        $result = $this->makeService()->canPublish(
            $this->makeArticle(createdBy: 42),
            $this->makeConnectedUser($this->makePerson(42), isEditor: false)
        );

        $this->assertTrue($result);
    }

    public function testCanPublishReturnsTrueWhenUserIsEditorEvenIfNotAuthor(): void
    {
        $result = $this->makeService()->canPublish(
            $this->makeArticle(createdBy: 10),
            $this->makeConnectedUser($this->makePerson(42), isEditor: true)
        );

        $this->assertTrue($result);
    }

    public function testCanPublishReturnsFalseWhenUserIsNeitherAuthorNorEditor(): void
    {
        $result = $this->makeService()->canPublish(
            $this->makeArticle(createdBy: 10),
            $this->makeConnectedUser($this->makePerson(42), isEditor: false)
        );

        $this->assertFalse($result);
    }

    public function testCanPublishReturnsFalseWhenUserHasNoPersonAndIsNotEditor(): void
    {
        $result = $this->makeService()->canPublish(
            $this->makeArticle(),
            $this->makeConnectedUser(person: null, isEditor: false)
        );

        $this->assertFalse($result);
    }

    public function testCanPublishReturnsTrueWhenUserHasNoPersonButIsEditor(): void
    {
        // canEdit is false (no person) but isEditor() is true
        $result = $this->makeService()->canPublish(
            $this->makeArticle(),
            $this->makeConnectedUser(person: null, isEditor: true)
        );

        $this->assertTrue($result);
    }

    public function testCanChangeOwnerIsReservedToEditors(): void
    {
        $service = $this->makeService();

        $this->assertTrue($service->canChangeOwner($this->makeConnectedUser($this->makePerson(1), isEditor: true)));
        $this->assertFalse($service->canChangeOwner($this->makeConnectedUser($this->makePerson(1), isEditor: false)));
    }

    // -------------------------------------------------------------------------
    // Read: public articles
    // -------------------------------------------------------------------------

    public function testReadIsGrantedToAnonymousOnPublishedPublicArticle(): void
    {
        // Forbidding helper: the test fails if the service queries the groups
        $denial = $this->denial(
            $this->makeArticle(),
            $this->makeConnectedUser(person: null),
            $this->makeForbiddingHelper()
        );

        $this->assertNull($denial);
    }

    public function testAuthorizeReadReturnsTheSameArticleOnSuccess(): void
    {
        $article = $this->makeArticle();

        $result = $this->makeService()->authorizeRead($article, $this->makeConnectedUser(person: null));

        $this->assertSame($article, $result->match(
            onOk: static fn(mixed $value): mixed => $value,
            onFail: static fn(ArticleAccess $reason): mixed => null,
        ));
    }

    public function testCanReadMatchesTheDenialOfAuthorizeRead(): void
    {
        $service = $this->makeService();
        $public = new ArticleAuthorizationRow(Id: 1, CreatedBy: 10, PublishedBy: 1, IdGroup: null, OnlyForMembers: false);
        $membersOnly = new ArticleAuthorizationRow(Id: 1, CreatedBy: 10, PublishedBy: 1, IdGroup: null, OnlyForMembers: true);
        $anonymous = $this->makeConnectedUser(person: null);

        $this->assertTrue($service->canRead($public, $anonymous));
        $this->assertFalse($service->canRead($membersOnly, $anonymous));
    }

    // -------------------------------------------------------------------------
    // Read: members-only articles
    // -------------------------------------------------------------------------

    public function testReadRequiresLoginForAnonymousOnMembersOnlyArticle(): void
    {
        $denial = $this->denial(
            $this->makeArticle(onlyForMembers: true),
            $this->makeConnectedUser(person: null)
        );

        $this->assertSame(ArticleAccess::LoginRequired, $denial);
    }

    public function testReadIsGrantedToAnyMemberOnMembersOnlyArticleWithoutGroup(): void
    {
        // Forbidding helper: the test fails if the service queries the groups
        $denial = $this->denial(
            $this->makeArticle(onlyForMembers: true),
            $this->makeConnectedUser($this->makePerson(42)),
            $this->makeForbiddingHelper()
        );

        $this->assertNull($denial);
    }

    // -------------------------------------------------------------------------
    // Read: group articles
    // -------------------------------------------------------------------------

    public function testReadIsGrantedWhenMemberBelongsToTheGroup(): void
    {
        $pdo = $this->makeSchemaPdo();
        $this->givenMemberInGroups($pdo, 42, [5]);

        $denial = $this->denial(
            $this->makeArticle(idGroup: 5),
            $this->makeConnectedUser($this->makePerson(42)),
            $this->makeHelper($pdo)
        );

        $this->assertNull($denial);
    }

    public function testReadIsForbiddenWhenMemberIsNotInTheGroup(): void
    {
        $pdo = $this->makeSchemaPdo();
        $this->givenMemberInGroups($pdo, 42, [7]);

        $denial = $this->denial(
            $this->makeArticle(idGroup: 5),
            $this->makeConnectedUser($this->makePerson(42)),
            $this->makeHelper($pdo)
        );

        $this->assertSame(ArticleAccess::Forbidden, $denial);
    }

    public function testReadRequiresLoginForAnonymousOnGroupArticleEvenIfNotMembersOnly(): void
    {
        // Forbidding helper: the test fails if the service queries the groups
        $denial = $this->denial(
            $this->makeArticle(idGroup: 5, onlyForMembers: false),
            $this->makeConnectedUser(person: null),
            $this->makeForbiddingHelper()
        );

        $this->assertSame(ArticleAccess::LoginRequired, $denial);
    }

    // -------------------------------------------------------------------------
    // Read: drafts
    // -------------------------------------------------------------------------

    public function testDraftIsReadableByItsAuthorWithoutLoadingGroups(): void
    {
        // Forbidding helper: the test fails if the service queries the groups
        $denial = $this->denial(
            $this->makeArticle(createdBy: 42, publishedBy: null, idGroup: 5, onlyForMembers: true),
            $this->makeConnectedUser($this->makePerson(42)),
            $this->makeForbiddingHelper()
        );

        $this->assertNull($denial);
    }

    public function testDraftIsReadableByAnEditor(): void
    {
        $denial = $this->denial(
            $this->makeArticle(createdBy: 10, publishedBy: null),
            $this->makeConnectedUser($this->makePerson(42), isEditor: true)
        );

        $this->assertNull($denial);
    }

    public function testDraftIsForbiddenToAnotherMember(): void
    {
        $denial = $this->denial(
            $this->makeArticle(createdBy: 10, publishedBy: null),
            $this->makeConnectedUser($this->makePerson(42))
        );

        $this->assertSame(ArticleAccess::Forbidden, $denial);
    }

    public function testDraftRequiresLoginForAnonymous(): void
    {
        $denial = $this->denial(
            $this->makeArticle(publishedBy: null),
            $this->makeConnectedUser(person: null)
        );

        $this->assertSame(ArticleAccess::LoginRequired, $denial);
    }

    // -------------------------------------------------------------------------
    // Survey / order results
    // -------------------------------------------------------------------------

    public function testSurveyResultsAreHiddenWhenThereIsNoSurvey(): void
    {
        $result = $this->makeService()->canReadSurveyResults(
            null,
            $this->makeArticle(createdBy: 42),
            $this->makeConnectedUser($this->makePerson(42))
        );

        $this->assertFalse($result);
    }

    public function testSurveyResultsAreHiddenFromAnonymous(): void
    {
        $result = $this->makeService()->canReadSurveyResults(
            $this->makeClosing('all', self::PAST_DATE),
            $this->makeArticle(),
            $this->makeConnectedUser(person: null)
        );

        $this->assertFalse($result);
    }

    public function testSurveyResultsAreVisibleToTheArticleOwnerWithoutQueryingReplies(): void
    {
        // Forbidding helper: the test fails if the service queries the replies
        $result = $this->makeService($this->makeForbiddingHelper())->canReadSurveyResults(
            $this->makeClosing('voters', self::FUTURE_DATE),
            $this->makeArticle(createdBy: 42),
            $this->makeConnectedUser($this->makePerson(42))
        );

        $this->assertTrue($result);
    }

    public function testSurveyResultsWithVisibilityAllAreVisibleToAnyMember(): void
    {
        // Forbidding helper: the test fails if the service queries the replies
        $result = $this->makeService($this->makeForbiddingHelper())->canReadSurveyResults(
            $this->makeClosing('all', self::FUTURE_DATE),
            $this->makeArticle(createdBy: 10),
            $this->makeConnectedUser($this->makePerson(42))
        );

        $this->assertTrue($result);
    }

    public function testSurveyResultsWithVisibilityAllAfterClosingDependOnTheClosingDate(): void
    {
        $service = $this->makeService();
        $article = $this->makeArticle(createdBy: 10);
        $member = $this->makeConnectedUser($this->makePerson(42));

        $this->assertTrue($service->canReadSurveyResults($this->makeClosing('allAfterClosing', self::PAST_DATE), $article, $member));
        $this->assertFalse($service->canReadSurveyResults($this->makeClosing('allAfterClosing', self::FUTURE_DATE), $article, $member));
    }

    public function testSurveyResultsWithVisibilityVotersDependOnParticipation(): void
    {
        $article = $this->makeArticle(createdBy: 10);
        $member = $this->makeConnectedUser($this->makePerson(42));
        $survey = $this->makeClosing('voters', self::FUTURE_DATE); // survey Id = 1

        $pdo = $this->makeSchemaPdo();

        // No reply yet
        $this->assertFalse($this->makeService($this->makeHelper($pdo))->canReadSurveyResults($survey, $article, $member));

        // Person 42 replied to survey 1
        $this->givenSurveyReply($pdo, surveyId: 1, personId: 42);
        $this->assertTrue($this->makeService($this->makeHelper($pdo))->canReadSurveyResults($survey, $article, $member));
    }

    public function testSurveyResultsWithVisibilityVotersAfterClosingNeedParticipationAndClosing(): void
    {
        $article = $this->makeArticle(createdBy: 10);
        $member = $this->makeConnectedUser($this->makePerson(42));

        $pdo = $this->makeSchemaPdo();
        $this->givenSurveyReply($pdo, surveyId: 1, personId: 42);

        $this->assertTrue($this->makeService($this->makeHelper($pdo))->canReadSurveyResults(
            $this->makeClosing('votersAfterClosing', self::PAST_DATE),
            $article,
            $member
        ));

        // Not closed yet: the replies are not even queried
        $this->assertFalse($this->makeService($this->makeForbiddingHelper())->canReadSurveyResults(
            $this->makeClosing('votersAfterClosing', self::FUTURE_DATE),
            $article,
            $member
        ));
    }

    public function testSurveyResultsWithUnknownVisibilityAreHidden(): void
    {
        $result = $this->makeService()->canReadSurveyResults(
            $this->makeClosing('somethingElse', self::PAST_DATE),
            $this->makeArticle(createdBy: 10),
            $this->makeConnectedUser($this->makePerson(42))
        );

        $this->assertFalse($result);
    }

    public function testOrderResultsUseTheOrderRepliesAndVocabulary(): void
    {
        $article = $this->makeArticle(createdBy: 10);
        $member = $this->makeConnectedUser($this->makePerson(42));

        // Only an order reply exists: no survey reply that could make the test pass by mistake
        $pdo = $this->makeSchemaPdo();
        $this->givenOrderReply($pdo, orderId: 1, personId: 42);
        $service = $this->makeService($this->makeHelper($pdo));

        $this->assertTrue($service->canReadOrderResults(
            $this->makeClosing('orderers', self::FUTURE_DATE),
            $article,
            $member
        ));

        // 'voters' is a survey vocabulary word: it means nothing for an order
        $this->assertFalse($service->canReadOrderResults(
            $this->makeClosing('voters', self::FUTURE_DATE),
            $article,
            $member
        ));
    }

    public function testOrderResultsWithOrderersAfterClosingNeedClosing(): void
    {
        $article = $this->makeArticle(createdBy: 10);
        $member = $this->makeConnectedUser($this->makePerson(42));

        $pdo = $this->makeSchemaPdo();
        $this->givenOrderReply($pdo, orderId: 1, personId: 42);
        $service = $this->makeService($this->makeHelper($pdo));

        $this->assertTrue($service->canReadOrderResults($this->makeClosing('orderersAfterClosing', self::PAST_DATE), $article, $member));
        $this->assertFalse($service->canReadOrderResults($this->makeClosing('orderersAfterClosing', self::FUTURE_DATE), $article, $member));
    }

    public function testOrderResultsAreHiddenWhenThereIsNoOrder(): void
    {
        $result = $this->makeService()->canReadOrderResults(
            null,
            $this->makeArticle(createdBy: 42),
            $this->makeConnectedUser($this->makePerson(42))
        );

        $this->assertFalse($result);
    }

    // -------------------------------------------------------------------------
    // Read: editors on published group articles
    // -------------------------------------------------------------------------

    public function testPublishedGroupArticleIsReadableByAnEditorWithoutLoadingGroups(): void
    {
        // Forbidding helper: the test fails if the service queries the groups
        $denial = $this->denial(
            $this->makeArticle(createdBy: 10, publishedBy: 1, idGroup: 5, onlyForMembers: true),
            $this->makeConnectedUser($this->makePerson(42), isEditor: true),
            $this->makeForbiddingHelper()
        );

        $this->assertNull($denial);
    }

    // -------------------------------------------------------------------------
    // Survey results: closing boundary and participation scope
    // -------------------------------------------------------------------------

    public function testSurveyResultsWithVisibilityAllAfterClosingAreHiddenOnTheClosingDay(): void
    {
        // A survey closing today is not closed yet: the comparison is strict
        $result = $this->makeService()->canReadSurveyResults(
            $this->makeClosing('allAfterClosing', date('Y-m-d')),
            $this->makeArticle(createdBy: 10),
            $this->makeConnectedUser($this->makePerson(42))
        );

        $this->assertFalse($result);
    }

    public function testSurveyResultsWithVisibilityVotersAfterClosingAreHiddenFromNonVotersEvenWhenClosed(): void
    {
        $result = $this->makeService()->canReadSurveyResults(
            $this->makeClosing('votersAfterClosing', self::PAST_DATE),
            $this->makeArticle(createdBy: 10),
            $this->makeConnectedUser($this->makePerson(42))
        );

        $this->assertFalse($result);
    }

    public function testSurveyParticipationIsScopedToTheSurveyAndToThePerson(): void
    {
        $pdo = $this->makeSchemaPdo();
        $this->givenSurveyReply($pdo, surveyId: 2, personId: 42); // another survey
        $this->givenSurveyReply($pdo, surveyId: 1, personId: 43); // another person

        $result = $this->makeService($this->makeHelper($pdo))->canReadSurveyResults(
            $this->makeClosing('voters', self::FUTURE_DATE), // survey Id = 1
            $this->makeArticle(createdBy: 10),
            $this->makeConnectedUser($this->makePerson(42))
        );

        $this->assertFalse($result);
    }

    // -------------------------------------------------------------------------
    // Order results: same rules, order vocabulary
    // -------------------------------------------------------------------------

    public function testOrderResultsAreHiddenFromAnonymous(): void
    {
        $result = $this->makeService()->canReadOrderResults(
            $this->makeClosing('all', self::PAST_DATE),
            $this->makeArticle(),
            $this->makeConnectedUser(person: null)
        );

        $this->assertFalse($result);
    }

    public function testOrderResultsAreVisibleToTheArticleOwnerWithoutQueryingReplies(): void
    {
        // Forbidding helper: the test fails if the service queries the replies
        $result = $this->makeService($this->makeForbiddingHelper())->canReadOrderResults(
            $this->makeClosing('orderers', self::FUTURE_DATE),
            $this->makeArticle(createdBy: 42),
            $this->makeConnectedUser($this->makePerson(42))
        );

        $this->assertTrue($result);
    }

    public function testOrderResultsWithOrderersAreHiddenFromNonOrderers(): void
    {
        $result = $this->makeService()->canReadOrderResults(
            $this->makeClosing('orderers', self::FUTURE_DATE),
            $this->makeArticle(createdBy: 10),
            $this->makeConnectedUser($this->makePerson(42))
        );

        $this->assertFalse($result);
    }

    public function testOrderResultsWithOrderersAfterClosingAreHiddenFromNonOrderersEvenWhenClosed(): void
    {
        $result = $this->makeService()->canReadOrderResults(
            $this->makeClosing('orderersAfterClosing', self::PAST_DATE),
            $this->makeArticle(createdBy: 10),
            $this->makeConnectedUser($this->makePerson(42))
        );

        $this->assertFalse($result);
    }

    public function testOrderParticipationIsScopedToTheOrderAndToThePerson(): void
    {
        $pdo = $this->makeSchemaPdo();
        $this->givenOrderReply($pdo, orderId: 2, personId: 42); // another order
        $this->givenOrderReply($pdo, orderId: 1, personId: 43); // another person

        $result = $this->makeService($this->makeHelper($pdo))->canReadOrderResults(
            $this->makeClosing('orderers', self::FUTURE_DATE), // order Id = 1
            $this->makeArticle(createdBy: 10),
            $this->makeConnectedUser($this->makePerson(42))
        );

        $this->assertFalse($result);
    }
}
