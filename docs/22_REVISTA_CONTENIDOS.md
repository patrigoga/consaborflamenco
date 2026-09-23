# Revista: contenidos de historia y artistas

## Que hay

Primera tanda de articulos de la revista, redactados a partir de paginas escaneadas del libro
"Arte Flamenco" (capitulo "Los grandes maestros de la guitarra flamenca", pp. 105-108).

- Migracion `database/20260923_revista_maestros_guitarra.sql`: 11 articulos en estado
  `BORRADOR` y dos categorias nuevas, **Historia** y **Artistas** (anadidas tambien a
  `db_seed_article_categories()`).
- Herramienta `tools/filtro_artistico.php` para las portadas.

| Categoria | Articulos |
|---|---|
| Historia | Panorama: de Sabicas a la generacion intermedia |
| Artistas | Sabicas, Serranito, Manuel Cano, Andres Batista, Luis Maravilla, Mario Escudero, los Habichuela, Enrique de Melchor, Pedro Bacan |
| Formacion | Tecnicas del toque: rasgueado, picado, alzapua, arpegio, tremolo, ligado |

## Reglas de contenido

- **Texto propio siempre.** Del libro se toman hechos (nombres, fechas, lugares,
  influencias), nunca parrafos. Las citas del libro se resumen o, como mucho, se citan en
  una frase atribuida.
- **Fechas contrastadas.** El libro es de finales de los 90: fallecimientos posteriores
  (Mario Escudero 2004, Luis Maravilla 2000, Enrique de Melchor 2012, Juan Habichuela 2016,
  Pepe Habichuela 2026) se han comprobado en otras fuentes.
- Cada articulo cierra con una linea de fuentes.
- El HTML usa solo etiquetas que admite `sanitize_html()` (`p`, `h3`, `ul`, `li`,
  `blockquote`, `strong`, `em`), asi que pasa por el saneado sin cambios.

## Imagenes

- **No se publican las fotos del libro**, ni siquiera filtradas: aplicar un filtro no cambia
  los derechos del fotografo o la editorial.
- Las portadas salen de fotos propias, con permiso del autor o con licencia libre (Wikimedia
  Commons u otras), manteniendo el credito que pida la licencia.
- `php tools/filtro_artistico.php foto.jpg nombre [--articulo=slug]`:
  - conserva el original intacto en `assets/images/revista/originales/`;
  - genera la version filtrada (estampa en tinta y marfil, 1200x675, WebP) en
    `assets/images/revista/<nombre>.webp`;
  - con `--articulo`, guarda esa ruta en `articulos.imagen_principal_path`.
- Son imagenes editoriales versionadas en el repo, no subidas de usuario, por eso no pasan
  por `media.php` ni por `RUNTIME_UPLOADS_DIR`.

Fotos pendientes (nombre sugerido para la herramienta):

| Articulo (slug) | Nombre de la portada |
|---|---|
| los-grandes-maestros-de-la-guitarra-flamenca | guitarra-flamenca-concierto |
| sabicas-el-guitarrista-que-llevo-el-flamenco-al-mundo | sabicas |
| victor-monge-serranito-el-guitarrista-de-guitarristas | serranito |
| manuel-cano-la-guitarra-flamenca-en-el-conservatorio | manuel-cano |
| andres-batista-concertista-y-maestro | andres-batista |
| luis-maravilla-del-colmao-a-los-grandes-escenarios | luis-maravilla |
| mario-escudero-el-flamenco-en-el-carnegie-hall | mario-escudero |
| los-habichuela-una-saga-granadina-de-la-guitarra | los-habichuela |
| enrique-de-melchor-herencia-de-marchena | enrique-de-melchor |
| pedro-bacan-el-toque-de-lebrija | pedro-bacan |
| picado-alzapua-tremolo-como-suena-la-tecnica-flamenca | tecnica-flamenca |

## Pendiente

- **No existe todavia pagina publica de articulo** ni listado de revista: `revista.php` e
  `historia-flamenco.php` son plantillas de seccion sin consulta a `articulos`. Los
  articulos solo se ven en el panel admin (seccion Articulos) hasta que se construya.
- El panel admin permite crear articulos pero no editarlos: las correcciones de estos
  borradores, por ahora, en la base de datos o en la migracion antes de ejecutarla.
- Por verificar: si Andres Batista sigue vivo y el ano exacto de nacimiento de Manuel Cano
  (el libro dice 1926; otras fuentes, 1925). Los textos evitan afirmar ninguno de los dos.
