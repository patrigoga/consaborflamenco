<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/geo_repository.php';
require_once __DIR__ . '/points_repository.php';
require_once __DIR__ . '/activity_log.php';

/**
 * Eventos de la agenda.
 *
 * Regla de negocio central: CREAR UN EVENTO ES GRATIS. Los puntos solo entran en
 * juego para PROMOCIONARLO. La agenda necesita contenido antes que ingresos, asi
 * que nada de esta capa cobra por publicar.
 *
 * Los eventos nunca se borran: csf_evento_eliminar() marca `deleted_at` y la
 * fila se queda para auditoria.
 */

const CSF_EVENTO_MAX_TITULO = 200;
const CSF_EVENTO_MAX_DESCRIPCION = 4000;

/**
 * @return array<string, string>
 */
function csf_evento_estados(): array
{
    return [
        'BORRADOR' => 'Borrador',
        'PUBLICADO' => 'Publicado',
        'CANCELADO' => 'Cancelado',
        'ARCHIVADO' => 'Archivado',
    ];
}

function csf_evento_estado_valido(string $estado): string
{
    $estado = strtoupper(trim($estado));

    return array_key_exists($estado, csf_evento_estados()) ? $estado : 'PUBLICADO';
}

/**
 * Condicion SQL de "promocion vigente".
 *
 * Un evento deja de estar destacado cuando caduca su promocion, pero la fila
 * conserva promocionado/promocionado_at como registro de que se pago. Por eso la
 * vigencia se calcula siempre con esta condicion y no leyendo `promocionado` a
 * secas.
 */
function csf_evento_sql_promocion_vigente(string $alias = 'e'): string
{
    return "({$alias}.promocionado = 1 AND ({$alias}.promocion_expira_at IS NULL OR {$alias}.promocion_expira_at > NOW()))";
}

/**
 * Version PHP de la condicion anterior, para una fila ya cargada.
 */
function csf_evento_promocion_vigente(array $evento): bool
{
    if (empty($evento['promocionado'])) {
        return false;
    }

    $expira = (string) ($evento['promocion_expira_at'] ?? '');
    if ($expira === '') {
        return true;
    }

    return strtotime($expira) > time();
}

/**
 * Slug unico en toda la plataforma, derivado del titulo y la fecha.
 *
 * Se le anade el ano-mes porque un artista repite titulo cada temporada
 * ("Recital de otono") y el slug es la URL publica del evento.
 */
function csf_evento_slug_unico(PDO $pdo, string $titulo, string $fecha, int $excluirId = 0): string
{
    $base = slugify($titulo);
    $sufijoFecha = '';
    $timestamp = strtotime($fecha);
    if ($timestamp !== false) {
        $sufijoFecha = '-' . date('Y-m', $timestamp);
    }

    $candidato = mb_substr($base . $sufijoFecha, 0, 200);
    $slug = $candidato;
    $intento = 2;

    $statement = $pdo->prepare('SELECT COUNT(*) FROM eventos WHERE slug = :slug AND id != :id');
    while (true) {
        $statement->execute(['slug' => $slug, 'id' => max(0, $excluirId)]);
        if ((int) $statement->fetchColumn() === 0) {
            return $slug;
        }
        $slug = mb_substr($candidato, 0, 195) . '-' . $intento;
        $intento++;
        if ($intento > 200) {
            return mb_substr($candidato, 0, 190) . '-' . bin2hex(random_bytes(4));
        }
    }
}

/**
 * Valida los datos de un evento que llegan por formulario.
 *
 * @return string[] Lista de errores; vacia si todo es correcto.
 */
