<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/activity_log.php';

/**
 * Catalogo de tienda (mega agenda, bloque 4).
 *
 * Escaparate sin carrito: cada producto es una ficha con foto, precio
 * orientativo y un enlace o contacto externo para comprar, no una compra
 * dentro de la web. El limite de fichas activas depende del nivel de
 * membresia que YA EXISTE (member_tier_has_high_limits(), app/auth.php): no
 * se crea un sistema de niveles paralelo para la tienda.
 */

const CSF_TIENDA_LIMITE_FREE = 5;
const CSF_TIENDA_LIMITE_VIP = 20;
const CSF_TIENDA_MAX_TITULO = 160;
const CSF_TIENDA_MAX_DESCRIPCION = 2000;

/**
 * @return array<string, string>
 */
function csf_tienda_estados(): array
{
    return [
        'ACTIVO' => 'Activo',
        'PAUSADO' => 'Pausado',
    ];
}

function csf_tienda_estado_valido(string $estado): string
{
    return array_key_exists($estado, csf_tienda_estados()) ? $estado : 'ACTIVO';
}

/**
 * Limite de fichas ACTIVAS que puede tener una tienda segun su nivel de
 * membresia. Mismo helper que ya distingue VIP/destacado de simpatizante para
 * los limites de la microweb (member_tier_has_high_limits(), app/auth.php):
 * 5 en el plan gratuito, 20 en VIP.
 */
function csf_tienda_limite_productos(string $tier): int
{
    return member_tier_has_high_limits($tier) ? CSF_TIENDA_LIMITE_VIP : CSF_TIENDA_LIMITE_FREE;
}

function csf_tienda_contar_activos(PDO $pdo, int $miembroId): int
{
    $statement = $pdo->prepare(
        'SELECT COUNT(*) FROM tienda_productos WHERE miembro_id = :miembro_id AND estado = "ACTIVO" AND deleted_at IS NULL'
    );
    $statement->execute(['miembro_id' => $miembroId]);

    return (int) $statement->fetchColumn();
}

/**
 * "25,00 €", o cadena vacia si no hay precio (es opcional: un escaparate
 * puede mostrar "consultar precio").
 */
function csf_tienda_precio_formato(?int $centimos): string
{
    if ($centimos === null) {
        return '';
    }

    return number_format($centimos / 100, 2, ',', '.') . ' €';
}

function csf_tienda_imagen_url(array $producto): string
{
    $ruta = clean_text((string) ($producto['imagen_path'] ?? ''));

    return $ruta !== '' ? csf_media_url_absolute($ruta) : '';
}

/**
 * @param array<string, mixed> $datos
 * @return string[]
 */
function csf_tienda_producto_validar(array $datos): array
{
    $errores = [];

    $titulo = clean_text((string) ($datos['titulo'] ?? ''));
    if ($titulo === '') {
        $errores[] = 'El artículo necesita un título.';
    } elseif (mb_strlen($titulo) > CSF_TIENDA_MAX_TITULO) {
        $errores[] = 'El título no puede superar los ' . CSF_TIENDA_MAX_TITULO . ' caracteres.';
    }

    if (mb_strlen((string) ($datos['descripcion'] ?? '')) > CSF_TIENDA_MAX_DESCRIPCION) {
        $errores[] = 'La descripción no puede superar los ' . CSF_TIENDA_MAX_DESCRIPCION . ' caracteres.';
    }

    $precio = trim(str_replace(',', '.', (string) ($datos['precio'] ?? '')));
    if ($precio !== '' && (!is_numeric($precio) || (float) $precio < 0)) {
        $errores[] = 'El precio debe ser un número positivo (usa punto o coma decimal).';
    }

    $enlace = trim((string) ($datos['enlace_externo'] ?? ''));
    if ($enlace !== '') {
        $esquema = strtolower((string) parse_url($enlace, PHP_URL_SCHEME));
        if (!in_array($esquema, ['http', 'https'], true) || filter_var($enlace, FILTER_VALIDATE_URL) === false) {
            $errores[] = 'El enlace o contacto externo debe ser una dirección web válida (empieza por http:// o https://).';
        }
    }

    return $errores;
}

