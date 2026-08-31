<?php
declare(strict_types=1);

require_once __DIR__ . '/events_repository.php';
require_once __DIR__ . '/geo_repository.php';

/**
 * Componentes visuales de eventos.
 *
 * Una sola definicion de la tarjeta para los cuatro sitios donde aparece: la
 * agenda publica, la pagina del evento (eventos relacionados), el microsite del
 * artista y el panel del artista. Si la tarjeta cambia, cambia en los cuatro.
 *
 * Los estilos viven en assets/css/styles.css bajo el prefijo `.csf-event-`.
 */

/**
 * @return string[]
 */
function csf_evento_meses_cortos(): array
{
    return ['ENE', 'FEB', 'MAR', 'ABR', 'MAY', 'JUN', 'JUL', 'AGO', 'SEP', 'OCT', 'NOV', 'DIC'];
}

/**
 * @return string[]
 */
function csf_evento_meses_largos(): array
{
    return [
        'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
        'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre',
    ];
}

/**
 * Partes de la fecha para el sello "20 / SEP" de la tarjeta.
 *
 * @return array{dia:string, mes:string, ano:string, valida:bool}
 */
function csf_evento_fecha_partes(?string $fecha): array
{
    $timestamp = $fecha !== null && $fecha !== '' ? strtotime($fecha) : false;
    if ($timestamp === false) {
        return ['dia' => '--', 'mes' => '', 'ano' => '', 'valida' => false];
    }

    return [
        'dia' => date('d', $timestamp),
        'mes' => csf_evento_meses_cortos()[(int) date('n', $timestamp) - 1],
        'ano' => date('Y', $timestamp),
        'valida' => true,
    ];
}

/**
 * "20 de septiembre de 2026".
 */
function csf_evento_fecha_larga(?string $fecha): string
{
    $timestamp = $fecha !== null && $fecha !== '' ? strtotime($fecha) : false;
    if ($timestamp === false) {
        return '';
    }

    return date('j', $timestamp) . ' de ' . csf_evento_meses_largos()[(int) date('n', $timestamp) - 1]
        . ' de ' . date('Y', $timestamp);
}

/**
 * "22:00" a partir de un TIME "22:00:00". Cadena vacia si no hay hora.
 */
function csf_evento_hora_corta(?string $hora): string
{
    $hora = trim((string) ($hora ?? ''));
    if ($hora === '') {
        return '';
    }

    return preg_match('/^(\d{2}:\d{2})/', $hora, $coincidencias) === 1 ? $coincidencias[1] : '';
}

/**
 * URL publica del evento. Absoluta, porque la tarjeta tambien se pinta dentro de
 * /artista/{slug}, donde una ruta relativa se resolveria contra /artista/.
 */
function csf_evento_url(array $evento): string
{
    $slug = clean_text((string) ($evento['slug'] ?? ''));

    return app_url('evento/' . rawurlencode($slug));
}

/**
 * URL del cartel, o cadena vacia. Pasa por csf_media_url_absolute() para que la
 * imagen no se rompa en las paginas con URL limpia.
 */
function csf_evento_imagen_url(array $evento): string
{
    $ruta = clean_text((string) ($evento['imagen_path'] ?? ''));

    return $ruta !== '' ? csf_media_url_absolute($ruta) : '';
}

/**
 * Ubicacion lista para pintar: "Montilla · Córdoba".
 */
function csf_evento_ubicacion(array $evento): string
{
    return csf_geo_etiqueta(
        (string) ($evento['municipio_texto'] ?? ''),
        (string) ($evento['provincia_texto'] ?? '')
    );
}

/**
 * Recorta la descripcion sin partir palabras.
 */
function csf_evento_resumen(?string $descripcion, int $maximo = 150): string
{
    $texto = clean_text(strip_tags((string) ($descripcion ?? '')));
    if ($texto === '' || mb_strlen($texto) <= $maximo) {
        return $texto;
    }

    $corte = mb_substr($texto, 0, $maximo);
    $ultimoEspacio = mb_strrpos($corte, ' ');
    if ($ultimoEspacio !== false && $ultimoEspacio > $maximo * 0.6) {
        $corte = mb_substr($corte, 0, $ultimoEspacio);
    }

    return rtrim($corte, " ,.;:") . '…';
}

/**
 * Tarjeta de evento.
 *
 * Opciones:
 *   'artista'   => bool  Mostrar el nombre del organizador (por defecto true).
 *                        Se apaga dentro del microsite del artista, donde seria
 *                        repetir su nombre en cada tarjeta.
 *   'acciones'  => string HTML extra al pie (botones del panel).
 *   'compacta'  => bool  Variante estrecha para barras laterales.
 *   'cta'       => string Texto del enlace principal.
 */
