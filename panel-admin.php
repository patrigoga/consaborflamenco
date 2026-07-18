<?php
declare(strict_types=1);

require_once __DIR__ . '/app/admin_repository.php';
require_once __DIR__ . '/app/legal_repository.php';
require_once __DIR__ . '/app/site_content_repository.php';
require_once __DIR__ . '/app/layout.php';

$user = require_login();
if (($user['role'] ?? 'user') !== 'admin') {
    redirect_to('panel-usuario.php');
}

$adminMessages = [];
$adminErrors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $adminErrors[] = 'La sesion ha caducado. Vuelve a intentarlo.';
    } else {
        try {
            $action = (string) ($_POST['admin_action'] ?? '');
            if ($action === 'create_category') {
                admin_create_category((string) ($_POST['category_name'] ?? ''));
                $adminMessages[] = 'Categoria creada.';
            } elseif ($action === 'create_article') {
                admin_create_article($_POST, $user);
                $adminMessages[] = 'Articulo guardado.';
            } elseif ($action === 'update_legal_document') {
                legal_update_document($_POST, $user);
                $adminMessages[] = 'Documento legal actualizado.';
            } elseif ($action === 'save_service') {
                site_save_service($_POST, $_FILES, $user);
                $adminMessages[] = !empty($_POST['service_id']) ? 'Servicio actualizado.' : 'Servicio creado.';
            } elseif ($action === 'toggle_service_status') {
                site_toggle_service_status((int) ($_POST['service_id'] ?? 0), (string) ($_POST['status'] ?? 'INACTIVE'), $user);
                $adminMessages[] = 'Estado del servicio actualizado.';
            } elseif ($action === 'toggle_service_featured') {
                site_toggle_service_featured((int) ($_POST['service_id'] ?? 0), !empty($_POST['is_featured']), $user);
                $adminMessages[] = 'Destacado del servicio actualizado.';
            } elseif ($action === 'update_service_order') {
                site_update_service_order((int) ($_POST['service_id'] ?? 0), (int) ($_POST['display_order'] ?? 0), $user);
                $adminMessages[] = 'Orden del servicio actualizado.';
            } elseif ($action === 'delete_service') {
                site_delete_service((int) ($_POST['service_id'] ?? 0));
                $adminMessages[] = 'Servicio eliminado.';
            } elseif ($action === 'save_contact_settings') {
                site_save_contact_settings($_POST, $_FILES, $user);
                $adminMessages[] = 'Area profesional de contacto actualizada.';
            } elseif ($action === 'update_contact_message_status') {
                site_update_contact_message_status((int) ($_POST['message_id'] ?? 0), (string) ($_POST['status'] ?? 'READ'));
                $adminMessages[] = 'Estado del mensaje actualizado.';
            } elseif ($action === 'delete_contact_message') {
                site_delete_contact_message((int) ($_POST['message_id'] ?? 0));
                $adminMessages[] = 'Mensaje eliminado.';
            }
        } catch (Throwable $exception) {
            $adminErrors[] = $exception->getMessage();
        }
    }
}

$databaseReady = admin_database() instanceof PDO;
$stats = admin_dashboard_stats();
$members = admin_members();
$setters = admin_setters();
$categories = admin_article_categories();
$articles = admin_articles();
$banners = admin_banners();
$legalDocuments = legal_documents_all();
$services = site_services_all();
$contactSettings = site_contact_settings();
$contactMessages = site_contact_messages();
$editingService = null;
if (isset($_GET['edit_service'])) {
    $editingService = site_service_by_id((int) $_GET['edit_service']);
}
$selectedContactMessage = null;
if (isset($_GET['contact_message'])) {
    $selectedContactMessage = site_contact_message_by_id((int) $_GET['contact_message']);
}

function admin_date(?string $value): string
{
    if (!$value) {
        return '-';
    }

    $timestamp = strtotime($value);
    return $timestamp ? date('d/m/Y', $timestamp) : $value;
}

function admin_stat(array $stats, string $key): int
{
    return (int) ($stats[$key] ?? 0);
}

function admin_metric_number(int $value): string
{
    return number_format($value, 0, ',', '.');
}

function admin_metric_money(int $cents): string
{
    return number_format($cents / 100, 2, ',', '.') . ' €';
}

function admin_metric_percent(int $part, int $total): string
{
    if ($total <= 0) {
        return '0%';
    }

    return number_format(($part / $total) * 100, 1, ',', '.') . '%';
}

$paidPayments = admin_stat($stats, 'payments_paid');
$paidRevenueCents = admin_stat($stats, 'revenue_paid_cents');
$averageTicketCents = $paidPayments > 0 ? (int) round($paidRevenueCents / $paidPayments) : 0;
$vipAnnualPotentialCents = admin_stat($stats, 'members_vip') * 8000;

