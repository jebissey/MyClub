<?php

declare(strict_types=1);

namespace test\Core\Enums;

enum TestResultStatus: string
{
    /** Credentials in test DB failed (or login endpoint unreachable). */
    case AuthenticationFailure = 'authentication_failure';

    /** Invalid/missing JsonGetParameters or JsonPostParameters in test DB. */
    case InvalidTestParameters = 'invalid_test_parameters';

    /**
     * HTTP status ≠ ExpectedResponseCode.
     * Ambiguous: wrong expectation in DB, or site bug.
     */
    case ResponseCodeFailure = 'response_code_failure';

    /**
     * SQL Query result ≠ QueryExpectedResponse.
     * Ambiguous: wrong expectation in DB, or site/data bug.
     */
    case DataFailure = 'data_failure';

    case Success = 'success';

    public function isFailure(): bool
    {
        return $this !== self::Success;
    }

    /** Failures that are clearly fixture / test-DB issues (no reliable signal on the app). */
    public function isTestDataIssue(): bool
    {
        return match ($this) {
            self::AuthenticationFailure,
            self::InvalidTestParameters => true,
            default => false,
        };
    }

    /** Failures that may be either fixture or application under test. */
    public function isAmbiguousOutcome(): bool
    {
        return match ($this) {
            self::ResponseCodeFailure,
            self::DataFailure => true,
            default => false,
        };
    }
}
