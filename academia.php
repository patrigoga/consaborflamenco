<?php
declare(strict_types=1);

require_once __DIR__ . '/app/auth.php';
require_once __DIR__ . '/app/layout.php';
require_once __DIR__ . '/app/directory_helpers.php';
require_once __DIR__ . '/app/admin_ui.php';

$slug = clean_text((string) ($_GET['slug'] ?? ''));
$pdo = db();
$academia = ($pdo && $slug !== '') ? academia_get_by_slug($pdo, $slug) : null;

if (!$academia || $academia['estado'] !== 'ACTIVA') {
    header('HTTP/1.1 404 Not Found');
    echo 'Academia no encontrada';
    exit;
}

$academiaId = (int) $academia['miembro_id'];
$displayName = clean_text((string) $academia['nombre_publico']);
$location = trim(clean_text((string) $academia['ciudad']) . (($academia['ciudad'] !== '' && $academia['provincia_texto'] !== '') ? ', ' : '') . clean_text((string) $academia['provincia_texto']));
$mainPhoto = clean_text((string) $academia['foto_principal_path']);
$disciplinas = academia_list_disciplinas($pdo, $academiaId);
$profesores = academia_public_profesores($pdo, $academiaId);
$cursos = academia_public_cursos($pdo, $academiaId);

