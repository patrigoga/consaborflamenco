<?php
declare(strict_types=1);

function admin_sections(): array
{
    return [
        'panel' => [
            'label' => 'Panel',
            'items' => [
                'general' => ['label' => 'Vista general', 'target' => 'general'],
            ],
        ],
        'usuarios' => [
            'label' => 'Usuarios',
            'items' => [
                'miembros' => ['label' => 'Miembros', 'target' => 'miembros'],
                'setters' => ['label' => 'Setters', 'target' => 'setters'],
            ],
        ],
        'contenido' => [
            'label' => 'Contenido',
            'items' => [
                'articulos' => ['label' => 'Articulos', 'target' => 'articulos'],
                'categorias' => ['label' => 'Categorias', 'target' => 'categorias'],
                'servicios' => ['label' => 'Servicios', 'target' => 'servicios-admin'],
                'legal' => ['label' => 'Contenido legal', 'target' => 'legal'],
            ],
        ],
        'publicidad' => [
            'label' => 'Publicidad',
            'items' => [
                'banners' => ['label' => 'Banners', 'target' => 'banners'],
            ],
        ],
        'finanzas' => [
            'label' => 'Finanzas',
            'items' => [
                'comisiones' => ['label' => 'Comisiones', 'target' => 'comisiones'],
            ],
        ],
        'contacto' => [
            'label' => 'Contacto',
            'items' => [
                'contacto' => ['label' => 'Configuracion de contacto', 'target' => 'contacto-admin'],
                'mensajes' => ['label' => 'Mensajes recibidos', 'target' => 'mensajes-contacto'],
            ],
        ],
    ];
}

function admin_section_targets(): array
{
    $targets = [];
    foreach (admin_sections() as $group) {
        foreach ($group['items'] as $key => $item) {
            $targets[$key] = (string) $item['target'];
        }
    }

    return $targets;
}

function admin_active_section_key(): string
{
    $section = clean_text((string) ($_GET['section'] ?? 'general'));
    return array_key_exists($section, admin_section_targets()) ? $section : 'general';
}

function admin_section_url(string $section, array $params = []): string
{
    $query = array_merge(['section' => $section], $params);
    return 'panel-admin.php?' . http_build_query(array_filter($query, static fn ($value): bool => $value !== null && $value !== ''));
}

function admin_badge_class(string $status): string
{
    $status = strtoupper(trim($status));
    if (in_array($status, ['ACTIVO', 'ACTIVE', 'PUBLICADO', 'PUBLISHED', 'APROBADO', 'APPROVED', 'PAGADO', 'PAID', 'VALIDADA', 'AL_DIA', 'VIP', 'COMPLETO'], true)) {
        return 'status-pill-active';
    }
    if (in_array($status, ['PENDIENTE', 'PENDING', 'REVISION', 'VALIDATING', 'PENDIENTE_PAGO', 'PENDIENTE_COBRO', 'NEW', 'NUEVO', 'SIMPATIZANTE'], true)) {
        return 'status-pill-pending';
    }
    if (in_array($status, ['SUSPENDIDO', 'RECHAZADO', 'REJECTED', 'SPAM', 'FALLIDO', 'BLOQUEADAS'], true)) {
        return 'status-pill-danger';
    }
    if (in_array($status, ['ARCHIVADO', 'ARCHIVED', 'BORRADOR', 'DRAFT', 'INACTIVO', 'INACTIVE', 'CADUCADO', 'CANCELADO'], true)) {
        return 'status-pill-neutral';
    }

    return 'status-pill-info';
}

function admin_status_badge(?string $status, ?string $label = null): string
{
    $status = clean_text((string) ($status ?? '-'));
    $text = $label !== null ? $label : str_replace('_', ' ', $status);
    return '<span class="status-pill ' . e(admin_badge_class($status)) . '">' . e($text) . '</span>';
}

function admin_recent_items(array $items, int $limit = 5): array
{
    return array_slice(array_values($items), 0, max(1, $limit));
}

function admin_safe_img(?string $path, string $alt, string $class = 'admin-thumb'): string
{
    $path = clean_text((string) ($path ?? ''));
    if ($path === '') {
        return '<span class="' . e($class) . ' admin-thumb-fallback" aria-hidden="true">' . e(strtoupper(substr($alt, 0, 1) ?: 'C')) . '</span>';
    }

    return '<img class="' . e($class) . '" src="' . e($path) . '" alt="' . e($alt) . '">';
}
