<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

function admin_database(): ?PDO
{
    try {
        return auth_database();
    } catch (Throwable $exception) {
        admin_record_error($exception);
        return null;
    }
}

function admin_record_error(Throwable $exception): void
{
    $GLOBALS['CSF_ADMIN_LAST_ERROR'] = $exception->getMessage();
    error_log('[CSF admin] ' . $exception->getMessage());
}

function admin_last_error(): ?string
{
    return isset($GLOBALS['CSF_ADMIN_LAST_ERROR']) ? (string) $GLOBALS['CSF_ADMIN_LAST_ERROR'] : null;
}

function admin_safe_count(PDO $pdo, string $sql): int
{
    try {
        return (int) $pdo->query($sql)->fetchColumn();
    } catch (Throwable $exception) {
        admin_record_error($exception);
        return 0;
    }
}

function admin_safe_fetch_all(PDO $pdo, string $sql): array
{
    try {
        $statement = $pdo->query($sql);
        return $statement ? $statement->fetchAll() : [];
    } catch (Throwable $exception) {
        admin_record_error($exception);
        return [];
    }
}

function admin_dashboard_stats(): array
{
    $pdo = admin_database();
    if (!$pdo) {
        $users = all_users();
        return [
            'members' => count(array_filter($users, static fn (array $user): bool => ($user['role'] ?? 'user') === 'user')),
            'setters' => count(array_filter($users, static fn (array $user): bool => ($user['role'] ?? 'user') === 'setter')),
            'articles' => 0,
            'banners' => 0,
            'categories' => 0,
            'leads' => 0,
            'sales' => 0,
            'member_types' => 0,
            'member_cards' => 0,
        ];
    }

    return [
        'members' => admin_safe_count($pdo, "SELECT COUNT(*) FROM usuarios WHERE rol = 'MIEMBRO'"),
        'setters' => admin_safe_count($pdo, "SELECT COUNT(*) FROM usuarios WHERE rol = 'SETTER'"),
        'articles' => admin_safe_count($pdo, 'SELECT COUNT(*) FROM articulos'),
        'banners' => admin_safe_count($pdo, 'SELECT COUNT(*) FROM banners_miembro'),
        'categories' => admin_safe_count($pdo, 'SELECT COUNT(*) FROM categorias_articulos'),
        'leads' => admin_safe_count($pdo, 'SELECT COUNT(*) FROM usos_codigo_descuento'),
        'sales' => admin_safe_count($pdo, 'SELECT COUNT(*) FROM pagos_stripe'),
        'member_types' => admin_safe_count($pdo, 'SELECT COUNT(*) FROM tipos_miembro WHERE activo = TRUE'),
        'member_cards' => admin_safe_count($pdo, 'SELECT COUNT(*) FROM tarjetas_miembro'),
    ];
}

function admin_members(): array
{
    $pdo = admin_database();
    if (!$pdo) {
        return array_filter(all_users(), static fn (array $user): bool => ($user['role'] ?? 'user') === 'user');
    }

    return admin_safe_fetch_all(
        $pdo,
        "SELECT
            u.nombre,
            u.email,
            u.estado AS usuario_estado,
            u.created_at,
            m.nombre_publico,
            m.numero_miembro,
            m.codigo_descuento,
            m.estado AS miembro_estado,
            m.perfil_completo_at,
            tm.nombre AS tipo_miembro
        FROM usuarios u
        LEFT JOIN miembros m ON m.usuario_id = u.id
        LEFT JOIN tipos_miembro tm ON tm.id = m.tipo_miembro_id
        WHERE u.rol = 'MIEMBRO'
        ORDER BY u.created_at DESC, u.id DESC"
    );
}

function admin_setters(): array
{
    $pdo = admin_database();
    if (!$pdo) {
        return array_filter(all_users(), static fn (array $user): bool => ($user['role'] ?? 'user') === 'setter');
    }

    return admin_safe_fetch_all(
        $pdo,
        "SELECT
            u.nombre,
            u.email,
            u.estado AS usuario_estado,
            u.created_at,
            s.nombre_comercial,
            s.estado_cuenta,
            s.estado_documentacion,
            s.estado_comisiones,
            s.codigo_promocional
        FROM usuarios u
        LEFT JOIN appointment_setters s ON s.usuario_id = u.id
        WHERE u.rol = 'SETTER'
        ORDER BY u.created_at DESC, u.id DESC"
    );
}