function csf_evento_validar(array $datos): array
{
    $errores = [];

    $titulo = clean_text((string) ($datos['titulo'] ?? ''));
    if ($titulo === '') {
        $errores[] = 'El evento necesita un título.';
    } elseif (mb_strlen($titulo) > CSF_EVENTO_MAX_TITULO) {
        $errores[] = 'El título no puede superar los ' . CSF_EVENTO_MAX_TITULO . ' caracteres.';
    }

    $fecha = clean_text((string) ($datos['fecha'] ?? ''));
    if ($fecha === '') {
        $errores[] = 'Indica la fecha del evento.';
    } elseif (!csf_evento_fecha_valida($fecha)) {
        $errores[] = 'La fecha del evento no es válida.';
    }

    // No basta con comprobar el formato: "25:00" encaja en el patron pero no es
    // una hora. Se valida con la misma funcion que normaliza antes de guardar,
    // asi que lo que valida y lo que se escribe no pueden discrepar.
    $hora = clean_text((string) ($datos['hora'] ?? ''));
    if ($hora !== '' && csf_evento_normalizar_hora($hora) === null) {
        $errores[] = 'La hora debe tener el formato HH:MM (entre 00:00 y 23:59).';
    }

    $fechaFin = clean_text((string) ($datos['fecha_fin'] ?? ''));
    if ($fechaFin !== '') {
        if (!csf_evento_fecha_valida($fechaFin)) {
            $errores[] = 'La fecha de fin no es válida.';
        } elseif ($fecha !== '' && csf_evento_fecha_valida($fecha) && $fechaFin < $fecha) {
            $errores[] = 'La fecha de fin no puede ser anterior a la de inicio.';
        }
    }

    if (mb_strlen((string) ($datos['descripcion'] ?? '')) > CSF_EVENTO_MAX_DESCRIPCION) {
        $errores[] = 'La descripción no puede superar los ' . CSF_EVENTO_MAX_DESCRIPCION . ' caracteres.';
    }

    foreach (['enlace_url' => 'El enlace externo', 'video_url' => 'El enlace del vídeo'] as $campo => $etiqueta) {
        $url = trim((string) ($datos[$campo] ?? ''));
        if ($url !== '' && csf_evento_url_valida($url) === null) {
            $errores[] = $etiqueta . ' no es una dirección web válida (debe empezar por http:// o https://).';
        }
    }

    return $errores;
}

function csf_evento_fecha_valida(string $fecha): bool
{
    $partes = explode('-', $fecha);
    if (count($partes) !== 3) {
        return false;
    }

    [$ano, $mes, $dia] = array_map('intval', $partes);

    return $ano >= 1900 && $ano <= 2999 && checkdate($mes, $dia, $ano);
}

/**
 * Devuelve la URL si es http/https, o null. Evita que un `javascript:` acabe
 * saliendo en un href del microsite.
 */
function csf_evento_url_valida(string $url): ?string
{
    $url = trim($url);
    if ($url === '' || mb_strlen($url) > 255) {
        return null;
    }

    $esquema = strtolower((string) parse_url($url, PHP_URL_SCHEME));
    if (!in_array($esquema, ['http', 'https'], true)) {
        return null;
    }

    return filter_var($url, FILTER_VALIDATE_URL) !== false ? $url : null;
}

/**
 * Guarda el cartel del evento.
 *
 * Reutiliza el directorio `curriculum-images/`, que es uno de los dos que
 * csf_normalize_media_file() admite. Asi el cartel se sirve por media.php sin
 * tocar el guardian de medios ni abrir una ruta nueva.
 *
 * @param string[] $errores
 */
function csf_evento_guardar_cartel(?array $file, array &$errores): ?string
{
    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        $errores[] = 'No se pudo subir el cartel del evento. Vuelve a intentarlo.';
        return null;
    }

    if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
        $errores[] = 'El cartel del evento debe pesar menos de 5 MB.';
        return null;
    }

    $info = @getimagesize((string) ($file['tmp_name'] ?? ''));
    if (!$info || empty($info['mime'])) {
        $errores[] = 'El cartel debe ser una imagen válida.';
        return null;
    }

    $extensiones = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $mime = (string) $info['mime'];
    if (!isset($extensiones[$mime])) {
        $errores[] = 'El cartel debe estar en formato JPG, PNG o WebP.';
        return null;
    }

    $nombre = 'evento-' . gmdate('YmdHis') . '-' . bin2hex(random_bytes(6)) . '.' . $extensiones[$mime];
    $destino = MEMBER_CV_IMAGES_DIR . '/' . $nombre;
    $temporal = (string) ($file['tmp_name'] ?? '');
    $movido = is_uploaded_file($temporal)
        ? move_uploaded_file($temporal, $destino)
        : rename($temporal, $destino);

    if (!$movido) {
        $errores[] = 'No se pudo guardar el cartel del evento.';
        return null;
    }

    return csf_media_url('curriculum-images/' . $nombre);
}

