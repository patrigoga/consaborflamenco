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

$membership = academia_require_role($pdo, $user, ['RESPONSABLE', 'PROFESOR']);
$academiaId = (int) $membership['academia_id'];
$academiaMiembroId = (int) $membership['id'];
$rol = (string) $membership['rol'];
$isResponsable = $rol === 'RESPONSABLE';

// Una academia SUSPENDIDA o de BAJA pasa a solo lectura. Antes conservaba el
// panel completo, asi que suspenderla desde administracion no tenia mas efecto
// que ocultar su web publica.
$academiaOperativa = academia_estado_operativo((string) ($membership['academia_estado'] ?? ''));
$canManage = $isResponsable && $academiaOperativa;

$sections = academia_panel_sections($rol);
$activeSection = academia_active_section_key($sections);

$messages = [];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'La sesión ha caducado. Vuelve a intentarlo.';
    } elseif (!$academiaOperativa) {
        $errors[] = 'Tu academia está ' . strtolower((string) ($membership['academia_estado'] ?? '')) . ': el panel queda en solo lectura. Contacta con Con Sabor Flamenco.';
    } else {
        $action = (string) ($_POST['action'] ?? '');

        try {
            if ($action === 'update_academia' && $canManage) {
                academia_update_profile($pdo, $academiaId, [
                    'nombre_publico' => clean_text((string) ($_POST['nombre_publico'] ?? '')),
                    'biografia' => clean_html_text((string) ($_POST['biografia'] ?? '')),
                    'ciudad' => clean_text((string) ($_POST['ciudad'] ?? '')),
                    'provincia_texto' => clean_text((string) ($_POST['provincia_texto'] ?? '')),
                    'telefono' => clean_text((string) ($_POST['telefono'] ?? '')),
                    'web_url' => trim((string) ($_POST['web_url'] ?? '')),
                    'instagram_url' => trim((string) ($_POST['instagram_url'] ?? '')),
                    'facebook_url' => trim((string) ($_POST['facebook_url'] ?? '')),
                    'razon_social' => clean_text((string) ($_POST['razon_social'] ?? '')),
                    'cif' => clean_text((string) ($_POST['cif'] ?? '')),
                ], academia_user_id($user));
                academia_set_disciplinas($pdo, $academiaId, array_map('intval', $_POST['disciplinas'] ?? []));
                $messages[] = 'Datos de la academia actualizados.';
            } elseif ($action === 'add_nivel' && $canManage) {
                academia_upsert_nivel($pdo, $academiaId, [
                    'nombre' => clean_text((string) ($_POST['nombre'] ?? '')),
                    'orden' => (string) ($_POST['orden'] ?? ''),
                    'descripcion' => clean_text((string) ($_POST['descripcion'] ?? '')),
                ]);
                $messages[] = 'Nivel añadido.';
            } elseif ($action === 'add_profesor' && $canManage) {
                $result = academia_add_profesor(
                    $pdo,
                    $academiaId,
                    (string) ($_POST['email'] ?? ''),
                    clean_text((string) ($_POST['especialidad'] ?? '')),
                    academia_user_id($user)
                );
                if ($result['ok']) {
                    $messages[] = 'Profesor añadido.';
                } else {
                    $errors[] = $result['error'];
                }
            } elseif ($action === 'set_profesor_estado' && $canManage) {
                $profesorId = (int) ($_POST['academia_miembro_id'] ?? 0);
                if (academia_verify_profesor_ownership($pdo, $academiaId, $profesorId)) {
                    academia_set_profesor_estado($pdo, $academiaId, $profesorId, (string) ($_POST['estado'] ?? 'ACTIVO'));
                    $messages[] = 'Estado del profesor actualizado.';
                } else {
                    $errors[] = 'Profesor no válido.';
                }
            } elseif ($action === 'upsert_alumno' && $canManage) {
                $alumnoEditadoId = !empty($_POST['alumno_id']) ? (int) $_POST['alumno_id'] : null;

                if ($alumnoEditadoId !== null && !academia_verify_alumno_ownership($pdo, $academiaId, $alumnoEditadoId)) {
                    $errors[] = 'Alumno no válido.';
                } else {
                    $alumnoGuardadoId = academia_upsert_alumno($pdo, $academiaId, $alumnoEditadoId, [
                        'nombre' => clean_text((string) ($_POST['nombre'] ?? '')),
                        'apellidos' => clean_text((string) ($_POST['apellidos'] ?? '')),
                        'fecha_nacimiento' => clean_text((string) ($_POST['fecha_nacimiento'] ?? '')),
                        'email' => normalize_email((string) ($_POST['email'] ?? '')),
                        'telefono' => clean_text((string) ($_POST['telefono'] ?? '')),
                        'estado' => (string) ($_POST['estado'] ?? 'ACTIVO'),
                        'notas_internas' => clean_text((string) ($_POST['notas_internas'] ?? '')),
                    ], academia_user_id($user));

                    $alumnoGuardado = academia_get_alumno($pdo, $academiaId, $alumnoGuardadoId);
                    $messages[] = !empty($alumnoGuardado['usuario_id'])
                        ? 'Alumno guardado y vinculado con su cuenta de Con Sabor Flamenco: ya puede entrar en su panel.'
                        : 'Alumno guardado. Si algún día se registra con ese email, se vinculará con su cuenta al volver a guardar la ficha.';
                }
            } elseif ($action === 'upsert_curso' && $canManage) {
                $cursoEditadoId = !empty($_POST['curso_id']) ? (int) $_POST['curso_id'] : null;
                if ($cursoEditadoId !== null && !academia_verify_curso_ownership($pdo, $academiaId, $cursoEditadoId)) {
                    throw new RuntimeException('Curso de otra academia');
                }
                $cursoGuardadoId = academia_upsert_curso($pdo, $academiaId, $cursoEditadoId, [
                    'disciplina_id' => (int) ($_POST['disciplina_id'] ?? 0),
                    'nivel_id' => (int) ($_POST['nivel_id'] ?? 0),
                    'nombre' => clean_text((string) ($_POST['nombre'] ?? '')),
                    'descripcion' => clean_html_text((string) ($_POST['descripcion'] ?? '')),
                    'modalidad' => (string) ($_POST['modalidad'] ?? 'PRESENCIAL'),
                    'fecha_inicio' => clean_text((string) ($_POST['fecha_inicio'] ?? '')),
                    'fecha_fin' => clean_text((string) ($_POST['fecha_fin'] ?? '')),
                    'precio' => clean_text((string) ($_POST['precio'] ?? '')),
                    'tipo_cuota' => clean_text((string) ($_POST['tipo_cuota'] ?? '')),
                    'estado' => (string) ($_POST['estado'] ?? 'BORRADOR'),
                    'requisitos' => clean_text((string) ($_POST['requisitos'] ?? '')),
                    'material_necesario' => clean_text((string) ($_POST['material_necesario'] ?? '')),
                    'visible_publico' => isset($_POST['visible_publico']),
                    'plazas_maximas' => clean_text((string) ($_POST['plazas_maximas'] ?? '')),
                    'periodo_matricula_inicio' => clean_text((string) ($_POST['periodo_matricula_inicio'] ?? '')),
                    'periodo_matricula_fin' => clean_text((string) ($_POST['periodo_matricula_fin'] ?? '')),
                ], academia_user_id($user));
                academia_set_curso_profesores($pdo, $academiaId, $cursoGuardadoId, $_POST['profesores'] ?? []);
                $messages[] = 'Curso guardado.';
            } elseif ($action === 'upsert_grupo' && $canManage) {
                $cursoId = (int) ($_POST['curso_id'] ?? 0);
                if (academia_verify_curso_ownership($pdo, $academiaId, $cursoId)) {
                    $grupoGuardadoId = academia_upsert_grupo($pdo, $academiaId, !empty($_POST['grupo_id']) ? (int) $_POST['grupo_id'] : null, [
                        'curso_id' => $cursoId,
                        'nombre' => clean_text((string) ($_POST['nombre'] ?? '')),
                        'aula_o_ubicacion' => clean_text((string) ($_POST['aula_o_ubicacion'] ?? '')),
                        'plazas_maximas' => clean_text((string) ($_POST['plazas_maximas'] ?? '')),
                        'estado' => (string) ($_POST['estado'] ?? 'ACTIVO'),
                    ]);

                    // El horario se guarda aparte y admite varias clases por semana.
                    $horariosEnviados = is_array($_POST['horarios'] ?? null) ? $_POST['horarios'] : [];
                    $horarios = [];
                    foreach ($horariosEnviados as $fila) {
                        if (!is_array($fila)) {
                            continue;
                        }
                        $horarios[] = [
                            'dia_semana' => array_key_exists((string) ($fila['dia_semana'] ?? ''), academia_dias_semana())
                                ? (int) $fila['dia_semana']
                                : null,
                            'hora_inicio' => clean_text((string) ($fila['hora_inicio'] ?? '')),
                            'hora_fin' => clean_text((string) ($fila['hora_fin'] ?? '')),
                            'aula' => clean_text((string) ($fila['aula'] ?? ($_POST['aula_o_ubicacion'] ?? ''))),
                        ];
                    }
                    $guardados = academia_set_grupo_horarios($pdo, $academiaId, $grupoGuardadoId, $horarios);
                    $messages[] = $guardados > 0
                        ? 'Grupo guardado con ' . $guardados . ' clase(s) a la semana.'
                        : 'Grupo guardado. No has indicado ningún horario válido.';
                } else {
                    $errors[] = 'Curso no válido.';
                }
            } elseif ($action === 'create_matricula' && $canManage) {
                $alumnoId = (int) ($_POST['alumno_id'] ?? 0);
                $cursoId = (int) ($_POST['curso_id'] ?? 0);
                $grupoId = (int) ($_POST['grupo_id'] ?? 0);

                if (!academia_verify_alumno_ownership($pdo, $academiaId, $alumnoId) || !academia_verify_curso_ownership($pdo, $academiaId, $cursoId)) {
                    $errors[] = 'Alumno o curso no válido.';
                } elseif ($grupoId > 0 && !academia_verify_grupo_del_curso($pdo, $academiaId, $cursoId, $grupoId)) {
                    // Faltaba esta comprobacion: el grupo llegaba crudo desde el
                    // formulario y podia ser de otro curso o de otra academia.
                    $errors[] = 'El grupo elegido no pertenece a ese curso de tu academia.';
                } else {
                    $ocupacion = $grupoId > 0 ? academia_grupo_ocupacion($pdo, $academiaId, $grupoId) : ['completo' => false, 'ocupadas' => 0, 'maximas' => null];

                    if ($ocupacion['completo'] && empty($_POST['confirmar_exceso'])) {
                        $errors[] = sprintf(
                            'El grupo está completo (%d de %d plazas). Marca "Matricular igualmente" si quieres superar el aforo.',
                            $ocupacion['ocupadas'],
                            (int) $ocupacion['maximas']
                        );
                    } else {
                        academia_create_matricula($pdo, $academiaId, [
                            'alumno_id' => $alumnoId,
                            'curso_id' => $cursoId,
                            'grupo_id' => $grupoId,
                            'estado' => (string) ($_POST['estado'] ?? 'SOLICITADA'),
                            'descuento_pct' => clean_text((string) ($_POST['descuento_pct'] ?? '')),
                            'observaciones_internas' => clean_text((string) ($_POST['observaciones_internas'] ?? '')),
                        ], academia_user_id($user));
                        $messages[] = $ocupacion['completo']
                            ? 'Matrícula creada superando el aforo del grupo.'
                            : 'Matrícula creada.';
                    }
                }
            } elseif ($action === 'update_matricula_estado' && $canManage) {
                $matriculaId = (int) ($_POST['matricula_id'] ?? 0);
                if (academia_verify_matricula_ownership($pdo, $academiaId, $matriculaId)) {
                    academia_update_matricula_estado($pdo, $academiaId, $matriculaId, (string) ($_POST['estado'] ?? ''));
                    $messages[] = 'Estado de la matrícula actualizado.';
                } else {
                    $errors[] = 'Matrícula no válida.';
                }
            }
        } catch (Throwable $exception) {
            error_log('[panel-academia] ' . $exception->getMessage());
            $errors[] = 'No se pudo guardar el cambio. Inténtalo de nuevo.';
        }
    }
}

