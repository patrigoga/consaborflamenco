<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/legal_repository.php';

header('Content-Type: application/json; charset=utf-8');

$key = clean_text((string) ($_GET['document'] ?? ''));
if (!in_array($key, legal_allowed_document_keys(), true)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Documento no válido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $document = legal_document_by_key($key, true);
    if (!$document) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'Documento no disponible.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $definitions = legal_document_definitions();
    echo json_encode([
        'ok' => true,
        'document' => [
            'key' => $key,
            'title' => (string) $document['title'],
            'content' => (string) $document['content'],
            'updated_at' => legal_format_date((string) ($document['visible_updated_at'] ?? $document['updated_at'] ?? '')),
            'url' => (string) ($definitions[$key]['page'] ?? ''),
        ],
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'No se pudo cargar el documento.'], JSON_UNESCAPED_UNICODE);
}
