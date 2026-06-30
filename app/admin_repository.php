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

function admin_dashboard_default_stats(array $overrides = []): array
{
    return array_merge([
        'users_total' => 0,
        'users_active' => 0,
        'users_inactive' => 0,
        'users_suspended' => 0,
        'users_verified' => 0,
        'users_email_pending' => 0,
        'users_new_7d' => 0,
        'users_new_30d' => 0,
        'admins' => 0,
        'members' => 0,
        'members_sympathizer' => 0,
        'members_vip' => 0,
        'members_pending' => 0,
        'members_inactive' => 0,
        'members_suspended' => 0,
        'profiles_complete' => 0,
        'profiles_pending' => 0,
        'member_types' => 0,
        'member_types_total' => 0,
        'member_cards' => 0,
        'member_cards_active' => 0,
        'curriculum_items' => 0,
        'curriculum_items_visible' => 0,
        'setters' => 0,
        'setters_active' => 0,
        'setters_pending' => 0,
        'setters_paused' => 0,
        'setters_suspended' => 0,
        'setters_docs_pending' => 0,
        'setters_docs_validated' => 0,
        'setters_commissions_pending' => 0,
        'setters_commissions_paid' => 0,
        'articles' => 0,
        'articles_published' => 0,
        'articles_draft' => 0,
        'articles_review' => 0,
        'articles_archived' => 0,
        'articles_new_30d' => 0,
        'categories' => 0,
        'categories_active' => 0,
        'banners' => 0,
        'banners_active' => 0,
        'banners_current' => 0,
        'banners_pending_payment' => 0,
        'banners_paid' => 0,
        'banners_draft' => 0,
        'banners_expired' => 0,
        'banners_rejected' => 0,
        'banners_expiring_7d' => 0,
        'leads' => 0,
        'leads_30d' => 0,
        'sales' => 0,
        'payments_paid' => 0,
        'payments_pending' => 0,
        'payments_failed' => 0,
        'payments_refunded' => 0,
        'payments_cancelled' => 0,
        'revenue_paid_cents' => 0,
        'revenue_pending_cents' => 0,
        'provinces' => 0,
        'password_reset_tokens' => 0,
        'password_reset_tokens_active' => 0,
        'password_reset_tokens_used' => 0,
    ], $overrides);
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
        return admin_dashboard_default_stats([
            'users_total' => count($users),
            'members' => count(array_filter($users, static fn (array $user): bool => ($user['role'] ?? 'user') === 'user')),
            'setters' => count(array_filter($users, static fn (array $user): bool => ($user['role'] ?? 'user') === 'setter')),
            'admins' => count(array_filter($users, static fn (array $user): bool => ($user['role'] ?? 'user') === 'admin')),
            'users_verified' => count(array_filter($users, static fn (array $user): bool => clean_text((string) ($user['email_verified_at'] ?? '')) !== '')),
            'users_email_pending' => count(array_filter($users, static fn (array $user): bool => clean_text((string) ($user['email_verified_at'] ?? '')) === '')),
        ]);
    }

    return admin_dashboard_default_stats([
        'users_total' => admin_safe_count($pdo, 'SELECT COUNT(*) FROM usuarios'),
        'users_active' => admin_safe_count($pdo, "SELECT COUNT(*) FROM usuarios WHERE estado = 'ACTIVO'"),
        'users_inactive' => admin_safe_count($pdo, "SELECT COUNT(*) FROM usuarios WHERE estado = 'INACTIVO'"),
        'users_suspended' => admin_safe_count($pdo, "SELECT COUNT(*) FROM usuarios WHERE estado = 'SUSPENDIDO'"),
        'users_verified' => admin_safe_count($pdo, 'SELECT COUNT(*) FROM usuarios WHERE email_verified_at IS NOT NULL'),
        'users_email_pending' => admin_safe_count($pdo, 'SELECT COUNT(*) FROM usuarios WHERE email_verified_at IS NULL'),
        'users_new_7d' => admin_safe_count($pdo, 'SELECT COUNT(*) FROM usuarios WHERE created_at >= (UTC_TIMESTAMP() - INTERVAL 7 DAY)'),
        'users_new_30d' => admin_safe_count($pdo, 'SELECT COUNT(*) FROM usuarios WHERE created_at >= (UTC_TIMESTAMP() - INTERVAL 30 DAY)'),
        'admins' => admin_safe_count($pdo, "SELECT COUNT(*) FROM usuarios WHERE rol = 'ADMIN'"),
        'members' => admin_safe_count($pdo, "SELECT COUNT(*) FROM usuarios WHERE rol = 'MIEMBRO'"),
        'members_sympathizer' => admin_safe_count($pdo, "SELECT COUNT(*) FROM miembros WHERE estado = 'SIMPATIZANTE'"),
        'members_vip' => admin_safe_count($pdo, "SELECT COUNT(*) FROM miembros WHERE estado = 'VIP'"),
        'members_pending' => admin_safe_count($pdo, "SELECT COUNT(*) FROM miembros WHERE estado = 'PENDIENTE'"),
        'members_inactive' => admin_safe_count($pdo, "SELECT COUNT(*) FROM miembros WHERE estado = 'INACTIVO'"),
        'members_suspended' => admin_safe_count($pdo, "SELECT COUNT(*) FROM miembros WHERE estado = 'SUSPENDIDO'"),
        'profiles_complete' => admin_safe_count($pdo, 'SELECT COUNT(*) FROM miembros WHERE perfil_completo_at IS NOT NULL'),
        'profiles_pending' => admin_safe_count($pdo, 'SELECT COUNT(*) FROM miembros WHERE perfil_completo_at IS NULL'),
        'setters' => admin_safe_count($pdo, "SELECT COUNT(*) FROM usuarios WHERE rol = 'SETTER'"),
        'setters_active' => admin_safe_count($pdo, "SELECT COUNT(*) FROM appointment_setters WHERE estado_cuenta = 'ACTIVO'"),
        'setters_pending' => admin_safe_count($pdo, "SELECT COUNT(*) FROM appointment_setters WHERE estado_cuenta = 'PENDIENTE'"),
        'setters_paused' => admin_safe_count($pdo, "SELECT COUNT(*) FROM appointment_setters WHERE estado_cuenta = 'PAUSADO'"),
        'setters_suspended' => admin_safe_count($pdo, "SELECT COUNT(*) FROM appointment_setters WHERE estado_cuenta = 'SUSPENDIDO'"),
        'setters_docs_pending' => admin_safe_count($pdo, "SELECT COUNT(*) FROM appointment_setters WHERE estado_documentacion = 'PENDIENTE'"),
        'setters_docs_validated' => admin_safe_count($pdo, "SELECT COUNT(*) FROM appointment_setters WHERE estado_documentacion = 'VALIDADA'"),
        'setters_commissions_pending' => admin_safe_count($pdo, "SELECT COUNT(*) FROM appointment_setters WHERE estado_comisiones = 'PENDIENTE_COBRO'"),
        'setters_commissions_paid' => admin_safe_count($pdo, "SELECT COUNT(*) FROM appointment_setters WHERE estado_comisiones = 'AL_DIA'"),
        'articles' => admin_safe_count($pdo, 'SELECT COUNT(*) FROM articulos'),
        'articles_published' => admin_safe_count($pdo, "SELECT COUNT(*) FROM articulos WHERE estado = 'PUBLICADO'"),
        'articles_draft' => admin_safe_count($pdo, "SELECT COUNT(*) FROM articulos WHERE estado = 'BORRADOR'"),
        'articles_review' => admin_safe_count($pdo, "SELECT COUNT(*) FROM articulos WHERE estado = 'REVISION'"),
        'articles_archived' => admin_safe_count($pdo, "SELECT COUNT(*) FROM articulos WHERE estado = 'ARCHIVADO'"),
        'articles_new_30d' => admin_safe_count($pdo, 'SELECT COUNT(*) FROM articulos WHERE created_at >= (UTC_TIMESTAMP() - INTERVAL 30 DAY)'),
        'banners' => admin_safe_count($pdo, 'SELECT COUNT(*) FROM banners_miembro'),
        'banners_active' => admin_safe_count($pdo, "SELECT COUNT(*) FROM banners_miembro WHERE estado = 'ACTIVO'"),
        'banners_current' => admin_safe_count($pdo, "SELECT COUNT(*) FROM banners_miembro WHERE estado = 'ACTIVO' AND (fecha_inicio_publicacion IS NULL OR fecha_inicio_publicacion <= UTC_TIMESTAMP()) AND (fecha_fin_publicacion IS NULL OR fecha_fin_publicacion >= UTC_TIMESTAMP())"),
        'banners_pending_payment' => admin_safe_count($pdo, "SELECT COUNT(*) FROM banners_miembro WHERE estado = 'PENDIENTE_PAGO'"),
        'banners_paid' => admin_safe_count($pdo, "SELECT COUNT(*) FROM banners_miembro WHERE estado = 'PAGADO'"),
        'banners_draft' => admin_safe_count($pdo, "SELECT COUNT(*) FROM banners_miembro WHERE estado = 'BORRADOR'"),
        'banners_expired' => admin_safe_count($pdo, "SELECT COUNT(*) FROM banners_miembro WHERE estado = 'CADUCADO'"),
        'banners_rejected' => admin_safe_count($pdo, "SELECT COUNT(*) FROM banners_miembro WHERE estado = 'RECHAZADO'"),
        'banners_expiring_7d' => admin_safe_count($pdo, "SELECT COUNT(*) FROM banners_miembro WHERE estado = 'ACTIVO' AND fecha_fin_publicacion BETWEEN UTC_TIMESTAMP() AND (UTC_TIMESTAMP() + INTERVAL 7 DAY)"),
        'categories' => admin_safe_count($pdo, 'SELECT COUNT(*) FROM categorias_articulos'),
        'categories_active' => admin_safe_count($pdo, 'SELECT COUNT(*) FROM categorias_articulos WHERE activo = TRUE'),
        'leads' => admin_safe_count($pdo, 'SELECT COUNT(*) FROM usos_codigo_descuento'),
        'leads_30d' => admin_safe_count($pdo, 'SELECT COUNT(*) FROM usos_codigo_descuento WHERE usado_at >= (UTC_TIMESTAMP() - INTERVAL 30 DAY)'),
        'sales' => admin_safe_count($pdo, 'SELECT COUNT(*) FROM pagos_stripe'),
        'payments_paid' => admin_safe_count($pdo, "SELECT COUNT(*) FROM pagos_stripe WHERE estado = 'PAGADO'"),
        'payments_pending' => admin_safe_count($pdo, "SELECT COUNT(*) FROM pagos_stripe WHERE estado = 'PENDIENTE'"),
        'payments_failed' => admin_safe_count($pdo, "SELECT COUNT(*) FROM pagos_stripe WHERE estado = 'FALLIDO'"),
        'payments_refunded' => admin_safe_count($pdo, "SELECT COUNT(*) FROM pagos_stripe WHERE estado = 'REEMBOLSADO'"),
        'payments_cancelled' => admin_safe_count($pdo, "SELECT COUNT(*) FROM pagos_stripe WHERE estado = 'CANCELADO'"),
        'revenue_paid_cents' => admin_safe_count($pdo, "SELECT COALESCE(SUM(importe_centimos), 0) FROM pagos_stripe WHERE estado = 'PAGADO'"),
        'revenue_pending_cents' => admin_safe_count($pdo, "SELECT COALESCE(SUM(importe_centimos), 0) FROM pagos_stripe WHERE estado = 'PENDIENTE'"),
        'member_types' => admin_safe_count($pdo, 'SELECT COUNT(*) FROM tipos_miembro WHERE activo = TRUE'),
        'member_types_total' => admin_safe_count($pdo, 'SELECT COUNT(*) FROM tipos_miembro'),
        'member_cards' => admin_safe_count($pdo, 'SELECT COUNT(*) FROM tarjetas_miembro'),
        'member_cards_active' => admin_safe_count($pdo, 'SELECT COUNT(*) FROM tarjetas_miembro WHERE activa = TRUE'),
        'curriculum_items' => admin_safe_count($pdo, 'SELECT COUNT(*) FROM miembros_curriculum_items'),
        'curriculum_items_visible' => admin_safe_count($pdo, 'SELECT COUNT(*) FROM miembros_curriculum_items WHERE visible_publico = TRUE'),
        'provinces' => admin_safe_count($pdo, 'SELECT COUNT(*) FROM provincias'),
        'password_reset_tokens' => admin_safe_count($pdo, 'SELECT COUNT(*) FROM password_reset_tokens'),
        'password_reset_tokens_active' => admin_safe_count($pdo, 'SELECT COUNT(*) FROM password_reset_tokens WHERE used_at IS NULL AND expires_at > UTC_TIMESTAMP()'),
        'password_reset_tokens_used' => admin_safe_count($pdo, 'SELECT COUNT(*) FROM password_reset_tokens WHERE used_at IS NOT NULL'),
    ]);
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
