<?php
declare(strict_types=1);

function csf_claim_artist(string $externalUserId, string $name, string $slug, ?string $email = null): array
{
    $apiSecret = csf_env('ARTIST_API_SECRET');
    $baseUrl = csf_env('PUBLIC_ARTIST_URL') ?: csf_env('ARTIST_MICROSITE_URL') ?: 'http://localhost:3000';
    $url = rtrim($baseUrl, '/') . '/api/artists/claim';

    $payload = json_encode(['externalUserId' => $externalUserId, 'name' => $name, 'slug' => $slug, 'email' => $email]);

    $ch = curl_init($url);
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
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($resp === false) {
        return ['ok' => false, 'error' => $err ?: 'curl_error'];
    }

    $data = json_decode($resp, true);
    return ['ok' => $code >= 200 && $code < 300, 'status' => $code, 'body' => $data];
}
