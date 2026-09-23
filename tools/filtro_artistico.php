<?php
declare(strict_types=1);

/*
 * Filtro artistico para las fotos de la revista.
 *
 * Convierte una foto en una estampa en tonos tinta y marfil: pocos tonos
 * planos, contornos marcados como trazo de tinta, grano de papel y vineta
 * suave, para que todas las portadas de la revista
 * compartan el mismo aire. El ORIGINAL NO SE TOCA: se copia tal cual a
 * assets/images/revista/originales/ y la version filtrada se escribe en
 * assets/images/revista/<nombre>.webp, que es la que usa la web.
 *
 * Uso:
 *   php tools/filtro_artistico.php ruta/a/foto.jpg nombre [--articulo=slug]
 *
 *   nombre     Nombre del fichero de salida, sin extension (p. ej. sabicas).
 *   --articulo Opcional: slug del articulo cuya imagen_principal_path se
 *              actualiza con la version filtrada (requiere base de datos).
 *
 * IMPORTANTE: usa solo fotos propias, con permiso del autor o con licencia
 * libre. Filtrar una foto no cambia sus derechos: el credito que pida la
 * licencia hay que mantenerlo en el articulo.
 *
 * Requiere la extension GD con soporte WebP (viene en XAMPP y en Hostinger).
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

const CSF_REVISTA_ANCHO = 1200;
const CSF_REVISTA_ALTO = 675;

/** Colores del duotono: sombras en tinta calida, luces en marfil. */
const CSF_FILTRO_TINTA = [42, 38, 34];
const CSF_FILTRO_MARFIL = [246, 239, 224];

function csf_filtro_uso(string $mensaje = ''): never
{
    if ($mensaje !== '') {
        fwrite(STDERR, $mensaje . "\n\n");
    }
    fwrite(STDERR, "Uso: php tools/filtro_artistico.php ruta/a/foto.jpg nombre [--articulo=slug]\n");
    exit(1);
}

function csf_filtro_cargar(string $ruta): GdImage
{
    $info = @getimagesize($ruta);
    if ($info === false) {
        csf_filtro_uso('No se reconoce la imagen: ' . $ruta);
    }

    $imagen = match ($info[2]) {
        IMAGETYPE_JPEG => @imagecreatefromjpeg($ruta),
        IMAGETYPE_PNG => @imagecreatefrompng($ruta),
        IMAGETYPE_WEBP => @imagecreatefromwebp($ruta),
        default => false,
    };
    if (!$imagen instanceof GdImage) {
        csf_filtro_uso('Formato no admitido (usa JPG, PNG o WebP): ' . $ruta);
    }

    return $imagen;
}

/** Recorta al centro y escala a 16:9, el formato de portada de la revista. */
function csf_filtro_encuadrar(GdImage $origen): GdImage
{
    $ancho = imagesx($origen);
    $alto = imagesy($origen);
    $proporcion = CSF_REVISTA_ANCHO / CSF_REVISTA_ALTO;

    if ($ancho / $alto > $proporcion) {
        $recorteAlto = $alto;
        $recorteAncho = (int) round($alto * $proporcion);
    } else {
        $recorteAncho = $ancho;
        $recorteAlto = (int) round($ancho / $proporcion);
    }
    $x = (int) (($ancho - $recorteAncho) / 2);
    // En retratos conviene recortar un poco mas abajo que arriba: las caras
    // suelen estar en el tercio superior.
    $y = (int) (($alto - $recorteAlto) / 3);

    $destino = imagecreatetruecolor(CSF_REVISTA_ANCHO, CSF_REVISTA_ALTO);
    imagecopyresampled($destino, $origen, 0, 0, $x, $y, CSF_REVISTA_ANCHO, CSF_REVISTA_ALTO, $recorteAncho, $recorteAlto);

    return $destino;
}

