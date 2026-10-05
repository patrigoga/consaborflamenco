# Figuras del flamenco (fichas de artistas)

## Que es

Una **figura** es la ficha editorial de un artista del flamenco, vivo o no: Sabicas, Pepe
Habichuela, Camaron... La escribe la redaccion y **solo la crea administracion**. No es un
miembro: los miembros son artistas que se registran y gestionan su propia web
(`/artista/{slug}`). Si una figura tiene ademas cuenta de miembro, `miembro_id` enlaza las dos.

Se separan porque son cosas distintas: un miembro paga o usa puntos, publica eventos y edita su
perfil; una figura es contenido de la revista, con fuentes y revision editorial.

Migracion: `database/20260928_figuras_flamenco.sql` (tablas + 10 guitarristas en `BORRADOR`).
Las tablas tambien se crean en `db_bootstrap()`.

## Tablas

### figuras_flamenco

| Campo | Para que |
|---|---|
| `slug` | URL de la ficha y clave para no duplicar. Unico. |
| `nombre_artistico`, `nombre_real`, `otros_nombres` | Los tres se buscan para detectar duplicados (indice FULLTEXT). En `otros_nombres` van apodos y variantes: "El Nino de Alicante". |
| `rol` | Texto que se ve: "Guitarrista", "Cantaora y bailaora", "Guitarrista y pedagogo". |
| `nacimiento_anio`, `nacimiento_fecha`, `nacimiento_lugar`, `nacimiento_provincia_id` | El ano siempre que se sepa; la fecha completa solo si se conoce el dia (sirve para efemerides). La provincia enlaza con el filtro por provincia de la web. |
| `fallecimiento_anio`, `fallecimiento_fecha`, `fallecimiento_lugar` | Igual. Vacio = vive o no se sabe (ver `por_verificar`). |
| `resumen` | 320 caracteres: entradilla, descripcion para Google y texto base para redes. |
| `trayectoria` | La biografia. HTML simple (`p`, `h3`, `ul`, `li`, `strong`, `em`, `blockquote`). |
| `aportaciones` | Estilo, tecnica, legado. |
| `obra_destacada` | Discos, libros, espectaculos, peliculas. |
| `premios` | Reconocimientos. |
| `anecdotas` | Anecdotas y citas breves con autor. |
| `imagen_path`, `imagen_credito` | Foto que sube administracion y su credito/licencia. |
| `miembro_id` | Opcional: su cuenta de miembro, si la tiene. |
| `por_verificar` | Datos pendientes de confirmar. Nunca se inventa: lo dudoso va a NULL y se anota aqui. |
| `estado` | `BORRADOR`, `REVISION`, `PUBLICADO`, `ARCHIVADO`, como los articulos. |
| `destacada` | Para portada o para destacar en el listado. |
| `vistas` | Contador de visitas de la ficha: el dato para ensenar a patrocinadores. |

### figura_disciplinas

Cante, baile, toque, percusion. Es la **misma tabla `disciplinas`** que usan los miembros, asi
que el filtro "Toque" puede mostrar a la vez figuras historicas y guitarristas de hoy. Una
figura puede tener varias (cantaora y bailaora).

### figura_fuentes

De donde sale cada dato: libro y paginas, web, entrevista. Sirve para dos cosas:

- **Saber que material ya esta procesado.** Antes de trabajar unas paginas nuevas se consulta si
  esa fuente y esas paginas ya estan registradas.
- **Citar las fuentes** al pie de la ficha publica.

## Panel de administracion

Seccion **Contenido > Figuras del flamenco** (`panel-admin.php?section=figuras`). La logica
vive en `app/figuras_repository.php` (prefijo `figuras_`).

- **Crear y editar** la ficha completa: nombres, rol, disciplinas, fechas, provincia, textos,
  estado y destacada. Tras guardar se sigue editando la misma ficha.
- **Imagen:** se sube desde el formulario (JPG, PNG o WebP, 3 MB) y se guarda como el resto de
  subidas, fuera del repo y servida por `media.php`. El credito va en su propio campo.
- **Barrera contra duplicados:** al crear, si ya hay fichas con alguna palabra del nombre en
  comun, no guarda y avisa con sus nombres. Si de verdad es otra persona, se marca "Es una
  figura distinta" y se guarda de nuevo.
- **Fuentes:** se anaden al crear o al ampliar, y se listan y quitan en la edicion. Debajo del
  listado, "Fuentes ya procesadas" muestra de que libros y paginas han salido ya fichas.
