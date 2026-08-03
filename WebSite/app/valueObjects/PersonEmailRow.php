<?php

declare(strict_types=1);

namespace app\valueObjects;

final readonly class PersonEmailRow extends AbstractValueObject
{
    public function __construct(
        public ?string $Email,
    ) {
    }

    /**
     * @param object{Email: string|null} $o
     */
    public static function fromStdClass(object $o): self
    {
        return new self(
            Email: $o->Email !== null ? (string)$o->Email : null,
        );
    }
}
