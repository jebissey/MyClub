<?php

declare(strict_types=1);

namespace tests\modules\Common\services;

use PHPUnit\Framework\TestCase;
use app\models\DataHelper;
use app\modules\Common\services\MessageRecipientService;
use app\modules\Common\valueObjects\MessageContext;
use app\modules\Common\interfaces\RecipientResolverInterface;
use app\modules\Notifications\ArticleRecipientResolver;
use app\modules\Notifications\EventRecipientResolver;
use app\modules\Notifications\GroupRecipientResolver;

final class MessageRecipientServiceTest extends TestCase
{
    private function makeService(?DataHelper $dataHelper = null): MessageRecipientService
    {
        return new MessageRecipientService(
            $dataHelper ?? $this->createStub(DataHelper::class)
        );
    }

    private function makeMember(int $id, ?string $notificationsJson): object
    {
        return (object) [
            'Id'            => $id,
            'Notifications' => $notificationsJson,
        ];
    }

    private function makeArticleContext(
        int $articleId = 1,
        ?int $articleAuthorId = null
    ): MessageContext {
        return new MessageContext(
            articleId: $articleId,
            articleAuthorId: $articleAuthorId
        );
    }

    private function makeEventContext(
        int $eventId = 1,
        ?int $eventCreatorId = null
    ): MessageContext {
        return new MessageContext(
            eventId: $eventId,
            eventCreatorId: $eventCreatorId
        );
    }

    private function makeGroupContext(int $groupId = 1): MessageContext
    {
        return new MessageContext(groupId: $groupId);
    }

    // -------------------------------------------------------------------------
    // getRecipientsForContext – empty / no candidates
    // -------------------------------------------------------------------------

    public function testGetRecipientsReturnsEmptyWhenNoMembers(): void
    {
        $dataHelper = $this->createMock(DataHelper::class);
        $dataHelper->expects($this->once())
            ->method('gets')
            ->with('Member', ['Inactivated' => 0], 'Id, Notifications')
            ->willReturn([]);

        $result = $this->makeService($dataHelper)->getRecipientsForContext(
            $this->makeArticleContext()
        );

        $this->assertSame([], $result);
    }

    public function testGetRecipientsSkipsMembersWithEmptyPreferences(): void
    {
        $dataHelper = $this->createMock(DataHelper::class);
        $dataHelper->method('gets')->willReturn([
            $this->makeMember(1, null),
            $this->makeMember(2, '{}'),
            $this->makeMember(3, ''),
        ]);

        $result = $this->makeService($dataHelper)->getRecipientsForContext(
            $this->makeArticleContext()
        );

        $this->assertSame([], $result);
    }

    public function testGetRecipientsSkipsMembersWithInvalidJsonPreferences(): void
    {
        $dataHelper = $this->createMock(DataHelper::class);
        $dataHelper->method('gets')->willReturn([
            $this->makeMember(1, 'not-json'),
        ]);

        $result = $this->makeService($dataHelper)->getRecipientsForContext(
            $this->makeArticleContext()
        );

        $this->assertSame([], $result);
    }

    // -------------------------------------------------------------------------
    // getRecipientsForContext – resolver matching
    // -------------------------------------------------------------------------

    public function testGetRecipientsReturnsMemberWhenAResolverMatches(): void
    {
        $preferences = json_encode(['article' => true]);

        $dataHelper = $this->createMock(DataHelper::class);
        $dataHelper->method('gets')->willReturn([
            $this->makeMember(42, $preferences),
        ]);

        $result = $this->makeService($dataHelper)->getRecipientsForContext(
            $this->makeArticleContext(articleId: 10)
        );

        $this->assertIsArray($result);
        foreach ($result as $id) {
            $this->assertIsInt($id);
        }
    }

    public function testGetRecipientsDoesNotDuplicateMemberWhenMultipleResolversMatch(): void
    {
        $preferences = json_encode([
            'article' => true,
            'event'   => true,
            'group'   => true,
        ]);

        $dataHelper = $this->createMock(DataHelper::class);
        $dataHelper->method('gets')->willReturn([
            $this->makeMember(7, $preferences),
        ]);

        $result = $this->makeService($dataHelper)->getRecipientsForContext(
            $this->makeArticleContext()
        );

        $this->assertIsArray($result);
        $this->assertSame($result, array_unique($result));
    }

    public function testGetRecipientsReturnsMultipleDistinctMembers(): void
    {
        $preferences = json_encode(['article' => true]);

        $dataHelper = $this->createMock(DataHelper::class);
        $dataHelper->method('gets')->willReturn([
            $this->makeMember(10, $preferences),
            $this->makeMember(20, $preferences),
            $this->makeMember(30, '{}'), // skipped
        ]);

        $result = $this->makeService($dataHelper)->getRecipientsForContext(
            $this->makeArticleContext(articleId: 5)
        );

        $this->assertIsArray($result);
        $this->assertSame($result, array_unique($result));
        foreach ($result as $id) {
            $this->assertContains($id, [10, 20]);
        }
    }

    // -------------------------------------------------------------------------
    // Constructor / resolvers registration
    // -------------------------------------------------------------------------

    public function testServiceInstantiatesWithDefaultResolvers(): void
    {
        $service = $this->makeService();

        $reflection = new \ReflectionClass($service);
        $property   = $reflection->getProperty('resolvers');
        $property->setAccessible(true);
        /** @var RecipientResolverInterface[] $resolvers */
        $resolvers = $property->getValue($service);

        $this->assertCount(3, $resolvers);
        $this->assertInstanceOf(ArticleRecipientResolver::class, $resolvers[0]);
        $this->assertInstanceOf(EventRecipientResolver::class, $resolvers[1]);
        $this->assertInstanceOf(GroupRecipientResolver::class, $resolvers[2]);
    }

    // -------------------------------------------------------------------------
    // Edge cases around DataHelper
    // -------------------------------------------------------------------------

    public function testGetRecipientsCallsDataHelperWithCorrectArguments(): void
    {
        $dataHelper = $this->createMock(DataHelper::class);
        $dataHelper->expects($this->once())
            ->method('gets')
            ->with(
                'Member',
                ['Inactivated' => 0],
                'Id, Notifications'
            )
            ->willReturn([]);

        $this->makeService($dataHelper)->getRecipientsForContext(
            $this->makeArticleContext()
        );
    }

    // -------------------------------------------------------------------------
    // Context helpers usage (smoke tests)
    // -------------------------------------------------------------------------

    public function testGetRecipientsWithEventContext(): void
    {
        $dataHelper = $this->createMock(DataHelper::class);
        $dataHelper->method('gets')->willReturn([]);

        $result = $this->makeService($dataHelper)->getRecipientsForContext(
            $this->makeEventContext(eventId: 99, eventCreatorId: 5)
        );

        $this->assertSame([], $result);
    }

    public function testGetRecipientsWithGroupContext(): void
    {
        $dataHelper = $this->createMock(DataHelper::class);
        $dataHelper->method('gets')->willReturn([]);

        $result = $this->makeService($dataHelper)->getRecipientsForContext(
            $this->makeGroupContext(groupId: 3)
        );

        $this->assertSame([], $result);
    }
}