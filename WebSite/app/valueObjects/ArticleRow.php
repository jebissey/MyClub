<?php

declare(strict_types=1);

namespace app\valueObjects;

/**
 * @phpstan-type ArticleRowShape object{
 *     Id: int|string,
 *     Title: string,
 *     Content: string,
 *     CreatedBy: int|string,
 *     PublishedBy?: int|string|null,
 *     IdGroup?: int|string|null,
 *     OnlyForMembers?: bool|int|null,
 *     LastUpdate: string,
 *     Timestamp?: string|null,
 *     Author?: AuthorInfo,
 *     Group?: GroupInfo
 * }
 */
final readonly class ArticleRow
{
    public function __construct(
        public int $Id,
        public string $Title,
        public string $Content,
        public int $CreatedBy,
        public ?int $PublishedBy,
        public ?int $IdGroup,
        public bool $OnlyForMembers,
        public string $LastUpdate,
        public string $Timestamp,
        public ?AuthorInfo $Author = null,
        public ?GroupInfo $Group = null,
    ) {
    }

    /**
     * @param ArticleRowShape $row
     */
    public static function fromStdClass(object $row): self
    {
        return new self(
            Id: (int)$row->Id,
            Title: $row->Title,
            Content: $row->Content,
            CreatedBy: (int)$row->CreatedBy,
            PublishedBy: isset($row->PublishedBy) ? (int)$row->PublishedBy : null,
            IdGroup: isset($row->IdGroup) ? (int)$row->IdGroup : null,
            OnlyForMembers: (bool)($row->OnlyForMembers ?? false),
            LastUpdate: $row->LastUpdate,
            Timestamp: $row->Timestamp ?? '',
            Author: AuthorInfo::fromStdClass($row),
            Group: GroupInfo::fromStdClass($row),
        );
    }

    /**
     * Compatibilité Latte / code legacy attendant un tableau associatif.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'Id' => $this->Id,
            'Title' => $this->Title,
            'Content' => $this->Content,
            'CreatedBy' => $this->CreatedBy,
            'PublishedBy' => $this->PublishedBy,
            'IdGroup' => $this->IdGroup,
            'OnlyForMembers' => $this->OnlyForMembers,
            'LastUpdate' => $this->LastUpdate,
            'Timestamp' => $this->Timestamp,
            'FirstName' => $this->Author?->FirstName,
            'LastName' => $this->Author?->LastName,
            'NickName' => $this->Author?->NickName,
            'GroupName' => $this->Group?->Name,
        ];
    }
}
