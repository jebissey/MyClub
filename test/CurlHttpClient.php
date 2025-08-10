<?php

class CurlHttpClient implements HttpClientInterface
{
    public function __construct(
        private TestConfiguration $config
    ) {}

    public function request(string $method, string $url, array $options = []): HttpResponse
    {
        $ch = curl_init();

        $curlOptions = $this->buildCurlOptions($method, $url, $options);
        curl_setopt_array($ch, $curlOptions);

        $startTime = microtime(true);
        $response  = curl_exec($ch);
        $endTime   = microtime(true);

        if ($response === false) {
            $error = curl_error($ch);
            $errno = curl_errno($ch);
            curl_close($ch);
            throw new RuntimeException("Erreur cURL ($errno): $error");
        }
        $httpCode   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headersRaw = substr($response, 0, $headerSize);
        $body       = substr($response, $headerSize);
        $responseTime = round(($endTime - $startTime) * 1000, 2);
        curl_close($ch);

        return new HttpResponse(
            httpCode: $httpCode,
            body: $body,
            headers: $headersRaw, 
            responseTimeMs: $responseTime,
            success: $httpCode > 0,
            url: $url
        );
    }

    private function buildCurlOptions(string $method, string $url, array $options): array
    {
        $opts = [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $this->config->timeout,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CUSTOMREQUEST  => strtoupper($method),
            CURLOPT_HEADER         => true,
            CURLOPT_NOBODY         => (strtoupper($method) === 'HEAD'),
            CURLOPT_SSL_VERIFYPEER => $this->config->verifySSL,
            CURLOPT_SSL_VERIFYHOST => $this->config->verifySSL ? 2 : 0,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (Linux; Android 99; TestDevice 1.0)',
        ];

        if (!empty($options['headers'])) $opts[CURLOPT_HTTPHEADER] = $options['headers'];
        if (!empty($options['cookies'])) $opts[CURLOPT_COOKIE] = implode('; ', $options['cookies']);
        if (!empty($options['postfields'])) {
            $opts[CURLOPT_POSTFIELDS] = is_array($options['postfields'])
                ? http_build_query($options['postfields'])
                : $options['postfields'];
        }
        return $opts;
    }
}