$kpiGroups = [
    [
        'title' => 'Comunidad',
        'kicker' => 'Usuarios',
        'items' => [
            ['label' => 'Usuarios totales', 'value' => admin_metric_number(admin_stat($stats, 'users_total')), 'detail' => 'Altas 30 dias: ' . admin_metric_number(admin_stat($stats, 'users_new_30d'))],
            ['label' => 'Usuarios activos', 'value' => admin_metric_number(admin_stat($stats, 'users_active')), 'detail' => 'Suspendidos: ' . admin_metric_number(admin_stat($stats, 'users_suspended'))],
            ['label' => 'Email verificado', 'value' => admin_metric_number(admin_stat($stats, 'users_verified')), 'detail' => admin_metric_percent(admin_stat($stats, 'users_verified'), admin_stat($stats, 'users_total')) . ' del total'],
            ['label' => 'Email pendiente', 'value' => admin_metric_number(admin_stat($stats, 'users_email_pending')), 'detail' => 'Altas 7 dias: ' . admin_metric_number(admin_stat($stats, 'users_new_7d'))],
            ['label' => 'Administradores', 'value' => admin_metric_number(admin_stat($stats, 'admins')), 'detail' => 'Acceso al panel admin'],
            ['label' => 'Provincias', 'value' => admin_metric_number(admin_stat($stats, 'provinces')), 'detail' => 'Base territorial disponible'],
        ],
    ],
    [
        'title' => 'Miembros y perfiles',
        'kicker' => 'Comunidad flamenca',
        'items' => [
            ['label' => 'Miembros', 'value' => admin_metric_number(admin_stat($stats, 'members')), 'detail' => 'Total con rol miembro'],
            ['label' => 'Simpatizantes', 'value' => admin_metric_number(admin_stat($stats, 'members_sympathizer')), 'detail' => 'Sin descuentos VIP'],
            ['label' => 'Miembros VIP', 'value' => admin_metric_number(admin_stat($stats, 'members_vip')), 'detail' => admin_metric_percent(admin_stat($stats, 'members_vip'), admin_stat($stats, 'members')) . ' de miembros'],
            ['label' => 'Potencial VIP anual', 'value' => admin_metric_money($vipAnnualPotentialCents), 'detail' => '80 €/ano por VIP actual'],
            ['label' => 'Perfiles completos', 'value' => admin_metric_number(admin_stat($stats, 'profiles_complete')), 'detail' => admin_metric_percent(admin_stat($stats, 'profiles_complete'), admin_stat($stats, 'members')) . ' completado'],
            ['label' => 'Perfiles pendientes', 'value' => admin_metric_number(admin_stat($stats, 'profiles_pending')), 'detail' => 'Fichas por completar'],
            ['label' => 'Tarjetas creadas', 'value' => admin_metric_number(admin_stat($stats, 'member_cards')), 'detail' => 'Activas: ' . admin_metric_number(admin_stat($stats, 'member_cards_active'))],
            ['label' => 'Items curriculum', 'value' => admin_metric_number(admin_stat($stats, 'curriculum_items')), 'detail' => 'Visibles: ' . admin_metric_number(admin_stat($stats, 'curriculum_items_visible'))],
            ['label' => 'Tipos de miembro', 'value' => admin_metric_number(admin_stat($stats, 'member_types')), 'detail' => 'Total: ' . admin_metric_number(admin_stat($stats, 'member_types_total'))],
        ],
    ],
    [
        'title' => 'Appointment setters',
        'kicker' => 'Comercial',
        'items' => [
            ['label' => 'Setters', 'value' => admin_metric_number(admin_stat($stats, 'setters')), 'detail' => 'Usuarios comerciales'],
            ['label' => 'Setters activos', 'value' => admin_metric_number(admin_stat($stats, 'setters_active')), 'detail' => admin_metric_percent(admin_stat($stats, 'setters_active'), admin_stat($stats, 'setters')) . ' activos'],
            ['label' => 'Setters pendientes', 'value' => admin_metric_number(admin_stat($stats, 'setters_pending')), 'detail' => 'Pausados: ' . admin_metric_number(admin_stat($stats, 'setters_paused'))],
            ['label' => 'Documentacion pendiente', 'value' => admin_metric_number(admin_stat($stats, 'setters_docs_pending')), 'detail' => 'Validada: ' . admin_metric_number(admin_stat($stats, 'setters_docs_validated'))],
            ['label' => 'Comisiones pendientes', 'value' => admin_metric_number(admin_stat($stats, 'setters_commissions_pending')), 'detail' => 'Al dia: ' . admin_metric_number(admin_stat($stats, 'setters_commissions_paid'))],
        ],
    ],
    [
        'title' => 'Revista y contenido',
        'kicker' => 'Editorial',
        'items' => [
            ['label' => 'Articulos', 'value' => admin_metric_number(admin_stat($stats, 'articles')), 'detail' => 'Nuevos 30 dias: ' . admin_metric_number(admin_stat($stats, 'articles_new_30d'))],
            ['label' => 'Publicados', 'value' => admin_metric_number(admin_stat($stats, 'articles_published')), 'detail' => admin_metric_percent(admin_stat($stats, 'articles_published'), admin_stat($stats, 'articles')) . ' publicados'],
            ['label' => 'Borradores', 'value' => admin_metric_number(admin_stat($stats, 'articles_draft')), 'detail' => 'En revision: ' . admin_metric_number(admin_stat($stats, 'articles_review'))],
            ['label' => 'Archivados', 'value' => admin_metric_number(admin_stat($stats, 'articles_archived')), 'detail' => 'Fuera de publicacion'],
            ['label' => 'Categorias', 'value' => admin_metric_number(admin_stat($stats, 'categories')), 'detail' => 'Activas: ' . admin_metric_number(admin_stat($stats, 'categories_active'))],
        ],
    ],
    [
        'title' => 'Publicidad y banners',
        'kicker' => 'Inventario',
        'items' => [
            ['label' => 'Banners totales', 'value' => admin_metric_number(admin_stat($stats, 'banners')), 'detail' => 'Todos los estados'],
            ['label' => 'Banners activos', 'value' => admin_metric_number(admin_stat($stats, 'banners_active')), 'detail' => 'Vigentes ahora: ' . admin_metric_number(admin_stat($stats, 'banners_current'))],
            ['label' => 'Pendientes de pago', 'value' => admin_metric_number(admin_stat($stats, 'banners_pending_payment')), 'detail' => 'Pagados sin activar: ' . admin_metric_number(admin_stat($stats, 'banners_paid'))],
            ['label' => 'Borradores', 'value' => admin_metric_number(admin_stat($stats, 'banners_draft')), 'detail' => 'Rechazados: ' . admin_metric_number(admin_stat($stats, 'banners_rejected'))],
            ['label' => 'Caducados', 'value' => admin_metric_number(admin_stat($stats, 'banners_expired')), 'detail' => 'Caducan 7 dias: ' . admin_metric_number(admin_stat($stats, 'banners_expiring_7d'))],
        ],
    ],
    [
        'title' => 'Ventas, leads y cobros',
        'kicker' => 'Ingresos',
        'items' => [
            ['label' => 'Leads por codigo', 'value' => admin_metric_number(admin_stat($stats, 'leads')), 'detail' => 'Ultimos 30 dias: ' . admin_metric_number(admin_stat($stats, 'leads_30d'))],
            ['label' => 'Pagos totales', 'value' => admin_metric_number(admin_stat($stats, 'sales')), 'detail' => 'Registros Stripe'],
            ['label' => 'Pagos cobrados', 'value' => admin_metric_number(admin_stat($stats, 'payments_paid')), 'detail' => 'Fallidos: ' . admin_metric_number(admin_stat($stats, 'payments_failed'))],
            ['label' => 'Pagos pendientes', 'value' => admin_metric_number(admin_stat($stats, 'payments_pending')), 'detail' => 'Cancelados: ' . admin_metric_number(admin_stat($stats, 'payments_cancelled'))],
            ['label' => 'Ingresos cobrados', 'value' => admin_metric_money($paidRevenueCents), 'detail' => 'Importe pagado confirmado'],
            ['label' => 'Ingresos pendientes', 'value' => admin_metric_money(admin_stat($stats, 'revenue_pending_cents')), 'detail' => 'Pendiente de confirmar'],
            ['label' => 'Ticket medio cobrado', 'value' => admin_metric_money($averageTicketCents), 'detail' => 'Sobre pagos cobrados'],
            ['label' => 'Reembolsos', 'value' => admin_metric_number(admin_stat($stats, 'payments_refunded')), 'detail' => 'Pagos reembolsados'],
        ],
    ],
    [
        'title' => 'Sistema',
        'kicker' => 'Operativa',
        'items' => [
            ['label' => 'Estado BD', 'value' => $databaseReady ? 'OK' : 'Sin BD', 'detail' => $databaseReady ? 'Conexion activa' : 'Lectura de respaldo'],
            ['label' => 'Tokens reset', 'value' => admin_metric_number(admin_stat($stats, 'password_reset_tokens')), 'detail' => 'Activos: ' . admin_metric_number(admin_stat($stats, 'password_reset_tokens_active'))],
            ['label' => 'Tokens usados', 'value' => admin_metric_number(admin_stat($stats, 'password_reset_tokens_used')), 'detail' => 'Recuperaciones completadas'],
        ],
    ],
];
?>
<!DOCTYPE html>
<html lang="es">
<?php page_head('Panel de administracion | Con Sabor Flamenco', 'Panel de administracion de Con Sabor Flamenco.', false); ?>
<body>
    <?php page_header(); ?>
    
    <!-- Sidebar de Administración -->
    <div class="admin-container">
        <button id="admin-sidebar-toggle" class="admin-sidebar-toggle" aria-label="Abrir menú de administración">
            <span aria-hidden="true">☰</span>
            <span>Menú</span>
        </button>
        
        <aside class="admin-sidebar">
            <div class="admin-sidebar-header">
                <h2>Panel Admin</h2>
                <button class="admin-sidebar-close" aria-label="Cerrar menú" style="display: none;">
                    <span aria-hidden="true">✕</span>
                </button>
            </div>
            
            <nav class="admin-sidebar-nav">
                <a href="#" class="admin-sidebar-link is-active" data-target="general">
                    <span aria-hidden="true">📊</span> Vista General
                </a>
                <a href="#" class="admin-sidebar-link" data-target="miembros">
                    <span aria-hidden="true">👥</span> Miembros
                </a>
                <a href="#" class="admin-sidebar-link" data-target="setters">
                    <span aria-hidden="true">🎯</span> Setters
                </a>
                <a href="#" class="admin-sidebar-link" data-target="articulos">
                    <span aria-hidden="true">📝</span> Artículos
                </a>
                <a href="#" class="admin-sidebar-link" data-target="legal">
                    <span aria-hidden="true">§</span> Contenido legal
                </a>
                <a href="#" class="admin-sidebar-link" data-target="servicios-admin">
                    <span aria-hidden="true">S</span> Servicios
                </a>
                <a href="#" class="admin-sidebar-link" data-target="contacto-admin">
                    <span aria-hidden="true">@</span> Contacto profesional
                </a>
                <a href="#" class="admin-sidebar-link" data-target="mensajes-contacto">
                    <span aria-hidden="true">M</span> Mensajes contacto
                </a>
                <a href="#" class="admin-sidebar-link" data-target="banners">
                    <span aria-hidden="true">🎨</span> Banners
                </a>
            </nav>
        </aside>
        
        <main class="admin-main-content">
        <section class="page-intro" data-ad-category="GENERAL">
            <p class="section-kicker">Administracion</p>
            <h1>Panel de administracion</h1>
            <p>Control inicial de miembros, setters, articulos, categorias y banners contratados.</p>
        </section>

        <section class="content-section admin-shell" id="general">
            <div class="section-heading">
                <div class="section-heading-content">
                    <p class="section-kicker">Control central</p>
                    <h2>Vista general</h2>
                    <p><?= $databaseReady ? 'Base de datos conectada.' : 'Base de datos no disponible: usando lectura de respaldo.' ?></p>
                </div>
                <a class="section-enter-link" href="panel-usuario.php">Vista usuario</a>
            </div>

            <?php if ($adminMessages): ?>
                <div class="form-alert form-alert-success" role="status">
                    <?php foreach ($adminMessages as $message): ?><p><?= e($message) ?></p><?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php if ($adminErrors): ?>
                <div class="form-alert form-alert-error" role="alert">
                    <?php foreach ($adminErrors as $error): ?><p><?= e($error) ?></p><?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="admin-kpi-groups">
                <?php foreach ($kpiGroups as $group): ?>
                    <section class="admin-kpi-group" aria-label="<?= e($group['title']) ?>">
                        <div class="admin-kpi-group-heading">
                            <span><?= e($group['kicker']) ?></span>
                            <h3><?= e($group['title']) ?></h3>
                        </div>
                        <div class="admin-metric-grid admin-kpi-grid">
                            <?php foreach ($group['items'] as $item): ?>
                                <article class="admin-metric-card admin-kpi-card">
                                    <span><?= e($item['label']) ?></span>
                                    <strong><?= e((string) $item['value']) ?></strong>
                                    <?php if (!empty($item['detail'])): ?><small><?= e((string) $item['detail']) ?></small><?php endif; ?>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="content-section admin-shell" id="miembros">
            <div class="section-heading">
                <div class="section-heading-content">
                    <p class="section-kicker">Comunidad</p>
                    <h2>Miembros registrados</h2>
                    <p>Estado de cuenta, tipo de espacio, membresia y perfil.</p>
                </div>
            </div>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Miembro</th>
                            <th>Tipo</th>
                            <th>Membresia</th>
                            <th>Numero</th>
                            <th>Perfil</th>
                            <th>Alta</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$members): ?>
                            <tr><td colspan="6">Todavia no hay miembros registrados.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($members as $member): ?>
                            <tr>
                                <td><strong><?= e((string) ($member['nombre_publico'] ?? $member['name'] ?? $member['nombre'] ?? 'Miembro')) ?></strong><small><?= e((string) ($member['email'] ?? '')) ?></small></td>
                                <td><?= e((string) ($member['tipo_miembro'] ?? '-')) ?></td>
                                <td><span class="status-pill status-pill-pending"><?= e((string) ($member['miembro_estado'] ?? 'SIMPATIZANTE')) ?></span></td>
                                <td><?= e((string) ($member['numero_miembro'] ?? '-')) ?></td>
                                <td><?= !empty($member['perfil_completo_at']) ? 'Completo' : 'Pendiente' ?></td>
                                <td><?= e(admin_date($member['created_at'] ?? null)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="content-section admin-shell" id="setters">
            <div class="section-heading">
                <div class="section-heading-content">
                    <p class="section-kicker">Comercial</p>
                    <h2>Appointment setters</h2>
                    <p>Seguimiento de estados de cuenta, documentacion, comisiones y codigos.</p>
                </div>
            </div>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Setter</th>
                            <th>Cuenta</th>
                            <th>Documentacion</th>
                            <th>Comisiones</th>
                            <th>Codigo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$setters): ?>
                            <tr><td colspan="5">Todavia no hay setters registrados.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($setters as $setter): ?>
                            <tr>
                                <td><strong><?= e((string) ($setter['nombre_comercial'] ?? $setter['nombre'] ?? 'Setter')) ?></strong><small><?= e((string) ($setter['email'] ?? '')) ?></small></td>
                                <td><?= e((string) ($setter['estado_cuenta'] ?? $setter['usuario_estado'] ?? 'PENDIENTE')) ?></td>
                                <td><?= e((string) ($setter['estado_documentacion'] ?? 'PENDIENTE')) ?></td>
                                <td><?= e((string) ($setter['estado_comisiones'] ?? 'SIN_VENTAS')) ?></td>
                                <td><?= e((string) ($setter['codigo_promocional'] ?? '-')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="content-section admin-shell admin-editor-grid" id="articulos">
            <div class="admin-editor-card">
                <p class="section-kicker">Revista</p>
                <h2>Nueva categoria</h2>
                <form method="post" action="panel-admin.php#articulos" class="admin-form">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="admin_action" value="create_category">
                    <label for="category_name">Nombre de categoria</label>
                    <input id="category_name" name="category_name" type="text" required>
                    <button class="button button-secondary" type="submit" <?= !$databaseReady ? 'disabled' : '' ?>>Crear categoria</button>
                </form>
            </div>

            <div class="admin-editor-card">
                <p class="section-kicker">Contenido</p>
                <h2>Crear articulo</h2>
                <form method="post" action="panel-admin.php#articulos" class="admin-form">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="admin_action" value="create_article">
                    <label for="title">Titulo</label>
                    <input id="title" name="title" type="text" required>
                    <label for="category_id">Categoria</label>
                    <select id="category_id" name="category_id" required>
                        <option value="">Selecciona categoria</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= e((string) $category['id']) ?>"><?= e((string) $category['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label for="summary">Resumen</label>
                    <input id="summary" name="summary" type="text" maxlength="320">
                    <label for="content">Contenido</label>
                    <textarea id="content" name="content" rows="7"></textarea>
                    <label for="status">Estado</label>
                    <select id="status" name="status">
                        <option value="BORRADOR">Borrador</option>
                        <option value="REVISION">Revision</option>
                        <option value="PUBLICADO">Publicado</option>
                        <option value="ARCHIVADO">Archivado</option>
                    </select>
                    <button class="button button-primary" type="submit" <?= !$databaseReady ? 'disabled' : '' ?>>Guardar articulo</button>
                </form>
            </div>
        </section>

        <section class="content-section admin-shell">
            <div class="section-heading">
                <div class="section-heading-content">
                    <p class="section-kicker">Revista</p>
                    <h2>Ultimos articulos</h2>
                </div>
            </div>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead><tr><th>Titulo</th><th>Categoria</th><th>Estado</th><th>Autor</th><th>Fecha</th></tr></thead>
                    <tbody>
                        <?php if (!$articles): ?><tr><td colspan="5">Todavia no hay articulos.</td></tr><?php endif; ?>
                        <?php foreach ($articles as $article): ?>
                            <tr>
                                <td><strong><?= e((string) $article['titulo']) ?></strong><small><?= e((string) $article['slug']) ?></small></td>
                                <td><?= e((string) ($article['categoria_nombre'] ?? '-')) ?></td>
                                <td><?= e((string) $article['estado']) ?></td>
                                <td><?= e((string) ($article['autor_nombre'] ?? '-')) ?></td>
                                <td><?= e(admin_date($article['created_at'] ?? null)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="content-section admin-shell" id="legal">
            <div class="section-heading">
                <div class="section-heading-content">
                    <p class="section-kicker">Legal</p>
                    <h2>Contenido legal</h2>
                    <p>Edicion administrable de terminos, aviso legal, privacidad y cookies.</p>
                </div>
            </div>

            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead><tr><th>Documento</th><th>Slug</th><th>Estado</th><th>Actualizacion</th><th>Ultimo usuario</th><th>Acciones</th></tr></thead>
                    <tbody>
                        <?php if (!$legalDocuments): ?><tr><td colspan="6">No hay documentos legales disponibles o la base de datos no esta conectada.</td></tr><?php endif; ?>
                        <?php foreach ($legalDocuments as $legalDocument): ?>
                            <?php $definition = legal_document_definitions()[$legalDocument['document_key']] ?? []; ?>
                            <tr>
                                <td><strong><?= e((string) $legalDocument['title']) ?></strong><small><?= e((string) $legalDocument['document_key']) ?> · v<?= e((string) $legalDocument['version']) ?></small></td>
                                <td><?= e((string) $legalDocument['slug']) ?></td>
                                <td><span class="status-pill <?= $legalDocument['status'] === 'PUBLISHED' ? 'status-pill-active' : 'status-pill-pending' ?>"><?= e((string) $legalDocument['status']) ?></span></td>
                                <td><?= e(admin_date((string) ($legalDocument['visible_updated_at'] ?? $legalDocument['updated_at'] ?? ''))) ?></td>
                                <td><?= e((string) ($legalDocument['updated_by_name'] ?? '-')) ?></td>
                                <td><a href="<?= e((string) ($definition['page'] ?? '')) ?>" target="_blank" rel="noopener">Previsualizar</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="legal-admin-grid">
                <?php foreach ($legalDocuments as $legalDocument): ?>
                    <?php $versions = legal_versions_for_document((int) $legalDocument['id']); ?>
                    <article class="admin-editor-card legal-admin-card">
                        <p class="section-kicker"><?= e((string) $legalDocument['document_key']) ?></p>
                        <h3><?= e((string) $legalDocument['title']) ?></h3>
                        <form method="post" action="panel-admin.php#legal" class="admin-form">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="admin_action" value="update_legal_document">
                            <input type="hidden" name="document_key" value="<?= e((string) $legalDocument['document_key']) ?>">
                            <label for="legal-title-<?= e((string) $legalDocument['document_key']) ?>">Titulo</label>
                            <input id="legal-title-<?= e((string) $legalDocument['document_key']) ?>" name="title" type="text" value="<?= e((string) $legalDocument['title']) ?>" required>
                            <label for="legal-status-<?= e((string) $legalDocument['document_key']) ?>">Estado</label>
                            <select id="legal-status-<?= e((string) $legalDocument['document_key']) ?>" name="status">
                                <option value="DRAFT" <?= $legalDocument['status'] === 'DRAFT' ? 'selected' : '' ?>>Borrador</option>
                                <option value="PUBLISHED" <?= $legalDocument['status'] === 'PUBLISHED' ? 'selected' : '' ?>>Publicado</option>
                            </select>
                            <label for="legal-date-<?= e((string) $legalDocument['document_key']) ?>">Fecha visible de actualizacion</label>
                            <input id="legal-date-<?= e((string) $legalDocument['document_key']) ?>" name="visible_updated_at" type="date" value="<?= e((string) ($legalDocument['visible_updated_at'] ?? '')) ?>">
                            <label for="legal-content-<?= e((string) $legalDocument['document_key']) ?>">Contenido HTML permitido</label>
                            <textarea id="legal-content-<?= e((string) $legalDocument['document_key']) ?>" name="content" rows="12" required><?= e((string) $legalDocument['content']) ?></textarea>
                            <p class="field-help">Etiquetas permitidas: h2, h3, h4, p, ul, ol, li, strong, em, a y br. Scripts, iframes y eventos se eliminan al guardar.</p>
                            <button class="button button-primary" type="submit" <?= !$databaseReady ? 'disabled' : '' ?>>Guardar documento</button>
                        </form>
                        <details class="legal-version-list">
                            <summary>Versiones anteriores</summary>
                            <?php if (!$versions): ?>
                                <p>No hay versiones anteriores.</p>
                            <?php else: ?>
                                <ul>
                                    <?php foreach ($versions as $version): ?>
                                        <li>v<?= e((string) $version['version']) ?> · <?= e(admin_date((string) $version['created_at'])) ?> · <?= e((string) ($version['created_by_name'] ?? '-')) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </details>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="content-section admin-shell" id="servicios-admin">
            <?php
            $serviceForm = $editingService ?: [
                'id' => '',
                'title' => '',
                'slug' => '',
                'short_description' => '',
                'full_description' => '',
                'image_path' => '',
                'icon' => '',
                'price' => '',
                'price_suffix' => '',
                'button_text' => 'Solicitar informacion',
                'button_url' => 'index.php#contacto-profesional',
                'display_order' => 0,
                'is_featured' => 0,
                'status' => 'ACTIVE',
            ];
            ?>
            <div class="section-heading">
                <div class="section-heading-content">
                    <p class="section-kicker">Servicios</p>
                    <h2>Gestion de servicios</h2>
                    <p>Crea, edita, ordena y publica los servicios visibles en la pagina publica de servicios.</p>
                </div>
                <?php if ($editingService): ?><a class="section-enter-link" href="panel-admin.php#servicios-admin">Nuevo servicio</a><?php endif; ?>
            </div>

            <div class="admin-editor-grid service-admin-grid">
                <article class="admin-editor-card">
                    <p class="section-kicker"><?= $editingService ? 'Editar' : 'Crear' ?></p>
                    <h3><?= $editingService ? e((string) $editingService['title']) : 'Nuevo servicio' ?></h3>
                    <form method="post" action="panel-admin.php#servicios-admin" class="admin-form" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="admin_action" value="save_service">
                        <input type="hidden" name="service_id" value="<?= e((string) ($serviceForm['id'] ?? '')) ?>">

                        <div class="form-grid-two">
                            <label for="service-title">Titulo
                                <input id="service-title" name="title" type="text" value="<?= e((string) ($serviceForm['title'] ?? '')) ?>" required>
                            </label>
                            <label for="service-slug">Slug
                                <input id="service-slug" name="slug" type="text" value="<?= e((string) ($serviceForm['slug'] ?? '')) ?>" placeholder="se genera si se deja vacio">
                            </label>
                        </div>

                        <label for="service-short">Descripcion breve</label>
                        <input id="service-short" name="short_description" type="text" maxlength="320" value="<?= e((string) ($serviceForm['short_description'] ?? '')) ?>" required>

                        <label for="service-full">Descripcion completa</label>
                        <textarea id="service-full" name="full_description" rows="8"><?= e((string) ($serviceForm['full_description'] ?? '')) ?></textarea>
                        <p class="field-help">HTML permitido: p, h2, h3, ul, ol, li, strong, em, a y br. Scripts, iframes y eventos se eliminan al guardar.</p>

                        <div class="form-grid-three">
                            <label for="service-price">Precio opcional
                                <input id="service-price" name="price" type="text" inputmode="decimal" value="<?= e((string) ($serviceForm['price'] ?? '')) ?>">
                            </label>
                            <label for="service-price-suffix">Sufijo precio
                                <input id="service-price-suffix" name="price_suffix" type="text" value="<?= e((string) ($serviceForm['price_suffix'] ?? '')) ?>" placeholder="desde / mes / unico">
                            </label>
                            <label for="service-order">Orden
                                <input id="service-order" name="display_order" type="number" value="<?= e((string) ($serviceForm['display_order'] ?? 0)) ?>">
                            </label>
                        </div>

                        <div class="form-grid-two">
                            <label for="service-button-text">Texto del boton
                                <input id="service-button-text" name="button_text" type="text" value="<?= e((string) ($serviceForm['button_text'] ?? '')) ?>">
                            </label>
                            <label for="service-button-url">URL del boton
                                <input id="service-button-url" name="button_url" type="text" value="<?= e((string) ($serviceForm['button_url'] ?? '')) ?>">
                            </label>
                        </div>

                        <div class="form-grid-two">
                            <label for="service-icon">Icono o etiqueta corta
                                <input id="service-icon" name="icon" type="text" maxlength="80" value="<?= e((string) ($serviceForm['icon'] ?? '')) ?>">
                            </label>
                            <label for="service-status">Estado
                                <select id="service-status" name="status">
                                    <option value="ACTIVE" <?= ($serviceForm['status'] ?? '') === 'ACTIVE' ? 'selected' : '' ?>>Activo</option>
                                    <option value="INACTIVE" <?= ($serviceForm['status'] ?? '') === 'INACTIVE' ? 'selected' : '' ?>>Inactivo</option>
                                </select>
                            </label>
                        </div>

                        <label class="visibility-toggle">
                            <input type="checkbox" name="is_featured" value="1" <?= !empty($serviceForm['is_featured']) ? 'checked' : '' ?>>
                            <span>Destacar en la home</span>
                        </label>

                        <label for="service-image">Imagen</label>
                        <input id="service-image" name="image" type="file" accept="image/jpeg,image/png,image/webp">
                        <?php if (!empty($serviceForm['image_path'])): ?>
                            <img class="admin-image-preview" src="<?= e((string) $serviceForm['image_path']) ?>" alt="">
                        <?php endif; ?>

                        <button class="button button-primary" type="submit" <?= !$databaseReady ? 'disabled' : '' ?>>Guardar servicio</button>
                    </form>
                </article>

                <article class="admin-editor-card service-list-card">
                    <p class="section-kicker">Listado</p>
                    <h3>Servicios publicados y borradores</h3>
                    <div class="admin-table-wrap">
                        <table class="admin-table service-admin-table">
                            <thead><tr><th>Servicio</th><th>Estado</th><th>Orden</th><th>Acciones</th></tr></thead>
                            <tbody>
                                <?php if (!$services): ?><tr><td colspan="4">Todavia no hay servicios creados.</td></tr><?php endif; ?>
                                <?php foreach ($services as $service): ?>
                                    <tr>
                                        <td>
                                            <strong><?= e((string) $service['title']) ?></strong>
                                            <small><?= e((string) $service['slug']) ?><?= !empty($service['is_featured']) ? ' · destacado' : '' ?></small>
                                        </td>
                                        <td><span class="status-pill <?= $service['status'] === 'ACTIVE' ? 'status-pill-active' : 'status-pill-pending' ?>"><?= e((string) $service['status']) ?></span></td>
                                        <td>
                                            <form method="post" action="panel-admin.php#servicios-admin" class="inline-admin-form">
                                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                                <input type="hidden" name="admin_action" value="update_service_order">
                                                <input type="hidden" name="service_id" value="<?= e((string) $service['id']) ?>">
                                                <input name="display_order" type="number" value="<?= e((string) $service['display_order']) ?>" aria-label="Orden de <?= e((string) $service['title']) ?>">
                                                <button type="submit" <?= !$databaseReady ? 'disabled' : '' ?>>OK</button>
                                            </form>
                                        </td>
                                        <td class="admin-actions-cell">
                                            <a href="panel-admin.php?edit_service=<?= e((string) $service['id']) ?>#servicios-admin">Editar</a>
                                            <form method="post" action="panel-admin.php#servicios-admin">
                                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                                <input type="hidden" name="admin_action" value="toggle_service_status">
                                                <input type="hidden" name="service_id" value="<?= e((string) $service['id']) ?>">
                                                <input type="hidden" name="status" value="<?= $service['status'] === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE' ?>">
                                                <button type="submit" <?= !$databaseReady ? 'disabled' : '' ?>><?= $service['status'] === 'ACTIVE' ? 'Desactivar' : 'Activar' ?></button>
                                            </form>
                                            <form method="post" action="panel-admin.php#servicios-admin">
                                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                                <input type="hidden" name="admin_action" value="toggle_service_featured">
                                                <input type="hidden" name="service_id" value="<?= e((string) $service['id']) ?>">
                                                <input type="hidden" name="is_featured" value="<?= empty($service['is_featured']) ? '1' : '0' ?>">
                                                <button type="submit" <?= !$databaseReady ? 'disabled' : '' ?>><?= empty($service['is_featured']) ? 'Destacar' : 'Quitar destacado' ?></button>
                                            </form>
                                            <form method="post" action="panel-admin.php#servicios-admin" onsubmit="return confirm('Eliminar este servicio de forma definitiva?');">
                                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                                <input type="hidden" name="admin_action" value="delete_service">
                                                <input type="hidden" name="service_id" value="<?= e((string) $service['id']) ?>">
                                                <button type="submit" <?= !$databaseReady ? 'disabled' : '' ?>>Eliminar</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </article>
            </div>
        </section>

        <section class="content-section admin-shell" id="contacto-admin">
            <div class="section-heading">
                <div class="section-heading-content">
                    <p class="section-kicker">Contacto</p>
                    <h2>Area profesional de contacto</h2>
                    <p>Configura el bloque publico de la portada y los datos visibles para consultas profesionales.</p>
                </div>
            </div>

            <article class="admin-editor-card">
                <form method="post" action="panel-admin.php#contacto-admin" class="admin-form" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="admin_action" value="save_contact_settings">
                    <div class="form-grid-two">
                        <label for="contact-title">Titulo de seccion
                            <input id="contact-title" name="section_title" type="text" value="<?= e((string) ($contactSettings['section_title'] ?? 'Hablemos de tu proyecto flamenco')) ?>" required>
                        </label>
                        <label for="contact-image-alt">Texto alternativo de imagen
                            <input id="contact-image-alt" name="image_alt" type="text" value="<?= e((string) ($contactSettings['image_alt'] ?? '')) ?>">
                        </label>
                    </div>
                    <label for="contact-intro">Introduccion</label>
                    <textarea id="contact-intro" name="section_intro" rows="4"><?= e((string) ($contactSettings['section_intro'] ?? '')) ?></textarea>

                    <div class="form-grid-three">
                        <label for="business-name">Nombre comercial
                            <input id="business-name" name="business_name" type="text" value="<?= e((string) ($contactSettings['business_name'] ?? '')) ?>">
                        </label>
                        <label for="contact-person">Persona de contacto
                            <input id="contact-person" name="contact_person" type="text" value="<?= e((string) ($contactSettings['contact_person'] ?? '')) ?>">
                        </label>
                        <label for="contact-email">Email
                            <input id="contact-email" name="email" type="email" value="<?= e((string) ($contactSettings['email'] ?? '')) ?>">
                        </label>
                    </div>

                    <div class="form-grid-three">
                        <label for="contact-phone">Telefono
                            <input id="contact-phone" name="phone" type="text" value="<?= e((string) ($contactSettings['phone'] ?? '')) ?>">
                        </label>
                        <label for="contact-whatsapp">WhatsApp
                            <input id="contact-whatsapp" name="whatsapp" type="text" value="<?= e((string) ($contactSettings['whatsapp'] ?? '')) ?>">
                        </label>
                        <label for="contact-whatsapp-url">URL WhatsApp
                            <input id="contact-whatsapp-url" name="whatsapp_url" type="text" value="<?= e((string) ($contactSettings['whatsapp_url'] ?? '')) ?>">
                        </label>
                    </div>

                    <div class="form-grid-three">
                        <label for="contact-address">Direccion
                            <input id="contact-address" name="address" type="text" value="<?= e((string) ($contactSettings['address'] ?? '')) ?>">
                        </label>
                        <label for="contact-city">Ciudad
                            <input id="contact-city" name="city" type="text" value="<?= e((string) ($contactSettings['city'] ?? '')) ?>">
                        </label>
                        <label for="contact-province">Provincia
                            <input id="contact-province" name="province" type="text" value="<?= e((string) ($contactSettings['province'] ?? '')) ?>">
                        </label>
                    </div>

                    <div class="form-grid-three">
                        <label for="contact-postal-code">Codigo postal
                            <input id="contact-postal-code" name="postal_code" type="text" value="<?= e((string) ($contactSettings['postal_code'] ?? '')) ?>">
                        </label>
                        <label for="phone-button-text">Texto boton telefono
                            <input id="phone-button-text" name="phone_button_text" type="text" value="<?= e((string) ($contactSettings['phone_button_text'] ?? '')) ?>">
                        </label>
                        <label for="whatsapp-button-text">Texto boton WhatsApp
                            <input id="whatsapp-button-text" name="whatsapp_button_text" type="text" value="<?= e((string) ($contactSettings['whatsapp_button_text'] ?? '')) ?>">
                        </label>
                    </div>

                    <label for="opening-hours">Horario</label>
                    <textarea id="opening-hours" name="opening_hours" rows="3"><?= e((string) ($contactSettings['opening_hours'] ?? '')) ?></textarea>

                    <div class="form-grid-two">
                        <label for="contact-facebook">Facebook
                            <input id="contact-facebook" name="facebook_url" type="text" value="<?= e((string) ($contactSettings['facebook_url'] ?? '')) ?>">
                        </label>
                        <label for="contact-instagram">Instagram
                            <input id="contact-instagram" name="instagram_url" type="text" value="<?= e((string) ($contactSettings['instagram_url'] ?? '')) ?>">
                        </label>
                        <label for="contact-youtube">YouTube
                            <input id="contact-youtube" name="youtube_url" type="text" value="<?= e((string) ($contactSettings['youtube_url'] ?? '')) ?>">
                        </label>
                        <label for="contact-tiktok">TikTok
                            <input id="contact-tiktok" name="tiktok_url" type="text" value="<?= e((string) ($contactSettings['tiktok_url'] ?? '')) ?>">
                        </label>
                    </div>

                    <fieldset class="checkbox-grid">
                        <legend>Visibilidad</legend>
                        <?php
                        $contactFlags = [
                            'is_enabled' => 'Mostrar seccion',
                            'show_email' => 'Mostrar email',
                            'show_phone' => 'Mostrar telefono',
                            'show_whatsapp' => 'Mostrar WhatsApp',
                            'show_address' => 'Mostrar direccion',
                            'show_opening_hours' => 'Mostrar horario',
                        ];
                        ?>
                        <?php foreach ($contactFlags as $flag => $label): ?>
                            <label><input type="checkbox" name="<?= e($flag) ?>" value="1" <?= !empty($contactSettings[$flag]) ? 'checked' : '' ?>> <?= e($label) ?></label>
                        <?php endforeach; ?>
                    </fieldset>

                    <label for="contact-image">Imagen de la seccion</label>
                    <input id="contact-image" name="image" type="file" accept="image/jpeg,image/png,image/webp">
                    <?php if (!empty($contactSettings['image_path'])): ?>
                        <img class="admin-image-preview" src="<?= e((string) $contactSettings['image_path']) ?>" alt="">
                    <?php endif; ?>

                    <button class="button button-primary" type="submit" <?= !$databaseReady ? 'disabled' : '' ?>>Guardar contacto</button>
                </form>
            </article>
        </section>

        <section class="content-section admin-shell" id="mensajes-contacto">
            <div class="section-heading">
                <div class="section-heading-content">
                    <p class="section-kicker">Contacto</p>
                    <h2>Mensajes de contacto</h2>
                    <p>Consulta y clasifica los mensajes enviados desde la pagina principal.</p>
                </div>
            </div>

            <?php if ($selectedContactMessage): ?>
                <article class="admin-editor-card contact-message-detail">
                    <p class="section-kicker">Mensaje seleccionado</p>
                    <h3><?= e((string) $selectedContactMessage['subject']) ?></h3>
                    <dl class="contact-detail-list">
                        <dt>Nombre</dt><dd><?= e((string) $selectedContactMessage['name']) ?></dd>
                        <dt>Email</dt><dd><?= e((string) $selectedContactMessage['email']) ?></dd>
                        <dt>Telefono</dt><dd><?= e((string) ($selectedContactMessage['phone'] ?? '-')) ?></dd>
                        <dt>Tipo</dt><dd><?= e((string) $selectedContactMessage['inquiry_type']) ?></dd>
                        <dt>Estado</dt><dd><?= e((string) $selectedContactMessage['status']) ?></dd>
                        <dt>Fecha</dt><dd><?= e(admin_date((string) ($selectedContactMessage['created_at'] ?? ''))) ?></dd>
                    </dl>
                    <p><?= nl2br(e((string) $selectedContactMessage['message'])) ?></p>
                </article>
            <?php endif; ?>

            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead><tr><th>Remitente</th><th>Tipo</th><th>Asunto</th><th>Fecha</th><th>Estado</th><th>Acciones</th></tr></thead>
                    <tbody>
                        <?php if (!$contactMessages): ?><tr><td colspan="6">Todavia no hay mensajes de contacto.</td></tr><?php endif; ?>
                        <?php foreach ($contactMessages as $message): ?>
                            <tr>
                                <td><strong><?= e((string) $message['name']) ?></strong><small><?= e((string) $message['email']) ?></small></td>
                                <td><?= e((string) $message['inquiry_type']) ?></td>
                                <td><?= e((string) $message['subject']) ?></td>
                                <td><?= e(admin_date((string) ($message['created_at'] ?? ''))) ?></td>
                                <td><span class="status-pill <?= $message['status'] === 'NEW' ? 'status-pill-pending' : 'status-pill-active' ?>"><?= e((string) $message['status']) ?></span></td>
                                <td class="admin-actions-cell">
                                    <a href="panel-admin.php?contact_message=<?= e((string) $message['id']) ?>#mensajes-contacto">Ver</a>
                                    <?php foreach (['READ' => 'Leido', 'ANSWERED' => 'Respondido', 'ARCHIVED' => 'Archivar', 'SPAM' => 'Spam'] as $status => $label): ?>
                                        <form method="post" action="panel-admin.php#mensajes-contacto">
                                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                            <input type="hidden" name="admin_action" value="update_contact_message_status">
                                            <input type="hidden" name="message_id" value="<?= e((string) $message['id']) ?>">
                                            <input type="hidden" name="status" value="<?= e($status) ?>">
                                            <button type="submit" <?= !$databaseReady ? 'disabled' : '' ?>><?= e($label) ?></button>
                                        </form>
                                    <?php endforeach; ?>
                                    <form method="post" action="panel-admin.php#mensajes-contacto" onsubmit="return confirm('Eliminar este mensaje de forma definitiva?');">
                                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                        <input type="hidden" name="admin_action" value="delete_contact_message">
                                        <input type="hidden" name="message_id" value="<?= e((string) $message['id']) ?>">
                                        <button type="submit" <?= !$databaseReady ? 'disabled' : '' ?>>Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="content-section admin-shell" id="banners">
            <div class="section-heading">
                <div class="section-heading-content">
                    <p class="section-kicker">Publicidad</p>
                    <h2>Banners contratados</h2>
                    <p>Vista inicial de estado, fechas y miembro asociado.</p>
                </div>
            </div>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead><tr><th>Banner</th><th>Miembro</th><th>Estado</th><th>Publicacion</th><th>Contratacion</th></tr></thead>
                    <tbody>
                        <?php if (!$banners): ?><tr><td colspan="5">Todavia no hay banners registrados.</td></tr><?php endif; ?>
                        <?php foreach ($banners as $banner): ?>
                            <tr>
                                <td><strong><?= e((string) $banner['titulo']) ?></strong><small><?= e((string) $banner['url_destino']) ?></small></td>
                                <td><?= e((string) ($banner['nombre_publico'] ?? $banner['usuario_email'] ?? '-')) ?></td>
                                <td><?= e((string) $banner['estado']) ?></td>
                                <td><?= e(admin_date($banner['fecha_inicio_publicacion'] ?? null)) ?> - <?= e(admin_date($banner['fecha_fin_publicacion'] ?? null)) ?></td>
                                <td><?= e(admin_date($banner['fecha_inicio_contratacion'] ?? null)) ?> - <?= e(admin_date($banner['fecha_fin_contratacion'] ?? null)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
        </main>
    </div>
    <?php page_footer(); ?>
    <?php province_modal('Asi podremos revisar la experiencia publica desde la provincia seleccionada.'); ?>
</body>
</html>