function csf_evento_card(array $evento, array $opciones = []): string
{
    $mostrarArtista = $opciones['artista'] ?? true;
    $compacta = !empty($opciones['compacta']);
    $cta = (string) ($opciones['cta'] ?? 'Ver evento');
    $acciones = (string) ($opciones['acciones'] ?? '');

    $destacado = csf_evento_promocion_vigente($evento);
    $fecha = csf_evento_fecha_partes((string) ($evento['fecha'] ?? ''));
    $hora = csf_evento_hora_corta((string) ($evento['hora'] ?? ''));
    $imagen = csf_evento_imagen_url($evento);
    $ubicacion = csf_evento_ubicacion($evento);
    $titulo = (string) ($evento['titulo'] ?? 'Evento');
    $resumen = csf_evento_resumen((string) ($evento['descripcion'] ?? ''), $compacta ? 90 : 150);
    $url = csf_evento_url($evento);
    $estado = (string) ($evento['estado'] ?? 'PUBLICADO');
    $pasado = csf_evento_es_pasado($evento);

    $clases = ['csf-event-card'];
    if ($destacado) {
        $clases[] = 'is-featured';
    }
    if ($compacta) {
        $clases[] = 'is-compact';
    }
    if ($pasado) {
        $clases[] = 'is-past';
    }

    ob_start();
    ?>
    <article class="<?= e(implode(' ', $clases)) ?>">
        <div class="csf-event-media">
            <?php if ($imagen !== ''): ?>
                <img src="<?= e($imagen) ?>" alt="Cartel de <?= e($titulo) ?>" loading="lazy" width="640" height="420">
            <?php else: ?>
                <?php /* Sin cartel no se deja un hueco roto: el sello de fecha
                         pasa a ser el propio contenido visual de la tarjeta. */ ?>
                <div class="csf-event-media-placeholder" aria-hidden="true">
                    <span><?= e($fecha['dia']) ?></span>
                    <small><?= e($fecha['mes']) ?></small>
                </div>
            <?php endif; ?>

            <span class="csf-event-date" aria-hidden="true">
                <strong><?= e($fecha['dia']) ?></strong>
                <small><?= e($fecha['mes']) ?></small>
            </span>

            <?php if ($destacado): ?>
                <span class="csf-event-flag">Destacado</span>
            <?php endif; ?>
            <?php if ($estado !== 'PUBLICADO'): ?>
                <span class="csf-event-state csf-event-state-<?= e(strtolower($estado)) ?>"><?= e(csf_evento_estados()[$estado] ?? $estado) ?></span>
            <?php endif; ?>
        </div>

        <div class="csf-event-body">
            <?php if ($mostrarArtista && trim((string) ($evento['artista_nombre'] ?? '')) !== ''): ?>
                <p class="csf-event-artist"><?= e((string) $evento['artista_nombre']) ?></p>
            <?php endif; ?>

            <h3 class="csf-event-title"><a href="<?= e($url) ?>"><?= e($titulo) ?></a></h3>

            <p class="csf-event-meta">
                <?php if ($ubicacion !== ''): ?>
                    <span class="csf-event-place"><?= e($ubicacion) ?></span>
                <?php endif; ?>
                <?php if ($hora !== ''): ?>
                    <span class="csf-event-time"><?= e($hora) ?></span>
                <?php endif; ?>
            </p>

            <?php if ($resumen !== ''): ?>
                <p class="csf-event-excerpt"><?= e($resumen) ?></p>
            <?php endif; ?>

            <div class="csf-event-actions">
                <a class="csf-event-cta" href="<?= e($url) ?>"><?= e($cta) ?><span aria-hidden="true"> →</span></a>
                <?= $acciones ?>
            </div>
        </div>
    </article>
    <?php

    return (string) ob_get_clean();
}

/**
 * True si el evento ya paso (usa la fecha de fin cuando existe).
 */
function csf_evento_es_pasado(array $evento): bool
{
    $referencia = (string) ($evento['fecha_fin'] ?: ($evento['fecha'] ?? ''));
    if ($referencia === '') {
        return false;
    }

    return strtotime($referencia . ' 23:59:59') < time();
}

/**
 * Rejilla de tarjetas. `vacio` es el texto que se muestra si no hay eventos.
 *
 * @param array<int, array<string, mixed>> $eventos
 */
function csf_evento_grid(array $eventos, array $opciones = []): string
{
    $vacio = (string) ($opciones['vacio'] ?? 'No hay eventos por ahora.');
    $modificador = (string) ($opciones['modificador'] ?? '');

    ob_start();
    if ($eventos === []) {
        ?><p class="csf-empty"><?= e($vacio) ?></p><?php
    } else {
        ?>
        <div class="csf-event-grid <?= e($modificador) ?>">
            <?php foreach ($eventos as $evento): ?><?= csf_evento_card($evento, $opciones) ?><?php endforeach; ?>
        </div>
        <?php
    }

    return (string) ob_get_clean();
}

/**
 * Agenda agrupada por mes. Es la vista de la agenda publica: manda el orden
 * cronologico y el mes hace de separador para poder recorrerla de un vistazo.
 *
 * @param array<int, array<string, mixed>> $eventos
 */
function csf_evento_agenda_agrupada(array $eventos, array $opciones = []): string
{
    if ($eventos === []) {
        return '<p class="csf-empty">' . e((string) ($opciones['vacio'] ?? 'No hay eventos programados con estos filtros.')) . '</p>';
    }

    $grupos = [];
    foreach ($eventos as $evento) {
        $timestamp = strtotime((string) ($evento['fecha'] ?? ''));
        $clave = $timestamp !== false ? date('Y-m', $timestamp) : 'sin-fecha';
        $grupos[$clave][] = $evento;
    }

    ob_start();
    foreach ($grupos as $clave => $delMes) {
        $timestamp = strtotime($clave . '-01');
        $etiqueta = $timestamp !== false
            ? ucfirst(csf_evento_meses_largos()[(int) date('n', $timestamp) - 1]) . ' ' . date('Y', $timestamp)
            : 'Próximamente';
        ?>
        <section class="csf-agenda-month">
            <h3 class="csf-agenda-month-title"><?= e($etiqueta) ?><span><?= e((string) count($delMes)) ?></span></h3>
            <div class="csf-event-grid">
                <?php foreach ($delMes as $evento): ?><?= csf_evento_card($evento, $opciones) ?><?php endforeach; ?>
            </div>
        </section>
        <?php
    }

    return (string) ob_get_clean();
}
