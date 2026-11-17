<?php

declare(strict_types=1);

namespace app\modules\Article\valueObjects;

final readonly class MediaListCriteria
{
    public function __construct(
        public int $year,
        public string $month,
        public string $fileExtension,
        public string $search,
        public bool $unusedOnly,
    ) {}

    /**
     * Builds the criteria from raw query parameters, ignoring invalid values.
     */
    public static function fromQuery(object $query): self
    {
        return new self(
            year: isset($query->year) && is_numeric($query->year) ? (int) $query->year : (int) date('Y'),
            month: isset($query->month) && is_string($query->month) ? $query->month : '',
            fileExtension: isset($query->fileExtension) && is_string($query->fileExtension) ? $query->fileExtension : '',
            search: isset($query->search) && is_string($query->search) ? trim($query->search) : '',
            unusedOnly: isset($query->unusedOnly) && $query->unusedOnly === '1',
        );
    }

    public function withMonth(string $month): self
    {
        return new self($this->year, $month, $this->fileExtension, $this->search, $this->unusedOnly);
    }

    /**
     * Same year and month, but without any other filter (used for the total count).
     */
    public function withoutFilters(): self
    {
        return new self($this->year, $this->month, '', '', false);
    }
}
