<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/points_repository.php';
require_once __DIR__ . '/activity_log.php';

/**
 * Redes sociales del miembro.
 *
 * Dos conceptos distintos y a proposito separados:
 *
 *   visible       -> la red se muestra en el perfil publico. Siempre gratis.
 *   enlace_activo -> ademas es clicable. El primero gratis; los siguientes,
 *                    csf_puntos_coste('enlace_social') puntos cada uno.
 *
 * Un enlace activado NO se desactiva ni se reembolsa nunca: el artista pago por
 * el y se queda. Cambiar la URL de una red ya activada tampoco vuelve a cobrar.
 */

/**
 * Catalogo de redes admitidas. Anadir una red es anadir una entrada aqui: la
 * columna `red` es VARCHAR, no ENUM, precisamente para no necesitar migracion.
 *
 * @return array<string, array{nombre:string, placeholder:string, prefijo:string}>
 */
function csf_redes_catalogo(): array
{
    return [
        'instagram' => [
            'nombre' => 'Instagram',
            'placeholder' => 'https://instagram.com/tu-usuario',
            'prefijo' => 'https://instagram.com/',
        ],
        'facebook' => [
            'nombre' => 'Facebook',
            'placeholder' => 'https://facebook.com/tu-pagina',
            'prefijo' => 'https://facebook.com/',
        ],
        'tiktok' => [
            'nombre' => 'TikTok',
            'placeholder' => 'https://tiktok.com/@tu-usuario',
            'prefijo' => 'https://tiktok.com/@',
        ],
        'youtube' => [
            'nombre' => 'YouTube',
            'placeholder' => 'https://youtube.com/@tu-canal',
            'prefijo' => 'https://youtube.com/@',
        ],
        'twitter' => [
            'nombre' => 'X / Twitter',
            'placeholder' => 'https://x.com/tu-usuario',
            'prefijo' => 'https://x.com/',
        ],
        'web' => [
            'nombre' => 'Página web',
            'placeholder' => 'https://tu-web.com',
            'prefijo' => '',
        ],
    ];
}

function csf_red_valida(string $red): bool
{
    return array_key_exists(strtolower(trim($red)), csf_redes_catalogo());
}

function csf_red_nombre(string $red): string
{
    return csf_redes_catalogo()[strtolower(trim($red))]['nombre'] ?? ucfirst($red);
}

/**
 * Todas las redes del miembro, en el orden del catalogo, incluidas las que
 * todavia no ha rellenado (con url vacia). Asi el formulario del panel siempre
 * pinta las seis filas y el usuario ve que puede anadir.
 *
 * @return array<string, array<string, mixed>>
 */
function csf_redes_de_miembro(PDO $pdo, int $miembroId): array
{
    $guardadas = [];
    if ($miembroId > 0) {
        $statement = $pdo->prepare('SELECT * FROM miembro_redes WHERE miembro_id = :miembro_id');
        $statement->execute(['miembro_id' => $miembroId]);
        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $fila) {
            $guardadas[(string) $fila['red']] = $fila;
        }
    }

    $resultado = [];
    foreach (csf_redes_catalogo() as $red => $meta) {
        $fila = $guardadas[$red] ?? null;
        $resultado[$red] = [
            'red' => $red,
            'nombre' => $meta['nombre'],
            'placeholder' => $meta['placeholder'],
            'url' => (string) ($fila['url'] ?? ''),
            'handle' => (string) ($fila['handle'] ?? ''),
            'visible' => $fila === null ? true : (bool) $fila['visible'],
            'enlace_activo' => $fila !== null && (bool) $fila['enlace_activo'],
            'activado_at' => $fila['activado_at'] ?? null,
            'coste_puntos' => (int) ($fila['coste_puntos'] ?? 0),
            'configurada' => trim((string) ($fila['url'] ?? '')) !== '',
        ];
    }

    return $resultado;
}

/**
 * Solo las redes que el visitante debe ver en el perfil publico: rellenadas y
 * marcadas como visibles.
 *
 * @return array<int, array<string, mixed>>
 */
