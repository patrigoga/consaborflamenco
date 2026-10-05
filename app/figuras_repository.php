<?php
declare(strict_types=1);

/*
 * Figuras del flamenco: fichas editoriales de artistas (vivos o no) que crea
 * solo administracion. No son miembros. Ver docs/23_FIGURAS_FLAMENCO.md.
 *
 * Todas las funciones degradan sin base de datos: las lecturas devuelven
 * vacio y las escrituras lanzan una excepcion con mensaje para el panel.
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/site_content_repository.php';

function figuras_db(): ?PDO
{
    try {
        return auth_database();
    } catch (Throwable $exception) {
        error_log('[CSF figuras] ' . $exception->getMessage());
        return null;
    }
}

function figuras_db_requerida(): PDO
{
    $pdo = figuras_db();
    if (!$pdo) {
        throw new RuntimeException('La base de datos no esta disponible.');
    }

    return $pdo;
}

/** @return array<string, string> estado => etiqueta */
function figuras_estados(): array
{
    return [
        'BORRADOR' => 'Borrador',
        'REVISION' => 'En revision',
        'PUBLICADO' => 'Publicada',
        'ARCHIVADO' => 'Archivada',
    ];
}

/**
 * Campos de texto largo de la ficha, con la etiqueta del formulario.
 *
 * @return array<string, string>
 */
function figuras_campos_texto(): array
{
    return [
        'trayectoria' => 'Trayectoria',
        'aportaciones' => 'Aportaciones y estilo',
        'obra_destacada' => 'Obra destacada',
        'premios' => 'Premios y reconocimientos',
        'anecdotas' => 'Anecdotas y citas',
    ];
}

/** @return list<array<string, mixed>> */
function figuras_disciplinas_disponibles(): array
{
    $pdo = figuras_db();
    if (!$pdo) {
        return [];
    }

    try {
        return $pdo->query("SELECT id, slug, nombre FROM disciplinas WHERE estado = 'ACTIVA' ORDER BY nombre")->fetchAll();
    } catch (Throwable $exception) {
        error_log('[CSF figuras] ' . $exception->getMessage());
        return [];
    }
}

/** @return list<array<string, mixed>> */
function figuras_provincias_disponibles(): array
{
    $pdo = figuras_db();
    if (!$pdo) {
        return [];
    }

    try {
        return $pdo->query('SELECT id, nombre FROM provincias ORDER BY nombre')->fetchAll();
    } catch (Throwable $exception) {
        error_log('[CSF figuras] ' . $exception->getMessage());
        return [];
    }
}

/**
 * Listado para el panel, con lo que le falta a cada ficha.
 *
 * @param array{estado?: string, q?: string} $filtros
 * @return list<array<string, mixed>>
 */
function figuras_admin_listado(array $filtros = []): array
{
    $pdo = figuras_db();
    if (!$pdo) {
        return [];
    }

    $where = [];
    $params = [];
    $estado = (string) ($filtros['estado'] ?? '');
    if (isset(figuras_estados()[$estado])) {
        $where[] = 'f.estado = :estado';
        $params['estado'] = $estado;
    }
    $busqueda = clean_text((string) ($filtros['q'] ?? ''));
    if ($busqueda !== '') {
        $where[] = '(f.nombre_artistico LIKE :q1 OR f.nombre_real LIKE :q2 OR f.otros_nombres LIKE :q3)';
        $like = '%' . figuras_escapar_like($busqueda) . '%';
        $params['q1'] = $like;
        $params['q2'] = $like;
        $params['q3'] = $like;
    }

    try {
        $statement = $pdo->prepare(
            "SELECT f.id, f.slug, f.nombre_artistico, f.nombre_real, f.rol, f.estado, f.destacada, f.vistas,
                    f.nacimiento_anio, f.fallecimiento_anio, f.imagen_path, f.por_verificar, f.updated_at,
                    (SELECT COUNT(*) FROM figura_fuentes ff WHERE ff.figura_id = f.id) AS total_fuentes
             FROM figuras_flamenco f"
            . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
            . " ORDER BY FIELD(f.estado, 'REVISION', 'BORRADOR', 'PUBLICADO', 'ARCHIVADO'), f.nombre_artistico
             LIMIT 300"
        );
        $statement->execute($params);

        return $statement->fetchAll();
    } catch (Throwable $exception) {
        error_log('[CSF figuras] ' . $exception->getMessage());
        return [];
    }
}

