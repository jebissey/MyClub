<?php

declare(strict_types=1);

namespace tests\modules\Article\services;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use app\enums\Authorization;
use app\helpers\ConnectedUser;
use app\models\AuthorizationDataHelper;
use app\models\Data;
use app\modules\Article\services\ArticleAuthorizationService;
use app\modules\Article\valueObjects\ArticleAuthorizationRow;
use app\modules\Common\valueObjects\ConnectedUser as ConnectedUserVO;
use app\modules\Common\valueObjects\Person;

final class ArticleAuthorizationServiceTest extends TestCase
{
    /**
     * Construit un stub de Data (jamais DataHelper, qui est final) dont get()
     * délègue à la closure fournie — permet de contrôler la réponse et/ou de
     * capturer les arguments d'appel sans double de classe finale.
     */
    private function makeDataHelperStub(callable $getBehavior): Data
    {
        return new class($getBehavior) extends Data {
            /** @var callable */
            private $getBehavior;

            public function __construct(callable $getBehavior)
            {
                $this->getBehavior = $getBehavior;
            }

            public function get(string $table, array $where, string $fields = '*'): object|false
            {
                return ($this->getBehavior)($table, $where, $fields);
            }
        };
    }

    private function makeService(
        ?Data $dataHelper = null,
        ?AuthorizationDataHelper $authorizationDataHelper = null
    ): ArticleAuthorizationService {
        return new ArticleAuthorizationService(
            $dataHelper ?? $this->makeDataHelperStub(fn() => false),
            $authorizationDataHelper ?? $this->createStub(AuthorizationDataHelper::class)
        );
    }

    /**
     * Construit un vrai helper ConnectedUser (final) sans passer par son
     * constructeur (qui exige DataHelper/AuthorizationDataHelper/MetadataDataHelper,
     * jamais utilisés par le code testé ici) et assigne directement ses
     * propriétés publiques $person / $user avec de vraies instances des VO.
     *
     * NB: en production $person et $user sont toujours renseignés ensemble ;
     * ici on peut volontairement les découpler (ex. testCanPublish...NoPersonButIsEditor)
     * pour isoler une branche logique du service, ce qui ne reflète pas un état
     * atteignable en production mais teste correctement le || du service.
     */
    private function makeConnectedUser(
        ?Person $person = null,
        bool $isEditor = false
    ): ConnectedUser {
        $ref = new ReflectionClass(ConnectedUser::class);
        /** @var ConnectedUser $helper */
        $helper = $ref->newInstanceWithoutConstructor();

        $helper->person = $person;

        $authorizations = $isEditor ? [Authorization::Editor->value] : [];
        // ConnectedUserVO exige une Person non-nulle ; on en fournit une factice
        // quand $person est null mais qu'on veut quand même piloter isEditor().
        $helper->user = new ConnectedUserVO($person ?? $this->makePerson(0), $authorizations);

        return $helper;
    }

    private function makePerson(int $id): Person
    {
        return new Person(Id: $id, Email: "person{$id}@example.test");
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
        $calls = [];
        $dataHelper = $this->makeDataHelperStub(function (string $table, array $where, string $fields) use (&$calls) {
            $calls[] = [$table, $where, $fields];
            return false;
        });

        $user = $this->makeConnectedUser($this->makePerson(42));

        $result = $this->makeService($dataHelper)->canEdit(99, $user);

        $this->assertFalse($result);
        $this->assertSame([['Article', ['Id' => 99], 'CreatedBy']], $calls);
    }

    public function testCanEditReturnsTrueWhenUserIsAuthor(): void
    {
        $dataHelper = $this->makeDataHelperStub(fn() => (object) ['CreatedBy' => 42]);

        $user = $this->makeConnectedUser($this->makePerson(42));

        $result = $this->makeService($dataHelper)->canEdit(1, $user);

        $this->assertTrue($result);
    }

    public function testCanEditReturnsFalseWhenUserIsNotAuthor(): void
    {
        $dataHelper = $this->makeDataHelperStub(fn() => (object) ['CreatedBy' => 10]);

        $user = $this->makeConnectedUser($this->makePerson(42));

        $result = $this->makeService($dataHelper)->canEdit(1, $user);

        $this->assertFalse($result);
    }

    public function testCanDeleteDelegatesToCanEdit(): void
    {
        $dataHelper = $this->makeDataHelperStub(fn() => (object) ['CreatedBy' => 42]);

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
        $dataHelper = $this->makeDataHelperStub(fn() => (object) ['CreatedBy' => 42]);

        $user = $this->makeConnectedUser($this->makePerson(42), isEditor: false);

        $result = $this->makeService($dataHelper)->canPublish(1, $user);

        $this->assertTrue($result);
    }

