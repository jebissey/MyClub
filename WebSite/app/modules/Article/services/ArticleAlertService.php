<?php

declare(strict_types=1);

namespace app\modules\Article\services;

use app\models\AlertRecipientDataHelper;
use app\modules\Article\valueObjects\ArticleAlertInfo;
use app\modules\Common\valueObjects\EmailMessage;

/**
 * Builds the "new article" alert e-mail. Sending it is the caller's job.
 */
final readonly class ArticleAlertService
{
    public function __construct(
        private AlertRecipientDataHelper $alertRecipientDataHelper,
        private string $baseUrl,
    ) {
    }

    /**
     * Resolves the recipients, then builds the alert.
     *
     * Side effect: AlertRecipientDataHelper::getPersonWantedToBeAlerted() records
     * one Message per recipient, whether or not the e-mail is eventually sent.
     *
     * @param callable(string): string $t translator
     */
    public function prepare(int $articleId, string $authorEmail, callable $t): ArticleAlertInfo
    {
        $recipients = $this->alertRecipientDataHelper->getPersonWantedToBeAlerted($articleId);

        return $this->compose($articleId, $authorEmail, $recipients, $t);
    }

    /**
     * Pure composition: no database access.
     *
     * @param string[] $recipients
     * @param callable(string): string $t translator
     */
    public function compose(int $articleId, string $authorEmail, array $recipients, callable $t): ArticleAlertInfo
    {
        $body =
            $t('article.email.body_intro') . "\n\n" .
            $this->baseUrl . '/article/' . $articleId . "\n\n" .
            $t('article.email.unsubscribe') . "\n" .
            $this->baseUrl . '/user/preferences';

        return new ArticleAlertInfo(
            message: new EmailMessage(
                from: $authorEmail,
                to: $authorEmail,
                subject: str_replace('{root}', $this->baseUrl, $t('article.email.new_title')),
                body: $body,
                isHtml: false,
                cc: $recipients,
            ),
            recipientCount: count($recipients),
        );
    }
}
