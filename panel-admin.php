<?php
declare(strict_types=1);

require_once __DIR__ . '/app/admin_repository.php';
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

function admin_date(?string $value): string
{
    if (!$value) {
        return '-';
    }

    $timestamp = strtotime($value);
    return $timestamp ? date('d/m/Y', $timestamp) : $value;
}
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

        <section class="content-section admin-shell">
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

            <div class="admin-metric-grid">
                <article class="admin-metric-card"><span>Miembros</span><strong><?= e((string) $stats['members']) ?></strong></article>
                <article class="admin-metric-card"><span>Setters</span><strong><?= e((string) $stats['setters']) ?></strong></article>
                <article class="admin-metric-card"><span>Articulos</span><strong><?= e((string) $stats['articles']) ?></strong></article>
                <article class="admin-metric-card"><span>Banners</span><strong><?= e((string) $stats['banners']) ?></strong></article>
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
