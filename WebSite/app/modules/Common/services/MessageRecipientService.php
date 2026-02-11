<?php

declare(strict_types=1);

namespace app\modules\Common\services;

use app\interfaces\RecipientResolverInterface;
use app\models\DataHelper;
use app\notifications\ArticleRecipientResolver;
use app\notifications\EventRecipientResolver;
use app\notifications\GroupRecipientResolver;
use app\valueObjects\MessageContext;

class MessageRecipientService
{
    /** @var RecipientResolverInterface[] */
    private array $resolvers;

    public function __construct(private DataHelper $dataHelper)
    {
        $this->resolvers = [
            new ArticleRecipientResolver(),
            new EventRecipientResolver(),
            new GroupRecipientResolver(),
        ];
    }

    public function getRecipientsForContext(MessageContext $context): array
    {
        $persons = $this->dataHelper->gets(
            'Person',
            ['Inactivated' => 0],
            'Id, Notifications'
        );
        $recipients = [];
        foreach ($persons as $person) {
            $preferences = json_decode(
                $person->Notifications ?? '{}',
                true
            );
            if ($preferences === []) {
                continue;
            }
            foreach ($this->resolvers as $resolver) {
                if (
                    $resolver->supports($context)
                    && $resolver->shouldNotify(
                        $context,
                        $person->Id,
                        $preferences
                    )
                ) {
                    $recipients[] = $person->Id;
                    break;
                }
            }
        }
        return $recipients;
    }
}