/**
 * Crea o actualiza un evento. Gratis siempre: aqui no se tocan puntos.
 *
 * $eventoId a null crea; con id actualiza, comprobando antes que el evento
 * pertenece a $miembroId (no basta con que el id exista).
 *
 * @return int Id del evento guardado.
 */
function csf_evento_guardar(
    PDO $pdo,
    int $miembroId,
    int $usuarioId,
    ?int $eventoId,
    array $datos
): int {
    $titulo = mb_substr(clean_text((string) ($datos['titulo'] ?? '')), 0, CSF_EVENTO_MAX_TITULO);
    $fecha = clean_text((string) ($datos['fecha'] ?? ''));
    $hora = clean_text((string) ($datos['hora'] ?? ''));
    $fechaFin = clean_text((string) ($datos['fecha_fin'] ?? ''));

    $geo = csf_geo_resolver(
        $pdo,
        (string) ($datos['provincia'] ?? ''),
        (string) ($datos['municipio'] ?? '')
    );

    $campos = [
        'titulo' => $titulo,
        'descripcion' => mb_substr(clean_html_text((string) ($datos['descripcion'] ?? '')), 0, CSF_EVENTO_MAX_DESCRIPCION),
        'imagen_path' => mb_substr(clean_text((string) ($datos['imagen_path'] ?? '')), 0, 255) ?: null,
        'video_url' => csf_evento_url_valida((string) ($datos['video_url'] ?? '')),
        'fecha' => $fecha,
        'hora' => $hora !== '' ? csf_evento_normalizar_hora($hora) : null,
        'fecha_fin' => $fechaFin !== '' ? $fechaFin : null,
        'lugar' => mb_substr(clean_text((string) ($datos['lugar'] ?? '')), 0, 190) ?: null,
        'direccion' => mb_substr(clean_text((string) ($datos['direccion'] ?? '')), 0, 255) ?: null,
        'provincia_id' => $geo['provincia_id'],
        'municipio_id' => $geo['municipio_id'],
        'provincia_texto' => $geo['provincia_texto'] !== '' ? $geo['provincia_texto'] : null,
        'municipio_texto' => $geo['municipio_texto'] !== '' ? $geo['municipio_texto'] : null,
        'enlace_url' => csf_evento_url_valida((string) ($datos['enlace_url'] ?? '')),
        'estado' => csf_evento_estado_valido((string) ($datos['estado'] ?? 'PUBLICADO')),
    ];

    if ($eventoId !== null && $eventoId > 0) {
        $existente = csf_evento_obtener_de_miembro($pdo, $eventoId, $miembroId);
        if ($existente === null) {
            throw new RuntimeException('El evento no existe o no te pertenece.');
        }

        // El slug es la URL publica: solo se regenera si cambia el titulo o la
        // fecha, para no romper enlaces ya compartidos por el artista.
        $slug = (string) $existente['slug'];
        if ($titulo !== (string) $existente['titulo'] || $fecha !== (string) $existente['fecha']) {
            $slug = csf_evento_slug_unico($pdo, $titulo, $fecha, $eventoId);
        }

        $asignaciones = [];
        foreach (array_keys($campos) as $campo) {
            $asignaciones[] = "`{$campo}` = :{$campo}";
        }
        $asignaciones[] = '`slug` = :slug';

        $statement = $pdo->prepare(
            'UPDATE eventos SET ' . implode(', ', $asignaciones) . ' WHERE id = :id AND miembro_id = :miembro_id'
        );
        $statement->execute($campos + ['slug' => $slug, 'id' => $eventoId, 'miembro_id' => $miembroId]);

        csf_log_actividad($pdo, $usuarioId, 'evento', $eventoId, 'editado', [
            'titulo' => $titulo,
            'fecha' => $fecha,
        ]);

        return $eventoId;
    }

    $campos['slug'] = csf_evento_slug_unico($pdo, $titulo, $fecha);
    $campos['miembro_id'] = $miembroId;
    $campos['usuario_id'] = $usuarioId;

    $columnas = array_keys($campos);
    $marcadores = array_map(static fn (string $campo): string => ':' . $campo, $columnas);

    $statement = $pdo->prepare(
        'INSERT INTO eventos (`' . implode('`, `', $columnas) . '`) VALUES (' . implode(', ', $marcadores) . ')'
    );
    $statement->execute($campos);

    $nuevoId = (int) $pdo->lastInsertId();

    csf_log_actividad($pdo, $usuarioId, 'evento', $nuevoId, 'creado', [
        'titulo' => $titulo,
        'fecha' => $fecha,
    ]);

    return $nuevoId;
}

