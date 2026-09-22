<?php

declare(strict_types=1);

namespace tests\modules\Article\services;

use PHPUnit\Framework\TestCase;
use app\helpers\ConnectedUser;
use app\models\AuthorizationDataHelper;
use app\models\DataHelper;
use app\modules\Article\services\ArticleAuthorizationService;
use app\modules\Article\valueObjects\ArticleAccessRow;
use app\modules\Article\valueObjects\ArticleOwnershipRow;

final class ArticleAuthorizationServiceTest extends TestCase
{
    private function makeService(
        ?DataHelper $dataHelper = null,
        ?AuthorizationDataHelper $authorizationDataHelper = null
    ): ArticleAuthorizationService {
        return new ArticleAuthorizationService(
            $dataHelper ?? $this->createStub(DataHelper::class),
            $authorizationDataHelper ?? $this->createStub(AuthorizationDataHelper::class)
        );
    }

    private function makeConnectedUser(
        ?object $person = null,
        bool $isEditor = false
    ): ConnectedUser {
        $user = $this->createMock(ConnectedUser::class);
        $user->person = $person;
        $user->method('isEditor')->willReturn($isEditor);
        return $user;
    }

    private function makePerson(int $id): object
    {
        return (object) ['Id' => $id];
    }

    // -------------------------------------------------------------------------
    // canEdit / canDelete
    // -------------------------------------------------------------------------

    public function testCanEditReturnsFalseWhenUserHasNoPerson(): void
    {
        $user = $this->makeConnectedUser(person: null);

        $result = $this->makeService()->canEdit(1, $user);

        $this->assertFalse($result);
    }

    public function testCanEditReturnsFalseWhenArticleDoesNotExist(): void
    {
        $dataHelper = $this->createMock(DataHelper::class);
        $dataHelper->expects($this->once())
            ->method('get')
            ->with('Article', ['Id' => 99], 'CreatedBy')
            ->willReturn(false);

        $user = $this->makeConnectedUser($this->makePerson(42));

        $result = $this->makeService($dataHelper)->canEdit(99, $user);

        $this->assertFalse($result);
    }

    public function testCanEditReturnsTrueWhenUserIsAuthor(): void
    {
        $dataHelper = $this->createMock(DataHelper::class);
        $dataHelper->method('get')->willReturn((object) ['CreatedBy' => 42]);

        $user = $this->makeConnectedUser($this->makePerson(42));

        $result = $this->makeService($dataHelper)->canEdit(1, $user);

        $this->assertTrue($result);
    }

    public function testCanEditReturnsFalseWhenUserIsNotAuthor(): void
    {
        $dataHelper = $this->createMock(DataHelper::class);
        $dataHelper->method('get')->willReturn((object) ['CreatedBy' => 10]);

        $user = $this->makeConnectedUser($this->makePerson(42));

        $result = $this->makeService($dataHelper)->canEdit(1, $user);

        $this->assertFalse($result);
    }

    public function testCanDeleteDelegatesToCanEdit(): void
    {
        $dataHelper = $this->createMock(DataHelper::class);
        $dataHelper->method('get')->willReturn((object) ['CreatedBy' => 42]);

        $user = $this->makeConnectedUser($this->makePerson(42));

        $service = $this->makeService($dataHelper);

        $this->assertTrue($service->canDelete(1, $user));
        $this->assertFalse($service->canDelete(1, $this->makeConnectedUser($this->makePerson(99))));
    }

    // -------------------------------------------------------------------------
    // canPublish
    // -------------------------------------------------------------------------

    public function testCanPublishReturnsTrueWhenUserIsAuthor(): void
    {
        $dataHelper = $this->createMock(DataHelper::class);
        $dataHelper->method('get')->willReturn((object) ['CreatedBy' => 42]);

        $user = $this->makeConnectedUser($this->makePerson(42), isEditor: false);

        $result = $this->makeService($dataHelper)->canPublish(1, $user);

        $this->assertTrue($result);
    }

    public function testCanPublishReturnsTrueWhenUserIsEditorEvenIfNotAuthor(): void
    {
        $dataHelper = $this->createMock(DataHelper::class);
        $dataHelper->method('get')->willReturn((object) ['CreatedBy' => 10]);

        $user = $this->makeConnectedUser($this->makePerson(42), isEditor: true);

        $result = $this->makeService($dataHelper)->canPublish(1, $user);

        $this->assertTrue($result);
    }

