<?php

declare(strict_types=1);

namespace tests\modules\Article\services;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use app\models\AlertRecipientDataHelper;
use app\modules\Article\services\ArticleAlertService;

final class ArticleAlertServiceTest extends TestCase
{
    private const BASE_URL = 'https://club.example.test';

    /**
     * AlertRecipientDataHelper is final and needs a full Application: its constructor is
     * bypassed. compose() never touches it, so no property has to be set.
     */
    private function makeService(): ArticleAlertService
    {
        /** @var AlertRecipientDataHelper $helper */
        $helper = (new ReflectionClass(AlertRecipientDataHelper::class))->newInstanceWithoutConstructor();

        return new ArticleAlertService($helper, self::BASE_URL);
    }

    /**
     * @return callable(string): string
     */
    private function makeTranslator(): callable
    {
        return static fn(string $key): string => match ($key) {
            'article.email.new_title' => 'New article on {root}',
            default => "[{$key}]",
        };
    }

    public function testTheAuthorIsBothSenderAndMainRecipient(): void
    {
        $alert = $this->makeService()->compose(12, 'author@example.test', [], $this->makeTranslator());

        $this->assertSame('author@example.test', $alert->message->from);
        $this->assertSame('author@example.test', $alert->message->to);
    }

    public function testTheInterestedPeopleAreInCopy(): void
    {
        $recipients = ['a@example.test', 'b@example.test'];

        $alert = $this->makeService()->compose(12, 'author@example.test', $recipients, $this->makeTranslator());

        $this->assertSame($recipients, $alert->message->cc);
        $this->assertSame(2, $alert->recipientCount);
    }

    public function testRecipientCountIsZeroWhenNobodyWantsTheAlert(): void
    {
        $alert = $this->makeService()->compose(12, 'author@example.test', [], $this->makeTranslator());

        $this->assertSame([], $alert->message->cc);
        $this->assertSame(0, $alert->recipientCount);
    }

    public function testSubjectReplacesTheRootPlaceholder(): void
    {
        $alert = $this->makeService()->compose(12, 'author@example.test', [], $this->makeTranslator());

        $this->assertSame('New article on https://club.example.test', $alert->message->subject);
    }

    public function testBodyLinksToTheArticleAndToThePreferences(): void
    {
        $alert = $this->makeService()->compose(12, 'author@example.test', [], $this->makeTranslator());

        $this->assertSame(
            "[article.email.body_intro]\n\n"
            . "https://club.example.test/article/12\n\n"
            . "[article.email.unsubscribe]\n"
            . 'https://club.example.test/user/preferences',
            $alert->message->body
        );
    }

    public function testTheAlertIsSentAsPlainText(): void
    {
        $alert = $this->makeService()->compose(12, 'author@example.test', [], $this->makeTranslator());

        $this->assertFalse($alert->message->isHtml);
    }
}