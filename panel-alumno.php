<?php
declare(strict_types=1);

require_once __DIR__ . '/app/auth.php';
require_once __DIR__ . '/app/layout.php';
require_once __DIR__ . '/app/academia_security.php';
require_once __DIR__ . '/app/academia_ui.php';

$user = require_login();
$pdo = db();
if (!$pdo) {
    redirect_to('panel-usuario.php');
}

$alumno = academia_require_alumno($pdo, $user);
$alumnoId = (int) $alumno['id'];

$sections = [
    'inicio' => 'Inicio',
    'cursos' => 'Mis cursos',
    'perfil' => 'Mi perfil',
];
$activeSection = clean_text((string) ($_GET['section'] ?? 'inicio'));
if (!array_key_exists($activeSection, $sections)) {
    $activeSection = 'inicio';
}

$messages = [];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'La sesión ha caducado. Vuelve a intentarlo.';
    } elseif ((string) ($_POST['action'] ?? '') === 'update_contacto') {
        academia_update_alumno_contacto($pdo, $alumnoId, [
            'email' => normalize_email((string) ($_POST['email'] ?? '')),
            'telefono' => clean_text((string) ($_POST['telefono'] ?? '')),
        ]);
        $alumno['email'] = normalize_email((string) ($_POST['email'] ?? ''));
        $alumno['telefono'] = clean_text((string) ($_POST['telefono'] ?? ''));
        $messages[] = 'Datos de contacto actualizados.';
    }
}

$matriculas = academia_list_matriculas_for_alumno($pdo, $alumnoId);
$horarios = academia_list_horarios_for_alumno($pdo, $alumnoId);
$matriculasActivas = array_filter($matriculas, static fn (array $m): bool => $m['estado'] === 'ACTIVA');
?>
<!DOCTYPE html>
<html lang="es">
<?php page_head('Mi panel de alumno | Con Sabor Flamenco', 'Panel del alumno en Con Sabor Flamenco.', false); ?>
<body>
    <?php page_header(); ?>
    <main>
        <section class="page-intro">
            <p class="section-kicker">Panel de alumno</p>
            <h1><?= e((string) $alumno['nombre']) ?></h1>
            <p><?= e((string) $alumno['academia_nombre']) ?></p>
        </section>

        <div class="page-shell">
            <div class="primary-content" style="width:100%;">
                <div class="member-panel-tabs" role="tablist" aria-label="Secciones del panel de alumno">
                    <?php foreach ($sections as $key => $label): ?>
                        <a class="tab-button<?= $activeSection === $key ? ' active' : '' ?>" href="panel-alumno.php?section=<?= e($key) ?>"><?= e($label) ?></a>
                    <?php endforeach; ?>
                </div>

                <?php if ($messages): ?>
                    <div class="form-alert form-alert-success"><?php foreach ($messages as $message): ?><p><?= e($message) ?></p><?php endforeach; ?></div>
                <?php endif; ?>
                <?php if ($errors): ?>
                    <div class="form-alert form-alert-error"><?php foreach ($errors as $error): ?><p><?= e($error) ?></p><?php endforeach; ?></div>
                <?php endif; ?>

                <?php if ($activeSection === 'inicio'): ?>
                    <section class="content-section">
                        <div class="section-heading"><div class="section-heading-content"><p class="section-kicker">Resumen</p><h2>Tu actividad</h2></div></div>
                        <div class="member-summary-grid">
                            <article class="member-summary-card"><span>Cursos activos</span><strong><?= e((string) count($matriculasActivas)) ?></strong></article>
                            <article class="member-summary-card"><span>Próxima clase</span><strong><?= $horarios ? e(academia_dia_semana_label((int) $horarios[0]['dia_semana']) . ' ' . substr((string) $horarios[0]['hora_inicio'], 0, 5)) : 'Sin horario' ?></strong></article>
                        </div>
                    </section>

                <?php elseif ($activeSection === 'cursos'): ?>
                    <section class="content-section">
                        <div class="section-heading"><div class="section-heading-content"><p class="section-kicker">Formación</p><h2>Mis cursos</h2></div></div>
                        <?php if ($matriculas): ?>
                            <div class="editorial-grid directory-grid">
                                <?php foreach ($matriculas as $matricula): ?>
                                    <article class="member-config-card">
                                        <h3><?= e((string) $matricula['curso_nombre']) ?></h3>
                                        <?php if ($matricula['grupo_nombre']): ?><p><?= e((string) $matricula['grupo_nombre']) ?></p><?php endif; ?>
                                        <p><?= e(ucfirst(strtolower((string) $matricula['modalidad']))) ?></p>
                                        <span class="status-pill <?= e(admin_badge_class((string) $matricula['estado'])) ?>"><?= e(str_replace('_', ' ', (string) $matricula['estado'])) ?></span>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="empty-state">Todavía no tienes matrículas registradas.</p>
                        <?php endif; ?>

                        <?php if ($horarios): ?>
                            <div class="member-config-card" style="margin-top:24px;">
                                <h3>Mi horario</h3>
                                <?php foreach ($horarios as $horario): ?>
                                    <p><?= e((string) $horario['curso_nombre']) ?> — <?= e(academia_dia_semana_label((int) $horario['dia_semana'])) ?> <?= e(substr((string) $horario['hora_inicio'], 0, 5)) ?>–<?= e(substr((string) $horario['hora_fin'], 0, 5)) ?></p>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </section>

                <?php elseif ($activeSection === 'perfil'): ?>
                    <section class="content-section">
                        <div class="section-heading"><div class="section-heading-content"><p class="section-kicker">Perfil</p><h2>Mis datos de contacto</h2></div></div>
                        <form method="post" class="form-grid-two">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="action" value="update_contacto">
                            <label>Email<input name="email" type="email" value="<?= e((string) $alumno['email']) ?>"></label>
                            <label>Teléfono<input name="telefono" type="tel" value="<?= e((string) $alumno['telefono']) ?>"></label>
                            <button class="button button-primary" type="submit" style="grid-column:1 / -1;">Guardar</button>
                        </form>
                    </section>
                <?php endif; ?>
            </div>
        </div>
    </main>
    <?php page_footer(); ?>
</body>
</html>
