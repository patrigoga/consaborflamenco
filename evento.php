<?php
declare(strict_types=1);

require_once __DIR__ . '/app/auth.php';
require_once __DIR__ . '/app/layout.php';
require_once __DIR__ . '/app/events_ui.php';

/**
 * Pagina publica de un evento: /evento/{slug}
 *
 * Sirve tanto la URL limpia (via .htaccess) como evento.php?slug=... Un slug
 * inexistente, borrado o de una cuenta inactiva responde 404 de verdad, no una
 * pagina vacia con codigo 200.
 */

$slug = clean_text((string) ($_GET['slug'] ?? ''));
$pdo = db();
$evento = null;

if ($pdo && $slug !== '') {
    try {
        $evento = csf_evento_por_slug($pdo, $slug);
    } catch (Throwable $exception) {
        error_log('[evento] ' . $exception->getMessage());
    }
}

if ($evento === null) {
    http_response_code(404);
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <?php page_head('Evento no encontrado | Con Sabor Flamenco', 'El evento que buscas no está disponible.', false); ?>
    <body>
        <?php page_header('EVENTOS'); ?>
        <main>
            <section class="page-intro">
                <p class="section-kicker">Agenda</p>
                <h1>Este evento ya no está disponible</h1>
                <p>Puede que se haya retirado o que la dirección no sea correcta.</p>
            </section>
            <div class="page-shell">
                <div class="primary-content">
                    <section class="content-section">
                        <p class="csf-empty">Consulta la <a href="<?= e(app_url('agenda')) ?>">agenda completa</a> para ver los próximos eventos flamencos.</p>
                    </section>
                </div>
            </div>
        </main>
        <?php page_footer(); ?>
    </body>
    </html>
    <?php
    exit;
}

// Los borradores solo los ve su propio autor; para el resto no existen.
$usuarioActual = current_user();
$esPropietario = $usuarioActual !== null && (int) ($usuarioActual['db_id'] ?? 0) === (int) $evento['usuario_id'];
if ((string) $evento['estado'] !== 'PUBLICADO' && !$esPropietario) {
    http_response_code(404);
    redirect_to(app_url('agenda'));
}

if (!$esPropietario) {
    csf_evento_registrar_visita($pdo, (int) $evento['id']);
}

$titulo = (string) $evento['titulo'];
$destacado = csf_evento_promocion_vigente($evento);
$imagen = csf_evento_imagen_url($evento);
$ubicacion = csf_evento_ubicacion($evento);
$fechaLarga = csf_evento_fecha_larga((string) $evento['fecha']);
$fechaFinLarga = $evento['fecha_fin'] ? csf_evento_fecha_larga((string) $evento['fecha_fin']) : '';
$hora = csf_evento_hora_corta((string) ($evento['hora'] ?? ''));
$descripcion = trim((string) ($evento['descripcion'] ?? ''));
$resumenMeta = csf_evento_resumen($descripcion, 155);
$enlaceExterno = (string) ($evento['enlace_url'] ?? '');
$videoUrl = (string) ($evento['video_url'] ?? '');
$pasado = csf_evento_es_pasado($evento);

$artistaNombre = (string) ($evento['artista_nombre'] ?? '');
$artistaSlug = (string) ($evento['artista_slug'] ?? '');
$artistaTipo = (string) ($evento['artista_tipo'] ?? 'artista');
$artistaUrl = $artistaSlug !== '' ? member_public_url($artistaTipo, $artistaSlug) : '';
$artistaFoto = clean_text((string) ($evento['artista_foto'] ?? ''));
$artistaFotoUrl = $artistaFoto !== '' ? csf_media_url_absolute($artistaFoto) : '';

// Otros eventos del mismo artista, sin repetir el que se esta viendo.
$otrosEventos = [];
try {
    $otrosEventos = csf_evento_agenda($pdo, [
        'miembro_id' => (int) $evento['miembro_id'],
        'excluir_id' => (int) $evento['id'],
        'limite' => 3,
    ]);
} catch (Throwable $exception) {
    error_log('[evento] relacionados: ' . $exception->getMessage());
}

