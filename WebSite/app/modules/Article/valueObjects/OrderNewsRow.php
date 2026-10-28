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
        public string $firstName,
        public string $lastName,
        public string $question,
        public string $closingDate,
        public string $visibility,
        public int $idArticle,
        public string $lastActivity,
        public string $orderers,
    ) {
    }

    /**
     * @param OrderNewsRowShape $row
     */
    public static function fromStdClass(object $row): self
    {
        return new self(
            firstName: (string) $row->FirstName,
            lastName: (string) $row->LastName,
            question: (string) $row->Question,
            closingDate: (string) $row->ClosingDate,
            visibility: (string) $row->Visibility,
            idArticle: (int) $row->IdArticle,
            lastActivity: (string) $row->LastActivity,
            orderers: (string) $row->Orderers,
        );
    }
}
