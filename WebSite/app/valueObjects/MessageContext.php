<?php

declare(strict_types=1);

namespace app\valueObjects;

readonly class MessageContext
{
    public function __construct(
        public ?int $articleId = null,
        public ?int $articleAuthorId = null,

        public ?int $eventId = null,
        public ?int $eventCreatorId = null,

        public ?int $groupId = null
    ) {}

    public function isArticle(): bool
    {
        return $this->articleId !== null;
    }

    public function isEvent(): bool
    {
        return $this->eventId !== null;
    }

    public function isGroup(): bool
    {
        return $this->groupId !== null;
    }

    public function isArticleAuthor(int $personId): bool
    {
        return $this->articleAuthorId === $personId;
    }

    public function isEventCreator(int $personId): bool
    {
        return $this->eventCreatorId === $personId;
    }
}
