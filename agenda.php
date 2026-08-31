<?php
declare(strict_types=1);

require_once __DIR__ . '/app/auth.php';
require_once __DIR__ . '/app/layout.php';
require_once __DIR__ . '/app/events_ui.php';
require_once __DIR__ . '/app/directory_helpers.php';

/**
 * Agenda publica de eventos flamencos.
 *
 * Solo eventos futuros o en curso, en orden cronologico estricto por dia. Los
 * promocionados encabezan su propio dia y ademas ocupan la franja de destacados
 * de arriba; nunca adelantan a un evento de una fecha anterior.
 *
 * Los eventos pasados no se pierden: siguen en el perfil publico del artista
 * como historico.
 */

$pdo = db();

$provincias = [];
$municipios = [];
$eventos = [];
$destacados = [];
$totalEventos = 0;

$provinciaSlug = slugify(clean_text((string) ($_GET['provincia'] ?? '')));
$municipioId = (int) ($_GET['municipio'] ?? 0);
$disciplina = csf_active_discipline($_GET);
$buscar = clean_text((string) ($_GET['q'] ?? ''));
$provinciaActual = null;

if ($pdo) {
    try {
        $provincias = csf_geo_provincias($pdo);
        $provinciaActual = $provinciaSlug !== '' ? csf_geo_provincia_por_slug($pdo, $provinciaSlug) : null;

        // El municipio solo tiene sentido dentro de su provincia: si se cambia de
        // provincia, el municipio heredado de la URL anterior se descarta.
        if ($provinciaActual !== null) {
            $municipios = csf_geo_municipios($pdo, $provinciaActual['id']);
            $idsValidos = array_column($municipios, 'id');
            if ($municipioId > 0 && !in_array($municipioId, $idsValidos, true)) {
                $municipioId = 0;
            }
        } else {
            $municipioId = 0;
        }

        $filtros = [
            'provincia_id' => $provinciaActual['id'] ?? 0,
            'municipio_id' => $municipioId,
            'disciplina' => $disciplina,
            'buscar' => $buscar,
            'limite' => 60,
        ];

        $eventos = csf_evento_agenda($pdo, $filtros);
        $destacados = csf_evento_destacados($pdo, $filtros, 3);
        $totalEventos = csf_evento_agenda_total($pdo, $filtros);
    } catch (Throwable $exception) {
        // Entorno sin migrar: la pagina sigue en pie y avisa de que no hay datos.
        error_log('[agenda] ' . $exception->getMessage());
    }
}

$municipioActual = $municipioId > 0 && $pdo ? csf_geo_municipio_por_id($pdo, $municipioId) : null;
$hayFiltros = $provinciaActual !== null || $municipioId > 0 || $disciplina !== 'todos' || $buscar !== '';

