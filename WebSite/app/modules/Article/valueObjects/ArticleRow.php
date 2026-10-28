<?php

declare(strict_types=1);

namespace app\modules\Article\valueObjects;

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
        public int $id,
        public string $title,
        public string $content,
        public int $createdBy,
        public ?int $publishedBy,
        public ?int $idGroup,
        public bool $onlyForMembers,
        public string $lastUpdate,
        public string $timestamp,
        public ?AuthorInfo $author = null,
        public ?GroupInfo $group = null,
    ) {
    }

    /**
     * @param ArticleRowShape $row
     */
    public static function fromStdClass(object $row): self
    {
        return new self(
            id: (int)$row->Id,
            title: $row->Title,
            content: $row->Content,
            createdBy: (int)$row->CreatedBy,
            publishedBy: isset($row->PublishedBy) ? (int)$row->PublishedBy : null,
            idGroup: isset($row->IdGroup) ? (int)$row->IdGroup : null,
            onlyForMembers: (bool)($row->OnlyForMembers ?? false),
            lastUpdate: $row->LastUpdate,
            timestamp: $row->Timestamp ?? '',
            author: AuthorInfo::fromStdClass($row),
            group: GroupInfo::fromStdClass($row),
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
            'Id'             => $this->id,
            'Title'          => $this->title,
            'Content'        => $this->content,
            'CreatedBy'      => $this->createdBy,
            'PublishedBy'    => $this->publishedBy,
            'IdGroup'        => $this->idGroup,
            'OnlyForMembers' => $this->onlyForMembers,
            'LastUpdate'     => $this->lastUpdate,
            'Timestamp'      => $this->timestamp,
            'FirstName'      => $this->author?->firstName,
            'LastName'       => $this->author?->lastName,
            'NickName'       => $this->author?->nickName,
            'GroupName'      => $this->group?->name,
        ];
    }
}
