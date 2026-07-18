<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

function legal_document_definitions(): array
{
    return [
        'terms' => [
            'title' => 'Términos y condiciones',
            'slug' => 'terminos',
            'page' => 'terminos.php',
            'description' => 'Términos y condiciones de uso de Con Sabor Flamenco.',
            'content' => '<h2>Datos pendientes de completar</h2><p>Este documento es una base administrable y debe ser revisado antes de publicarse como texto definitivo.</p><ul><li>Titular o razón social pendiente.</li><li>NIF/CIF pendiente.</li><li>Domicilio pendiente.</li><li>Condiciones de contratación, precios, renovaciones y cancelaciones pendientes.</li><li>Normas aplicables a miembros, anunciantes y servicios digitales pendientes.</li></ul><h2>Objeto</h2><p>Con Sabor Flamenco ofrece una plataforma digital orientada a la difusión, promoción y gestión de contenidos, perfiles y servicios relacionados con el flamenco.</p><h2>Uso de la plataforma</h2><p>Las personas usuarias deben facilitar información veraz y no publicar contenidos ilícitos, engañosos o que vulneren derechos de terceros.</p>',
        ],
        'legal_notice' => [
            'title' => 'Aviso legal',
            'slug' => 'aviso-legal',
            'page' => 'aviso-legal.php',
            'description' => 'Aviso legal de Con Sabor Flamenco.',
            'content' => '<h2>Datos identificativos pendientes</h2><p>El titular de la web debe completar y validar estos datos antes de publicar el aviso legal como definitivo.</p><ul><li>Titular o razón social pendiente.</li><li>NIF/CIF pendiente.</li><li>Domicilio pendiente.</li><li>Datos registrales pendientes, si corresponden.</li><li>Correo de contacto: hola@consaborflamenco.com.</li></ul><h2>Responsabilidad</h2><p>Con Sabor Flamenco trabaja para mantener información actualizada, pero los contenidos editoriales, perfiles y servicios pueden requerir revisión o confirmación adicional.</p>',
        ],
        'privacy' => [
            'title' => 'Política de privacidad',
            'slug' => 'privacidad',
            'page' => 'privacidad.php',
            'description' => 'Política de privacidad y protección de datos de Con Sabor Flamenco.',
            'content' => '<h2>Información pendiente de completar</h2><p>Esta política debe completarse con los datos reales del responsable del tratamiento y revisarse legalmente.</p><ul><li>Responsable del tratamiento pendiente.</li><li>NIF/CIF y domicilio pendientes.</li><li>Bases jurídicas detalladas pendientes.</li><li>Plazos de conservación pendientes.</li><li>Destinatarios, encargados y proveedores pendientes.</li><li>Transferencias internacionales pendientes.</li><li>Procedimiento para ejercer derechos pendiente.</li></ul><h2>Datos tratados actualmente</h2><p>La plataforma puede tratar datos de cuenta, acceso, perfil público, provincia seleccionada, comunicaciones necesarias y datos asociados a servicios solicitados.</p>',
        ],
        'cookies' => [
            'title' => 'Política de cookies',
            'slug' => 'cookies',
            'page' => 'cookies.php',
            'description' => 'Política de cookies de Con Sabor Flamenco.',
            'content' => '<h2>Qué son las cookies</h2><p>Las cookies y tecnologías similares permiten recordar información técnica o preferencias de navegación.</p><h2>Auditoría actual</h2><p>En la versión actual se han detectado cookies necesarias de sesión PHP y almacenamiento local para preferencias funcionales como provincia y consentimiento. No se han detectado Google Analytics, Meta Pixel ni publicidad personalizada cargada por terceros.</p><h2>Categorías</h2><ul><li><strong>Necesarias:</strong> sesión, seguridad, CSRF y funcionamiento básico.</li><li><strong>Preferencias:</strong> provincia o ajustes elegidos por la persona usuaria.</li><li><strong>Analítica:</strong> desactivada hasta consentimiento y sin proveedor configurado actualmente.</li><li><strong>Publicidad:</strong> desactivada hasta consentimiento y sin proveedor externo configurado actualmente.</li></ul><h2>Modificar consentimiento</h2><p>Puedes cambiar o retirar tu elección desde el enlace Configurar cookies del footer.</p>',
        ],
    ];
}

function legal_allowed_document_keys(): array
{
    return array_keys(legal_document_definitions());
}

function legal_document_key_from_page(string $page): ?string
{
    foreach (legal_document_definitions() as $key => $definition) {
        if (($definition['page'] ?? '') === $page) {
            return $key;
        }
    }

    return null;
}

