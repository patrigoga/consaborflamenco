<?php
declare(strict_types=1);

require_once __DIR__ . '/app/qr.php';

$data = trim((string) ($_GET['data'] ?? ''));

try {
    $svg = csf_qr_svg($data);
    header('Content-Type: image/svg+xml; charset=UTF-8');
    header('Cache-Control: public, max-age=86400');
    header('X-Content-Type-Options: nosniff');
    echo $svg;
} catch (Throwable $exception) {
    http_response_code(400);
    header('Content-Type: image/svg+xml; charset=UTF-8');
    echo '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" width="64" height="64"><rect width="64" height="64" fill="#fff"/><path d="M16 16h32v32H16z" fill="none" stroke="#c94f5c" stroke-width="4"/><path d="M22 22l20 20M42 22L22 42" stroke="#c94f5c" stroke-width="4" stroke-linecap="round"/></svg>';
}