/**
 * "9:30" -> "09:30:00". La columna es TIME y no acepta "9:30" en modo estricto.
 */
function csf_evento_normalizar_hora(string $hora): ?string
{
    if (preg_match('/^(\d{1,2}):(\d{2})/', trim($hora), $coincidencias) !== 1) {
        return null;
    }

    $horas = (int) $coincidencias[1];
    $minutos = (int) $coincidencias[2];
    if ($horas > 23 || $minutos > 59) {
        return null;
    }

    return sprintf('%02d:%02d:00', $horas, $minutos);
}

/**
 * SELECT comun de eventos con los datos del artista organizador.
 */
function csf_evento_select_sql(): string
{
    return 'SELECT
                e.*,
                ' . csf_evento_sql_promocion_vigente('e') . ' AS destacado,
                m.nombre_publico AS artista_nombre,
                m.slug AS artista_slug,
                m.foto_principal_path AS artista_foto,
                tm.slug AS artista_tipo
            FROM eventos e
            INNER JOIN miembros m ON m.id = e.miembro_id
            INNER JOIN usuarios u ON u.id = e.usuario_id
            LEFT JOIN tipos_miembro tm ON tm.id = m.tipo_miembro_id';
}

/**
 * @return array<string, mixed>|null
 */
function csf_evento_obtener(PDO $pdo, int $eventoId): ?array
{
    $statement = $pdo->prepare(csf_evento_select_sql() . ' WHERE e.id = :id AND e.deleted_at IS NULL');
    $statement->execute(['id' => $eventoId]);

    return $statement->fetch(PDO::FETCH_ASSOC) ?: null;
}

/**
 * Evento que ademas pertenece al miembro indicado. Es la comprobacion de
 * propiedad que deben usar todas las acciones del panel.
 *
 * @return array<string, mixed>|null
 */
function csf_evento_obtener_de_miembro(PDO $pdo, int $eventoId, int $miembroId): ?array
{
    $statement = $pdo->prepare(
        csf_evento_select_sql() . ' WHERE e.id = :id AND e.miembro_id = :miembro_id AND e.deleted_at IS NULL'
    );
    $statement->execute(['id' => $eventoId, 'miembro_id' => $miembroId]);

    return $statement->fetch(PDO::FETCH_ASSOC) ?: null;
}

/**
 * @return array<string, mixed>|null
 */
function csf_evento_por_slug(PDO $pdo, string $slug): ?array
{
    $slug = slugify(clean_text($slug));
    $statement = $pdo->prepare(
        csf_evento_select_sql() . ' WHERE e.slug = :slug AND e.deleted_at IS NULL AND u.estado = "ACTIVO"'
    );
    $statement->execute(['slug' => $slug]);

    return $statement->fetch(PDO::FETCH_ASSOC) ?: null;
}

/**
 * Eventos de un miembro.
 *
 * $ambito: 'proximos' (hoy en adelante), 'pasados' (historico) o 'todos'.
 * Los proximos van de mas cercano a mas lejano; los pasados, al reves.
 *
 * @return array<int, array<string, mixed>>
 */
