<?php

declare(strict_types=1);

namespace app\modules\Article\valueObjects;

use app\modules\Common\valueObjects\EmailMessage;

/**
 * The alert e-mail to send for a new article, and how many people it targets.
 */
final readonly class ArticleAlertInfo
{
    public function __construct(
        public EmailMessage $message,
        public int $recipientCount,
    ) {
    }
}
