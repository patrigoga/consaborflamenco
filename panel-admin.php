<?php
declare(strict_types=1);

require_once __DIR__ . '/app/auth.php';
require_once __DIR__ . '/app/layout.php';

$user = require_login();
if (($user['role'] ?? 'user') !== 'admin') {
    redirect_to('panel-usuario.php');
}
?>
<!DOCTYPE html>
<html lang="es">
<?php page_head('Panel de administración | Con Sabor Flamenco', 'Panel de administración de Con Sabor Flamenco.', false); ?>
<body>
    <?php page_header(); ?>
    <main>
        <section class="page-intro" data-ad-category="GENERAL">
            <p class="section-kicker">Administración</p>
            <h1>Panel de administración</h1>
            <p>Base protegida para gestionar contenidos, miembros, leads, servicios, ventas y comisiones en próximas fases.</p>
        </section>
        <section class="content-section dashboard-section">
            <div class="section-heading">
                <div class="section-heading-content">
                    <p class="section-kicker">Control central</p>
                    <h2>Un panel preparado para crecer</h2>
                    <p>Mantenemos el lenguaje visual de Inicio mientras dejamos claras las áreas que irán entrando en producción.</p>
                </div>
                <a class="section-enter-link" href="panel-usuario.php">Vista usuario</a>
            </div>
            <div class="feature-grid">
                <article class="feature-card"><h3>Contenidos</h3><p>Revista, eventos, artistas, academias, tablaos, peñas y festivales.</p></article>
                <article class="feature-card"><h3>Negocio</h3><p>Leads, servicios, ventas, códigos promocionales y comisiones.</p></article>
                <article class="feature-card"><h3>Comunidad</h3><p>Miembros, setters, conversaciones e inbox con futura IA.</p></article>
            </div>
        </section>
    </main>
    <?php page_footer(); ?>
    <?php province_modal('Así podremos revisar la experiencia pública desde la provincia seleccionada.'); ?>
</body>
</html>