/** @return array<string, int> estado => total, mas la clave TOTAL */
function figuras_admin_recuento(): array
{
    $recuento = array_fill_keys(array_keys(figuras_estados()), 0);
    $recuento['TOTAL'] = 0;
    $pdo = figuras_db();
    if (!$pdo) {
        return $recuento;
    }

    try {
        foreach ($pdo->query('SELECT estado, COUNT(*) AS total FROM figuras_flamenco GROUP BY estado') as $fila) {
            $recuento[(string) $fila['estado']] = (int) $fila['total'];
            $recuento['TOTAL'] += (int) $fila['total'];
        }
    } catch (Throwable $exception) {
        error_log('[CSF figuras] ' . $exception->getMessage());
    }

    return $recuento;
}

/** Ficha completa con sus disciplinas (ids) y fuentes, o null si no existe. */
function figuras_por_id(int $id): ?array
{
    $pdo = figuras_db();
    if (!$pdo || $id <= 0) {
        return null;
    }

    try {
        $statement = $pdo->prepare('SELECT * FROM figuras_flamenco WHERE id = :id');
        $statement->execute(['id' => $id]);
        $figura = $statement->fetch();
        if (!$figura) {
            return null;
        }

        $statement = $pdo->prepare('SELECT disciplina_id FROM figura_disciplinas WHERE figura_id = :id');
        $statement->execute(['id' => $id]);
        $figura['disciplinas'] = array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN));

        $statement = $pdo->prepare('SELECT id, fuente, detalle, url, created_at FROM figura_fuentes WHERE figura_id = :id ORDER BY id');
        $statement->execute(['id' => $id]);
        $figura['fuentes'] = $statement->fetchAll();

        return $figura;
    } catch (Throwable $exception) {
        error_log('[CSF figuras] ' . $exception->getMessage());
        return null;
    }
}

function figuras_escapar_like(string $valor): string
{
    return addcslashes($valor, '\\%_');
}

/**
 * Fichas que podrian ser la misma persona: comparten alguna palabra
 * significativa en el nombre artistico, el real o los alias.
 *
 * Se usa al crear, para no duplicar una figura que ya existe con otro nombre
 * ("Sabicas" / "Agustin Castellon"). Es deliberadamente amplio: prefiere
 * avisar de mas y que decida la persona.
 *
 * @return list<array<string, mixed>>
 */
function figuras_posibles_duplicados(string $nombreArtistico, string $nombreReal = '', string $otrosNombres = '', int $ignorarId = 0): array
{
    $pdo = figuras_db();
    if (!$pdo) {
        return [];
    }

    // Palabras que no distinguen a nadie en el flamenco.
    $vacias = ['nino', 'nina', 'hijo', 'hija', 'viejo', 'vieja', 'chico', 'chica', 'jose', 'maria', 'juan', 'manuel', 'antonio', 'francisco'];
    $palabras = [];
    foreach (preg_split('/[^\p{L}]+/u', $nombreArtistico . ' ' . $nombreReal . ' ' . $otrosNombres) ?: [] as $palabra) {
        $normalizada = slugify($palabra);
        if (mb_strlen($palabra) >= 4 && !in_array($normalizada, $vacias, true)) {
            $palabras[$normalizada] = $palabra;
        }
    }
    if (!$palabras) {
        return [];
    }

    $condiciones = [];
    $params = ['ignorar' => $ignorarId];
    $indice = 0;
    foreach (array_slice(array_values($palabras), 0, 8) as $palabra) {
        $like = '%' . figuras_escapar_like($palabra) . '%';
        foreach (['nombre_artistico', 'nombre_real', 'otros_nombres'] as $campo) {
            $clave = 'p' . $indice++;
            $condiciones[] = "{$campo} LIKE :{$clave}";
            $params[$clave] = $like;
        }
    }

    try {
        $statement = $pdo->prepare(
            'SELECT id, slug, nombre_artistico, nombre_real, nacimiento_anio, estado
             FROM figuras_flamenco
             WHERE id <> :ignorar AND (' . implode(' OR ', $condiciones) . ')
             ORDER BY nombre_artistico LIMIT 10'
        );
        $statement->execute($params);

        return $statement->fetchAll();
    } catch (Throwable $exception) {
        error_log('[CSF figuras] ' . $exception->getMessage());
        return [];
    }
}