$tituloFiltrado = 'Agenda flamenca';
if ($provinciaActual !== null) {
    $tituloFiltrado = 'Agenda flamenca en ' . ($municipioActual['nombre'] ?? $provinciaActual['nombre']);
}
?>
<!DOCTYPE html>
<html lang="es">
<?php page_head(
    $tituloFiltrado . ' | Con Sabor Flamenco',
    'Próximos eventos flamencos ordenados por fecha: espectáculos, recitales, festivales y encuentros por provincia y municipio.',
    false
); ?>
<body>
    <?php page_header('EVENTOS'); ?>
    <main>
        <section class="page-intro" data-ad-category="EVENTOS">
            <p class="section-kicker">Agenda</p>
            <h1><?= e($tituloFiltrado) ?></h1>
            <p>Los próximos eventos flamencos, del más cercano al más lejano. Publicar es gratis para cualquier artista de la comunidad.</p>
        </section>

        <div class="page-shell">
            <div class="primary-content">
                <aside class="ad-mobile-strip" aria-label="Publicidad local">
                    <div class="ad-sidebar-heading">
                        <div>
                            <span class="ad-eyebrow">Selección patrocinada</span>
                            <h2><span data-ad-category-label>Agenda</span> · <span data-ad-province>tu provincia</span></h2>
                        </div>
                        <button type="button" class="text-button" data-open-province>Cambiar provincia</button>
                    </div>
                    <div class="ad-slots" data-ad-slots></div>
                </aside>

                <section id="agenda" class="content-section" data-ad-category="EVENTOS">
                    <div class="section-heading">
                        <div class="section-heading-content">
                            <p class="section-kicker">Próximos eventos</p>
                            <h2>Qué se cuece</h2>
                            <p>Filtra por provincia, municipio o disciplina para encontrar el flamenco que tienes cerca.</p>
                        </div>
                    </div>

                    <form class="csf-agenda-filters" method="get" action="agenda.php">
                        <div>
                            <label for="filtro-provincia">Provincia</label>
                            <select id="filtro-provincia" name="provincia" onchange="this.form.municipio.value=''; this.form.submit();">
                                <option value="">Toda España</option>
                                <?php foreach ($provincias as $provincia): ?>
                                    <option value="<?= e($provincia['slug']) ?>"<?= ($provinciaActual['slug'] ?? '') === $provincia['slug'] ? ' selected' : '' ?>><?= e($provincia['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label for="filtro-municipio">Municipio</label>
                            <select id="filtro-municipio" name="municipio"<?= $municipios === [] ? ' disabled' : '' ?>>
                                <option value="">Todos</option>
                                <?php foreach ($municipios as $municipio): ?>
                                    <option value="<?= e((string) $municipio['id']) ?>"<?= $municipioId === $municipio['id'] ? ' selected' : '' ?>><?= e($municipio['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label for="filtro-disciplina">Disciplina</label>
                            <select id="filtro-disciplina" name="disciplina">
                                <?php foreach (csf_discipline_options() as $valor => $etiqueta): ?>
                                    <option value="<?= e($valor) ?>"<?= $disciplina === $valor ? ' selected' : '' ?>><?= e($etiqueta) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label for="filtro-buscar">Buscar</label>
                            <input type="search" id="filtro-buscar" name="q" value="<?= e($buscar) ?>" placeholder="Artista, título o lugar">
                        </div>

                        <div class="csf-agenda-filters-actions">
                            <button class="button button-primary" type="submit">Filtrar</button>
                            <?php if ($hayFiltros): ?>
                                <a class="button button-secondary" href="agenda.php">Quitar filtros</a>
                            <?php endif; ?>
                        </div>
                    </form>

                    <?php if (!$pdo): ?>
                        <p class="csf-empty">La agenda no está disponible en este momento. Vuelve a intentarlo en unos minutos.</p>
                    <?php else: ?>
                        <?php if ($destacados): ?>
                            <div class="csf-agenda-featured">
                                <h3 class="csf-agenda-featured-title">Destacados</h3>
                                <div class="csf-event-grid">
                                    <?php foreach ($destacados as $evento): ?><?= csf_evento_card($evento) ?><?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <p class="csf-agenda-count">
                            <?= e($totalEventos === 1 ? '1 evento programado' : $totalEventos . ' eventos programados') ?><?php if ($hayFiltros): ?> con los filtros aplicados<?php endif; ?>.
                        </p>

                        <?= csf_evento_agenda_agrupada($eventos, [
                            'vacio' => $hayFiltros
                                ? 'No hay eventos programados con estos filtros. Prueba con otra provincia o quita los filtros.'
                                : 'Todavía no hay eventos publicados. Si eres artista, puedes publicar el tuyo gratis desde tu panel.',
                        ]) ?>
                    <?php endif; ?>

                    <div class="csf-social-note" style="margin-top: 30px;">
                        <strong>¿Eres artista?</strong>
                        Publicar tus eventos en esta agenda es gratis y sin límite.
                        <a href="<?= e(app_url('panel-usuario.php#evento-form')) ?>">Crea tu evento</a>
                        o <a href="<?= e(app_url('registro.php')) ?>">hazte miembro</a>.
                    </div>
                </section>
            </div>

            <aside class="ad-sidebar" aria-label="Publicidad local">
                <div class="ad-sidebar-inner">
                    <div class="ad-sidebar-heading">
                        <div>
                            <span class="ad-eyebrow">Selección patrocinada</span>
                            <h2><span data-ad-category-label>Agenda</span> · <span data-ad-province>tu provincia</span></h2>
                        </div>
                        <button type="button" class="text-button" data-open-province>Cambiar</button>
                    </div>
                    <div class="ad-slots" data-ad-slots></div>
                    <p class="ad-disclosure">Espacios publicitarios seleccionados por sección y provincia.</p>
                </div>
            </aside>
        </div>
    </main>
    <?php page_footer(); ?>
    <?php province_modal('Así te mostraremos primero los eventos flamencos más cercanos. Guardaremos únicamente la provincia en este dispositivo.'); ?>
</body>
</html>
