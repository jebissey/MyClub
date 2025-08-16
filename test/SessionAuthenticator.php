<?php

class SessionAuthenticator implements AuthenticatorInterface
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $loginEndpoint
    ) {}

    public function authenticate(array $credentials): AuthenticationResult
    {
        if (empty($credentials)) return new AuthenticationResult(success: true);
        try {
            $response = $this->httpClient->request('POST', $this->loginEndpoint, [
                'postfields' => http_build_query($credentials),
                'headers' => ['Content-Type: application/x-www-form-urlencoded']
            ]);
            $success = in_array($response->httpCode, [200, 302, 303]);

            return new AuthenticationResult(
                success: $success,
                error: $success ? '' : "Code HTTP: {$response->httpCode}"
            );
        } catch (Exception $e) {
            return new AuthenticationResult(
                success: false,
                error: $e->getMessage()
            );
        }
    }
}
