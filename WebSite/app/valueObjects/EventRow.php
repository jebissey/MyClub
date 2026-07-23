<?php

declare(strict_types=1);

namespace app\valueObjects;

/**
 * Représentation fortement typée d'un événement.
 *
 * @phpstan-type EventRowShape object{
 *     id: int|string,
 *     summary: string,
 *     description?: string|null,
 *     fullDateTime: string,
 *     duration?: string|null,
 *     location?: string|null,
 *     eventType?: string|null,
 *     groupName?: string|null,
 *     audience?: string|null,
 *     attributes?: list<EventAttributeRow>
 * }
 *
 * @phpstan-type EventArrayShape array{
 *     id: int|string,
 *     summary: string,
 *     description?: string|null,
 *     fullDateTime: string,
 *     duration?: string|null,
 *     location?: string|null,
 *     eventType?: string|null,
 *     groupName?: string|null,
 *     audience?: string|null,
 *     attributes?: list<EventAttributeRow>
 * }
 */
final readonly class EventRow extends AbstractValueObject
{
    /**
     * @param list<EventAttributeRow> $attributes
     */
    public function __construct(
        public int $id,
        public string $summary,
        public ?string $description,
        public string $fullDateTime,
        public ?string $duration,
        public ?string $location,
        public ?string $eventType,
        public ?string $groupName,
        public ?string $audience,
        public array $attributes = [],
    ) {
    }

    /** @param EventRowShape $row */
    public static function fromStdClass(object $row): self
    {
        return new self(
            id: (int) $row->id,
            summary: $row->summary,
            description: $row->description ?? null,
            fullDateTime: $row->fullDateTime,
            duration: $row->duration ?? null,
            location: $row->location ?? null,
            eventType: $row->eventType ?? null,
            groupName: $row->groupName ?? null,
            audience: $row->audience ?? null,
            attributes: $row->attributes ?? [],
        );
    }

    /**
     * @return array{
     *     id:int, summary:string, description:?string, fullDateTime:string,
     *     duration:?string, location:?string, eventType:?string, groupName:?string,
     *     audience:?string, attributes:list<EventAttributeRow>
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'summary' => $this->summary,
            'description' => $this->description,
            'fullDateTime' => $this->fullDateTime,
            'duration' => $this->duration,
            'location' => $this->location,
            'eventType' => $this->eventType,
            'groupName' => $this->groupName,
            'audience' => $this->audience,
            'attributes' => $this->attributes,
        ];
    }

    /** @param EventArrayShape $row */
    public static function fromArray(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            summary: $row['summary'],
            description: $row['description'] ?? null,
            fullDateTime: $row['fullDateTime'],
            duration: $row['duration'] ?? null,
            location: $row['location'] ?? null,
            eventType: $row['eventType'] ?? null,
            groupName: $row['groupName'] ?? null,
            audience: $row['audience'] ?? null,
            attributes: $row['attributes'] ?? [],
        );
    }
}
