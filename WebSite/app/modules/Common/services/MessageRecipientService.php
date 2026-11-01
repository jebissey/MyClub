<?php

declare(strict_types=1);

namespace app\modules\Common\services;

use app\helpers\To;
use app\modules\Common\interfaces\RecipientResolverInterface;
use app\models\Data;
use app\modules\Notifications\services\ArticleRecipientResolver;
use app\modules\Notifications\services\EventRecipientResolver;
use app\modules\Notifications\services\GroupRecipientResolver;
use app\modules\Common\valueObjects\MessageContext;

final readonly class MessageRecipientService
{
    /** @var RecipientResolverInterface[] */
    private array $resolvers;

    public function __construct(private Data $dataHelper)
    {
        $this->resolvers = [
            new ArticleRecipientResolver(),
            new EventRecipientResolver(),
            new GroupRecipientResolver(),
        ];
    }

    /**
     * @return list<int>
     */
    public function getRecipientsForContext(MessageContext $context): array
    {
        $members = $this->dataHelper->gets(
            'Member',
            ['Inactivated' => 0],
            'Id, Notifications'
        );

        $recipients = [];
        foreach ($members as $member) {
            $memberId = To::int($member->Id);
            $notifications = $member->Notifications;

            $decoded = is_string($notifications) && $notifications !== ''
                ? json_decode($notifications, true)
                : null;

            if (!is_array($decoded) || $decoded === []) {
                continue;
            }

            /** @var array<string, mixed> $preferences */
            $preferences = $decoded;

            foreach ($this->resolvers as $resolver) {
                if (
                    $resolver->supports($context)
                    && $resolver->shouldNotify($context, $memberId, $preferences)
                ) {
                    $recipients[] = $memberId;
                    break;
                }
            }
        }

        return $recipients;
    }
}