    public function testCanPublishReturnsFalseWhenUserIsNeitherAuthorNorEditor(): void
    {
        $dataHelper = $this->createMock(DataHelper::class);
        $dataHelper->method('get')->willReturn((object) ['CreatedBy' => 10]);

        $user = $this->makeConnectedUser($this->makePerson(42), isEditor: false);

        $result = $this->makeService($dataHelper)->canPublish(1, $user);

        $this->assertFalse($result);
    }

    public function testCanPublishReturnsFalseWhenUserHasNoPersonAndIsNotEditor(): void
    {
        $user = $this->makeConnectedUser(person: null, isEditor: false);

        $result = $this->makeService()->canPublish(1, $user);

        $this->assertFalse($result);
    }

    public function testCanPublishReturnsTrueWhenUserHasNoPersonButIsEditor(): void
    {
        // canEdit returns false (no person), but isEditor() is true
        $user = $this->makeConnectedUser(person: null, isEditor: true);

        $result = $this->makeService()->canPublish(1, $user);

        $this->assertTrue($result);
    }

    // -------------------------------------------------------------------------
    // canRead
    // -------------------------------------------------------------------------

    public function testCanReadReturnsFalseWhenArticleDoesNotExist(): void
    {
        $dataHelper = $this->createMock(DataHelper::class);
        $dataHelper->expects($this->once())
            ->method('get')
            ->with('Article', ['Id' => 99], 'OnlyForMembers, IdGroup')
            ->willReturn(false);

        $result = $this->makeService($dataHelper)->canRead(99, $this->makeConnectedUser());

        $this->assertFalse($result);
    }

    public function testCanReadReturnsTrueWhenArticleIsPublic(): void
    {
        $dataHelper = $this->createMock(DataHelper::class);
        $dataHelper->method('get')->willReturn((object) [
            'OnlyForMembers' => 0,
            'IdGroup'        => null,
        ]);

        // Even without a connected person
        $result = $this->makeService($dataHelper)->canRead(1, $this->makeConnectedUser(person: null));

        $this->assertTrue($result);
    }

    public function testCanReadReturnsFalseWhenArticleIsMembersOnlyAndUserHasNoPerson(): void
    {
        $dataHelper = $this->createMock(DataHelper::class);
        $dataHelper->method('get')->willReturn((object) [
            'OnlyForMembers' => 1,
            'IdGroup'        => null,
        ]);

        $result = $this->makeService($dataHelper)->canRead(1, $this->makeConnectedUser(person: null));

        $this->assertFalse($result);
    }

    public function testCanReadReturnsTrueWhenArticleIsMembersOnlyAndAuthorizationAllows(): void
    {
        $dataHelper = $this->createMock(DataHelper::class);
        $dataHelper->method('get')->willReturn((object) [
            'OnlyForMembers' => 1,
            'IdGroup'        => 5,
        ]);

        $authHelper = $this->createMock(AuthorizationDataHelper::class);
        $authHelper->expects($this->once())
            ->method('getArticle')
            ->with(1, $this->isInstanceOf(ConnectedUser::class))
            ->willReturn((object) ['Id' => 1]); // any truthy value

        $user = $this->makeConnectedUser($this->makePerson(42));

        $result = $this->makeService($dataHelper, $authHelper)->canRead(1, $user);

        $this->assertTrue($result);
    }

    public function testCanReadReturnsFalseWhenArticleIsMembersOnlyAndAuthorizationDenies(): void
    {
        $dataHelper = $this->createMock(DataHelper::class);
        $dataHelper->method('get')->willReturn((object) [
            'OnlyForMembers' => 1,
            'IdGroup'        => 5,
        ]);

        $authHelper = $this->createMock(AuthorizationDataHelper::class);
        $authHelper->method('getArticle')->willReturn(false);

        $user = $this->makeConnectedUser($this->makePerson(42));

        $result = $this->makeService($dataHelper, $authHelper)->canRead(1, $user);

        $this->assertFalse($result);
    }

    public function testCanReadTreatsTruthyOnlyForMembersAsRestricted(): void
    {
        // OnlyForMembers peut arriver en string "1" ou bool true
        $dataHelper = $this->createMock(DataHelper::class);
        $dataHelper->method('get')->willReturn((object) [
            'OnlyForMembers' => '1',
            'IdGroup'        => null,
        ]);

        $result = $this->makeService($dataHelper)->canRead(1, $this->makeConnectedUser(person: null));

        $this->assertFalse($result);
    }
}