function csf_evento_listar_miembro(PDO $pdo, int $miembroId, string $ambito = 'todos', bool $soloPublicados = false): array
{
    $condiciones = ['e.miembro_id = :miembro_id', 'e.deleted_at IS NULL'];
    $parametros = ['miembro_id' => $miembroId];

    if ($soloPublicados) {
        $condiciones[] = 'e.estado = "PUBLICADO"';
    }

    $orden = 'e.fecha ASC, e.hora ASC';
    if ($ambito === 'proximos') {
        $condiciones[] = 'COALESCE(e.fecha_fin, e.fecha) >= CURDATE()';
    } elseif ($ambito === 'pasados') {
        $condiciones[] = 'COALESCE(e.fecha_fin, e.fecha) < CURDATE()';
        $orden = 'e.fecha DESC, e.hora DESC';
    }

    $statement = $pdo->prepare(
        csf_evento_select_sql() . ' WHERE ' . implode(' AND ', $condiciones) . ' ORDER BY ' . $orden . ' LIMIT 200'
    );
    $statement->execute($parametros);

    return $statement->fetchAll(PDO::FETCH_ASSOC);
}

function csf_evento_contar_proximos(PDO $pdo, int $miembroId): int
{
    $statement = $pdo->prepare(
        'SELECT COUNT(*) FROM eventos
          WHERE miembro_id = :miembro_id
            AND deleted_at IS NULL
            AND estado = "PUBLICADO"
            AND COALESCE(fecha_fin, fecha) >= CURDATE()'
    );
    $statement->execute(['miembro_id' => $miembroId]);

    return (int) $statement->fetchColumn();
}

/**
 * Agenda publica.
 *
 * Solo eventos publicados, no borrados, de cuentas activas y que no hayan
 * pasado. Orden: fecha ASC, y DENTRO de cada dia los promocionados primero.
 *
 * Se resuelve asi a proposito. La agenda es cronologica y esa promesa no se
 * rompe: un evento del dia 20 nunca adelanta a uno del dia 5 por mucho que se
 * pague. La visibilidad comprada se cobra dentro del dia y en la franja de
 * destacados de csf_evento_destacados().
 *
 * Filtros admitidos: provincia_id, municipio_id, disciplina (slug), buscar,
 * desde (fecha), limite, offset.
 *
 * @return array<int, array<string, mixed>>
 */
function csf_evento_agenda(PDO $pdo, array $filtros = []): array
{
    [$condiciones, $parametros] = csf_evento_condiciones_agenda($pdo, $filtros);

    $limite = max(1, min(200, (int) ($filtros['limite'] ?? 60)));
    $offset = max(0, (int) ($filtros['offset'] ?? 0));

    $statement = $pdo->prepare(
        csf_evento_select_sql()
        . ' WHERE ' . implode(' AND ', $condiciones)
        . ' ORDER BY e.fecha ASC, ' . csf_evento_sql_promocion_vigente('e') . ' DESC, e.hora ASC, e.id ASC'
        . ' LIMIT ' . $limite . ' OFFSET ' . $offset
    );
    $statement->execute($parametros);

    return $statement->fetchAll(PDO::FETCH_ASSOC);
}

function csf_evento_agenda_total(PDO $pdo, array $filtros = []): int
{
    [$condiciones, $parametros] = csf_evento_condiciones_agenda($pdo, $filtros);

    $statement = $pdo->prepare(
        'SELECT COUNT(*)
         FROM eventos e
         INNER JOIN miembros m ON m.id = e.miembro_id
         INNER JOIN usuarios u ON u.id = e.usuario_id
         WHERE ' . implode(' AND ', $condiciones)
    );
    $statement->execute($parametros);

    return (int) $statement->fetchColumn();
}

/**
 * Franja de destacados: lo que compra realmente la promocion.
 *
 * @return array<int, array<string, mixed>>
 */