function figuras_slug_unico(PDO $pdo, string $base, int $ignorarId = 0): string
{
    $base = $base !== '' ? $base : 'figura';
    $slug = $base;
    $contador = 2;
    $statement = $pdo->prepare('SELECT COUNT(*) FROM figuras_flamenco WHERE slug = :slug AND id <> :id');
    while (true) {
        $statement->execute(['slug' => $slug, 'id' => $ignorarId]);
        if ((int) $statement->fetchColumn() === 0) {
            return $slug;
        }
        $slug = $base . '-' . $contador++;
    }
}

/**
 * Valida un par ano / fecha completa. La fecha es opcional; si viene, manda
 * sobre el ano. Devuelve [ano|null, fecha|null].
 *
 * @param list<string> $errores
 * @return array{0: ?int, 1: ?string}
 */
function figuras_normalizar_fecha(string $anio, string $fecha, string $etiqueta, array &$errores): array
{
    $anio = trim($anio);
    $fecha = trim($fecha);
    $anioFinal = null;
    $fechaFinal = null;

    if ($fecha !== '') {
        $partes = date_parse_from_format('Y-m-d', $fecha);
        if ($partes['error_count'] > 0 || $partes['warning_count'] > 0 || !checkdate((int) $partes['month'], (int) $partes['day'], (int) $partes['year'])) {
            $errores[] = "La fecha de {$etiqueta} no es valida.";
        } else {
            $fechaFinal = $fecha;
            $anioFinal = (int) $partes['year'];
        }
    }

    if ($anio !== '') {
        if (!ctype_digit($anio) || (int) $anio < 1700 || (int) $anio > (int) date('Y')) {
            $errores[] = "El ano de {$etiqueta} no es valido.";
        } elseif ($fechaFinal !== null && (int) $anio !== $anioFinal) {
            $errores[] = "El ano y la fecha de {$etiqueta} no coinciden.";
        } else {
            $anioFinal = (int) $anio;
        }
    }

    return [$anioFinal, $fechaFinal];
}

/**
 * Crea o actualiza una ficha desde el formulario del panel.
 *
 * Al crear, si hay fichas con nombre parecido y no se ha marcado
 * `confirmar_distinta`, no guarda y avisa: es la barrera contra duplicados.
 *
 * @return int id de la ficha guardada
 */