    public function testCanPublishReturnsTrueWhenUserIsEditorEvenIfNotAuthor(): void
    {
        $dataHelper = $this->makeDataHelperStub(fn() => (object) ['CreatedBy' => 10]);

        $user = $this->makeConnectedUser($this->makePerson(42), isEditor: true);

        $result = $this->makeService($dataHelper)->canPublish(1, $user);

        $this->assertTrue($result);
    }

    public function testCanPublishReturnsFalseWhenUserIsNeitherAuthorNorEditor(): void
    {
        $dataHelper = $this->makeDataHelperStub(fn() => (object) ['CreatedBy' => 10]);

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
        // canEdit renvoie false (pas de personne), mais isEditor() est vrai
        $user = $this->makeConnectedUser(person: null, isEditor: true);

        $result = $this->makeService()->canPublish(1, $user);

        $this->assertTrue($result);
    }

    // -------------------------------------------------------------------------
    // canRead
    // -------------------------------------------------------------------------

    public function testCanReadReturnsFalseWhenArticleDoesNotExist(): void
    {
        $calls = [];
        $dataHelper = $this->makeDataHelperStub(function (string $table, array $where, string $fields) use (&$calls) {
            $calls[] = [$table, $where, $fields];
            return false;
        });

        $result = $this->makeService($dataHelper)->canRead(99, $this->makeConnectedUser());

        $this->assertFalse($result);
        $this->assertSame([['Article', ['Id' => 99], 'OnlyForMembers, IdGroup']], $calls);
    }

    public function testCanReadReturnsTrueWhenArticleIsPublic(): void
    {
        $dataHelper = $this->makeDataHelperStub(fn() => (object) [
            'OnlyForMembers' => 0,
            'IdGroup'        => null,
        ]);

        // Même sans personne connectée
        $result = $this->makeService($dataHelper)->canRead(1, $this->makeConnectedUser(person: null));

        $this->assertTrue($result);
    }

    public function testCanReadReturnsFalseWhenArticleIsMembersOnlyAndUserHasNoPerson(): void
    {
        $dataHelper = $this->makeDataHelperStub(fn() => (object) [
            'OnlyForMembers' => 1,
            'IdGroup'        => null,
        ]);

        $result = $this->makeService($dataHelper)->canRead(1, $this->makeConnectedUser(person: null));

        $this->assertFalse($result);
    }

    public function testCanReadReturnsTrueWhenArticleIsMembersOnlyAndAuthorizationAllows(): void
    {
        $dataHelper = $this->makeDataHelperStub(fn() => (object) [
            'OnlyForMembers' => 1,
            'IdGroup'        => 5,
        ]);

        $authHelper = $this->createMock(AuthorizationDataHelper::class);
        $authHelper->expects($this->once())
            ->method('getArticle')
            ->with(1, $this->isInstanceOf(ConnectedUser::class))
            ->willReturn(new ArticleAuthorizationRow(
                Id: 1,
                CreatedBy: 42,
                PublishedBy: null,
                IdGroup: 5,
                OnlyForMembers: true,
            ));

        $user = $this->makeConnectedUser($this->makePerson(42));

        $result = $this->makeService($dataHelper, $authHelper)->canRead(1, $user);

        $this->assertTrue($result);
    }

    public function testCanReadReturnsFalseWhenArticleIsMembersOnlyAndAuthorizationDenies(): void
    {
        $dataHelper = $this->makeDataHelperStub(fn() => (object) [
            'OnlyForMembers' => 1,
            'IdGroup'        => 5,
        ]);

        $authHelper = $this->createStub(AuthorizationDataHelper::class);
        $authHelper->method('getArticle')->willReturn(false);

        $user = $this->makeConnectedUser($this->makePerson(42));

        $result = $this->makeService($dataHelper, $authHelper)->canRead(1, $user);

        $this->assertFalse($result);
    }

    public function testCanReadTreatsTruthyOnlyForMembersAsRestricted(): void
    {
        // OnlyForMembers peut arriver en string "1" ou bool true
        $dataHelper = $this->makeDataHelperStub(fn() => (object) [
            'OnlyForMembers' => '1',
            'IdGroup'        => null,
        ]);

        $result = $this->makeService($dataHelper)->canRead(1, $this->makeConnectedUser(person: null));

        $this->assertFalse($result);
    }
}