function csf_evento_destacados(PDO $pdo, array $filtros = [], int $limite = 3): array
{
    [$condiciones, $parametros] = csf_evento_condiciones_agenda($pdo, $filtros);
    $condiciones[] = csf_evento_sql_promocion_vigente('e');

    $statement = $pdo->prepare(
        csf_evento_select_sql()
        . ' WHERE ' . implode(' AND ', $condiciones)
        . ' ORDER BY e.fecha ASC, e.hora ASC LIMIT ' . max(1, min(12, $limite))
    );
    $statement->execute($parametros);

    return $statement->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Condiciones y parametros compartidos por la agenda, el contador y los
 * destacados, para que los tres filtren exactamente igual.
 *
 * @return array{0: string[], 1: array<string, mixed>}
 */
function csf_evento_condiciones_agenda(PDO $pdo, array $filtros): array
{
    $condiciones = [
        'e.deleted_at IS NULL',
        'e.estado = "PUBLICADO"',
        'u.estado = "ACTIVO"',
    ];
    $parametros = [];

    $desde = clean_text((string) ($filtros['desde'] ?? ''));
    if ($desde !== '' && csf_evento_fecha_valida($desde)) {
        $condiciones[] = 'COALESCE(e.fecha_fin, e.fecha) >= :desde';
        $parametros['desde'] = $desde;
    } else {
        // Por defecto la agenda solo mira hacia delante: los eventos pasados
        // quedan en el perfil del artista como historico.
        $condiciones[] = 'COALESCE(e.fecha_fin, e.fecha) >= CURDATE()';
    }

    $hasta = clean_text((string) ($filtros['hasta'] ?? ''));
    if ($hasta !== '' && csf_evento_fecha_valida($hasta)) {
        $condiciones[] = 'e.fecha <= :hasta';
        $parametros['hasta'] = $hasta;
    }

    $provinciaId = (int) ($filtros['provincia_id'] ?? 0);
    if ($provinciaId > 0) {
        $condiciones[] = 'e.provincia_id = :provincia_id';
        $parametros['provincia_id'] = $provinciaId;
    }

    $municipioId = (int) ($filtros['municipio_id'] ?? 0);
    if ($municipioId > 0) {
        $condiciones[] = 'e.municipio_id = :municipio_id';
        $parametros['municipio_id'] = $municipioId;
    }

    $miembroId = (int) ($filtros['miembro_id'] ?? 0);
    if ($miembroId > 0) {
        $condiciones[] = 'e.miembro_id = :miembro_id';
        $parametros['miembro_id'] = $miembroId;
    }

    $excluirId = (int) ($filtros['excluir_id'] ?? 0);
    if ($excluirId > 0) {
        $condiciones[] = 'e.id != :excluir_id';
        $parametros['excluir_id'] = $excluirId;
    }

    // La disciplina vive en el artista, no en el evento: un evento de un bailaor
    // es un evento de baile. Solo se aplica si la tabla existe en este entorno.
    $disciplina = slugify(clean_text((string) ($filtros['disciplina'] ?? '')));
    if ($disciplina !== '' && $disciplina !== 'todos' && $disciplina !== 'contenido'
        && csf_db_table_exists($pdo, 'miembro_disciplinas')) {
        $condiciones[] = 'EXISTS (
            SELECT 1 FROM miembro_disciplinas md
            INNER JOIN disciplinas d ON d.id = md.disciplina_id
            WHERE md.miembro_id = e.miembro_id AND d.slug = :disciplina AND d.estado = "ACTIVA"
        )';
        $parametros['disciplina'] = $disciplina;
    }

    $buscar = clean_text((string) ($filtros['buscar'] ?? ''));
    if ($buscar !== '') {
        $condiciones[] = '(e.titulo LIKE :buscar OR e.descripcion LIKE :buscar OR e.lugar LIKE :buscar OR m.nombre_publico LIKE :buscar)';
        $parametros['buscar'] = '%' . $buscar . '%';
    }

    return [$condiciones, $parametros];
}

/**
 * Baja logica. La fila permanece con `deleted_at` para poder auditarla.
 */
