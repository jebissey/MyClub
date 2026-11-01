<?php

declare(strict_types=1);

namespace app\modules\Common\valueObjects;

use app\helpers\To;

/**
 * Feedback shown on the contact page after a redirect: success or error messages
 * and the values to put back in the form fields.
 */
final readonly class ContactFeedbackInfo
{
    /**
     * @param list<string> $errors
     */
    public function __construct(
        public ?string $success = null,
        public ?string $error = null,
        public array $errors = [],
        public string $oldName = '',
        public string $oldEmail = '',
        public string $oldMessage = '',
    ) {
    }

    /**
     * @param array<string, mixed> $query Query string parameters of the current request
     */
    public static function fromQuery(array $query): self
    {
        $read = static fn(string $key): string => To::str($query[$key] ?? null);
        $nullable = static fn(string $value): ?string => $value === '' ? null : $value;

        $errors = array_values(array_filter(
            explode('|', $read('errors')),
            static fn(string $error): bool => $error !== ''
        ));

        return new self(
            success: $nullable($read('success')),
            error: $nullable($read('error')),
            errors: $errors,
            oldName: $read('old_name'),
            oldEmail: $read('old_email'),
            oldMessage: $read('old_message'),
        );
    }
}
