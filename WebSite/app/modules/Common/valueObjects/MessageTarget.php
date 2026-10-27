<?php

declare(strict_types=1);

namespace app\modules\Common\valueObjects;

final readonly class MessageTarget
{
    private function __construct(
        public ?int $articleId,
        public ?int $eventId,
        public ?int $groupId,
    ) {
    }

    public static function article(int $id): self
    {
        return new self($id, null, null);
    }

    public static function event(int $id): self
    {
        return new self(null, $id, null);
    }

    public static function group(int $id): self
    {
        return new self(null, null, $id);
    }

    /**
     * Boundary helper for raw input: returns null unless exactly one id is provided.
     */
    public static function fromIds(?int $articleId, ?int $eventId, ?int $groupId): ?self
    {
        $provided = count(array_filter(
            [$articleId, $eventId, $groupId],
            static fn (?int $id): bool => $id !== null,
        ));

        return $provided === 1 ? new self($articleId, $eventId, $groupId) : null;
    }

    /**
     * @return array{0: string, 1: int} Table name and id of the targeted record.
     */
    public function reference(): array
    {
        return match (true) {
            $this->articleId !== null => ['Article', $this->articleId],
            $this->eventId !== null => ['Event', $this->eventId],
            default => ['Group', (int) $this->groupId],
        };
    }
}