- **Listado** con buscador por nombre, filtro por estado, lo que le falta a cada ficha
  (imagen, ano, fuentes, datos por verificar), visitas y boton de publicar.
- **Publicar** exige resumen y trayectoria. No se borran fichas: se archivan.
- El slug no cambia al renombrar al artista; solo si se escribe otro a proposito.

## Como se trabaja con material nuevo

1. Se reciben las paginas (escaneos, fotos).
2. Se consulta si la fuente ya se proceso (consulta B).
3. Por cada artista que aparece, se busca si ya tiene ficha (consulta A).
   - **No existe:** se crea la ficha (consulta C) con sus disciplinas y su fuente.
   - **Existe:** se amplian solo los campos donde el material nuevo aporta algo (consulta D), se
     anade la fuente y se revisa `por_verificar`.
4. La foto la sube administracion. Si se quiere el filtro artistico:
   `php tools/filtro_artistico.php foto.jpg sabicas --figura=sabicas`, y luego el credito
   (consulta E).
5. Revision y publicacion (consulta F).

Texto siempre de redaccion propia: de las fuentes se toman hechos, nunca parrafos.

## Consultas

El dia a dia se hace desde el panel; estas consultas son para phpMyAdmin, para informes o para
cargas en bloque. Todas probadas en MariaDB 10.11. Sustituye los valores de ejemplo.

Si se ejecuta un fichero `.sql` con tildes desde la consola `mysql`, tiene que empezar por
`SET NAMES utf8mb4;` (las migraciones ya lo llevan): sin eso los acentos se guardan corrompidos.

### A. Existe ya esta figura?

Por un nombre o apellido, en los tres campos de nombre:

```sql
SELECT id, slug, nombre_artistico, nombre_real, otros_nombres, nacimiento_anio, estado
FROM figuras_flamenco
WHERE nombre_artistico LIKE '%Habichuela%'
   OR nombre_real LIKE '%Habichuela%'
   OR otros_nombres LIKE '%Habichuela%';
```

Por parecido, cuando no se sabe como esta escrito (usa el indice FULLTEXT):

```sql
SELECT id, slug, nombre_artistico,
       MATCH(nombre_artistico, nombre_real, otros_nombres) AGAINST ('Nino de Alicante') AS parecido
FROM figuras_flamenco
WHERE MATCH(nombre_artistico, nombre_real, otros_nombres) AGAINST ('Nino de Alicante')
ORDER BY parecido DESC
LIMIT 5;
```

### B. Ya se proceso esta fuente?

```sql
SELECT f.nombre_artistico, ff.detalle, ff.created_at
FROM figura_fuentes ff
JOIN figuras_flamenco f ON f.id = ff.figura_id
WHERE ff.fuente = 'Arte Flamenco'
ORDER BY f.nombre_artistico;
```

### C. Crear una ficha

```sql
INSERT INTO figuras_flamenco (slug, nombre_artistico, nombre_real, otros_nombres, rol,
    nacimiento_anio, nacimiento_fecha, nacimiento_lugar, nacimiento_provincia_id,
    fallecimiento_anio, fallecimiento_fecha, fallecimiento_lugar,
    resumen, trayectoria, por_verificar, estado)
VALUES ('nino-ricardo', 'Nino Ricardo', 'Manuel Serrapi Sanchez', NULL, 'Guitarrista',
    1904, NULL, 'Sevilla', (SELECT id FROM provincias WHERE slug = 'sevilla'),
    1972, NULL, 'Sevilla',
    'Resumen de hasta 320 caracteres.',
    '<p>Trayectoria...</p>',
    'Fechas exactas de nacimiento y muerte.', 'BORRADOR');

-- Disciplinas (cante, baile, toque, percusion)
INSERT IGNORE INTO figura_disciplinas (figura_id, disciplina_id)
SELECT f.id, d.id FROM figuras_flamenco f JOIN disciplinas d ON d.slug = 'toque'
WHERE f.slug = 'nino-ricardo';

-- Fuente
INSERT INTO figura_fuentes (figura_id, fuente, detalle, url)
SELECT id, 'Arte Flamenco', 'pp. 110-112', NULL FROM figuras_flamenco WHERE slug = 'nino-ricardo';
```

Los datos de este ejemplo son ilustrativos; al crear una ficha real se contrastan.

### D. Ampliar una ficha existente

Anadir sin borrar lo que hay (anecdotas, trayectoria, obra...):

