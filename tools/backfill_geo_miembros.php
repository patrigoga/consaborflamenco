<?php
declare(strict_types=1);

/**
 * Rellena miembros.provincia_id y miembros.municipio_id a partir del texto que
 * ya tenian guardado en provincia_texto y ciudad.
 *
 * No modifica el texto original: solo anade la clasificacion normalizada, que
 * es lo que permite filtrar el directorio por provincia y municipio. Es
 * idempotente: se puede ejecutar tantas veces como haga falta.
 *
 *   php tools/backfill_geo_miembros.php          (simulacion, no escribe)
 *   php tools/backfill_geo_miembros.php --aplicar
 */

require_once __DIR__ . '/../app/geo_repository.php';

$aplicar = in_array('--aplicar', $argv, true);

$pdo = db();
if (!$pdo) {
    echo "Sin conexion a la base de datos. Revisa la configuracion de entorno.\n";
    exit(1);
}

if (!db_column_exists($pdo, 'miembros', 'provincia_id')) {
    echo "Falta la columna miembros.provincia_id. Ejecuta antes:\n";
    echo "  php tools/run_migration.php database/20260831_fase1_red_social.sql\n";
    exit(1);
}

$miembros = $pdo->query(
    'SELECT id, nombre_publico, ciudad, provincia_texto, provincia_id, municipio_id FROM miembros ORDER BY id ASC'
)->fetchAll(PDO::FETCH_ASSOC);

$actualizar = $pdo->prepare(
    'UPDATE miembros SET provincia_id = :provincia_id, municipio_id = :municipio_id WHERE id = :id'
);

$resueltos = 0;
$sinProvincia = 0;
$yaEstaban = 0;

foreach ($miembros as $miembro) {
    $provinciaTexto = (string) ($miembro['provincia_texto'] ?? '');
    $municipioTexto = (string) ($miembro['ciudad'] ?? '');

    if ((int) $miembro['provincia_id'] > 0) {
        $yaEstaban++;
        continue;
    }

    $geo = csf_geo_resolver($pdo, $provinciaTexto, $municipioTexto);

    if ($geo['provincia_id'] === null) {
        $sinProvincia++;
        printf(
            "  [sin resolver] #%d %-28s provincia=\"%s\"\n",
            (int) $miembro['id'],
            mb_substr((string) $miembro['nombre_publico'], 0, 28),
            $provinciaTexto
        );
        continue;
    }

    printf(
        "  [ok] #%d %-28s -> %s%s\n",
        (int) $miembro['id'],
        mb_substr((string) $miembro['nombre_publico'], 0, 28),
        $geo['provincia_texto'],
        $geo['municipio_id'] !== null ? ' / ' . $geo['municipio_texto'] : ''
    );

    if ($aplicar) {
        $actualizar->execute([
            'provincia_id' => $geo['provincia_id'],
            'municipio_id' => $geo['municipio_id'],
            'id' => (int) $miembro['id'],
        ]);
    }
    $resueltos++;
}

echo "\n";
echo 'Miembros revisados : ' . count($miembros) . "\n";
echo 'Ya clasificados    : ' . $yaEstaban . "\n";
echo 'Resueltos          : ' . $resueltos . "\n";
echo 'Sin provincia valida: ' . $sinProvincia . "\n";

if (!$aplicar) {
    echo "\nSimulacion: no se ha escrito nada. Repite con --aplicar para guardar.\n";
} else {
    echo "\nCambios guardados.\n";
}