function legal_db(): ?PDO
{
    try {
        return auth_database();
    } catch (Throwable) {
        return null;
    }
}

function legal_ensure_schema(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS legal_documents (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        document_key ENUM('terms','legal_notice','privacy','cookies') NOT NULL,
        title VARCHAR(180) NOT NULL,
        slug VARCHAR(120) NOT NULL,
        content MEDIUMTEXT NOT NULL,
        status ENUM('DRAFT','PUBLISHED') NOT NULL DEFAULT 'DRAFT',
        version INT UNSIGNED NOT NULL DEFAULT 1,
        published_at DATETIME NULL,
        visible_updated_at DATE NULL,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        updated_by BIGINT UNSIGNED NULL,
        UNIQUE KEY uq_legal_documents_key (document_key),
        UNIQUE KEY uq_legal_documents_slug (slug),
        INDEX idx_legal_documents_status (status),
        CONSTRAINT fk_legal_documents_updated_by FOREIGN KEY (updated_by) REFERENCES usuarios(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS legal_document_versions (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        legal_document_id BIGINT UNSIGNED NOT NULL,
        version INT UNSIGNED NOT NULL,
        title VARCHAR(180) NOT NULL,
        content MEDIUMTEXT NOT NULL,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        created_by BIGINT UNSIGNED NULL,
        CONSTRAINT fk_legal_versions_document FOREIGN KEY (legal_document_id) REFERENCES legal_documents(id) ON DELETE CASCADE,
        CONSTRAINT fk_legal_versions_user FOREIGN KEY (created_by) REFERENCES usuarios(id) ON DELETE SET NULL,
        INDEX idx_legal_versions_document (legal_document_id, version)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $statement = $pdo->prepare(
        'INSERT IGNORE INTO legal_documents (document_key, title, slug, content, status, version, published_at, visible_updated_at)
        VALUES (:document_key, :title, :slug, :content, :status, 1, UTC_TIMESTAMP(), CURRENT_DATE())'
    );
    foreach (legal_document_definitions() as $key => $definition) {
        $statement->execute([
            'document_key' => $key,
            'title' => $definition['title'],
            'slug' => $definition['slug'],
            'content' => legal_sanitize_html($definition['content']),
            'status' => 'PUBLISHED',
        ]);
    }
}

function legal_repository_ready(): ?PDO
{
    $pdo = legal_db();
    if (!$pdo) {
        return null;
    }

    legal_ensure_schema($pdo);
    return $pdo;
}

function legal_sanitize_html(string $html): string
{
    $html = preg_replace('#<(script|style|iframe|object|embed)\b[^>]*>.*?</\1>#is', '', $html) ?? '';
    $html = preg_replace('/\son[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html) ?? '';
    $html = preg_replace('/\s(href)\s*=\s*([\'"])\s*(javascript:|data:)[^\'"]*\2/i', ' href="#"', $html) ?? '';

    $allowed = '<h2><h3><h4><p><ul><ol><li><strong><em><a><br>';
    $html = strip_tags($html, $allowed);
    $html = preg_replace_callback('/<a\b([^>]*)>/i', static function (array $matches): string {
        $attrs = $matches[1] ?? '';
        if (!preg_match('/href\s*=\s*([\'"])(.*?)\1/i', $attrs, $hrefMatch)) {
            return '<a>';
        }
        $href = trim(html_entity_decode($hrefMatch[2], ENT_QUOTES, 'UTF-8'));
        $isSafe = preg_match('#^(https?://|mailto:|/|[a-z0-9._/-]+(?:\?[a-z0-9=&._%-]+)?(?:#[a-z0-9_-]+)?)#i', $href) === 1
            && stripos($href, 'javascript:') !== 0
            && stripos($href, 'data:') !== 0;
        if (!$isSafe) {
            return '<a>';
        }
        $safeHref = e($href);
        $rel = preg_match('#^https?://#i', $href) ? ' rel="noopener noreferrer"' : '';
        return '<a href="' . $safeHref . '"' . $rel . '>';
    }, $html) ?? '';

    return trim($html);
}

function legal_document_by_key(string $key, bool $publishedOnly = true): ?array
{
    if (!in_array($key, legal_allowed_document_keys(), true)) {
        return null;
    }
    $pdo = legal_repository_ready();
    if (!$pdo) {
        return null;
    }

    $sql = 'SELECT ld.*, u.nombre AS updated_by_name FROM legal_documents ld LEFT JOIN usuarios u ON u.id = ld.updated_by WHERE ld.document_key = :document_key';
    if ($publishedOnly) {
        $sql .= " AND ld.status = 'PUBLISHED'";
    }
    $sql .= ' LIMIT 1';
    $statement = $pdo->prepare($sql);
    $statement->execute(['document_key' => $key]);
    $document = $statement->fetch(PDO::FETCH_ASSOC);

    return $document ?: null;
}

function legal_documents_all(): array
{
    $pdo = legal_repository_ready();
    if (!$pdo) {
        return [];
    }

    $statement = $pdo->query('SELECT ld.*, u.nombre AS updated_by_name FROM legal_documents ld LEFT JOIN usuarios u ON u.id = ld.updated_by ORDER BY FIELD(ld.document_key, "terms", "legal_notice", "privacy", "cookies")');
    return $statement ? $statement->fetchAll(PDO::FETCH_ASSOC) : [];
}

function legal_versions_for_document(int $documentId): array
{
    $pdo = legal_repository_ready();
    if (!$pdo) {
        return [];
    }

    $statement = $pdo->prepare('SELECT v.*, u.nombre AS created_by_name FROM legal_document_versions v LEFT JOIN usuarios u ON u.id = v.created_by WHERE v.legal_document_id = :id ORDER BY v.version DESC, v.id DESC LIMIT 12');
    $statement->execute(['id' => $documentId]);
    return $statement->fetchAll(PDO::FETCH_ASSOC);
}

function legal_update_document(array $input, array $admin): void
{
    $key = (string) ($input['document_key'] ?? '');
    if (!in_array($key, legal_allowed_document_keys(), true)) {
        throw new InvalidArgumentException('Documento legal no válido.');
    }
    $pdo = legal_repository_ready();
    if (!$pdo) {
        throw new RuntimeException('La base de datos no está disponible.');
    }

    $document = legal_document_by_key($key, false);
    if (!$document) {
        throw new RuntimeException('Documento legal no encontrado.');
    }

    $title = clean_text((string) ($input['title'] ?? ''));
    $status = in_array(($input['status'] ?? ''), ['DRAFT', 'PUBLISHED'], true) ? (string) $input['status'] : 'DRAFT';
    $content = legal_sanitize_html((string) ($input['content'] ?? ''));
    $visibleDate = clean_text((string) ($input['visible_updated_at'] ?? ''));

    if (mb_strlen($title, 'UTF-8') < 4) {
        throw new InvalidArgumentException('El título debe tener al menos 4 caracteres.');
    }
    if (mb_strlen(strip_tags($content), 'UTF-8') < 30) {
        throw new InvalidArgumentException('El contenido legal debe tener al menos 30 caracteres.');
    }
    if ($visibleDate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $visibleDate) !== 1) {
        throw new InvalidArgumentException('La fecha visible no tiene un formato válido.');
    }

    $pdo->beginTransaction();
    try {
        $versionInsert = $pdo->prepare(
            'INSERT INTO legal_document_versions (legal_document_id, version, title, content, created_by)
            VALUES (:legal_document_id, :version, :title, :content, :created_by)'
        );
        $versionInsert->execute([
            'legal_document_id' => (int) $document['id'],
            'version' => (int) $document['version'],
            'title' => (string) $document['title'],
            'content' => (string) $document['content'],
            'created_by' => (int) ($admin['db_id'] ?? 0) ?: null,
        ]);

        $update = $pdo->prepare(
            'UPDATE legal_documents
            SET title = :title,
                content = :content,
                status = :status,
                version = version + 1,
                published_at = CASE WHEN :status_published = "PUBLISHED" THEN COALESCE(published_at, UTC_TIMESTAMP()) ELSE published_at END,
                visible_updated_at = :visible_updated_at,
                updated_by = :updated_by,
                updated_at = UTC_TIMESTAMP()
            WHERE document_key = :document_key'
        );
        $update->execute([
            'title' => $title,
            'content' => $content,
            'status' => $status,
            'status_published' => $status,
            'visible_updated_at' => $visibleDate !== '' ? $visibleDate : null,
            'updated_by' => (int) ($admin['db_id'] ?? 0) ?: null,
            'document_key' => $key,
        ]);
        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}

function legal_format_date(?string $value): string
{
    if (!$value) {
        return 'Pendiente';
    }
    $timestamp = strtotime($value);
    return $timestamp ? date('d/m/Y', $timestamp) : $value;
}