function csf_filtro_aplicar(GdImage $imagen): void
{
    $ancho = imagesx($imagen);
    $alto = imagesy($imagen);

    imagefilter($imagen, IMG_FILTER_GRAYSCALE);
    // Suaviza antes de posterizar: evita que el ruido y los artefactos de
    // compresion del original se conviertan en manchas.
    imagefilter($imagen, IMG_FILTER_GAUSSIAN_BLUR);
    imagefilter($imagen, IMG_FILTER_GAUSSIAN_BLUR);

    // Niveles automaticos: estira el histograma entre los percentiles 2 y 98
    // para que fotos antiguas o apagadas queden con el mismo contraste.
    $histograma = array_fill(0, 256, 0);
    for ($y = 0; $y < $alto; $y += 2) {
        for ($x = 0; $x < $ancho; $x += 2) {
            $histograma[imagecolorat($imagen, $x, $y) & 0xFF]++;
        }
    }
    $total = array_sum($histograma);
    $acumulado = 0;
    $bajo = 0;
    $alto98 = 255;
    foreach ($histograma as $valor => $cuenta) {
        $acumulado += $cuenta;
        if ($acumulado <= $total * 0.02) {
            $bajo = $valor;
        }
        if ($acumulado >= $total * 0.98) {
            $alto98 = $valor;
            break;
        }
    }
    $rango = max(1, $alto98 - $bajo);

    // Tabla del duotono con una curva suave (luces abiertas, sombras densas).
    $tabla = [];
    for ($v = 0; $v < 256; $v++) {
        $t = min(1.0, max(0.0, ($v - $bajo) / $rango));
        $t = $t * $t * (3 - 2 * $t);
        $tabla[$v] = [
            (int) round(CSF_FILTRO_TINTA[0] + (CSF_FILTRO_MARFIL[0] - CSF_FILTRO_TINTA[0]) * $t),
            (int) round(CSF_FILTRO_TINTA[1] + (CSF_FILTRO_MARFIL[1] - CSF_FILTRO_TINTA[1]) * $t),
            (int) round(CSF_FILTRO_TINTA[2] + (CSF_FILTRO_MARFIL[2] - CSF_FILTRO_TINTA[2]) * $t),
        ];
    }

    // Luminancia en memoria para el trazo de tinta (bordes) y la posterizacion.
    $lum = [];
    for ($y = 0; $y < $alto; $y++) {
        for ($x = 0; $x < $ancho; $x++) {
            $lum[$y * $ancho + $x] = imagecolorat($imagen, $x, $y) & 0xFF;
        }
    }

    mt_srand(1912); // grano estable: el mismo original da siempre el mismo resultado
    $cx = $ancho / 2;
    $cy = $alto / 2;
    $radio = sqrt($cx * $cx + $cy * $cy);
    $niveles = 6;

    for ($y = 0; $y < $alto; $y++) {
        for ($x = 0; $x < $ancho; $x++) {
            $v = $lum[$y * $ancho + $x];

            // Tonos planos, como una estampa: se reduce a pocos niveles y se
            // mezcla con el tono continuo para que no queden escalones duros.
            $normal = min(1.0, max(0.0, ($v - $bajo) / $rango));
            $plano = round($normal * ($niveles - 1)) / ($niveles - 1);
            $v = (int) round(($bajo + (0.5 * $plano + 0.5 * $normal) * $rango));
            $v = max(0, min(255, $v));

            // Trazo de tinta: Sobel sobre la luminancia; los contornos marcados
            // se oscurecen como una linea de dibujo.
            if ($x > 0 && $y > 0 && $x < $ancho - 1 && $y < $alto - 1) {
                $p = static fn (int $dx, int $dy): int => $lum[($y + $dy) * $ancho + $x + $dx];
                $gx = -$p(-1, -1) - 2 * $p(-1, 0) - $p(-1, 1) + $p(1, -1) + 2 * $p(1, 0) + $p(1, 1);
                $gy = -$p(-1, -1) - 2 * $p(0, -1) - $p(1, -1) + $p(-1, 1) + 2 * $p(0, 1) + $p(1, 1);
                $borde = min(1.0, max(0.0, (sqrt($gx * $gx + $gy * $gy) - 60) / 220));
                $v = (int) round($v * (1 - 0.75 * $borde));
            }

            [$r, $g, $b] = $tabla[$v];

            $distancia = sqrt(($x - $cx) ** 2 + ($y - $cy) ** 2) / $radio;
            $vineta = 1.0 - 0.28 * max(0.0, $distancia - 0.45) / 0.55;
            $grano = mt_rand(-9, 9);

            imagesetpixel($imagen, $x, $y, imagecolorallocate(
                $imagen,
                max(0, min(255, (int) ($r * $vineta) + $grano)),
                max(0, min(255, (int) ($g * $vineta) + $grano)),
                max(0, min(255, (int) ($b * $vineta) + $grano))
            ));
        }
    }
}