```sql
UPDATE figuras_flamenco
SET anecdotas = CONCAT(COALESCE(anecdotas, ''), '<p>Nueva anecdota.</p>')
WHERE slug = 'luis-maravilla';
```

Completar un dato que faltaba y quitarlo de pendientes:

```sql
UPDATE figuras_flamenco
SET nacimiento_fecha = '1942-06-18', por_verificar = NULL
WHERE slug = 'serranito';
```

(Fecha de ejemplo: no esta verificada.)

### E. Imagen y credito

```sql
UPDATE figuras_flamenco
SET imagen_path = 'assets/images/revista/sabicas.webp',
    imagen_credito = 'Foto: Nombre Autor, CC BY-SA 4.0'
WHERE slug = 'sabicas';
```

### F. Publicar

```sql
UPDATE figuras_flamenco
SET estado = 'PUBLICADO', publicado_at = COALESCE(publicado_at, CURRENT_TIMESTAMP)
WHERE slug = 'sabicas';
```

### G. Listado publico (con filtros opcionales)

```sql
SELECT f.slug, f.nombre_artistico, f.rol, f.nacimiento_anio, f.fallecimiento_anio,
       f.imagen_path, p.nombre AS provincia,
       GROUP_CONCAT(d.nombre ORDER BY d.nombre SEPARATOR ', ') AS disciplinas
FROM figuras_flamenco f
LEFT JOIN provincias p ON p.id = f.nacimiento_provincia_id
LEFT JOIN figura_disciplinas fd ON fd.figura_id = f.id
LEFT JOIN disciplinas d ON d.id = fd.disciplina_id
WHERE f.estado = 'PUBLICADO'
  -- AND p.slug = 'granada'
  -- AND EXISTS (SELECT 1 FROM figura_disciplinas x JOIN disciplinas y ON y.id = x.disciplina_id
  --             WHERE x.figura_id = f.id AND y.slug = 'toque')
GROUP BY f.id
ORDER BY f.destacada DESC, f.nombre_artistico;
```

### H. Efemerides de hoy (para redes sociales)

Quien nacio o murio un dia como hoy y cuantos anos se cumplen:

```sql
SELECT 'Nace' AS tipo, nombre_artistico, rol, YEAR(CURDATE()) - YEAR(nacimiento_fecha) AS anos, slug
FROM figuras_flamenco
WHERE estado = 'PUBLICADO'
  AND MONTH(nacimiento_fecha) = MONTH(CURDATE()) AND DAY(nacimiento_fecha) = DAY(CURDATE())
UNION ALL
SELECT 'Muere', nombre_artistico, rol, YEAR(CURDATE()) - YEAR(fallecimiento_fecha), slug
FROM figuras_flamenco
WHERE estado = 'PUBLICADO'
  AND MONTH(fallecimiento_fecha) = MONTH(CURDATE()) AND DAY(fallecimiento_fecha) = DAY(CURDATE());
```

Para planificar el mes siguiente, cambia `CURDATE()` por la fecha que quieras.

### I. Fichas pendientes de trabajo

```sql
SELECT slug, estado,
       imagen_path IS NULL AS sin_imagen,
       nacimiento_anio IS NULL AS sin_ano_nacimiento,
       por_verificar
FROM figuras_flamenco
WHERE estado <> 'ARCHIVADO'
  AND (imagen_path IS NULL OR nacimiento_anio IS NULL OR por_verificar IS NOT NULL OR estado <> 'PUBLICADO')
ORDER BY estado, nombre_artistico;
```

### J. Visitas (para patrocinadores)

Sumar una visita (lo hara la pagina publica de la ficha):

```sql
UPDATE figuras_flamenco SET vistas = vistas + 1 WHERE slug = 'sabicas' AND estado = 'PUBLICADO';
```

Las mas vistas:

```sql
SELECT nombre_artistico, vistas FROM figuras_flamenco
WHERE estado = 'PUBLICADO' ORDER BY vistas DESC LIMIT 20;
```

## Pendiente

- **Parte publica:** listado con filtros por disciplina y provincia, y ficha con URL propia
  (propuesta: `/figura/{slug}`) con Open Graph (imagen + resumen) para que se vea bien al
  compartir en redes.
- **Menu:** propuesta de colgar el listado de Flamenco > Figuras, junto a Historia, Palos y
  Llaves de Oro. Revista > Artistas sigue siendo el directorio de miembros.
- **Relaciones entre figuras** (padre de, hermano de, acompano a, maestro de): utiles para
  sagas como los Habichuela y para enlazar fichas entre si. Se dejan para cuando haya mas fichas.
