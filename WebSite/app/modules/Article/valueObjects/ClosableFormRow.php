<?php

declare(strict_types=1);

namespace app\modules\Article\valueObjects;

use app\helpers\To;

/**
 * Editable content of a closable form (survey or order) shown on the add / edit page.
 *
 * @phpstan-type ClosableFormRowShape object{Question: string, Options: string, ClosingDate: string, Visibility: string}
 */
final readonly class ClosableFormRow
{
    /**
     * @param list<string> $options
     */
    public function __construct(
        public string $question,
        public array $options,
        public string $closingDate,
        public string $visibility,
    ) {
    }

    public static function fromStdClass(object $row): self
    {
        /** @var ClosableFormRowShape $row */
        $decoded = json_decode($row->Options, true);
        $options = is_array($decoded)
            ? array_values(array_map(static fn(mixed $option): string => To::str($option), $decoded))
            : [];

        return new self(
            question: To::str($row->Question),
            options: $options,
            closingDate: To::str($row->ClosingDate),
            visibility: To::str($row->Visibility),
        );
    }
}
