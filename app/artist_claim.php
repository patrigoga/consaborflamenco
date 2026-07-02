<?php
declare(strict_types=1);

function csf_claim_artist(string $externalUserId, string $name, string $slug, ?string $email = null): array
{
    $apiSecret = csf_env('ARTIST_API_SECRET');
    $baseUrl = csf_env('PUBLIC_ARTIST_URL') ?: csf_env('ARTIST_MICROSITE_URL') ?: 'http://localhost:3000';
    $url = rtrim($baseUrl, '/') . '/api/artists/claim';

    $payload = json_encode(['externalUserId' => $externalUserId, 'name' => $name, 'slug' => $slug, 'email' => $email]);
    if (!is_string($payload) || $payload === '') {
        return ['ok' => false, 'error' => 'invalid_payload'];
    }

    // Prefer cURL when available, but do not fail hard if the extension is missing.
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        if ($ch === false) {
            return ['ok' => false, 'error' => 'curl_init_failed'];
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'x-api-key: ' . ($apiSecret ?? ''),
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $resp = curl_exec($ch);
        $err = curl_error($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($resp === false) {
            return ['ok' => false, 'error' => $err ?: 'curl_error'];
        }

        $data = json_decode($resp, true);
        return ['ok' => $code >= 200 && $code < 300, 'status' => $code, 'body' => $data];
    }

    $headers = [
        'Content-Type: application/json',
        'x-api-key: ' . ($apiSecret ?? ''),
    ];
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => implode("\r\n", $headers),
            'content' => $payload,
            'timeout' => 10,
            'ignore_errors' => true,
        ],
    ]);

    $resp = @file_get_contents($url, false, $context);
    if ($resp === false) {
        return ['ok' => false, 'error' => 'http_post_failed'];
    }

    $status = 0;
    if (!empty($http_response_header) && is_array($http_response_header)) {
        foreach ($http_response_header as $line) {
            if (preg_match('/^HTTP\/\S+\s+(\d{3})\b/', (string) $line, $matches)) {
                $status = (int) ($matches[1] ?? 0);
                break;
            }
        }
    }

    $data = json_decode($resp, true);
    return ['ok' => $status >= 200 && $status < 300, 'status' => $status, 'body' => $data];
}
