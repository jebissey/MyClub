<?php

declare(strict_types=1);

namespace app\modules\Event\valueObjects;

use app\enums\ContactRegistrationOutcome;

final readonly class ContactRegistrationResult
{
    public function __construct(
        public ContactRegistrationOutcome $outcome,
        public ?int $contactId = null,
        public ?string $contactEmail = null,
        public ?string $contactNickName = null,
    ) {
    }
}
