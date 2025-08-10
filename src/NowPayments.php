<?php

declare(strict_types=1);

require_once __DIR__ . '/Config.php';

class NowPayments
{
    private const BASE = 'https://api.nowpayments.io/v1';

    public static function createPayment(array $payload): array
    {
        $apiKey = Config::get('nowpayments_api_key');
        if (!$apiKey) {
            throw new RuntimeException('NOWPayments API key not configured');
        }
        $url = self::BASE . '/payment';
        $res = self::httpJson('POST', $url, $payload, [
            'x-api-key: ' . $apiKey,
            'Content-Type: application/json',
        ]);
        return $res;
    }

    public static function verifyIpn(string $rawBody, string $signature): bool
    {
        $secret = Config::get('ipn_secret_key');
        if (!$secret) { return false; }
        $calc = hash_hmac('sha512', $rawBody, trim($secret));
        return hash_equals($calc, $signature);
    }

    private static function httpJson(string $method, string $url, array $body = null, array $headers = []): array
    {
        $opts = [
            'http' => [
                'method' => $method,
                'header' => implode("\r\n", $headers) . "\r\n",
                'ignore_errors' => true,
                'content' => $body ? json_encode($body) : '',
                'timeout' => 15,
            ]
        ];
        $ctx = stream_context_create($opts);
        $raw = file_get_contents($url, false, $ctx);
        if ($raw === false) {
            throw new RuntimeException('HTTP request failed');
        }
        $resp = json_decode($raw, true);
        if (!is_array($resp)) {
            throw new RuntimeException('Invalid JSON response');
        }
        return $resp;
    }
}