function admin_article_categories(): array
{
    $pdo = admin_database();
    if (!$pdo) {
        return [];
    }

    return admin_safe_fetch_all($pdo, 'SELECT * FROM categorias_articulos ORDER BY nombre ASC');
}

function admin_articles(): array
{
    $pdo = admin_database();
    if (!$pdo) {
        return [];
    }

    return admin_safe_fetch_all(
        $pdo,
        "SELECT a.*, c.nombre AS categoria_nombre, u.nombre AS autor_nombre
        FROM articulos a
        LEFT JOIN categorias_articulos c ON c.id = a.categoria_id
        LEFT JOIN usuarios u ON u.id = a.autor_usuario_id
        ORDER BY a.created_at DESC, a.id DESC
        LIMIT 30"
    );
}

function admin_banners(): array
{
    $pdo = admin_database();
    if (!$pdo) {
        return [];
    }

    return admin_safe_fetch_all(
        $pdo,
        "SELECT
            b.*,
            m.nombre_publico,
            u.email AS usuario_email
        FROM banners_miembro b
        INNER JOIN miembros m ON m.id = b.miembro_id
        INNER JOIN usuarios u ON u.id = m.usuario_id
        ORDER BY b.created_at DESC, b.id DESC
        LIMIT 40"
    );
}

function admin_create_category(string $name): void
{
    $pdo = admin_database();
    if (!$pdo) {
        throw new RuntimeException('La base de datos no esta disponible.');
    }

    $name = clean_text($name);
    if (strlen($name) < 2) {
        throw new InvalidArgumentException('Indica un nombre de categoria.');
    }

    $slug = admin_unique_slug($pdo, 'categorias_articulos', slugify($name));
    $statement = $pdo->prepare('INSERT INTO categorias_articulos (nombre, slug) VALUES (:nombre, :slug)');
    $statement->execute(['nombre' => $name, 'slug' => $slug]);
}

function admin_create_article(array $input, array $author): void
{
    $pdo = admin_database();
    if (!$pdo) {
        throw new RuntimeException('La base de datos no esta disponible.');
    }

    $title = clean_text((string) ($input['title'] ?? ''));
    $summary = clean_text((string) ($input['summary'] ?? ''));
    $content = trim((string) ($input['content'] ?? ''));
    $status = in_array(($input['status'] ?? ''), ['BORRADOR', 'REVISION', 'PUBLICADO', 'ARCHIVADO'], true)
        ? (string) $input['status']
        : 'BORRADOR';
    $categoryId = (int) ($input['category_id'] ?? 0);

    if (strlen($title) < 4) {
        throw new InvalidArgumentException('El articulo necesita titulo.');
    }
    if ($categoryId <= 0) {
        throw new InvalidArgumentException('Selecciona una categoria.');
    }

    $slug = admin_unique_slug($pdo, 'articulos', slugify($title));
    $publishedAt = $status === 'PUBLICADO' ? db_datetime() : null;
    $statement = $pdo->prepare(
        'INSERT INTO articulos (autor_usuario_id, categoria_id, titulo, slug, resumen, contenido, estado, publicado_at)
        VALUES (:autor_usuario_id, :categoria_id, :titulo, :slug, :resumen, :contenido, :estado, :publicado_at)'
    );
    $statement->execute([
        'autor_usuario_id' => (int) ($author['db_id'] ?? 0) ?: null,
        'categoria_id' => $categoryId,
        'titulo' => $title,
        'slug' => $slug,
        'resumen' => $summary,
        'contenido' => $content,
        'estado' => $status,
        'publicado_at' => $publishedAt,
    ]);
}

function admin_unique_slug(PDO $pdo, string $table, string $baseSlug): string
{
    $slug = $baseSlug;
    $counter = 2;
    $statement = $pdo->prepare('SELECT COUNT(*) FROM `' . str_replace('`', '``', $table) . '` WHERE slug = :slug');

    while (true) {
        $statement->execute(['slug' => $slug]);
        if ((int) $statement->fetchColumn() === 0) {
            return $slug;
        }

        $slug = $baseSlug . '-' . $counter;
        $counter++;
    }
}