function csf_redes_publicas(PDO $pdo, int $miembroId): array
{
    $publicas = [];
    foreach (csf_redes_de_miembro($pdo, $miembroId) as $red) {
        if ($red['configurada'] && $red['visible']) {
            $publicas[] = $red;
        }
    }

    return $publicas;
}

function csf_redes_contar_activas(PDO $pdo, int $miembroId): int
{
    $statement = $pdo->prepare(
        'SELECT COUNT(*) FROM miembro_redes WHERE miembro_id = :miembro_id AND enlace_activo = 1'
    );
    $statement->execute(['miembro_id' => $miembroId]);

    return (int) $statement->fetchColumn();
}

/**
 * Coste real de activar el proximo enlace de este miembro.
 *
 * Devuelve 0 si aun no tiene ninguno activo (el primero es gratis). Es la unica
 * fuente valida del importe: el formulario solo lo muestra.
 */
function csf_redes_coste_activacion(PDO $pdo, int $miembroId): int
{
    return csf_redes_contar_activas($pdo, $miembroId) === 0
        ? 0
        : csf_puntos_coste('enlace_social');
}

/**
 * Extrae "@usuario" de una URL, para mostrarlo bajo el nombre de la red.
 */
function csf_red_handle_desde_url(string $red, string $url): string
{
    $url = trim($url);
    if ($url === '') {
        return '';
    }

    if ($red === 'web') {
        $host = (string) parse_url($url, PHP_URL_HOST);
        return $host !== '' ? preg_replace('/^www\./', '', $host) : '';
    }

    $ruta = trim((string) parse_url($url, PHP_URL_PATH), '/');
    if ($ruta === '') {
        return '';
    }

    // Solo el primer segmento: /@bailaor/videos -> @bailaor
    $primero = explode('/', $ruta)[0];
    $primero = ltrim($primero, '@');

    return $primero !== '' ? '@' . mb_substr($primero, 0, 118) : '';
}

/**
 * Guarda las URLs y la visibilidad de las redes. No cobra nunca: rellenar y
 * mostrar redes es gratis, solo se cobra activar el enlace.
 *
 * Una red que se envia vacia se borra de la tabla, salvo que tenga el enlace ya
 * activado: en ese caso se conserva la fila para no perder el registro de la
 * activacion pagada.
 *
 * Solo se tocan las redes PRESENTES en $entrada. Una red ausente se deja como
 * estaba: asi un guardado parcial (o un formulario que solo envie un bloque) no
 * puede borrar en silencio lo que el artista tenia configurado.
 *
 * @param array<string, array{url?:string, visible?:mixed}> $entrada
 * @param string[] $errores
 */
function csf_redes_guardar(PDO $pdo, int $miembroId, int $usuarioId, array $entrada, array &$errores): void
{
    $actuales = csf_redes_de_miembro($pdo, $miembroId);

    foreach (csf_redes_catalogo() as $red => $meta) {
        if (!array_key_exists($red, $entrada)) {
            continue;
        }

        $datos = is_array($entrada[$red] ?? null) ? $entrada[$red] : [];
        $url = trim((string) ($datos['url'] ?? ''));
        $visible = !empty($datos['visible']);
        $actual = $actuales[$red];

        if ($url !== '') {
            $url = csf_red_normalizar_url($url);
            if ($url === null) {
                $errores[] = 'La dirección de ' . $meta['nombre'] . ' no es válida (debe empezar por https://).';
                continue;
            }
        }

        if ($url === '') {
            // Sin URL: se borra, salvo que ya se pagara por su enlace.
            if ($actual['enlace_activo']) {
                $pdo->prepare('UPDATE miembro_redes SET url = NULL, handle = NULL WHERE miembro_id = :m AND red = :r')
                    ->execute(['m' => $miembroId, 'r' => $red]);
            } else {
                $pdo->prepare('DELETE FROM miembro_redes WHERE miembro_id = :m AND red = :r')
                    ->execute(['m' => $miembroId, 'r' => $red]);
            }
            continue;
        }

        $statement = $pdo->prepare(
            'INSERT INTO miembro_redes (miembro_id, red, url, handle, visible)
             VALUES (:miembro_id, :red, :url, :handle, :visible)
             ON DUPLICATE KEY UPDATE url = VALUES(url), handle = VALUES(handle), visible = VALUES(visible)'
        );
        $statement->execute([
            'miembro_id' => $miembroId,
            'red' => $red,
            'url' => mb_substr($url, 0, 255),
            'handle' => mb_substr(csf_red_handle_desde_url($red, $url), 0, 120) ?: null,
            'visible' => $visible ? 1 : 0,
        ]);
    }

    csf_log_actividad($pdo, $usuarioId, 'miembro_redes', $miembroId, 'guardadas');
}