function figuras_guardar(array $input, array $files, array $user): int
{
    $pdo = figuras_db_requerida();

    $id = max(0, (int) ($input['figura_id'] ?? 0));
    $existente = $id > 0 ? figuras_por_id($id) : null;
    if ($id > 0 && !$existente) {
        throw new RuntimeException('No se encontro la ficha indicada.');
    }

    $errores = [];
    $nombreArtistico = clean_text((string) ($input['nombre_artistico'] ?? ''));
    $nombreReal = clean_text((string) ($input['nombre_real'] ?? ''));
    $otrosNombres = clean_text((string) ($input['otros_nombres'] ?? ''));
    $rol = clean_text((string) ($input['rol'] ?? ''));
    $resumen = clean_text((string) ($input['resumen'] ?? ''));
    $imagenCredito = clean_text((string) ($input['imagen_credito'] ?? ''));
    $porVerificar = clean_text((string) ($input['por_verificar'] ?? ''));
    $nacimientoLugar = clean_text((string) ($input['nacimiento_lugar'] ?? ''));
    $fallecimientoLugar = clean_text((string) ($input['fallecimiento_lugar'] ?? ''));
    $estado = (string) ($input['estado'] ?? 'BORRADOR');
    $provinciaId = max(0, (int) ($input['nacimiento_provincia_id'] ?? 0));

    if (mb_strlen($nombreArtistico) < 2) {
        $errores[] = 'El nombre artistico es obligatorio.';
    }
    foreach ([
        'nombre artistico' => [$nombreArtistico, 160],
        'nombre real' => [$nombreReal, 200],
        'otros nombres' => [$otrosNombres, 255],
        'rol' => [$rol, 120],
        'resumen' => [$resumen, 320],
        'credito de la imagen' => [$imagenCredito, 255],
        'por verificar' => [$porVerificar, 500],
        'lugar de nacimiento' => [$nacimientoLugar, 160],
        'lugar de fallecimiento' => [$fallecimientoLugar, 160],
    ] as $campo => [$valor, $maximo]) {
        if (mb_strlen($valor) > $maximo) {
            $errores[] = "El campo {$campo} admite como maximo {$maximo} caracteres.";
        }
    }
    if (!isset(figuras_estados()[$estado])) {
        $estado = 'BORRADOR';
    }

    [$nacAnio, $nacFecha] = figuras_normalizar_fecha((string) ($input['nacimiento_anio'] ?? ''), (string) ($input['nacimiento_fecha'] ?? ''), 'nacimiento', $errores);
    [$falAnio, $falFecha] = figuras_normalizar_fecha((string) ($input['fallecimiento_anio'] ?? ''), (string) ($input['fallecimiento_fecha'] ?? ''), 'fallecimiento', $errores);
    if ($nacAnio !== null && $falAnio !== null && $falAnio < $nacAnio) {
        $errores[] = 'El fallecimiento no puede ser anterior al nacimiento.';
    }

    if ($provinciaId > 0) {
        $statement = $pdo->prepare('SELECT COUNT(*) FROM provincias WHERE id = :id');
        $statement->execute(['id' => $provinciaId]);
        if ((int) $statement->fetchColumn() === 0) {
            $provinciaId = 0;
        }
    }

    $textos = [];
    foreach (array_keys(figuras_campos_texto()) as $campo) {
        $html = trim(sanitize_html((string) ($input[$campo] ?? '')));
        $textos[$campo] = $html !== '' ? $html : null;
    }

    if ($estado === 'PUBLICADO' && ($resumen === '' || $textos['trayectoria'] === null)) {
        $errores[] = 'Para publicar la ficha hacen falta al menos el resumen y la trayectoria.';
    }

    // Barrera contra duplicados, solo al crear.
    if ($id === 0 && !$errores && empty($input['confirmar_distinta'])) {
        $parecidas = figuras_posibles_duplicados($nombreArtistico, $nombreReal, $otrosNombres);
        if ($parecidas) {
            $nombres = array_map(
                static fn (array $fila): string => (string) $fila['nombre_artistico']
                    . ($fila['nacimiento_anio'] ? ' (' . $fila['nacimiento_anio'] . ')' : ''),
                $parecidas
            );
            $errores[] = 'Ya hay fichas con un nombre parecido: ' . implode(', ', $nombres)
                . '. Si es la misma persona, edita esa ficha. Si es otra, marca "Es una figura distinta" y guarda de nuevo.';
        }
    }

    $imagenPath = (string) ($existente['imagen_path'] ?? '');
    if (!$errores) {
        $subida = site_save_image_upload($files['imagen'] ?? null, $errores, 'figura');
        if ($subida !== null) {
            $imagenPath = $subida;
        }
    }
    if (!empty($input['quitar_imagen']) && empty($files['imagen']['name'])) {
        $imagenPath = '';
    }

    if ($errores) {
        throw new InvalidArgumentException(implode(' ', $errores));
    }

    // slugify('') no devuelve vacio sino un valor por defecto, asi que el
    // "no han escrito slug" se decide antes de normalizar.
    $slugTexto = clean_text((string) ($input['slug'] ?? ''));
    $slugEntrada = $slugTexto !== '' ? slugify($slugTexto) : '';
    // El slug es la URL publica: una vez creada la ficha solo cambia si se
    // escribe otro a proposito, nunca por renombrar al artista.
    $slug = $id > 0 && $slugEntrada === ''
        ? (string) $existente['slug']
        : figuras_slug_unico($pdo, $slugEntrada !== '' ? $slugEntrada : slugify($nombreArtistico), $id);

    $params = [
        'slug' => $slug,
        'nombre_artistico' => $nombreArtistico,
        'nombre_real' => $nombreReal !== '' ? $nombreReal : null,
        'otros_nombres' => $otrosNombres !== '' ? $otrosNombres : null,
        'rol' => $rol !== '' ? $rol : null,
        'nacimiento_anio' => $nacAnio,
        'nacimiento_fecha' => $nacFecha,
        'nacimiento_lugar' => $nacimientoLugar !== '' ? $nacimientoLugar : null,
        'nacimiento_provincia_id' => $provinciaId > 0 ? $provinciaId : null,
        'fallecimiento_anio' => $falAnio,
        'fallecimiento_fecha' => $falFecha,
        'fallecimiento_lugar' => $fallecimientoLugar !== '' ? $fallecimientoLugar : null,
        'resumen' => $resumen !== '' ? $resumen : null,
        'trayectoria' => $textos['trayectoria'],
        'aportaciones' => $textos['aportaciones'],
        'obra_destacada' => $textos['obra_destacada'],
        'premios' => $textos['premios'],
        'anecdotas' => $textos['anecdotas'],
        'imagen_path' => $imagenPath !== '' ? $imagenPath : null,
        'imagen_credito' => $imagenCredito !== '' ? $imagenCredito : null,
        'por_verificar' => $porVerificar !== '' ? $porVerificar : null,
        'estado' => $estado,
        'destacada' => !empty($input['destacada']) ? 1 : 0,
    ];

    $disciplinasValidas = array_map(static fn (array $fila): int => (int) $fila['id'], figuras_disciplinas_disponibles());
    $disciplinas = array_values(array_intersect(
        array_map('intval', (array) ($input['disciplinas'] ?? [])),
        $disciplinasValidas
    ));

    $pdo->beginTransaction();
    try {
        if ($id > 0) {
            $asignaciones = implode(', ', array_map(static fn (string $campo): string => "{$campo} = :{$campo}", array_keys($params)));
            $params['id'] = $id;
            $pdo->prepare(
                "UPDATE figuras_flamenco SET {$asignaciones},
                    publicado_at = CASE WHEN :estado_pub = 'PUBLICADO' THEN COALESCE(publicado_at, CURRENT_TIMESTAMP) ELSE publicado_at END
                 WHERE id = :id"
            )->execute($params + ['estado_pub' => $estado]);
        } else {
            $params['creado_por_usuario_id'] = (int) ($user['db_id'] ?? 0) ?: null;
            $params['publicado_at'] = $estado === 'PUBLICADO' ? db_datetime() : null;
            $campos = array_keys($params);
            $pdo->prepare(
                'INSERT INTO figuras_flamenco (' . implode(', ', $campos) . ') VALUES (:' . implode(', :', $campos) . ')'
            )->execute($params);
            $id = (int) $pdo->lastInsertId();
        }

        $pdo->prepare('DELETE FROM figura_disciplinas WHERE figura_id = :id')->execute(['id' => $id]);
        $insertar = $pdo->prepare('INSERT INTO figura_disciplinas (figura_id, disciplina_id) VALUES (:figura, :disciplina)');
        foreach ($disciplinas as $disciplinaId) {
            $insertar->execute(['figura' => $id, 'disciplina' => $disciplinaId]);
        }

        $pdo->commit();
    } catch (Throwable $exception) {
        $pdo->rollBack();
        throw $exception;
    }

    // Fuente opcional en el mismo formulario: lo normal al crear o ampliar.
    if (clean_text((string) ($input['fuente'] ?? '')) !== '') {
        figuras_anadir_fuente($id, $input);
    }

    return $id;
}