/**
 * Guarda la foto del producto. Reutiliza `curriculum-images/`, el mismo
 * directorio que ya usan los carteles de evento
 * (csf_evento_guardar_cartel(), app/events_repository.php): es uno de los dos
 * que admite csf_normalize_media_file(), asi que no hace falta tocar el
 * guardian de medios ni abrir un directorio nuevo.
 *
 * @param string[] $errores
 */
function csf_tienda_guardar_imagen(?array $file, array &$errores): ?string
{
    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        $errores[] = 'No se pudo subir la imagen del artículo. Vuelve a intentarlo.';

        return null;
    }

    if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
        $errores[] = 'La imagen debe pesar menos de 5 MB.';

        return null;
    }

    $info = @getimagesize((string) ($file['tmp_name'] ?? ''));
    if (!$info || empty($info['mime'])) {
        $errores[] = 'La imagen debe ser un archivo válido.';

        return null;
    }

    $extensiones = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $mime = (string) $info['mime'];
    if (!isset($extensiones[$mime])) {
        $errores[] = 'La imagen debe estar en formato JPG, PNG o WebP.';

        return null;
    }

    $nombre = 'producto-' . gmdate('YmdHis') . '-' . bin2hex(random_bytes(6)) . '.' . $extensiones[$mime];
    $destino = MEMBER_CV_IMAGES_DIR . '/' . $nombre;
    $temporal = (string) ($file['tmp_name'] ?? '');
    $movido = is_uploaded_file($temporal)
        ? move_uploaded_file($temporal, $destino)
        : rename($temporal, $destino);

    if (!$movido) {
        $errores[] = 'No se pudo guardar la imagen del artículo.';

        return null;
    }

    return csf_media_url('curriculum-images/' . $nombre);
}

/**
 * @return array<string, mixed>|null
 */
function csf_tienda_producto_de_miembro(PDO $pdo, int $productoId, int $miembroId): ?array
{
    $statement = $pdo->prepare(
        'SELECT * FROM tienda_productos WHERE id = :id AND miembro_id = :miembro_id AND deleted_at IS NULL'
    );
    $statement->execute(['id' => $productoId, 'miembro_id' => $miembroId]);
    $fila = $statement->fetch(PDO::FETCH_ASSOC);

    return $fila !== false ? $fila : null;
}

/**
 * Todos los productos de una tienda (activos y pausados), para su panel.
 *
 * @return array<int, array<string, mixed>>
 */