/**
 * Acepta "instagram.com/foo" y lo convierte en "https://instagram.com/foo".
 * Devuelve null si tras normalizar sigue sin ser una URL http/https valida.
 */
function csf_red_normalizar_url(string $url): ?string
{
    $url = trim($url);
    if ($url === '' || mb_strlen($url) > 255) {
        return null;
    }

    if (preg_match('#^https?://#i', $url) !== 1) {
        $url = 'https://' . ltrim($url, '/');
    }

    $esquema = strtolower((string) parse_url($url, PHP_URL_SCHEME));
    if (!in_array($esquema, ['http', 'https'], true)) {
        return null;
    }

    return filter_var($url, FILTER_VALIDATE_URL) !== false ? $url : null;
}

/**
 * Activa el enlace de una red, cobrando los puntos que correspondan.
 *
 * El importe se calcula aqui (gratis el primero, tarifa a partir del segundo):
 * lo que mande el formulario no interviene. Todo va en una transaccion, asi que
 * o se cobra y se activa, o no pasa nada.
 *
 * @return array{red:string, puntos:int, saldo:int, gratis:bool}
 */
function csf_redes_activar_enlace(PDO $pdo, int $miembroId, int $usuarioId, string $red): array
{
    $red = strtolower(trim($red));
    if (!csf_red_valida($red)) {
        throw new RuntimeException('Red social desconocida.');
    }

    $pdo->beginTransaction();
    try {
        // Se bloquea la fila para que dos peticiones simultaneas no puedan
        // activar dos "primeros enlaces gratis".
        $statement = $pdo->prepare(
            'SELECT id, url, enlace_activo FROM miembro_redes WHERE miembro_id = :m AND red = :r FOR UPDATE'
        );
        $statement->execute(['m' => $miembroId, 'r' => $red]);
        $fila = $statement->fetch(PDO::FETCH_ASSOC);

        if (!$fila || trim((string) $fila['url']) === '') {
            throw new RuntimeException('Añade primero la dirección de ' . csf_red_nombre($red) . '.');
        }

        if ((bool) $fila['enlace_activo']) {
            throw new RuntimeException('El enlace de ' . csf_red_nombre($red) . ' ya está activo.');
        }

        $coste = csf_redes_coste_activacion($pdo, $miembroId);
        $movimientoId = null;
        $saldo = csf_puntos_saldo($pdo, $usuarioId);

        if ($coste > 0) {
            $movimiento = csf_puntos_registrar(
                $pdo,
                $usuarioId,
                -$coste,
                'ENLACE_SOCIAL',
                'Activación enlace ' . csf_red_nombre($red),
                [
                    'referencia_tipo' => 'miembro_red',
                    'referencia_id' => (int) $fila['id'],
                ]
            );
            $movimientoId = $movimiento['movimiento_id'];
            $saldo = $movimiento['saldo'];
        }

        $activar = $pdo->prepare(
            'UPDATE miembro_redes
                SET enlace_activo = 1, visible = 1, activado_at = NOW(), coste_puntos = :coste, movimiento_id = :movimiento_id
              WHERE id = :id'
        );
        $activar->execute([
            'coste' => $coste,
            'movimiento_id' => $movimientoId,
            'id' => (int) $fila['id'],
        ]);

        $pdo->commit();

        csf_log_actividad($pdo, $usuarioId, 'miembro_red', (int) $fila['id'], 'enlace_activado', [
            'red' => $red,
            'puntos' => $coste,
            'gratis' => $coste === 0,
        ]);

        return ['red' => $red, 'puntos' => $coste, 'saldo' => $saldo, 'gratis' => $coste === 0];
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}
