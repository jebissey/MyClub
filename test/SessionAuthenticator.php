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
            $success = in_array($response->httpCode, [200, 302]);
            preg_match_all('/Set-Cookie:\s*([^;\r\n]+)/', $response->headers, $matches);
            $sessionData = ['cookies' => $matches[1] ?? []];

            return new AuthenticationResult(
                success: $success,
                sessionData: $sessionData,
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
