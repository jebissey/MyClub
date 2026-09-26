<?php

declare(strict_types=1);

namespace app\modules\Common\services;

use app\modules\Common\interfaces\RecipientResolverInterface;
use app\models\Data;
use app\modules\Notifications\services\ArticleRecipientResolver;
use app\modules\Notifications\services\EventRecipientResolver;
use app\modules\Notifications\services\GroupRecipientResolver;
use app\modules\Common\valueObjects\MessageContext;

final class MessageRecipientService
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
     * @return array<int, int>
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
            /** @var array<string, mixed> $preferences */
            $preferences = json_decode(
                $member->Notifications ?? '{}',
                true
            ) ?? [];

            if ($preferences === []) {
                continue;
            }

            foreach ($this->resolvers as $resolver) {
                if (
                    $resolver->supports($context)
                    && $resolver->shouldNotify(
                        $context,
                        $member->Id,
                        $preferences
                    )
                ) {
                    $recipients[] = $member->Id;
                    break;
                }
            }
        }

        return $recipients;
    }
}