$academia = academia_get($pdo, $academiaId);
$stats = $isResponsable ? academia_dashboard_stats($pdo, $academiaId) : [];
$disciplinasDisponibles = academia_all_disciplinas($pdo);
$disciplinasAcademia = academia_list_disciplinas($pdo, $academiaId);
$disciplinasAcademiaIds = array_column($disciplinasAcademia, 'id');
$niveles = academia_list_niveles($pdo, $academiaId);

if ($isResponsable) {
    // Cursos tambien necesita el listado: sin asignar profesores a un curso, el
    // panel del profesor se queda vacio porque todo lo suyo pasa por esa relacion.
    $profesores = in_array($activeSection, ['profesores', 'cursos'], true) ? academia_list_profesores($pdo, $academiaId) : [];
    $alumnos = $activeSection === 'alumnos' ? academia_list_alumnos($pdo, $academiaId, clean_text((string) ($_GET['buscar'] ?? ''))) : [];
    $cursos = in_array($activeSection, ['cursos', 'grupos', 'matriculas'], true) ? academia_list_cursos($pdo, $academiaId) : [];
    $grupos = $activeSection === 'grupos' ? academia_list_grupos($pdo, $academiaId, !empty($_GET['curso_id']) ? (int) $_GET['curso_id'] : null) : [];
    $matriculas = $activeSection === 'matriculas' ? academia_list_matriculas($pdo, $academiaId, ['estado' => academia_enum_filter($_GET['estado'] ?? '', academia_matricula_estados())]) : [];
    // Se carga una sola vez: el formulario de nueva matricula volvia a consultar
    // la lista entera de alumnos dentro del propio render.
    $alumnosParaMatricular = $activeSection === 'matriculas' ? academia_list_alumnos($pdo, $academiaId) : [];
    $gruposParaMatricular = $activeSection === 'matriculas' ? academia_list_grupos($pdo, $academiaId) : [];
} else {
    $cursos = $activeSection === 'cursos' ? academia_list_cursos_for_profesor($pdo, $academiaMiembroId) : [];
    $grupos = $activeSection === 'grupos' ? academia_list_grupos_for_profesor($pdo, $academiaMiembroId) : [];
    $alumnos = $activeSection === 'alumnos' ? academia_list_alumnos_for_profesor($pdo, $academiaMiembroId) : [];
}
?>
<!DOCTYPE html>
<html lang="es">
<?php page_head('Panel de la academia | Con Sabor Flamenco', 'Panel de gestión de la academia.', false); ?>
<body>
    <?php page_header(); ?>
    <main>
        <section class="page-intro">
            <p class="section-kicker">Panel de academia</p>
            <h1><?= e((string) ($academia['nombre_publico'] ?? 'Mi academia')) ?></h1>
            <p>Rol: <?= e($rol === 'RESPONSABLE' ? 'Responsable' : 'Profesor') ?> · Estado de la academia: <span class="status-pill <?= e(admin_badge_class((string) ($academia['estado'] ?? ''))) ?>"><?= e((string) ($academia['estado'] ?? '')) ?></span></p>
        </section>

        <div class="page-shell">
            <div class="primary-content" style="width:100%;">
                <div class="member-panel-tabs" role="tablist" aria-label="Secciones del panel de academia">
                    <?php foreach ($sections as $key => $label): ?>
                        <a class="tab-button<?= $activeSection === $key ? ' active' : '' ?>" href="<?= e(academia_section_url($key)) ?>"><?= e($label) ?></a>
                    <?php endforeach; ?>
                </div>

                <?php if ($messages): ?>
                    <div class="form-alert form-alert-success"><?php foreach ($messages as $message): ?><p><?= e($message) ?></p><?php endforeach; ?></div>
                <?php endif; ?>
                <?php if ($errors): ?>
                    <div class="form-alert form-alert-error"><?php foreach ($errors as $error): ?><p><?= e($error) ?></p><?php endforeach; ?></div>
                <?php endif; ?>

                <?php if (!$academiaOperativa): ?>
                    <div class="form-alert form-alert-error" role="status">
                        <p><strong>Panel en solo lectura.</strong> Tu academia está en estado
                        <?= e((string) ($academia['estado'] ?? '')) ?> y no admite cambios.
                        Puedes seguir consultando tus datos. Escríbenos para revisar la situación.</p>
                    </div>
                <?php endif; ?>

                <?php if ($activeSection === 'resumen'): ?>
                    <section class="content-section">
                        <div class="section-heading"><div class="section-heading-content"><p class="section-kicker">Resumen</p><h2>Estado de la academia</h2></div></div>
                        <?php if ($isResponsable): ?>
                            <div class="member-summary-grid">
                                <article class="member-summary-card"><span>Alumnos activos</span><strong><?= e((string) $stats['alumnos_activos']) ?></strong></article>
                                <article class="member-summary-card"><span>Profesores activos</span><strong><?= e((string) $stats['profesores_activos']) ?></strong></article>
                                <article class="member-summary-card"><span>Cursos activos</span><strong><?= e((string) $stats['cursos_activos']) ?></strong></article>
                                <article class="member-summary-card"><span>Matrículas pendientes</span><strong><?= e((string) $stats['matriculas_pendientes']) ?></strong></article>
                                <article class="member-summary-card"><span>Solicitudes de información</span><strong><?= e((string) $stats['solicitudes_info_nuevas']) ?></strong></article>
                                <article class="member-summary-card"><span>Solicitudes de matrícula</span><strong><?= e((string) $stats['solicitudes_matricula_nuevas']) ?></strong></article>
                            </div>
                            <?php if ($academia['estado'] === 'PENDIENTE'): ?>
                                <p class="empty-state">Tu academia está pendiente de aprobación por el equipo de Con Sabor Flamenco. Mientras tanto puedes ir completando profesores, cursos y grupos: no serán visibles públicamente hasta la aprobación.</p>
                            <?php endif; ?>
                        <?php else: ?>
                            <p>Bienvenido/a al panel de profesor. Desde aquí puedes consultar tus cursos, tus grupos y tus alumnos.</p>
                        <?php endif; ?>
                    </section>

                <?php elseif ($activeSection === 'academia'): ?>
                    <section class="content-section">
                        <div class="section-heading"><div class="section-heading-content"><p class="section-kicker">Mi academia</p><h2>Información pública y datos fiscales</h2></div></div>
                        <?php if ($isResponsable): ?>
                            <form method="post" class="member-profile-form" style="display:grid;gap:16px;">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="action" value="update_academia">
                                <fieldset class="cv-fieldset">
                                    <legend><span>Perfil público</span><strong>Datos de la academia</strong></legend>
                                    <label for="nombre_publico">Nombre comercial
                                        <input id="nombre_publico" name="nombre_publico" type="text" value="<?= e((string) $academia['nombre_publico']) ?>" required>
                                    </label>
                                    <label for="biografia">Descripción / historia
                                        <textarea id="biografia" name="biografia" rows="4"><?= e(academia_descripcion($academia)) ?></textarea>
                                    </label>
                                    <div class="form-grid-two">
                                        <label for="ciudad">Municipio
                                            <input id="ciudad" name="ciudad" type="text" value="<?= e((string) $academia['ciudad']) ?>">
                                        </label>
                                        <label for="provincia_texto">Provincia
                                            <input id="provincia_texto" name="provincia_texto" type="text" value="<?= e((string) $academia['provincia_texto']) ?>">
                                        </label>
                                    </div>
                                    <div class="form-grid-two">
                                        <label for="telefono">Teléfono
                                            <input id="telefono" name="telefono" type="tel" value="<?= e((string) $academia['telefono']) ?>">
                                        </label>
                                        <label for="web_url">Página web
                                            <input id="web_url" name="web_url" type="url" value="<?= e((string) $academia['web_url']) ?>">
                                        </label>
                                    </div>
                                    <div class="form-grid-two">
                                        <label for="instagram_url">Instagram
                                            <input id="instagram_url" name="instagram_url" type="url" value="<?= e((string) $academia['instagram_url']) ?>">
                                        </label>
                                        <label for="facebook_url">Facebook
                                            <input id="facebook_url" name="facebook_url" type="url" value="<?= e((string) $academia['facebook_url']) ?>">
                                        </label>
                                    </div>
                                    <label>Disciplinas impartidas
                                        <div class="card-background-options" aria-label="Disciplinas">
                                            <?php foreach ($disciplinasDisponibles as $disciplina): ?>
                                                <label class="checkbox-field">
                                                    <input type="checkbox" name="disciplinas[]" value="<?= e((string) $disciplina['id']) ?>" <?= in_array($disciplina['id'], $disciplinasAcademiaIds, true) ? 'checked' : '' ?>>
                                                    <span><?= e((string) $disciplina['nombre']) ?></span>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </label>
                                </fieldset>
                                <fieldset class="cv-fieldset">
                                    <legend><span>Privado</span><strong>Datos fiscales</strong></legend>
                                    <div class="form-grid-two">
                                        <label for="razon_social">Razón social
                                            <input id="razon_social" name="razon_social" type="text" value="<?= e((string) ($academia['razon_social'] ?? '')) ?>">
                                        </label>
                                        <label for="cif">CIF
                                            <input id="cif" name="cif" type="text" value="<?= e((string) ($academia['cif'] ?? '')) ?>">
                                        </label>
                                    </div>
                                </fieldset>
                                <button class="button button-primary" type="submit">Guardar cambios</button>
                            </form>

                            <div class="member-config-card" style="margin-top:24px;">
                                <h3>Niveles</h3>
                                <?php if ($niveles): ?>
                                    <ul>
                                        <?php foreach ($niveles as $nivel): ?>
                                            <li><strong><?= e((string) $nivel['nombre']) ?></strong><?= $nivel['descripcion'] ? ' — ' . e((string) $nivel['descripcion']) : '' ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <p class="empty-state">Todavía no has creado niveles.</p>
                                <?php endif; ?>
                                <form method="post" class="form-grid-three">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="action" value="add_nivel">
                                    <label>Nombre<input name="nombre" type="text" required></label>
                                    <label>Orden<input name="orden" type="number" min="0" value="0"></label>
                                    <label>Descripción<input name="descripcion" type="text"></label>
                                    <button class="button button-secondary" type="submit">Añadir nivel</button>
                                </form>
                            </div>
                        <?php else: ?>
                            <p><?= e((string) $academia['nombre_publico']) ?> — <?= e((string) $academia['ciudad']) ?></p>
                        <?php endif; ?>
                    </section>

                <?php elseif ($activeSection === 'profesores' && $isResponsable): ?>
                    <section class="content-section">
                        <div class="section-heading"><div class="section-heading-content"><p class="section-kicker">Equipo</p><h2>Profesores</h2></div></div>
                        <?php if ($profesores): ?>
                            <div class="editorial-grid directory-grid">
                                <?php foreach ($profesores as $profesor): ?>
                                    <article class="member-config-card">
                                        <h3><?= e((string) $profesor['nombre']) ?></h3>
                                        <p><?= e((string) $profesor['email']) ?></p>
                                        <?php if ($profesor['especialidad']): ?><p><?= e((string) $profesor['especialidad']) ?></p><?php endif; ?>
                                        <span class="status-pill <?= e(admin_badge_class((string) $profesor['estado'])) ?>"><?= e((string) $profesor['estado']) ?></span>
                                        <form method="post" style="margin-top:12px;">
                                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                            <input type="hidden" name="action" value="set_profesor_estado">
                                            <input type="hidden" name="academia_miembro_id" value="<?= e((string) $profesor['academia_miembro_id']) ?>">
                                            <input type="hidden" name="estado" value="<?= e($profesor['estado'] === 'ACTIVO' ? 'INACTIVO' : 'ACTIVO') ?>">
                                            <button class="text-button" type="submit"><?= $profesor['estado'] === 'ACTIVO' ? 'Desactivar' : 'Activar' ?></button>
                                        </form>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="empty-state">Todavía no has añadido profesores.</p>
                        <?php endif; ?>

                        <div class="member-config-card" style="margin-top:24px;">
                            <h3>Añadir profesor</h3>
                            <p class="field-help">La persona debe tener ya una cuenta en Con Sabor Flamenco.</p>
                            <form method="post" class="form-grid-three">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="action" value="add_profesor">
                                <label>Email<input name="email" type="email" required></label>
                                <label>Especialidad<input name="especialidad" type="text"></label>
                                <button class="button button-primary" type="submit">Añadir</button>
                            </form>
                        </div>
                    </section>

                <?php elseif ($activeSection === 'alumnos'): ?>
                    <section class="content-section">
                        <div class="section-heading"><div class="section-heading-content"><p class="section-kicker">Comunidad</p><h2>Alumnos</h2></div></div>
                        <?php if ($isResponsable): ?>
                            <form method="get" class="directory-filters" style="margin-bottom:16px;">
                                <input type="hidden" name="section" value="alumnos">
                                <input type="text" name="buscar" placeholder="Buscar por nombre o email" value="<?= e((string) ($_GET['buscar'] ?? '')) ?>">
                                <button class="button button-secondary" type="submit">Buscar</button>
                            </form>
                        <?php endif; ?>

                        <?php if ($alumnos): ?>
                            <div class="editorial-grid directory-grid">
                                <?php foreach ($alumnos as $alumno): ?>
                                    <article class="member-config-card">
                                        <h3><?= e((string) $alumno['nombre'] . ' ' . (string) $alumno['apellidos']) ?></h3>
                                        <?php if (!empty($alumno['email'])): ?><p><?= e((string) $alumno['email']) ?></p><?php endif; ?>
                                        <?php if (!empty($alumno['es_menor_edad'])): ?><span class="status-pill status-pill-pending">Menor de edad</span><?php endif; ?>
                                        <span class="status-pill <?= e(admin_badge_class((string) $alumno['estado'])) ?>"><?= e((string) $alumno['estado']) ?></span>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="empty-state">No hay alumnos que mostrar.</p>
                        <?php endif; ?>

                        <?php if ($isResponsable): ?>
                            <div class="member-config-card" style="margin-top:24px;">
                                <h3>Añadir alumno</h3>
                                <form method="post" class="form-grid-two">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="action" value="upsert_alumno">
                                    <label>Nombre<input name="nombre" type="text" required></label>
                                    <label>Apellidos<input name="apellidos" type="text"></label>
                                    <label>Fecha de nacimiento<input name="fecha_nacimiento" type="date"></label>
                                    <label>Email<input name="email" type="email"></label>
                                    <label>Teléfono<input name="telefono" type="tel"></label>
                                    <label>Estado
                                        <select name="estado"><?= academia_enum_options(academia_alumno_estados(), 'ACTIVO') ?></select>
                                    </label>
                                    <label>Notas internas<input name="notas_internas" type="text"></label>
                                    <p class="field-help" style="grid-column:1 / -1;">Si el email coincide con una cuenta de Con Sabor Flamenco, la ficha se vincula sola y esa persona podrá entrar en su panel de alumno.</p>
                                    <button class="button button-primary" type="submit">Guardar alumno</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </section>

                <?php elseif ($activeSection === 'cursos'): ?>
                    <section class="content-section">
                        <div class="section-heading"><div class="section-heading-content"><p class="section-kicker">Formación</p><h2>Cursos</h2></div></div>
                        <?php if ($cursos): ?>
                            <div class="editorial-grid directory-grid">
                                <?php foreach ($cursos as $curso): ?>
                                    <article class="member-config-card">
                                        <h3><?= e((string) $curso['nombre']) ?></h3>
                                        <p><?= e((string) ($curso['disciplina_nombre'] ?? 'Sin disciplina')) ?><?= $curso['nivel_nombre'] ? ' · ' . e((string) $curso['nivel_nombre']) : '' ?></p>
                                        <p><?= e(ucfirst(strtolower((string) $curso['modalidad']))) ?><?= $curso['precio'] !== null ? ' · ' . e(number_format((float) $curso['precio'], 2, ',', '.')) . ' €' : '' ?></p>
                                        <?php if (!empty($curso['profesores'])): ?>
                                            <p class="field-help">Profesores: <?= e(implode(', ', $curso['profesores'])) ?></p>
                                        <?php else: ?>
                                            <p class="field-help">Sin profesor asignado.</p>
                                        <?php endif; ?>
                                        <span class="status-pill <?= e(admin_badge_class((string) $curso['estado'])) ?>"><?= e(str_replace('_', ' ', (string) $curso['estado'])) ?></span>
                                        <?php if (!empty($curso['visible_publico'])): ?><span class="status-pill status-pill-active">Visible en la web</span><?php endif; ?>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="empty-state">No hay cursos que mostrar.</p>
                        <?php endif; ?>

                        <?php if ($isResponsable): ?>
                            <div class="member-config-card" style="margin-top:24px;">
                                <h3>Nuevo curso</h3>
                                <form method="post" style="display:grid;gap:16px;">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="action" value="upsert_curso">
                                    <div class="form-grid-two">
                                        <label>Nombre<input name="nombre" type="text" required></label>
                                        <label>Disciplina
                                            <select name="disciplina_id">
                                                <option value="">Sin especificar</option>
                                                <?php foreach ($disciplinasDisponibles as $disciplina): ?>
                                                    <option value="<?= e((string) $disciplina['id']) ?>"><?= e((string) $disciplina['nombre']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </label>
                                    </div>
                                    <label>Descripción<textarea name="descripcion" rows="3"></textarea></label>
                                    <div class="form-grid-three">
                                        <label>Nivel
                                            <select name="nivel_id">
                                                <option value="">Sin especificar</option>
                                                <?php foreach ($niveles as $nivel): ?>
                                                    <option value="<?= e((string) $nivel['id']) ?>"><?= e((string) $nivel['nombre']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </label>
                                        <label>Modalidad
                                            <select name="modalidad"><?= academia_enum_options(academia_curso_modalidades(), 'PRESENCIAL') ?></select>
                                        </label>
                                        <label>Estado
                                            <select name="estado"><?= academia_enum_options(academia_curso_estados(), 'BORRADOR') ?></select>
                                        </label>
                                    </div>
                                    <div class="form-grid-three">
                                        <label>Fecha inicio<input name="fecha_inicio" type="date"></label>
                                        <label>Fecha fin<input name="fecha_fin" type="date"></label>
                                        <label>Plazas máximas<input name="plazas_maximas" type="number" min="0"></label>
                                    </div>
                                    <div class="form-grid-two">
                                        <label>Precio (€)<input name="precio" type="number" step="0.01" min="0"></label>
                                        <label>Tipo de cuota<input name="tipo_cuota" type="text" placeholder="Mensual, trimestral..."></label>
                                    </div>
                                    <label>Profesores del curso
                                        <?php if ($profesores): ?>
                                            <div class="card-background-options" aria-label="Profesores asignados">
                                                <?php foreach ($profesores as $profesor): ?>
                                                    <label class="checkbox-field">
                                                        <input type="checkbox" name="profesores[]" value="<?= e((string) $profesor['academia_miembro_id']) ?>">
                                                        <span><?= e((string) $profesor['nombre']) ?></span>
                                                    </label>
                                                <?php endforeach; ?>
                                            </div>
                                            <span class="field-help">Cada profesor solo ve los cursos, grupos y alumnos que tenga asignados aquí.</span>
                                        <?php else: ?>
                                            <span class="field-help">Todavía no hay profesores. Añádelos en la pestaña Profesores para poder asignarlos a este curso.</span>
                                        <?php endif; ?>
                                    </label>
                                    <label class="checkbox-field"><input type="checkbox" name="visible_publico"> <span>Mostrar en la ficha pública de la academia</span></label>
                                    <button class="button button-primary" type="submit">Guardar curso</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </section>

                <?php elseif ($activeSection === 'grupos'): ?>
                    <section class="content-section">
                        <div class="section-heading"><div class="section-heading-content"><p class="section-kicker">Organización</p><h2>Grupos</h2></div></div>
                        <?php if ($grupos): ?>
                            <div class="editorial-grid directory-grid">
                                <?php foreach ($grupos as $grupo): ?>
                                    <article class="member-config-card">
                                        <h3><?= e((string) $grupo['nombre']) ?></h3>
                                        <p><?= e((string) $grupo['curso_nombre']) ?></p>
                                        <?php if (!empty($grupo['aula_o_ubicacion'])): ?><p><?= e((string) $grupo['aula_o_ubicacion']) ?></p><?php endif; ?>
                                        <?php foreach ($grupo['horarios'] ?? [] as $horario): ?>
                                            <p><?= e(academia_dia_semana_label((int) $horario['dia_semana'])) ?> <?= e(substr((string) $horario['hora_inicio'], 0, 5)) ?>–<?= e(substr((string) $horario['hora_fin'], 0, 5)) ?></p>
                                        <?php endforeach; ?>
                                        <span class="status-pill <?= e(admin_badge_class((string) $grupo['estado'])) ?>"><?= e((string) $grupo['estado']) ?></span>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="empty-state">No hay grupos que mostrar.</p>
                        <?php endif; ?>

                        <?php if ($isResponsable): ?>
                            <div class="member-config-card" style="margin-top:24px;">
                                <h3>Nuevo grupo</h3>
                                <?php if ($cursos): ?>
                                    <form method="post" style="display:grid;gap:16px;">
                                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                        <input type="hidden" name="action" value="upsert_grupo">
                                        <div class="form-grid-two">
                                            <label>Curso
                                                <select name="curso_id" required>
                                                    <?php foreach ($cursos as $curso): ?>
                                                        <option value="<?= e((string) $curso['id']) ?>"><?= e((string) $curso['nombre']) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </label>
                                            <label>Nombre del grupo<input name="nombre" type="text" required></label>
                                        </div>
                                        <div class="form-grid-three">
                                            <label>Aula o ubicación<input name="aula_o_ubicacion" type="text"></label>
                                            <label>Plazas máximas<input name="plazas_maximas" type="number" min="0"></label>
                                            <label>Estado
                                                <select name="estado"><?= academia_enum_options(academia_grupo_estados(), 'ACTIVO') ?></select>
                                            </label>
                                        </div>

                                        <fieldset class="cv-fieldset">
                                            <legend><span>Horario</span><strong>Clases de la semana</strong><em>Puedes indicar varios días. Se guardan todos.</em></legend>
                                            <?php for ($fila = 0; $fila < 3; $fila++): ?>
                                                <div class="form-grid-three">
                                                    <label>Día
                                                        <select name="horarios[<?= $fila ?>][dia_semana]">
                                                            <option value="">Sin clase</option>
                                                            <?php foreach (academia_dias_semana() as $diaValor => $diaLabel): ?>
                                                                <option value="<?= e((string) $diaValor) ?>"><?= e($diaLabel) ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </label>
                                                    <label>Hora inicio<input name="horarios[<?= $fila ?>][hora_inicio]" type="time"></label>
                                                    <label>Hora fin<input name="horarios[<?= $fila ?>][hora_fin]" type="time"></label>
                                                </div>
                                            <?php endfor; ?>
                                        </fieldset>

                                        <button class="button button-primary" type="submit">Guardar grupo</button>
                                    </form>
                                <?php else: ?>
                                    <p class="empty-state">Crea primero un curso para poder añadir grupos.</p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </section>

                <?php elseif ($activeSection === 'matriculas' && $isResponsable): ?>
                    <section class="content-section">
                        <div class="section-heading"><div class="section-heading-content"><p class="section-kicker">Administración</p><h2>Matrículas</h2></div></div>
                        <?php if ($matriculas): ?>
                            <div class="editorial-grid directory-grid">
                                <?php foreach ($matriculas as $matricula): ?>
                                    <article class="member-config-card">
                                        <h3><?= e((string) $matricula['alumno_nombre'] . ' ' . (string) $matricula['alumno_apellidos']) ?></h3>
                                        <p><?= e((string) $matricula['curso_nombre']) ?><?= $matricula['grupo_nombre'] ? ' · ' . e((string) $matricula['grupo_nombre']) : '' ?></p>
                                        <span class="status-pill <?= e(admin_badge_class((string) $matricula['estado'])) ?>"><?= e(str_replace('_', ' ', (string) $matricula['estado'])) ?></span>
                                        <form method="post" class="form-grid-two" style="margin-top:12px;">
                                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                            <input type="hidden" name="action" value="update_matricula_estado">
                                            <input type="hidden" name="matricula_id" value="<?= e((string) $matricula['id']) ?>">
                                            <select name="estado"><?= academia_enum_options(academia_matricula_estados(), (string) $matricula['estado']) ?></select>
                                            <button class="button button-secondary" type="submit">Actualizar</button>
                                        </form>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="empty-state">No hay matrículas que mostrar.</p>
                        <?php endif; ?>

                        <div class="member-config-card" style="margin-top:24px;">
                            <h3>Nueva matrícula</h3>
                            <form method="post" class="form-grid-three">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="action" value="create_matricula">
                                <label>Alumno
                                    <select name="alumno_id" required>
                                        <?php foreach ($alumnosParaMatricular as $alumno): ?>
                                            <option value="<?= e((string) $alumno['id']) ?>"><?= e(trim((string) $alumno['nombre'] . ' ' . (string) $alumno['apellidos'])) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <label>Curso
                                    <select name="curso_id" required>
                                        <?php foreach ($cursos as $curso): ?>
                                            <option value="<?= e((string) $curso['id']) ?>"><?= e((string) $curso['nombre']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <label>Grupo
                                    <select name="grupo_id">
                                        <option value="">Sin grupo asignado</option>
                                        <?php foreach ($gruposParaMatricular as $grupoOpcion): ?>
                                            <?php $ocupacionGrupo = academia_grupo_ocupacion($pdo, $academiaId, (int) $grupoOpcion['id']); ?>
                                            <option value="<?= e((string) $grupoOpcion['id']) ?>">
                                                <?= e((string) $grupoOpcion['curso_nombre'] . ' — ' . (string) $grupoOpcion['nombre']) ?>
                                                <?php if ($ocupacionGrupo['maximas'] !== null): ?>
                                                    (<?= e((string) $ocupacionGrupo['ocupadas']) ?>/<?= e((string) $ocupacionGrupo['maximas']) ?><?= $ocupacionGrupo['completo'] ? ' COMPLETO' : '' ?>)
                                                <?php endif; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <label>Estado inicial
                                    <select name="estado"><?= academia_enum_options(academia_matricula_estados(), 'SOLICITADA') ?></select>
                                </label>
                                <label>Descuento (%)<input name="descuento_pct" type="number" step="0.01" min="0" max="100"></label>
                                <label class="checkbox-field" style="align-self:end;">
                                    <input type="checkbox" name="confirmar_exceso" value="1">
                                    <span>Matricular igualmente si el grupo está completo</span>
                                </label>
                                <p class="field-help" style="grid-column:1 / -1;">El grupo debe pertenecer al curso elegido. Si está completo, hace falta marcar la casilla para superar el aforo.</p>
                                <button class="button button-primary" type="submit" style="grid-column:1 / -1;">Matricular</button>
                            </form>
                        </div>
                    </section>
                <?php endif; ?>
            </div>
        </div>
    </main>
    <?php page_footer(); ?>
</body>
</html>
