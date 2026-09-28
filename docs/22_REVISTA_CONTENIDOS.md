# Revista: articulos de historia y tecnica

## Que hay

Primera tanda de articulos de la revista, redactados a partir de paginas escaneadas del libro
"Arte Flamenco" (capitulo "Los grandes maestros de la guitarra flamenca", pp. 105-108).

- Migracion `database/20260923_revista_maestros_guitarra.sql`: 2 articulos en estado
  `BORRADOR` y la categoria nueva **Historia** (anadida tambien a
  `db_seed_article_categories()`).
- Las biografias de cada artista no son articulos sino **fichas de figuras**: ver
  `docs/23_FIGURAS_FLAMENCO.md`.
- Herramienta `tools/filtro_artistico.php` para las portadas.

| Categoria | Articulos |
|---|---|
| Historia | Panorama: de Sabicas a la generacion intermedia |
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
- `php tools/filtro_artistico.php foto.jpg nombre [--articulo=slug | --figura=slug]`:
  - conserva el original intacto en `assets/images/revista/originales/`;
  - genera la version filtrada (estampa en tinta y marfil, 1200x675, WebP) en
    `assets/images/revista/<nombre>.webp`;
  - con `--articulo`, guarda esa ruta en `articulos.imagen_principal_path`; con `--figura`,
    en `figuras_flamenco.imagen_path`.
- Son imagenes editoriales versionadas en el repo, no subidas de usuario, por eso no pasan
  por `media.php` ni por `RUNTIME_UPLOADS_DIR`.

Portadas pendientes: `guitarra-flamenca-concierto` para el panorama y `tecnica-flamenca` para
el articulo de tecnica.

## Pendiente

- **No existe todavia pagina publica de articulo** ni listado de revista: `revista.php` e
  `historia-flamenco.php` son plantillas de seccion sin consulta a `articulos`. Los
  articulos solo se ven en el panel admin (seccion Articulos) hasta que se construya.
- El panel admin permite crear articulos pero no editarlos: las correcciones de estos
  borradores, por ahora, en la base de datos o en la migracion antes de ejecutarla.
