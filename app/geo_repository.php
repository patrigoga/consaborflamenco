<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

/**
 * Provincias y municipios normalizados.
 *
 * Antes de esta fase la ubicacion era texto libre (`miembros.ciudad` y
 * `miembros.provincia_texto`), asi que "Cordoba", "Córdoba" y "CORDOBA" eran
 * tres sitios distintos y no habia forma de filtrar la agenda por territorio.
 *
 * Estrategia: las 52 provincias se siembran (db_seed_provincias) y los
 * municipios se dan de alta segun se usan, con find-or-create dentro de su
 * provincia. Asi no hace falta cargar el censo del INE y aun asi todo queda
 * normalizado desde el primer evento.
 *
 * Importante: quien guarda una ubicacion conserva SIEMPRE el texto original
 * junto al id. El id sirve para filtrar; el texto es lo que escribio la persona
 * y sobrevive aunque la ficha geografica cambie o se quede sin resolver.
 */

/**
 * Todas las provincias, ordenadas por nombre. Se cachea en memoria porque la
 * agenda y el directorio la piden varias veces en la misma peticion.
 *
 * @return array<int, array{id:int, nombre:string, slug:string}>
 */
function csf_geo_provincias(PDO $pdo): array
{
    static $cache = null;
    if (is_array($cache)) {
        return $cache;
    }

    $rows = $pdo->query('SELECT id, nombre, slug FROM provincias ORDER BY nombre ASC')->fetchAll(PDO::FETCH_ASSOC);
    $cache = array_map(static fn (array $row): array => [
        'id' => (int) $row['id'],
        'nombre' => (string) $row['nombre'],
        'slug' => (string) $row['slug'],
    ], $rows);

    return $cache;
}

/**
 * @return array{id:int, nombre:string, slug:string}|null
 */
function csf_geo_provincia_por_id(PDO $pdo, ?int $provinciaId): ?array
{
    if ($provinciaId === null || $provinciaId <= 0) {
        return null;
    }

    foreach (csf_geo_provincias($pdo) as $provincia) {
        if ($provincia['id'] === $provinciaId) {
            return $provincia;
        }
    }

    return null;
}

/**
 * @return array{id:int, nombre:string, slug:string}|null
 */
function csf_geo_provincia_por_slug(PDO $pdo, string $slug): ?array
{
    $slug = slugify(clean_text($slug));
    foreach (csf_geo_provincias($pdo) as $provincia) {
        if ($provincia['slug'] === $slug) {
            return $provincia;
        }
    }

    return null;
}

/**
 * Provincia a partir de un texto escrito por el usuario.
 *
 * Se compara por slug, no por nombre, para que "Cordoba", "Córdoba" y "CÓRDOBA"
 * resuelvan a la misma fila. Devuelve null si no hay coincidencia: la ubicacion
 * se guarda entonces solo como texto y el evento simplemente no aparece en los
 * filtros por provincia.
 *
 * @return array{id:int, nombre:string, slug:string}|null
 */
function csf_geo_provincia_por_texto(PDO $pdo, string $texto): ?array
{
    $texto = clean_text($texto);
    if ($texto === '') {
        return null;
    }

    return csf_geo_provincia_por_slug($pdo, $texto);
}

/**
 * Municipios ya registrados de una provincia. Alimenta los <datalist> de
 * autocompletado del formulario de evento y del perfil.
 *
 * @return array<int, array{id:int, nombre:string, slug:string}>
 */
function csf_geo_municipios(PDO $pdo, int $provinciaId): array
{
    if ($provinciaId <= 0) {
        return [];
    }

    $statement = $pdo->prepare(
        'SELECT id, nombre, slug FROM municipios WHERE provincia_id = :provincia_id ORDER BY nombre ASC'
    );
    $statement->execute(['provincia_id' => $provinciaId]);

    return array_map(static fn (array $row): array => [
        'id' => (int) $row['id'],
        'nombre' => (string) $row['nombre'],
        'slug' => (string) $row['slug'],
    ], $statement->fetchAll(PDO::FETCH_ASSOC));
}

/**
 * @return array{id:int, nombre:string, slug:string, provincia_id:int}|null
 */