// --- Programa -------------------------------------------------------------

$argumentos = array_slice($argv, 1);
$articulo = null;
foreach ($argumentos as $i => $argumento) {
    if (str_starts_with($argumento, '--articulo=')) {
        $articulo = substr($argumento, strlen('--articulo='));
        unset($argumentos[$i]);
    }
}
$argumentos = array_values($argumentos);
if (count($argumentos) !== 2) {
    csf_filtro_uso();
}

[$rutaOriginal, $nombre] = $argumentos;
if (!is_file($rutaOriginal)) {
    csf_filtro_uso('No existe el fichero: ' . $rutaOriginal);
}
if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $nombre)) {
    csf_filtro_uso('El nombre solo admite minusculas, numeros y guiones (p. ej. mario-escudero).');
}
if ($articulo !== null) {
    // Se carga antes de escribir nada por pantalla: auth.php arranca la sesion
    // y eso falla si ya se ha enviado salida.
    require_once __DIR__ . '/../app/auth.php';
}
if (!function_exists('imagewebp')) {
    csf_filtro_uso('Esta instalacion de PHP no tiene GD con soporte WebP.');
}

$carpeta = dirname(__DIR__) . '/assets/images/revista';
$carpetaOriginales = $carpeta . '/originales';
if (!is_dir($carpetaOriginales) && !mkdir($carpetaOriginales, 0775, true)) {
    csf_filtro_uso('No se pudo crear ' . $carpetaOriginales);
}

// 1) Conservar el original intacto.
$extension = strtolower(pathinfo($rutaOriginal, PATHINFO_EXTENSION)) ?: 'jpg';
$copiaOriginal = $carpetaOriginales . '/' . $nombre . '.' . $extension;
if (realpath($rutaOriginal) !== realpath($copiaOriginal) && !copy($rutaOriginal, $copiaOriginal)) {
    csf_filtro_uso('No se pudo guardar el original en ' . $copiaOriginal);
}

// 2) Generar la version filtrada para la web.
$original = csf_filtro_cargar($rutaOriginal);
$portada = csf_filtro_encuadrar($original);
csf_filtro_aplicar($portada);
$salida = $carpeta . '/' . $nombre . '.webp';
imagewebp($portada, $salida, 86);

$rutaWeb = 'assets/images/revista/' . $nombre . '.webp';
echo "Original conservado: assets/images/revista/originales/{$nombre}.{$extension}\n";
echo "Version filtrada:    {$rutaWeb}\n";

// 3) Opcional: enlazar la portada al articulo.
if ($articulo !== null) {
    $pdo = db();
    if (!$pdo) {
        fwrite(STDERR, "Sin base de datos: la imagen no se ha enlazado a ningun articulo.\n");
        exit(1);
    }
    $statement = $pdo->prepare('UPDATE articulos SET imagen_principal_path = :ruta WHERE slug = :slug');
    $statement->execute(['ruta' => $rutaWeb, 'slug' => $articulo]);
    echo $statement->rowCount() > 0
        ? "Enlazada al articulo: {$articulo}\n"
        : "No hay ningun articulo con slug {$articulo} (o ya tenia esa imagen).\n";
}
