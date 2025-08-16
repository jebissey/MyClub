<?php

class CurlHttpClient implements HttpClientInterface
{
    private array $storedCookies = [];

    public function __construct(private TestConfiguration $config) {}

    public function request(string $method, string $url, array $options = []): HttpResponse
    {
        $ch = curl_init();
        $fullUrl = $this->buildFullUrl($url);
        $curlOptions = $this->buildCurlOptions($method, $fullUrl, $options);
        curl_setopt_array($ch, $curlOptions);

        $startTime = microtime(true);
        $response = curl_exec($ch);
        $endTime = microtime(true);

        if ($response === false) {
            $error = curl_error($ch);
            $errno = curl_errno($ch);
            curl_close($ch);
            throw new RuntimeException("Erreur cURL ($errno): $error");
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headersRaw = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);

        foreach (explode("\r\n", $headersRaw) as $header) {
            if (stripos($header, 'Set-Cookie:') === 0) {
                $cookie = trim(substr($header, 11));
                $parts = explode(';', $cookie);
                if (count($parts) > 0) {
                    $nameValue = explode('=', trim($parts[0]), 2);
                    if (count($nameValue) === 2) {
                        $this->storedCookies[$nameValue[0]] = $nameValue[1];
                    }
                }
            }
        }

        $responseTime = round(($endTime - $startTime) * 1000, 2);
        curl_close($ch);

        return new HttpResponse(
            httpCode: $httpCode,
            body: $body,
            headers: $headersRaw,
            responseTimeMs: $responseTime,
            success: $httpCode > 0,
            url: $fullUrl
        );
    }

    #region Private functions
    private function buildFullUrl(string $endpoint): string
    {
        return rtrim($this->config->baseUrl, '/') . '/' . ltrim($endpoint, '/');
    }

    private function buildCurlOptions(string $method, string $url, array $options): array
    {
        $curlOptions = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Linux; Android 99; TestDevice 1.0)',
            CURLOPT_TIMEOUT => $this->config->timeout,
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_SSL_VERIFYPEER => $this->config->verifySSL,
            CURLOPT_SSL_VERIFYHOST => $this->config->verifySSL ? 2 : 0,
        ];

        $cookiesToSend = $this->storedCookies;
        if (!empty($options['cookies'])) {
            foreach ($options['cookies'] as $cookie) {
                [$name, $value] = explode('=', $cookie, 2);
                $cookiesToSend[$name] = $value;
            }
        }
        if (!empty($cookiesToSend)) {
            $curlOptions[CURLOPT_COOKIE] = implode('; ', array_map(
                fn($name, $value) => "$name=$value",
                array_keys($cookiesToSend),
                $cookiesToSend
            ));
        }
        if (isset($options['headers']))                                             $curlOptions[CURLOPT_HTTPHEADER] = $options['headers'];
        if (isset($options['body']) && in_array($method, ['POST', 'PUT', 'PATCH'])) $curlOptions[CURLOPT_POSTFIELDS] = $options['body'];
        if (!empty($options['postfields']))                                         $curlOptions[CURLOPT_POSTFIELDS] = is_array($options['postfields'])
            ? http_build_query($options['postfields'])
            : $options['postfields'];
        return $curlOptions;
    }
}