$urlCanonica = app_url('evento/' . rawurlencode((string) $evento['slug']));
$textoCompartir = $titulo . ($ubicacion !== '' ? ' · ' . $ubicacion : '');
?>
<!DOCTYPE html>
<html lang="es">
<?php page_head(
    $titulo . ' | Con Sabor Flamenco',
    $resumenMeta !== '' ? $resumenMeta : ('Evento flamenco' . ($ubicacion !== '' ? ' en ' . $ubicacion : '') . '. ' . $fechaLarga),
    false
); ?>
<body>
    <?php page_header('EVENTOS'); ?>
    <main>
        <section class="page-intro" data-ad-category="EVENTOS">
            <p class="section-kicker"><a href="<?= e(app_url('agenda')) ?>" style="color: inherit;">Agenda</a></p>
            <h1><?= e($titulo) ?></h1>
            <p><?= e($fechaLarga) ?><?= $fechaFinLarga !== '' ? ' — ' . e($fechaFinLarga) : '' ?><?= $hora !== '' ? ' · ' . e($hora) . ' h' : '' ?><?= $ubicacion !== '' ? ' · ' . e($ubicacion) : '' ?></p>
        </section>

        <div class="page-shell">
            <div class="primary-content">
                <section class="content-section" data-ad-category="EVENTOS">
                    <?php if ($pasado): ?>
                        <p class="csf-social-note" style="margin-bottom: 20px;">Este evento ya se celebró. Se conserva como parte del histórico del artista.</p>
                    <?php endif; ?>
                    <?php if ((string) $evento['estado'] !== 'PUBLICADO'): ?>
                        <p class="csf-social-note" style="margin-bottom: 20px;"><strong>Vista previa.</strong> Este evento está en estado «<?= e(csf_evento_estados()[(string) $evento['estado']] ?? (string) $evento['estado']) ?>» y solo lo ves tú.</p>
                    <?php endif; ?>

                    <article class="csf-event-detail">
                        <?php if ($imagen !== ''): ?>
                            <img class="csf-event-detail-poster" src="<?= e($imagen) ?>" alt="Cartel de <?= e($titulo) ?>" loading="lazy">
                        <?php endif; ?>

                        <?php if ($destacado): ?>
                            <p><span class="csf-event-flag" style="position: static; display: inline-block;">Destacado</span></p>
                        <?php endif; ?>

                        <dl class="csf-event-facts">
                            <div><dt>Fecha</dt><dd><?= e($fechaLarga) ?><?= $fechaFinLarga !== '' ? ' — ' . e($fechaFinLarga) : '' ?></dd></div>
                            <?php if ($hora !== ''): ?><div><dt>Hora</dt><dd><?= e($hora) ?> h</dd></div><?php endif; ?>
                            <?php if (trim((string) $evento['lugar']) !== ''): ?><div><dt>Lugar</dt><dd><?= e((string) $evento['lugar']) ?></dd></div><?php endif; ?>
                            <?php if (trim((string) $evento['direccion']) !== ''): ?><div><dt>Dirección</dt><dd><?= e((string) $evento['direccion']) ?></dd></div><?php endif; ?>
                            <?php if (trim((string) $evento['municipio_texto']) !== ''): ?><div><dt>Municipio</dt><dd><?= e((string) $evento['municipio_texto']) ?></dd></div><?php endif; ?>
                            <?php if (trim((string) $evento['provincia_texto']) !== ''): ?><div><dt>Provincia</dt><dd><?= e((string) $evento['provincia_texto']) ?></dd></div><?php endif; ?>
                            <?php if ($artistaNombre !== ''): ?><div><dt>Organiza</dt><dd><?= e($artistaNombre) ?></dd></div><?php endif; ?>
                        </dl>

                        <?php if ($descripcion !== ''): ?>
                            <div class="csf-event-description">
                                <?php foreach (preg_split('/\n\s*\n/', $descripcion) ?: [] as $parrafo): ?>
                                    <p><?= nl2br(e(trim($parrafo))) ?></p>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($videoUrl !== ''): ?>
                            <p><a class="button button-secondary" href="<?= e($videoUrl) ?>" target="_blank" rel="noopener">Ver el vídeo</a></p>
                        <?php endif; ?>

                        <?php if ($enlaceExterno !== ''): ?>
                            <p><a class="button button-primary" href="<?= e($enlaceExterno) ?>" target="_blank" rel="noopener">Más información y entradas</a></p>
                        <?php endif; ?>

                        <?php /* Compartir sin dependencias externas ni rastreadores. */ ?>
                        <div class="csf-event-share">
                            <span>Compartir:</span>
                            <a href="https://wa.me/?text=<?= e(rawurlencode($textoCompartir . ' ' . $urlCanonica)) ?>" target="_blank" rel="noopener">WhatsApp</a>
                            <a href="https://www.facebook.com/sharer/sharer.php?u=<?= e(rawurlencode($urlCanonica)) ?>" target="_blank" rel="noopener">Facebook</a>
                            <a href="https://x.com/intent/tweet?text=<?= e(rawurlencode($textoCompartir)) ?>&url=<?= e(rawurlencode($urlCanonica)) ?>" target="_blank" rel="noopener">X</a>
                            <a href="mailto:?subject=<?= e(rawurlencode($titulo)) ?>&body=<?= e(rawurlencode($textoCompartir . "\n\n" . $urlCanonica)) ?>">Email</a>
                        </div>
                    </article>
                </section>

                <?php if ($artistaNombre !== ''): ?>
                    <section class="content-section">
                        <div class="section-heading">
                            <div class="section-heading-content">
                                <p class="section-kicker">Quién lo organiza</p>
                                <h2>El artista</h2>
                            </div>
                        </div>
                        <div class="csf-event-artist-card">
                            <?php if ($artistaFotoUrl !== ''): ?>
                                <img src="<?= e($artistaFotoUrl) ?>" alt="Foto de <?= e($artistaNombre) ?>" loading="lazy">
                            <?php endif; ?>
                            <div>
                                <strong><?= e($artistaNombre) ?></strong>
                                <?php if ($artistaUrl !== ''): ?>
                                    <p><a href="<?= e($artistaUrl) ?>">Ver su perfil completo →</a></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </section>
                <?php endif; ?>

                <?php if ($otrosEventos): ?>
                    <section class="content-section">
                        <div class="section-heading">
                            <div class="section-heading-content">
                                <p class="section-kicker">Más fechas</p>
                                <h2>Otros eventos de <?= e($artistaNombre !== '' ? $artistaNombre : 'este artista') ?></h2>
                            </div>
                        </div>
                        <div class="csf-event-grid">
                            <?php foreach ($otrosEventos as $otro): ?><?= csf_evento_card($otro, ['artista' => false]) ?><?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>
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
    <?php province_modal(); ?>
</body>
</html>
