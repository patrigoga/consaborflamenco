<?php
declare(strict_types=1);

require_once __DIR__ . '/app/auth.php';

$storageKey = clean_text((string) ($_GET['k'] ?? ''));
if ($storageKey === '' || !preg_match('/^[a-z0-9\-]{10,120}$/', $storageKey)) {
    http_response_code(404);
    exit;
}

$pdo = db();
if (!$pdo) {
    http_response_code(503);
    exit;
}

try {
    $statement = $pdo->prepare(
        'SELECT mime_type, size_bytes, contenido_binario, updated_at, created_at
         FROM media_archivos
         WHERE storage_key = :storage_key
         LIMIT 1'
    );
    $statement->execute(['storage_key' => $storageKey]);
    $media = $statement->fetch(PDO::FETCH_ASSOC) ?: null;
} catch (Throwable $exception) {
    error_log('Media read failed: ' . $exception->getMessage());
    http_response_code(500);
    exit;
}

if (!$media) {
    http_response_code(404);
    exit;
}

$binary = $media['contenido_binario'] ?? null;
if (!is_string($binary) || $binary === '') {
    http_response_code(404);
    exit;
}

$mimeType = clean_text((string) ($media['mime_type'] ?? ''));
if ($mimeType === '') {
    $mimeType = 'application/octet-stream';
}

$size = (int) ($media['size_bytes'] ?? 0);
if ($size <= 0) {
    $size = strlen($binary);
}

$lastModifiedSource = clean_text((string) ($media['updated_at'] ?? $media['created_at'] ?? ''));
$lastModifiedTimestamp = strtotime($lastModifiedSource);
if ($lastModifiedTimestamp === false) {
    $lastModifiedTimestamp = time();
}

$etag = '"' . sha1($storageKey . ':' . $size . ':' . $lastModifiedTimestamp) . '"';
$ifNoneMatch = (string) ($_SERVER['HTTP_IF_NONE_MATCH'] ?? '');
if ($ifNoneMatch !== '' && trim($ifNoneMatch) === $etag) {
    header('ETag: ' . $etag);
    header('Cache-Control: public, max-age=31536000, immutable');
    http_response_code(304);
    exit;
}

header('Content-Type: ' . $mimeType);
header('Content-Length: ' . (string) $size);
header('Cache-Control: public, max-age=31536000, immutable');
header('ETag: ' . $etag);
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $lastModifiedTimestamp) . ' GMT');
header('X-Content-Type-Options: nosniff');

echo $binary;
