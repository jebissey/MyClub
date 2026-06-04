<?php

declare(strict_types=1);

namespace app\modules\HelloAsso\services;

use RuntimeException;

use app\models\DataHelper;
use app\modules\Common\services\CredentialService;


/**
 * Thin wrapper around the HelloAsso API.
 *
 * Credentials (client_id, client_secret) → CredentialService::getInstance()
 * Settings    (org_slug)                 → DataHelper (passed once from a controller)
 *
 * Usage (from any controller):
 *   $service = HelloAssoService::getInstance($this->dataHelper);
 *   // subsequent calls anywhere:
 *   $service = HelloAssoService::getInstance();
 *
 * Sandbox base URL : https://api.helloasso-sandbox.com
 * Docs: https://api.helloasso.com/swagger/index.html
 */
class HelloAssoService
{
    private const BASE_URL  = 'https://api.helloasso-sandbox.com';
    private const TOKEN_URL = self::BASE_URL . '/oauth2/token';

    private static ?self $instance = null;

    private ?string $accessToken  = null;
    private ?string $clientId     = null;
    private ?string $clientSecret = null;
    private ?string $orgSlug      = null;

    // ─── Singleton ────────────────────────────────────────────────────────────

    private function __construct(
        private DataHelper $dataHelper,
    ) {}

    /**
     * Returns the singleton instance.
     * $dataHelper is required on the first call (pass $this->dataHelper from any controller).
     * Subsequent calls can omit it.
     */
    public static function getInstance(?DataHelper $dataHelper = null): self
    {
        if (self::$instance === null) {
            if ($dataHelper === null) {
                throw new RuntimeException(
                    'HelloAssoService::getInstance() requires $dataHelper on first call.'
                );
            }
            self::$instance = new self($dataHelper);
        }

        return self::$instance;
    }

    // ─── Public API ───────────────────────────────────────────────────────────

    /**
     * Returns true if client_id and client_secret are both configured.
     */
    public function isConfigured(): bool
    {
        $credentials = CredentialService::getInstance();
        return !empty($credentials->get('helloasso', 'client_id'))
            && !empty($credentials->get('helloasso', 'client_secret'));
    }

    /**
     * Creates a checkout intent and returns the redirect URL + intent id.
     *
     * @param int    $amountCents  Amount in euro-cents (e.g. 1000 = 10 €)
     * @param string $description  Label shown on the HelloAsso payment page
     * @param string $returnUrl    URL to redirect the user after payment
     * @param string $errorUrl     URL to redirect the user on error / cancel
     * @param array  $payer        ['firstName'=>..., 'lastName'=>..., 'email'=>...]
     *
     * @return array{checkoutIntentId: string, redirectUrl: string}
     * @throws RuntimeException on API error
     */
    public function createCheckoutIntent(
        int    $amountCents,
        string $description,
        string $returnUrl,
        string $errorUrl,
        array  $payer,
    ): array {
        $token = $this->getAccessToken();

        $payload = [
            'totalAmount'      => $amountCents,
            'initialAmount'    => $amountCents,
            'itemName'         => $description,
            'backUrl'          => $errorUrl,
            'errorUrl'         => $errorUrl,
            'returnUrl'        => $returnUrl,
            'containsDonation' => false,
            'payer' => [
                'firstName' => $payer['firstName'] ?? '',
                'lastName'  => $payer['lastName']  ?? '',
                'email'     => $payer['email']     ?? '',
            ],
        ];

        $url      = self::BASE_URL . "/v5/organizations/{$this->resolveOrgSlug()}/checkout-intents";
        $response = $this->request('POST', $url, $payload, $token);

        if (empty($response['id']) || empty($response['redirectUrl'])) {
            throw new RuntimeException(
                'HelloAsso: unexpected checkout intent response: ' . json_encode($response)
            );
        }

        return [
            'checkoutIntentId' => (string)$response['id'],
            'redirectUrl'      => (string)$response['redirectUrl'],
        ];
    }

    /**
     * Returns the embeddable widget URL for a given form.
     *
     * @param string $formType  'adhesions' | 'evenements' | 'dons' | ...
     * @param string $formSlug  Slug of the form (e.g. 'saison-2026-2027')
     * @param array  $options   Optional: 'firstName', 'lastName', 'email', 'accentColor'
     */
    public function getWidgetUrl(string $formType, string $formSlug, array $options = []): string
    {
        $host = str_contains(self::BASE_URL, 'sandbox')
            ? 'www.helloasso-sandbox.com'
            : 'www.helloasso.com';

        $base = "https://{$host}/associations/{$this->resolveOrgSlug()}/{$formType}/{$formSlug}/widget";

        $params = array_filter([
            'firstName'   => $options['firstName']   ?? null,
            'lastName'    => $options['lastName']     ?? null,
            'email'       => $options['email']        ?? null,
            'accentColor' => $options['accentColor']  ?? null,
        ]);

        return empty($params) ? $base : $base . '?' . http_build_query($params);
    }

    // ─── Private helpers ──────────────────────────────────────────────────────

    private function resolveClientId(): string
    {
        $this->clientId ??= CredentialService::getInstance()->get('helloasso', 'client_id') ?? '';
        if (empty($this->clientId)) {
            throw new RuntimeException('HelloAsso: client_id is not configured.');
        }
        return $this->clientId;
    }

    private function resolveClientSecret(): string
    {
        $this->clientSecret ??= CredentialService::getInstance()->get('helloasso', 'client_secret') ?? '';
        if (empty($this->clientSecret)) {
            throw new RuntimeException('HelloAsso: client_secret is not configured.');
        }
        return $this->clientSecret;
    }

    private function resolveOrgSlug(): string
    {
        $this->orgSlug ??= $this->dataHelper->getSetting('HelloAsso_OrgSlug', '');
        if (empty($this->orgSlug)) {
            throw new RuntimeException('HelloAsso: org_slug is not configured.');
        }
        return $this->orgSlug;
    }

    private function getAccessToken(): string
    {
        if ($this->accessToken !== null) {
            return $this->accessToken;
        }

        $ch = curl_init(self::TOKEN_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query([
                'grant_type'    => 'client_credentials',
                'client_id'     => $this->resolveClientId(),
                'client_secret' => $this->resolveClientSecret(),
            ]),
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        ]);

        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code !== 200 || $body === false) {
            throw new RuntimeException("HelloAsso OAuth2 failed (HTTP {$code})");
        }

        $data = json_decode($body, true);
        if (empty($data['access_token'])) {
            throw new RuntimeException('HelloAsso OAuth2: no access_token in response');
        }

        $this->accessToken = $data['access_token'];
        return $this->accessToken;
    }

    private function request(string $method, string $url, array $body = [], ?string $token = null): array
    {
        $headers = ['Content-Type: application/json', 'Accept: application/json'];
        if ($token !== null) {
            $headers[] = "Authorization: Bearer {$token}";
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => $method,
        ]);

        if ($method === 'POST' && !empty($body)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false) {
            throw new RuntimeException("HelloAsso cURL error for {$url}");
        }

        $decoded = json_decode($response, true);

        if ($httpCode >= 400) {
            $msg = $decoded['message'] ?? $response;
            throw new RuntimeException("HelloAsso API error {$httpCode}: {$msg}");
        }

        return $decoded ?? [];
    }
}