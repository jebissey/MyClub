<?php

declare(strict_types=1);

namespace app\modules\Article\valueObjects;

/**
 * @phpstan-type OrderNewsRowShape object{
 *     FirstName: string,
 *     LastName: string,
 *     Question: string,
 *     ClosingDate: string,
 *     Visibility: string,
 *     IdArticle: int|string,
 *     LastActivity: string,
 *     Orderers: string,
 * }
 */
final readonly class OrderNewsRow
{
    public function __construct(
        public string $FirstName,
        public string $LastName,
        public string $Question,
        public string $ClosingDate,
        public string $Visibility,
        public int $IdArticle,
        public string $LastActivity,
        public string $Orderers,
    ) {
    }

    /**
     * @param OrderNewsRowShape $row
     */
    public static function fromStdClass(object $row): self
    {
        return new self(
            FirstName: (string) $row->FirstName,
            LastName: (string) $row->LastName,
            Question: (string) $row->Question,
            ClosingDate: (string) $row->ClosingDate,
            Visibility: (string) $row->Visibility,
            IdArticle: (int) $row->IdArticle,
            LastActivity: (string) $row->LastActivity,
            Orderers: (string) $row->Orderers,
        );
    }
}
