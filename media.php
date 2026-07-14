<?php
declare(strict_types=1);

require_once __DIR__ . '/app/config.php';

$file = csf_normalize_media_file((string) ($_GET['file'] ?? ''));
if ($file === null) {
    http_response_code(404);
    exit;
}

$base = realpath(RUNTIME_UPLOADS_DIR);
$path = RUNTIME_UPLOADS_DIR . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $file);
$realPath = realpath($path);
if ($base === false || $realPath === false || !str_starts_with($realPath, $base . DIRECTORY_SEPARATOR) || !is_file($realPath)) {
    http_response_code(404);
    exit;
}

$imageInfo = @getimagesize($realPath);
if (!$imageInfo || empty($imageInfo['mime'])) {
    http_response_code(404);
    exit;
}

$allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
$mime = (string) $imageInfo['mime'];
if (!in_array($mime, $allowedMimes, true)) {
    http_response_code(404);
    exit;
}

$lastModified = filemtime($realPath) ?: time();
header('Content-Type: ' . $mime);
header('Content-Length: ' . (string) filesize($realPath));
header('Cache-Control: public, max-age=31536000, immutable');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $lastModified) . ' GMT');

readfile($realPath);