function csf_evento_eliminar(PDO $pdo, int $eventoId, int $miembroId, int $usuarioId): bool
{
    $statement = $pdo->prepare(
        'UPDATE eventos SET deleted_at = NOW() WHERE id = :id AND miembro_id = :miembro_id AND deleted_at IS NULL'
    );
    $statement->execute(['id' => $eventoId, 'miembro_id' => $miembroId]);

    $eliminado = $statement->rowCount() > 0;
    if ($eliminado) {
        csf_log_actividad($pdo, $usuarioId, 'evento', $eventoId, 'eliminado');
    }

    return $eliminado;
}

/**
 * Promociona un evento cobrando los puntos.
 *
 * El coste NO llega por parametro: se lee de csf_puntos_coste(), asi que da
 * igual lo que mande el formulario. Todo ocurre en una sola transaccion: si
 * falla el descuento no se marca el evento, y si falla el marcado se devuelven
 * los puntos por el rollback.
 *
 * @return array{evento_id:int, puntos:int, saldo:int, expira:string}
 */
function csf_evento_promocionar(PDO $pdo, int $eventoId, int $miembroId, int $usuarioId): array
{
    $coste = csf_puntos_coste('promocion_evento');

    $pdo->beginTransaction();
    try {
        $evento = csf_evento_obtener_de_miembro($pdo, $eventoId, $miembroId);
        if ($evento === null) {
            throw new RuntimeException('El evento no existe o no te pertenece.');
        }

        if (csf_evento_promocion_vigente($evento)) {
            throw new RuntimeException('Este evento ya está promocionado.');
        }

        if ((string) $evento['estado'] !== 'PUBLICADO') {
            throw new RuntimeException('Solo se pueden promocionar eventos publicados.');
        }

        $movimiento = csf_puntos_registrar(
            $pdo,
            $usuarioId,
            -$coste,
            'PROMOCION',
            'Promoción evento #' . $eventoId . ' · ' . mb_substr((string) $evento['titulo'], 0, 100),
            [
                'referencia_tipo' => 'evento',
                'referencia_id' => $eventoId,
            ]
        );

        // La promocion dura CSF_PUNTOS_PROMOCION_DIAS o hasta que pase el
        // evento, lo que ocurra antes: destacar algo que ya paso no aporta nada.
        $expira = date('Y-m-d H:i:s', strtotime('+' . CSF_PUNTOS_PROMOCION_DIAS . ' days'));
        $finEvento = (string) ($evento['fecha_fin'] ?: $evento['fecha']) . ' 23:59:59';
        if (strtotime($finEvento) < strtotime($expira)) {
            $expira = $finEvento;
        }

        $actualizar = $pdo->prepare(
            'UPDATE eventos
                SET promocionado = 1, promocionado_at = NOW(), promocion_expira_at = :expira
              WHERE id = :id AND miembro_id = :miembro_id'
        );
        $actualizar->execute(['expira' => $expira, 'id' => $eventoId, 'miembro_id' => $miembroId]);

        $pdo->commit();

        csf_log_actividad($pdo, $usuarioId, 'evento', $eventoId, 'promocionado', [
            'puntos' => $coste,
            'movimiento_id' => $movimiento['movimiento_id'],
            'expira' => $expira,
        ]);

        return [
            'evento_id' => $eventoId,
            'puntos' => $coste,
            'saldo' => $movimiento['saldo'],
            'expira' => $expira,
        ];
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}

/**
 * Suma una visita a la pagina publica del evento.
 *
 * No es analitica seria (no deduplica): es un contador para que el artista vea
 * movimiento y para ordenar recomendaciones mas adelante.
 */
function csf_evento_registrar_visita(PDO $pdo, int $eventoId): void
{
    try {
        $pdo->prepare('UPDATE eventos SET vistas = vistas + 1 WHERE id = :id')->execute(['id' => $eventoId]);
    } catch (Throwable $exception) {
        error_log('[eventos] visita no registrada: ' . $exception->getMessage());
    }
}