function csf_tienda_productos_de_miembro(PDO $pdo, int $miembroId): array
{
    $statement = $pdo->prepare(
        'SELECT * FROM tienda_productos
         WHERE miembro_id = :miembro_id AND deleted_at IS NULL
         ORDER BY (estado = "ACTIVO") DESC, orden ASC, created_at DESC'
    );
    $statement->execute(['miembro_id' => $miembroId]);

    return $statement->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Catálogo público de una tienda: solo los productos activos.
 *
 * @return array<int, array<string, mixed>>
 */
function csf_tienda_productos_publicos(PDO $pdo, int $miembroId, int $limite = 20): array
{
    $statement = $pdo->prepare(
        'SELECT * FROM tienda_productos
         WHERE miembro_id = :miembro_id AND estado = "ACTIVO" AND deleted_at IS NULL
         ORDER BY orden ASC, created_at DESC
         LIMIT ' . max(1, min(96, $limite))
    );
    $statement->execute(['miembro_id' => $miembroId]);

    return $statement->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Crea o actualiza un producto. El límite por nivel solo se comprueba al
 * CREAR uno nuevo que se quede activo: editar, pausar o reactivar uno ya
 * existente nunca revienta el límite retroactivamente (evita que una bajada
 * de VIP a gratuito borre nada, igual que el resto de la plataforma: "nada se
 * borra al bajar de nivel", docs/17_RED_SOCIAL_FASE1.md).
 *
 * @param array<string, mixed> $datos
 * @return array{ok: bool, id: int, errors: string[]}
 */
function csf_tienda_producto_guardar(
    PDO $pdo,
    int $miembroId,
    int $usuarioId,
    ?int $productoId,
    array $datos,
    string $memberTier
): array {
    $errores = csf_tienda_producto_validar($datos);
    $esNuevo = $productoId === null || $productoId <= 0;
    $estadoDeseado = csf_tienda_estado_valido((string) ($datos['estado'] ?? 'ACTIVO'));
    $existente = null;

    if (!$esNuevo) {
        $existente = csf_tienda_producto_de_miembro($pdo, (int) $productoId, $miembroId);
        if ($existente === null) {
            return ['ok' => false, 'id' => 0, 'errors' => ['El artículo no existe o no te pertenece.']];
        }
    }

    // Solo se comprueba el limite si el resultado final es un producto activo
    // nuevo, o uno pausado que se reactiva: en ambos casos crece el recuento.
    $pasaAActivo = $estadoDeseado === 'ACTIVO' && ($esNuevo || (string) ($existente['estado'] ?? '') !== 'ACTIVO');
    if ($pasaAActivo && !$errores) {
        $limite = csf_tienda_limite_productos($memberTier);
        if (csf_tienda_contar_activos($pdo, $miembroId) >= $limite) {
            $errores[] = 'Has llegado al límite de ' . $limite . ' artículos activos de tu plan. Pausa alguno o hazte VIP para publicar más.';
        }
    }

    if ($errores) {
        return ['ok' => false, 'id' => 0, 'errors' => $errores];
    }

    $precioTexto = trim(str_replace(',', '.', (string) ($datos['precio'] ?? '')));
    $precioCentimos = $precioTexto !== '' ? (int) round(((float) $precioTexto) * 100) : null;

    $campos = [
        'titulo' => mb_substr(clean_text((string) ($datos['titulo'] ?? '')), 0, CSF_TIENDA_MAX_TITULO),
        'descripcion' => mb_substr(clean_html_text((string) ($datos['descripcion'] ?? '')), 0, CSF_TIENDA_MAX_DESCRIPCION) ?: null,
        'precio_centimos' => $precioCentimos,
        'imagen_path' => mb_substr(clean_text((string) ($datos['imagen_path'] ?? '')), 0, 255) ?: null,
        'enlace_externo' => trim((string) ($datos['enlace_externo'] ?? '')) ?: null,
        'estado' => $estadoDeseado,
    ];

    if (!$esNuevo) {
        $asignaciones = [];
        foreach (array_keys($campos) as $campo) {
            $asignaciones[] = "`{$campo}` = :{$campo}";
        }

        $statement = $pdo->prepare(
            'UPDATE tienda_productos SET ' . implode(', ', $asignaciones) . ' WHERE id = :id AND miembro_id = :miembro_id'
        );
        $statement->execute($campos + ['id' => $productoId, 'miembro_id' => $miembroId]);

        csf_log_actividad($pdo, $usuarioId, 'tienda_producto', (int) $productoId, 'editado');

        return ['ok' => true, 'id' => (int) $productoId, 'errors' => []];
    }

    $campos['miembro_id'] = $miembroId;
    $columnas = array_keys($campos);
    $marcadores = array_map(static fn (string $campo): string => ':' . $campo, $columnas);

    $statement = $pdo->prepare(
        'INSERT INTO tienda_productos (`' . implode('`, `', $columnas) . '`) VALUES (' . implode(', ', $marcadores) . ')'
    );
    $statement->execute($campos);

    $nuevoId = (int) $pdo->lastInsertId();
    csf_log_actividad($pdo, $usuarioId, 'tienda_producto', $nuevoId, 'creado');

    return ['ok' => true, 'id' => $nuevoId, 'errors' => []];
}

/**
 * Baja logica: comprueba propiedad antes de borrar, igual que
 * csf_evento_eliminar() (app/events_repository.php).
 */
function csf_tienda_producto_eliminar(PDO $pdo, int $productoId, int $miembroId, int $usuarioId): bool
{
    $statement = $pdo->prepare(
        'UPDATE tienda_productos SET deleted_at = NOW() WHERE id = :id AND miembro_id = :miembro_id AND deleted_at IS NULL'
    );
    $statement->execute(['id' => $productoId, 'miembro_id' => $miembroId]);

    if ($statement->rowCount() > 0) {
        csf_log_actividad($pdo, $usuarioId, 'tienda_producto', $productoId, 'eliminado');

        return true;
    }

    return false;
}
