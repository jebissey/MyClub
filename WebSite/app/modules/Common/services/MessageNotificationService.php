<?php

declare(strict_types=1);

namespace app\modules\Common\services;

use app\enums\ApplicationError;
use app\helpers\Application;
use app\helpers\NotificationSender;
use app\models\MessageTargetDataHelper;
use app\modules\Common\valueObjects\MessageContext;
use app\modules\Common\valueObjects\MessageTarget;
use app\modules\Common\valueObjects\MessageTargetInfo;

final class MessageNotificationService
{
    public function __construct(
        private readonly Application $application,
        private readonly MessageTargetDataHelper $messageTargetDataHelper,
        private readonly MessageRecipientService $messageRecipientService,
        private readonly NotificationSender $notificationSender,
    ) {
    }

    public function notifyRecipients(MessageTarget $target, int $messageId): void
    {
        $info = $this->messageTargetDataHelper->find($target);
        if ($info === null) {
            $this->application->getErrorManager()->raise(
                ApplicationError::Error,
                "Target not found when notifying message recipients for message {$messageId}"
            );
            return;
        }

        $context = new MessageContext(
            articleId: $target->articleId,
            articleAuthorId: $info->articleAuthorId,
            eventId: $target->eventId,
            eventCreatorId: $info->eventCreatorId,
            groupId: $target->groupId
        );
        $personIds = $this->messageRecipientService->getRecipientsForContext($context);

        $this->notificationSender->sendToRecipients($personIds, [
            'title' => 'Nouveau message',
            'body'  => $this->buildBody($info),
            'data'  => [
                'url'       => "/{$info->kind}/chat/{$info->id}",
                'messageId' => $messageId,
            ],
        ]);
    }

    private function buildBody(MessageTargetInfo $info): string
    {
        return 'Un nouveau message a été ajouté ' . match ($info->kind) {
            'article' => "à l'article {$info->label}",
            'event'   => "à l'événement {$info->label} ({$info->id})",
            default   => "au groupe {$info->label}",
        };
    }
}
