<?php

class CurlHttpClient implements HttpClientInterface
{
    public function __construct(
        private TestConfiguration $config
    ) {}

    public function request(string $method, string $url, array $options = []): HttpResponse
    {
        $ch = curl_init();
        $curlOptions = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->config->timeout,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HEADER => true,
            CURLOPT_NOBODY => ($method === 'HEAD'),
            CURLOPT_SSL_VERIFYPEER => $this->config->verifySSL,
            CURLOPT_SSL_VERIFYHOST => $this->config->verifySSL ? 2 : 0
        ];

        if (isset($options['headers']))    $curlOptions[CURLOPT_HTTPHEADER] = $options['headers'];
        if (isset($options['cookies']))    $curlOptions[CURLOPT_COOKIE] = implode('; ', $options['cookies']);
        if (isset($options['postfields'])) $curlOptions[CURLOPT_POSTFIELDS] = $options['postfields'];
        curl_setopt_array($ch, $curlOptions);

        $startTime = microtime(true);
        $response = curl_exec($ch);
        $endTime = microtime(true);

        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException("Erreur cURL: $error");
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headers = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);
        $responseTime = round(($endTime - $startTime) * 1000, 2);

        curl_close($ch);

        return new HttpResponse(
            httpCode: $httpCode,
            body: $body,
            headers: $headers,
            responseTimeMs: $responseTime,
            success: $httpCode > 0,
            url: $url
        );
    }
}