function csf_geo_municipio_por_id(PDO $pdo, ?int $municipioId): ?array
{
    if ($municipioId === null || $municipioId <= 0) {
        return null;
    }

    $statement = $pdo->prepare('SELECT id, nombre, slug, provincia_id FROM municipios WHERE id = :id');
    $statement->execute(['id' => $municipioId]);
    $row = $statement->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return null;
    }

    return [
        'id' => (int) $row['id'],
        'nombre' => (string) $row['nombre'],
        'slug' => (string) $row['slug'],
        'provincia_id' => (int) $row['provincia_id'],
    ];
}

/**
 * Busca el municipio dentro de la provincia y lo crea si no existe.
 *
 * El slug es unico por provincia (uq_municipios_provincia_slug), no a nivel
 * nacional: hay municipios homonimos en provincias distintas. Ese indice es
 * ademas la defensa contra la condicion de carrera de dos altas simultaneas,
 * por eso el INSERT se hace con IGNORE y se relee despues.
 */
function csf_geo_find_or_create_municipio(PDO $pdo, int $provinciaId, string $nombre): ?int
{
    $nombre = clean_text($nombre);
    if ($provinciaId <= 0 || $nombre === '') {
        return null;
    }

    $slug = slugify($nombre);

    $buscar = $pdo->prepare('SELECT id FROM municipios WHERE provincia_id = :provincia_id AND slug = :slug');
    $buscar->execute(['provincia_id' => $provinciaId, 'slug' => $slug]);
    $existente = $buscar->fetchColumn();
    if ($existente !== false) {
        return (int) $existente;
    }

    $insertar = $pdo->prepare(
        'INSERT IGNORE INTO municipios (provincia_id, nombre, slug) VALUES (:provincia_id, :nombre, :slug)'
    );
    $insertar->execute([
        'provincia_id' => $provinciaId,
        'nombre' => mb_substr($nombre, 0, 160),
        'slug' => mb_substr($slug, 0, 180),
    ]);

    $buscar->execute(['provincia_id' => $provinciaId, 'slug' => $slug]);
    $creado = $buscar->fetchColumn();

    return $creado !== false ? (int) $creado : null;
}

/**
 * Normaliza el par provincia + municipio que llega de un formulario.
 *
 * Devuelve siempre las cuatro claves: los ids para filtrar (pueden ser null si
 * la provincia no se reconoce) y los textos para mostrar. Es la unica funcion
 * que deberian usar los formularios que guardan una ubicacion.
 *
 * @return array{provincia_id:?int, municipio_id:?int, provincia_texto:string, municipio_texto:string}
 */
function csf_geo_resolver(PDO $pdo, string $provinciaTexto, string $municipioTexto): array
{
    $provinciaTexto = clean_text($provinciaTexto);
    $municipioTexto = clean_text($municipioTexto);

    $provincia = csf_geo_provincia_por_texto($pdo, $provinciaTexto);
    $provinciaId = $provincia['id'] ?? null;

    // Sin provincia reconocida no se crea municipio: quedaria huerfano y sin
    // forma de filtrarlo. El texto se conserva igualmente.
    $municipioId = $provinciaId !== null
        ? csf_geo_find_or_create_municipio($pdo, $provinciaId, $municipioTexto)
        : null;

    return [
        'provincia_id' => $provinciaId,
        'municipio_id' => $municipioId,
        // Si la provincia se reconoce se guarda su nombre canonico, para que
        // "cordoba" acabe mostrandose como "Córdoba".
        'provincia_texto' => mb_substr($provincia['nombre'] ?? $provinciaTexto, 0, 120),
        'municipio_texto' => mb_substr($municipioTexto, 0, 160),
    ];
}

/**
 * Texto de ubicacion para tarjetas y listados: "Montilla · Córdoba".
 */
function csf_geo_etiqueta(?string $municipio, ?string $provincia): string
{
    $partes = array_values(array_filter([
        clean_text((string) ($municipio ?? '')),
        clean_text((string) ($provincia ?? '')),
    ], static fn (string $parte): bool => $parte !== ''));

    // Un evento en la capital no deberia leerse "Córdoba · Córdoba".
    if (count($partes) === 2 && mb_strtolower($partes[0], 'UTF-8') === mb_strtolower($partes[1], 'UTF-8')) {
        return $partes[0];
    }

    return implode(' · ', $partes);
}