function figuras_cambiar_estado(int $id, string $estado): void
{
    $pdo = figuras_db_requerida();
    if (!isset(figuras_estados()[$estado])) {
        throw new InvalidArgumentException('Estado no valido.');
    }

    $figura = figuras_por_id($id);
    if (!$figura) {
        throw new RuntimeException('No se encontro la ficha indicada.');
    }
    if ($estado === 'PUBLICADO' && (trim((string) $figura['resumen']) === '' || trim((string) $figura['trayectoria']) === '')) {
        throw new InvalidArgumentException('Para publicar la ficha hacen falta al menos el resumen y la trayectoria.');
    }

    $pdo->prepare(
        "UPDATE figuras_flamenco
         SET estado = :estado,
             publicado_at = CASE WHEN :estado_pub = 'PUBLICADO' THEN COALESCE(publicado_at, CURRENT_TIMESTAMP) ELSE publicado_at END
         WHERE id = :id"
    )->execute(['estado' => $estado, 'estado_pub' => $estado, 'id' => $id]);
}

function figuras_anadir_fuente(int $figuraId, array $input): void
{
    $pdo = figuras_db_requerida();
    $fuente = clean_text((string) ($input['fuente'] ?? ''));
    $detalle = clean_text((string) ($input['fuente_detalle'] ?? ''));
    $urlEntrada = trim((string) ($input['fuente_url'] ?? ''));
    $url = $urlEntrada !== '' ? site_sanitize_url($urlEntrada) : '';

    if ($fuente === '' || mb_strlen($fuente) > 255) {
        throw new InvalidArgumentException('Indica el nombre de la fuente (maximo 255 caracteres).');
    }
    if (mb_strlen($detalle) > 255) {
        throw new InvalidArgumentException('El detalle de la fuente admite como maximo 255 caracteres.');
    }
    if ($urlEntrada !== '' && ($url === '' || preg_match('#^https?://#i', $url) !== 1 || mb_strlen($url) > 500)) {
        throw new InvalidArgumentException('La URL de la fuente debe empezar por http:// o https://.');
    }
    if (!figuras_por_id($figuraId)) {
        throw new RuntimeException('No se encontro la ficha indicada.');
    }

    $pdo->prepare('INSERT INTO figura_fuentes (figura_id, fuente, detalle, url) VALUES (:figura, :fuente, :detalle, :url)')
        ->execute([
            'figura' => $figuraId,
            'fuente' => $fuente,
            'detalle' => $detalle !== '' ? $detalle : null,
            'url' => $url !== '' ? $url : null,
        ]);
}

