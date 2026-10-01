<?php

declare(strict_types=1);

namespace test\Infrastructure;

use Throwable;

use test\Core\ValueObjects\AuthenticationResult;
use test\Interfaces\HttpClientInterface;

final class SessionAuthenticator
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $loginEndpoint
    ) {}

    /**
     * @param array<string, string> $credentials
     */
    public function authenticate(array $credentials): AuthenticationResult
    {
        if (empty($credentials)) {
            return new AuthenticationResult(success: true);
        }

        try {
            $response = $this->httpClient->request('POST', $this->loginEndpoint, [
                'postfields' => http_build_query($credentials),
                'headers' => ['Content-Type: application/x-www-form-urlencoded'],
            ]);

            $success = in_array($response->httpCode, [200, 302, 303], true);

            return new AuthenticationResult(
                success: $success,
                error: $success ? '' : "Code HTTP: {$response->httpCode}",
            );
        } catch (Throwable $e) {
            return new AuthenticationResult(
                success: false,
                error: $e->getMessage(),
            );
        }
    }
}
