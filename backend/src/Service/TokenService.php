<?php

namespace App\Service;

class TokenService
{
    private string $secret;

    public function __construct(string $secret)
    {
        $this->secret = $secret;
    }

    public function generate(array $payload): string
    {
        $header = json_encode(['alg' => 'HS256', 'typ' => 'JWT']);
        $payload['iat'] = time();
        if (!isset($payload['exp'])) {
            $payload['exp'] = time() + 3600;
        }
        $payloadJson = json_encode($payload);
        $segments = [
            $this->base64UrlEncode($header),
            $this->base64UrlEncode($payloadJson),
        ];
        $signature = hash_hmac('sha256', implode('.', $segments), $this->secret, true);
        $segments[] = $this->base64UrlEncode($signature);
        return implode('.', $segments);
    }

    public function decode(string $token): ?array
    {
        $segments = explode('.', $token);
        if (count($segments) !== 3) {
            return null;
        }
        $payload = json_decode($this->base64UrlDecode($segments[1]), true);
        if (!is_array($payload) || ($payload['exp'] ?? 0) < time()) {
            return null;
        }
        $expectedSignature = hash_hmac('sha256', $segments[0] . '.' . $segments[1], $this->secret, true);
        if (!hash_equals($expectedSignature, $this->base64UrlDecode($segments[2]))) {
            return null;
        }
        return $payload;
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $data): string
    {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }
}