function figuras_quitar_fuente(int $figuraId, int $fuenteId): void
{
    figuras_db_requerida()
        ->prepare('DELETE FROM figura_fuentes WHERE id = :id AND figura_id = :figura')
        ->execute(['id' => $fuenteId, 'figura' => $figuraId]);
}

/**
 * Fuentes ya registradas, con cuantas fichas salen de cada una: responde a
 * "estas paginas ya las hemos procesado?".
 *
 * @return list<array<string, mixed>>
 */
function figuras_fuentes_procesadas(): array
{
    $pdo = figuras_db();
    if (!$pdo) {
        return [];
    }

    try {
        return $pdo->query(
            "SELECT ff.fuente, ff.detalle, COUNT(DISTINCT ff.figura_id) AS total_figuras,
                    GROUP_CONCAT(DISTINCT f.nombre_artistico ORDER BY f.nombre_artistico SEPARATOR ', ') AS figuras
             FROM figura_fuentes ff
             JOIN figuras_flamenco f ON f.id = ff.figura_id
             WHERE ff.url IS NULL
             GROUP BY ff.fuente, ff.detalle
             ORDER BY ff.fuente, ff.detalle"
        )->fetchAll();
    } catch (Throwable $exception) {
        error_log('[CSF figuras] ' . $exception->getMessage());
        return [];
    }
}
