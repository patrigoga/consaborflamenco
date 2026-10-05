-- Con Sabor Flamenco - Revista: primeros articulos de historia y tecnica.
--
-- Migracion INCREMENTAL y NO DESTRUCTIVA. No borra ni renombra nada existente.
-- Idempotente: se puede ejecutar tantas veces como haga falta (INSERT IGNORE
-- sobre los slugs, que son UNIQUE). Si un articulo ya existe, NO se pisa: las
-- correcciones hechas desde el panel se conservan.
--
--   php tools/run_migration.php database/20260923_revista_maestros_guitarra.sql
--
-- Contenido: 2 articulos (panorama historico y tecnica) redactados a partir
-- del capitulo "Los grandes maestros de la guitarra flamenca" del libro
-- Arte Flamenco (pp. 105-108).
-- Texto de redaccion propia: del libro solo se toman hechos, con las fechas
-- contrastadas en otras fuentes. Entran como BORRADOR para revisarlos antes de
-- publicar. Las biografias de cada artista NO van aqui: son fichas de
-- figuras_flamenco (database/20260928_figuras_flamenco.sql).
--
-- Las portadas (imagen_principal_path) quedan vacias: se generan con
-- tools/filtro_artistico.php a partir de fotos con permiso o licencia libre,
-- y esa herramienta enlaza la imagen al articulo con --articulo=<slug>.

-- SET NAMES: el fichero esta en UTF-8. Sin esto, el cliente mysql de consola
-- lo lee como latin1 y guarda las tildes y enes corrompidas.
SET NAMES utf8mb4;

-- Categoria nueva (tambien esta en db_seed_article_categories()).
INSERT IGNORE INTO categorias_articulos (nombre, slug) VALUES ('Historia', 'historia');

-- Los grandes maestros de la guitarra flamenca: de Sabicas a la generación intermedia
INSERT IGNORE INTO articulos (autor_usuario_id, categoria_id, titulo, slug, resumen, contenido, imagen_principal_path, estado, publicado_at)
SELECT NULL, c.id, 'Los grandes maestros de la guitarra flamenca: de Sabicas a la generación intermedia', 'los-grandes-maestros-de-la-guitarra-flamenca', 'Cómo la guitarra flamenca pasó de acompañar al cante a llenar salas de concierto: Sabicas abrió el camino y una generación de maestros lo ensanchó antes de la llegada de Paco de Lucía.',
'<p>Durante mucho tiempo la guitarra flamenca fue, ante todo, compañera: sostenía al cante y marcaba el compás del baile. Que hoy se escuche sola en teatros de todo el mundo es el resultado de varias revoluciones encadenadas. La más conocida es la de Paco de Lucía, pero no se entiende sin las que vinieron antes.</p>
<h3>Sabicas, el punto de partida</h3>
<p>El navarro Agustín Castellón, «Sabicas», se marchó a América en 1936 y desde allí dio a conocer la guitarra flamenca como instrumento solista. Su técnica deslumbró a los entendidos: picados en todas las cuerdas, un pulgar poderoso y arpegios que nadie había tocado así. Paco de Lucía reconoció que Sabicas le descubrió una limpieza y una velocidad que no conocía.</p>
<h3>Una generación entre dos revoluciones</h3>
<p>Entre Sabicas y Paco de Lucía hubo una generación de guitarristas muy distintos entre sí, sin una escuela común, pero con un mismo empeño: que el guitarrista flamenco fuera considerado un músico completo.</p>
<ul>
<li><strong>Víctor Monge «Serranito»</strong>, madrileño, pasó por todos los oficios del toque antes de convertirse en gran concertista.</li>
<li><strong>Manuel Cano</strong>, granadino, llevó la guitarra flamenca al conservatorio y escribió sobre su historia.</li>
<li><strong>Andrés Batista</strong>, barcelonés, acompañó a Carmen Amaya y se volcó después en la enseñanza.</li>
<li><strong>Mario Escudero</strong> y <strong>Luis Maravilla</strong> hicieron carrera en los escenarios de medio mundo.</li>
<li>Familias como <strong>los Habichuela</strong> y figuras como <strong>Enrique de Melchor</strong> o <strong>Pedro Bacán</strong> completan el mapa.</li>
</ul>
<h3>La lucha por el respeto</h3>
<p>Varios de estos maestros insistieron en lo mismo: tocar la guitarra flamenca exige años de estudio, como cualquier otra disciplina musical. Esa reivindicación, junto a los conciertos y los métodos de enseñanza, preparó el terreno para la explosión que vino después.</p>
<p><em>Fuentes: libro «Arte Flamenco», capítulo «Los grandes maestros de la guitarra flamenca»; datos biográficos contrastados en enciclopedias y prensa.</em></p>',
NULL, 'BORRADOR', NULL
FROM categorias_articulos c WHERE c.slug = 'historia';

-- Picado, alzapúa, trémolo: cómo suena la técnica flamenca
INSERT IGNORE INTO articulos (autor_usuario_id, categoria_id, titulo, slug, resumen, contenido, imagen_principal_path, estado, publicado_at)
SELECT NULL, c.id, 'Picado, alzapúa, trémolo: cómo suena la técnica flamenca', 'picado-alzapua-tremolo-como-suena-la-tecnica-flamenca', 'Seis técnicas que definen el toque flamenco explicadas sin tecnicismos, para quien empieza a escuchar o a tocar la guitarra.',
'<p>Cuando se escucha una guitarra flamenca pasan muchas cosas en pocos segundos. Estas son las técnicas que las producen, y las que maestros como Sabicas llevaron más lejos que nadie.</p>
<ul>
<li><strong>Rasgueado:</strong> los dedos se abren en abanico sobre las cuerdas. Es el golpe rítmico, casi de percusión, que identifica al flamenco al instante.</li>
<li><strong>Picado:</strong> escalas rápidas pulsando con índice y medio alternos. Es el pasaje veloz y nítido.</li>
<li><strong>Alzapúa:</strong> el pulgar golpea hacia abajo y hacia arriba, como una púa, mezclando melodía y ritmo.</li>
<li><strong>Arpegio:</strong> las notas de un acorde tocadas una tras otra, no a la vez.</li>
<li><strong>Trémolo:</strong> una nota repetida tan rápido que parece sostenida, sobre un bajo que lleva el pulgar.</li>
<li><strong>Ligado:</strong> notas encadenadas con la mano izquierda, sin volver a pulsar con la derecha.</li>
</ul>
<h3>Del acompañamiento al concierto</h3>
<p>Todas estas técnicas nacieron para acompañar al cante y al baile, pero con el tiempo sirvieron para construir piezas de concierto. Hoy forman la base de cualquier curso de guitarra flamenca.</p>
<p><em>Fuentes: libro «Arte Flamenco», capítulo «Los grandes maestros de la guitarra flamenca»; datos biográficos contrastados en enciclopedias y prensa.</em></p>',
NULL, 'BORRADOR', NULL
FROM categorias_articulos c WHERE c.slug = 'formacion';
