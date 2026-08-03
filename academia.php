<?php
declare(strict_types=1);

require_once __DIR__ . '/app/auth.php';
require_once __DIR__ . '/app/layout.php';
require_once __DIR__ . '/app/directory_helpers.php';
require_once __DIR__ . '/app/admin_ui.php';

$slug = clean_text((string) ($_GET['slug'] ?? ''));
$pdo = db();
$academia = ($pdo && $slug !== '') ? academia_get_by_slug($pdo, $slug) : null;

if (!$academia) {
    // El slug es unico para todos los tipos de miembro: si existe pero no es una
    // academia, se manda al prefijo que le corresponde en vez de dar un 404.
    $member = $slug !== '' ? find_user_by_member_slug($slug) : null;
    if ($member) {
        $memberProfile = default_member_profile($member);
        $memberType = (string) ($memberProfile['member_type'] ?? 'artista');
        // Nunca se redirige a 'academia': seria un bucle. Si el miembro es de tipo
        // academia pero no tiene fila en `academias`, se cae al 404 de abajo.
        if (member_type_url_prefix($memberType) !== 'academia') {
            header('Location: ' . app_url(member_public_path($memberType, $slug)), true, 301);
            exit;
        }
    }

    header('HTTP/1.1 404 Not Found');
    echo 'Academia no encontrada';
    exit;
}

// La microweb publica solo se muestra cuando la administracion aprueba la
// academia (estado ACTIVA). Ver docs/15_AREA_ACADEMIAS.md, "Alta de una academia".
if ($academia['estado'] !== 'ACTIVA') {
    header('HTTP/1.1 404 Not Found');
    echo 'Academia no encontrada';
    exit;
}

$academiaId = (int) $academia['miembro_id'];
$displayName = clean_text((string) $academia['nombre_publico']);
$location = trim(clean_text((string) $academia['ciudad']) . (($academia['ciudad'] !== '' && $academia['provincia_texto'] !== '') ? ', ' : '') . clean_text((string) $academia['provincia_texto']));
// Absoluta: esta pagina vive en /academia/{slug} y una ruta relativa se
// resolveria contra /academia/, dejando la imagen rota.
$mainPhoto = csf_media_url_absolute(clean_text((string) $academia['foto_principal_path']));
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
        <section class="page-intro academia-hero" data-ad-category="ACADEMIAS">
            <div>
                <p class="section-kicker">Academia de flamenco</p>
                <h1><?= e($displayName) ?></h1>
                <p class="academia-hero-meta">
                    <?php if ($location !== ''): ?><span><?= e($location) ?></span><?php endif; ?>
                    <?php foreach ($disciplinas as $disciplina): ?>
                        <span class="academia-tag"><?= e((string) $disciplina['nombre']) ?></span>
                    <?php endforeach; ?>
                </p>
            </div>
            <a class="button button-primary" href="#contacto">Solicitar información</a>
        </section>

        <div class="page-shell">
            <div class="primary-content">
                <section class="content-section">
                    <div class="academia-presentacion">
                        <?php if ($mainPhoto !== ''): ?>
                            <img class="academia-portada" src="<?= e($mainPhoto) ?>" alt="Imagen de <?= e($displayName) ?>" loading="lazy">
                        <?php endif; ?>

                        <div class="academia-presentacion-texto">
                            <?php $descripcion = academia_descripcion($academia); ?>
                            <?php if ($descripcion !== ''): ?>
                                <p class="academia-descripcion"><?= e($descripcion) ?></p>
                            <?php else: ?>
                                <p class="academia-descripcion"><?= e($displayName) ?> todavía no ha publicado su presentación<?= $location !== '' ? ', pero imparte clases en ' . e($location) : '' ?>. Escríbeles y te cuentan.</p>
                            <?php endif; ?>

                            <dl class="academia-datos">
                                <?php if ((string) $academia['telefono'] !== ''): ?>
                                    <div><dt>Teléfono</dt><dd><a href="tel:<?= e(preg_replace('/\s+/', '', (string) $academia['telefono'])) ?>"><?= e((string) $academia['telefono']) ?></a></dd></div>
                                <?php endif; ?>
                                <?php if ((string) $academia['web_url'] !== ''): ?>
                                    <div><dt>Web</dt><dd><a href="<?= e((string) $academia['web_url']) ?>" target="_blank" rel="noopener"><?= e(preg_replace('#^https?://#', '', (string) $academia['web_url'])) ?></a></dd></div>
                                <?php endif; ?>
                                <?php if ((string) $academia['instagram_url'] !== ''): ?>
                                    <div><dt>Instagram</dt><dd><a href="<?= e((string) $academia['instagram_url']) ?>" target="_blank" rel="noopener">Perfil</a></dd></div>
                                <?php endif; ?>
                                <?php if ($cursos): ?>
                                    <div><dt>Cursos abiertos</dt><dd><?= e((string) count($cursos)) ?></dd></div>
                                <?php endif; ?>
                            </dl>
                        </div>
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
                                <article class="academia-curso-card">
                                    <header>
                                        <span class="academia-curso-disciplina"><?= e((string) ($curso['disciplina_nombre'] ?? 'Flamenco')) ?><?= $curso['nivel_nombre'] ? ' · ' . e((string) $curso['nivel_nombre']) : '' ?></span>
                                        <h3><?= e((string) $curso['nombre']) ?></h3>
                                    </header>
                                    <?php if ($curso['descripcion']): ?><p><?= e((string) $curso['descripcion']) ?></p><?php endif; ?>
                                    <ul class="academia-curso-datos">
                                        <li><?= e(ucfirst(strtolower((string) $curso['modalidad']))) ?></li>
                                        <?php if ($curso['precio'] !== null): ?>
                                            <li><strong><?= e(number_format((float) $curso['precio'], 2, ',', '.')) ?> €</strong><?= $curso['tipo_cuota'] ? ' / ' . e(strtolower((string) $curso['tipo_cuota'])) : '' ?></li>
                                        <?php endif; ?>
                                        <?php if (!empty($curso['fecha_inicio'])): ?>
                                            <li>Desde <?= e(date('d/m/Y', strtotime((string) $curso['fecha_inicio']))) ?></li>
                                        <?php endif; ?>
                                    </ul>
                                    <footer>
                                        <span class="status-pill <?= e(admin_badge_class((string) $curso['estado'])) ?>"><?= e(str_replace('_', ' ', (string) $curso['estado'])) ?></span>
                                        <a class="text-button" href="#contacto">Solicitar plaza</a>
                                    </footer>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="empty-state">Esta academia todavía no ha publicado su oferta de cursos. Puedes escribirle igualmente y te informará de lo que imparte.</p>
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