$formErrors = [];
$formSent = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $formErrors[] = 'La sesión ha caducado. Vuelve a intentarlo.';
    }

    $action = (string) ($_POST['form_action'] ?? '');
    $nombre = clean_text((string) ($_POST['nombre'] ?? ''));
    $email = normalize_email((string) ($_POST['email'] ?? ''));
    $telefono = clean_text((string) ($_POST['telefono'] ?? ''));
    $mensaje = clean_html_text((string) ($_POST['mensaje'] ?? ''));

    if ($nombre === '') {
        $formErrors[] = 'Indica tu nombre.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $formErrors[] = 'Indica un email válido.';
    }

    if (!$formErrors && $action === 'solicitar_info') {
        academia_create_solicitud_info($pdo, $academiaId, [
            'nombre' => $nombre,
            'email' => $email,
            'telefono' => $telefono,
            'mensaje' => $mensaje,
            'disciplina_interes' => clean_text((string) ($_POST['disciplina_interes'] ?? '')),
            'ip_hash' => hash('sha256', (string) ($_SERVER['REMOTE_ADDR'] ?? '')),
        ]);
        $formSent = 'info';
    } elseif (!$formErrors && $action === 'solicitar_matricula') {
        $cursoId = (int) ($_POST['curso_id'] ?? 0);
        academia_create_solicitud_matricula($pdo, $academiaId, [
            'curso_id' => $cursoId > 0 ? $cursoId : null,
            'nombre_alumno' => $nombre,
            'fecha_nacimiento' => clean_text((string) ($_POST['fecha_nacimiento'] ?? '')),
            'nombre_tutor' => clean_text((string) ($_POST['nombre_tutor'] ?? '')),
            'email' => $email,
            'telefono' => $telefono,
            'mensaje' => $mensaje,
        ]);
        $formSent = 'matricula';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<?php page_head($displayName . ' | Con Sabor Flamenco', 'Academia de flamenco: ' . $displayName . ($location !== '' ? ' (' . $location . ')' : '')); ?>
<body>
    <?php page_header('ACADEMIAS'); ?>
    <main>
        <section class="page-intro" data-ad-category="ACADEMIAS">
            <p class="section-kicker">Academia</p>
            <h1><?= e($displayName) ?></h1>
            <?php if ($location !== ''): ?><p><?= e($location) ?></p><?php endif; ?>
            <?php if ($disciplinas): ?>
                <p><?= e(implode(' · ', array_map(static fn (array $d): string => $d['nombre'], $disciplinas))) ?></p>
            <?php endif; ?>
        </section>

        <div class="page-shell">
            <div class="primary-content">
                <section class="content-section">
                    <?php if ($mainPhoto !== ''): ?>
                        <img src="<?= e($mainPhoto) ?>" alt="Imagen de <?= e($displayName) ?>" loading="lazy" style="width:100%;border-radius:var(--radius);margin-bottom:24px;">
                    <?php endif; ?>
                    <?php $descripcion = academia_descripcion($academia); ?>
                    <?php if ($descripcion !== ''): ?>
                        <p><?= e($descripcion) ?></p>
                    <?php endif; ?>

                    <div class="editorial-story-content" style="margin-top:16px;">
                        <?php if ($academia['telefono'] !== ''): ?><p>Teléfono: <?= e((string) $academia['telefono']) ?></p><?php endif; ?>
                        <?php if ($academia['web_url'] !== ''): ?><p>Web: <a href="<?= e((string) $academia['web_url']) ?>" target="_blank" rel="noopener"><?= e((string) $academia['web_url']) ?></a></p><?php endif; ?>
                    </div>
                </section>

                <?php if ($profesores): ?>
                    <section id="profesores" class="content-section">
                        <div class="section-heading">
                            <div class="section-heading-content">
                                <p class="section-kicker">Equipo docente</p>
                                <h2>Profesores</h2>
                            </div>
                        </div>
                        <div class="editorial-grid directory-grid">
                            <?php foreach ($profesores as $profesor): ?>
                                <article class="editorial-story-content member-config-card">
                                    <strong><?= e((string) $profesor['nombre']) ?></strong>
                                    <?php if (!empty($profesor['especialidad'])): ?><p><?= e((string) $profesor['especialidad']) ?></p><?php endif; ?>
                                    <?php if (!empty($profesor['biografia_docente'])): ?><p><?= e((string) $profesor['biografia_docente']) ?></p><?php endif; ?>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>

                <section id="cursos" class="content-section">
                    <div class="section-heading">
                        <div class="section-heading-content">
                            <p class="section-kicker">Formación</p>
                            <h2>Cursos disponibles</h2>
                        </div>
                    </div>

                    <?php if ($cursos): ?>
                        <div class="editorial-grid directory-grid">
                            <?php foreach ($cursos as $curso): ?>
                                <article class="editorial-story-content member-config-card">
                                    <span class="editorial-meta">
                                        <strong><?= e((string) $curso['nombre']) ?></strong>
                                        <span><?= e((string) ($curso['disciplina_nombre'] ?? 'Flamenco')) ?><?= $curso['nivel_nombre'] ? ' · ' . e((string) $curso['nivel_nombre']) : '' ?></span>
                                    </span>
                                    <?php if ($curso['descripcion']): ?><p><?= e((string) $curso['descripcion']) ?></p><?php endif; ?>
                                    <p>
                                        <?= e(ucfirst(strtolower((string) $curso['modalidad']))) ?>
                                        <?php if ($curso['precio'] !== null): ?> · <?= e(number_format((float) $curso['precio'], 2, ',', '.')) ?> €<?php endif; ?>
                                    </p>
                                    <span class="status-pill <?= e(admin_badge_class((string) $curso['estado'])) ?>"><?= e(str_replace('_', ' ', (string) $curso['estado'])) ?></span>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="empty-state">Esta academia todavía no ha publicado cursos.</p>
                    <?php endif; ?>
                </section>

                <section id="contacto" class="content-section">
                    <div class="section-heading">
                        <div class="section-heading-content">
                            <p class="section-kicker">Contacto</p>
                            <h2>Solicita información o matrícula</h2>
                        </div>
                    </div>

                    <?php if ($formSent === 'info'): ?>
                        <div class="form-alert form-alert-success"><p>Gracias, hemos recibido tu solicitud de información.</p></div>
                    <?php elseif ($formSent === 'matricula'): ?>
                        <div class="form-alert form-alert-success"><p>Gracias, hemos recibido tu solicitud de matrícula.</p></div>
                    <?php endif; ?>
                    <?php if ($formErrors): ?>
                        <div class="form-alert form-alert-error">
                            <?php foreach ($formErrors as $error): ?><p><?= e($error) ?></p><?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <form method="post" class="member-profile-form" style="display:grid;gap:16px;">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <div class="form-grid-two">
                            <label for="nombre">Nombre
                                <input id="nombre" name="nombre" type="text" required>
                            </label>
                            <label for="email">Email
                                <input id="email" name="email" type="email" required>
                            </label>
                        </div>
                        <div class="form-grid-two">
                            <label for="telefono">Teléfono
                                <input id="telefono" name="telefono" type="tel">
                            </label>
                            <?php if ($cursos): ?>
                                <label for="curso_id">Curso de interés
                                    <select id="curso_id" name="curso_id">
                                        <option value="">Sin especificar</option>
                                        <?php foreach ($cursos as $curso): ?>
                                            <option value="<?= e((string) $curso['id']) ?>"><?= e((string) $curso['nombre']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                            <?php endif; ?>
                        </div>
                        <label for="mensaje">Mensaje
                            <textarea id="mensaje" name="mensaje" rows="4"></textarea>
                        </label>
                        <div class="member-card-heading-actions" style="justify-content:flex-start;gap:12px;">
                            <button class="button button-secondary" type="submit" name="form_action" value="solicitar_info">Solicitar información</button>
                            <button class="button button-primary" type="submit" name="form_action" value="solicitar_matricula">Solicitar matrícula</button>
                        </div>
                    </form>
                </section>
            </div>
        </div>
    </main>
    <?php page_footer(); ?>
</body>
</html>
