-- Con Sabor Flamenco - Revista: primeros articulos de historia y artistas.
--
-- Migracion INCREMENTAL y NO DESTRUCTIVA. No borra ni renombra nada existente.
-- Idempotente: se puede ejecutar tantas veces como haga falta (INSERT IGNORE
-- sobre los slugs, que son UNIQUE). Si un articulo ya existe, NO se pisa: las
-- correcciones hechas desde el panel se conservan.
--
--   php tools/run_migration.php database/20260923_revista_maestros_guitarra.sql
--
-- Contenido: 11 articulos redactados a partir del capitulo "Los grandes
-- maestros de la guitarra flamenca" del libro Arte Flamenco (pp. 105-108).
-- Texto de redaccion propia: del libro solo se toman hechos, con las fechas
-- contrastadas en otras fuentes. Entran como BORRADOR para revisarlos antes de
-- publicar.
--
-- Las portadas (imagen_principal_path) quedan vacias: se generan con
-- tools/filtro_artistico.php a partir de fotos con permiso o licencia libre,
-- y esa herramienta enlaza la imagen al articulo con --articulo=<slug>.

-- Categorias nuevas (tambien estan en db_seed_article_categories()).
INSERT IGNORE INTO categorias_articulos (nombre, slug) VALUES ('Historia', 'historia');
INSERT IGNORE INTO categorias_articulos (nombre, slug) VALUES ('Artistas', 'artistas');

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

-- Sabicas, el guitarrista que llevó el flamenco al mundo
INSERT IGNORE INTO articulos (autor_usuario_id, categoria_id, titulo, slug, resumen, contenido, imagen_principal_path, estado, publicado_at)
SELECT NULL, c.id, 'Sabicas, el guitarrista que llevó el flamenco al mundo', 'sabicas-el-guitarrista-que-llevo-el-flamenco-al-mundo', 'Nació en Pamplona en 1912, triunfó en América y cambió para siempre la forma de tocar. Vida y huella de Agustín Castellón «Sabicas», el maestro sin el que no se entiende a Paco de Lucía.',
'<p>Agustín Castellón Campos nació en Pamplona el 16 de marzo de 1912. Empezó a tocar siendo muy niño y con siete años ya actuaba en público. Nunca estudió solfeo y presumía de no haber tenido maestros: lo suyo fue oído, intuición y muchas horas con el instrumento.</p>
<h3>El salto a América</h3>
<p>En 1936, con el estallido de la Guerra Civil, salió de España junto a la bailaora Carmen Amaya. Vivió primero en México y después se instaló en Nueva York, donde pasó la mayor parte de su vida. No volvió a España hasta 1967. Desde Estados Unidos grabó decenas de discos y recorrió escenarios de todo el mundo, algo casi inédito para un guitarrista flamenco de su época.</p>
<h3>Qué aportó a la guitarra</h3>
<ul>
<li>Llevó el <strong>picado</strong> a todas las cuerdas, con una limpieza y una velocidad nuevas.</li>
<li>Dio al pulgar un papel protagonista con la <strong>alzapúa</strong>.</li>
<li>Amplió el repertorio con arpegios, trémolos y ligados de enorme dificultad.</li>
<li>Acercó al flamenco aires de otras tradiciones hispanas y los hizo propios.</li>
</ul>
<h3>Su herencia</h3>
<p>Sabicas se veía a sí mismo como el creador de la guitarra moderna, y aunque otros, como Ramón Montoya, ya habían dado conciertos fuera de España, su influencia es indiscutible. Paco de Lucía contó que, hasta conocerle, creía que lo más alto era Niño Ricardo; con Sabicas descubrió otra manera de tocar.</p>
<p>Murió en Nueva York el 14 de abril de 1990. Pamplona le recuerda hoy con un grupo escultórico en la ciudad.</p>
<p><em>Fuentes: libro «Arte Flamenco», capítulo «Los grandes maestros de la guitarra flamenca»; datos biográficos contrastados en enciclopedias y prensa.</em></p>',
NULL, 'BORRADOR', NULL
FROM categorias_articulos c WHERE c.slug = 'artistas';

-- Víctor Monge «Serranito», el guitarrista de guitarristas
INSERT IGNORE INTO articulos (autor_usuario_id, categoria_id, titulo, slug, resumen, contenido, imagen_principal_path, estado, publicado_at)
SELECT NULL, c.id, 'Víctor Monge «Serranito», el guitarrista de guitarristas', 'victor-monge-serranito-el-guitarrista-de-guitarristas', 'Madrileño de 1942, pasó por todos los oficios del toque antes de convertirse en uno de los grandes concertistas de la guitarra flamenca. Medalla de Oro de las Bellas Artes en 2019.',
'<p>Víctor Monge nació en Madrid en 1942 y empezó a estudiar música con ocho años. Con doce ya trabajaba junto a su hermano en un grupo familiar, Los Serranos, de donde le viene el nombre artístico.</p>
<h3>Todos los oficios del toque</h3>
<p>Antes de dar conciertos, Serranito hizo de todo: acompañó al cante, acompañó al baile, tocó en tablaos y en agrupaciones de todo tipo. Esa escuela práctica le dio un dominio completo del instrumento y le permitió dar el paso al concierto en solitario, cuando en España casi nadie lo hacía.</p>
<h3>Un estilo propio</h3>
<p>Aunque aprendió de oído, como tantos guitarristas de su tiempo, su música es compleja, muy elaborada y a la vez tierna. Por eso se le ha llamado «el guitarrista de guitarristas»: los propios compañeros de oficio lo admiran por su técnica y su inventiva.</p>
<ul>
<li>Más de sesenta años de carrera y una veintena de grabaciones.</li>
<li>Medalla de Oro al Mérito en las Bellas Artes (2019).</li>
<li>Gira de despedida de los escenarios en 2021.</li>
</ul>
<p><em>Fuentes: libro «Arte Flamenco», capítulo «Los grandes maestros de la guitarra flamenca»; datos biográficos contrastados en enciclopedias y prensa.</em></p>',
NULL, 'BORRADOR', NULL
FROM categorias_articulos c WHERE c.slug = 'artistas';

-- Manuel Cano, la guitarra flamenca en el conservatorio
INSERT IGNORE INTO articulos (autor_usuario_id, categoria_id, titulo, slug, resumen, contenido, imagen_principal_path, estado, publicado_at)
SELECT NULL, c.id, 'Manuel Cano, la guitarra flamenca en el conservatorio', 'manuel-cano-la-guitarra-flamenca-en-el-conservatorio', 'El granadino Manuel Cano dedicó su vida a que la guitarra flamenca tuviera la misma categoría que cualquier otra música: concertista, catedrático y autor de un libro pionero sobre su historia.',
'<p>Manuel Cano Tamayo nació en Granada a mediados de los años veinte en una casa donde la música era cosa de todos los días: su padre tocaba la guitarra y su madre el laúd. Sin embargo, no se dedicó profesionalmente al toque hasta pasados los treinta años.</p>
<h3>Un músico con causa</h3>
<p>Toda su carrera estuvo al servicio de una idea: la guitarra flamenca merecía el mismo respeto que la música culta. Solía decir que Bach o Scarlatti hicieron música magnífica, pero que en el flamenco había algo más. Esa convicción le llevó a las aulas: ocupó la cátedra de guitarra flamenca del Conservatorio Superior de Córdoba.</p>
<h3>Obra y reconocimientos</h3>
<ul>
<li>Una veintena de discos, entre ellos un homenaje a la guitarra de Ramón Montoya.</li>
<li>Premio de guitarra en el Concurso Nacional de Arte Flamenco de Córdoba (1965).</li>
<li>El libro <em>La guitarra. Historia, estudios y aportaciones al flamenco</em> (1986), sin precedentes en la bibliografía flamenca.</li>
<li>Medalla de Andalucía a título póstumo (1992).</li>
</ul>
<p>Murió en su Granada natal en enero de 1990.</p>
<p><em>Fuentes: libro «Arte Flamenco», capítulo «Los grandes maestros de la guitarra flamenca»; datos biográficos contrastados en enciclopedias y prensa.</em></p>',
NULL, 'BORRADOR', NULL
FROM categorias_articulos c WHERE c.slug = 'artistas';

-- Andrés Batista, concertista y maestro de guitarristas
INSERT IGNORE INTO articulos (autor_usuario_id, categoria_id, titulo, slug, resumen, contenido, imagen_principal_path, estado, publicado_at)
SELECT NULL, c.id, 'Andrés Batista, concertista y maestro de guitarristas', 'andres-batista-concertista-y-maestro', 'Nacido en Barcelona en 1937, acompañó a Carmen Amaya en sus últimos años y dedicó después su vida a enseñar, con métodos que han formado a generaciones de guitarristas.',
'<p>Andrés Batista nació en Barcelona el 12 de octubre de 1937. Combinó desde joven la formación clásica y la flamenca, y en lo flamenco tuvo como referente a Miguel Borrull, con quien trabajó en los tablaos barceloneses.</p>
<h3>Con Carmen Amaya</h3>
<p>Fue guitarrista de Carmen Amaya en los últimos años de la bailaora, con giras por Estados Unidos y Sudamérica, y la acompañó en su última actuación. También trabajó en compañías de teatro flamenco, entre ellas la de Vicente Escudero.</p>
<h3>La vocación de enseñar</h3>
<p>Con el tiempo se centró en la docencia. Desde los años setenta publicó métodos y partituras pensados para aprender de forma progresiva y ordenada, con el objetivo de acortar el camino hasta dominar la guitarra flamenca.</p>
<blockquote>Para Batista, el guitarrista flamenco es un profesional con muchos años de estudio detrás, no un oficio al margen.</blockquote>
<p>Esa defensa de la dignidad del guitarrista recorre toda su trayectoria, que incluye actuaciones en el Carnegie Hall de Nueva York.</p>
<p><em>Fuentes: libro «Arte Flamenco», capítulo «Los grandes maestros de la guitarra flamenca»; datos biográficos contrastados en enciclopedias y prensa.</em></p>',
NULL, 'BORRADOR', NULL
FROM categorias_articulos c WHERE c.slug = 'artistas';

-- Luis Maravilla, del colmao a los grandes escenarios
INSERT IGNORE INTO articulos (autor_usuario_id, categoria_id, titulo, slug, resumen, contenido, imagen_principal_path, estado, publicado_at)
SELECT NULL, c.id, 'Luis Maravilla, del colmao a los grandes escenarios', 'luis-maravilla-del-colmao-a-los-grandes-escenarios', 'Sevillano de 1914, empezó de niño en el colmao Villa Rosa de Madrid, recorrió América y Europa y acabó dedicado a la enseñanza. Repasamos la carrera de Luis López Tejera «Maravilla».',
'<p>Luis López Tejera nació en Sevilla el 1 de junio de 1914 y creció entre cantaores: era hijo del cantaor Niño de las Marianas. Muy pronto cambió la voz por la guitarra.</p>
<h3>Una carrera precoz</h3>
<p>Siendo todavía un niño ya tocaba en el colmao madrileño Villa Rosa, uno de los locales de cante y baile de referencia de la época. En los años treinta recorrió América y, durante la Guerra Civil, se estableció en Francia.</p>
<h3>En las grandes compañías</h3>
<p>De vuelta en España trabajó en formaciones teatrales, sobre todo junto a la bailaora Pilar López, y con artistas como Estrellita Castro. Su toque, elegante y seguro, le hizo imprescindible en los espectáculos de su tiempo.</p>
<p>En 1957 se retiró de los escenarios para dedicarse a enseñar. Murió el 10 de julio de 2000.</p>
<p><em>Fuentes: libro «Arte Flamenco», capítulo «Los grandes maestros de la guitarra flamenca»; datos biográficos contrastados en enciclopedias y prensa.</em></p>',
NULL, 'BORRADOR', NULL
FROM categorias_articulos c WHERE c.slug = 'artistas';

-- Mario Escudero, el flamenco en el Carnegie Hall
INSERT IGNORE INTO articulos (autor_usuario_id, categoria_id, titulo, slug, resumen, contenido, imagen_principal_path, estado, publicado_at)
SELECT NULL, c.id, 'Mario Escudero, el flamenco en el Carnegie Hall', 'mario-escudero-el-flamenco-en-el-carnegie-hall', 'El alicantino Mario Escudero siguió el camino americano de Sabicas, triunfó en Nueva York y grabó con él tres discos a dúo. Más de 250 obras propias avalan su legado.',
'<p>Mario Escudero nació en Alicante en 1928 y empezó a actuar muy joven. Como Sabicas, hizo el grueso de su carrera en Estados Unidos, adonde se trasladó a comienzos de los años cincuenta.</p>
<h3>Nueva York</h3>
<p>Su concierto de 1955 en el Carnegie Hall le abrió las puertas del público norteamericano. Actuó varias veces en las grandes salas de Nueva York e incluso tocó en la Casa Blanca durante la presidencia de John F. Kennedy.</p>
<h3>Junto a Sabicas</h3>
<p>Entre 1957 y 1959 grabó tres discos a dúo con Sabicas, una colaboración que hoy es un clásico de la guitarra flamenca. También trabajó con figuras como Carmen Amaya.</p>
<ul>
<li>Más de treinta discos de larga duración.</li>
<li>Más de 250 composiciones propias.</li>
<li>Giras por Rusia, Japón, Hong Kong o Turquía.</li>
</ul>
<p>Murió en Miami el 19 de noviembre de 2004.</p>
<p><em>Fuentes: libro «Arte Flamenco», capítulo «Los grandes maestros de la guitarra flamenca»; datos biográficos contrastados en enciclopedias y prensa.</em></p>',
NULL, 'BORRADOR', NULL
FROM categorias_articulos c WHERE c.slug = 'artistas';

-- Los Habichuela, una saga granadina de la guitarra
INSERT IGNORE INTO articulos (autor_usuario_id, categoria_id, titulo, slug, resumen, contenido, imagen_principal_path, estado, publicado_at)
SELECT NULL, c.id, 'Los Habichuela, una saga granadina de la guitarra', 'los-habichuela-una-saga-granadina-de-la-guitarra', 'Cuatro hermanos, una ciudad y un apellido que ha marcado el toque flamenco. Tras la muerte de Pepe Habichuela en agosto de 2026, repasamos la historia de la familia Carmona.',
'<p>En Granada, decir Habichuela es decir guitarra. Los hermanos Carmona —Juan (1933), Pepe (1944), Luis (1947) y Carlos (1958)— heredaron el oficio de su familia y lo llevaron a escenarios de todo el mundo.</p>
<h3>Juan, el acompañante de las grandes voces</h3>
<p>El mayor, Juan Habichuela, empezó como bailaor y acabó siendo uno de los guitarristas de acompañamiento más respetados. Tocó para cantaores como Juanito Valderrama y Enrique Morente, y en 2012 recibió un premio honorífico de los Grammy Latinos. Murió en Madrid en 2016.</p>
<h3>Pepe, el revolucionario discreto</h3>
<p>Pepe Habichuela formó con Enrique Morente una de las parejas más fecundas del flamenco y abrió la guitarra a otros mundos: grabó con músicos de jazz como Don Cherry y Dave Holland, y con la orquesta Bollywood Strings en el disco <em>Yerbabuena</em> (2001). Murió en Madrid el 4 de agosto de 2026, a los 82 años.</p>
<h3>Una familia que sigue sonando</h3>
<p>Luis Habichuela murió en 1993; Carlos completa la saga. La siguiente generación mantuvo vivo el apellido en el grupo Ketama, formado por hijos de la familia.</p>
<p><em>Fuentes: libro «Arte Flamenco», capítulo «Los grandes maestros de la guitarra flamenca»; datos biográficos contrastados en enciclopedias y prensa.</em></p>',
NULL, 'BORRADOR', NULL
FROM categorias_articulos c WHERE c.slug = 'artistas';

-- Enrique de Melchor, la herencia de Marchena
INSERT IGNORE INTO articulos (autor_usuario_id, categoria_id, titulo, slug, resumen, contenido, imagen_principal_path, estado, publicado_at)
SELECT NULL, c.id, 'Enrique de Melchor, la herencia de Marchena', 'enrique-de-melchor-herencia-de-marchena', 'Hijo de Melchor de Marchena, acompañó a Camarón, Mairena o Morente y giró con Paco de Lucía. Semblanza de un guitarrista que unió tradición y modernidad.',
'<p>Enrique Jiménez Ramírez nació en Marchena (Sevilla) el 15 de julio de 1950. Era hijo del guitarrista Melchor de Marchena, así que aprendió el toque en casa y adoptó el nombre del padre como apellido artístico.</p>
<h3>Al servicio del cante</h3>
<p>Fue un acompañante muy buscado. Tocó para Antonio Mairena, El Lebrijano, José Menese, Enrique Morente o Camarón de la Isla, entre otros. Durante dos temporadas formó parte del grupo de Paco de Lucía en sus giras internacionales.</p>
<h3>Obra en solitario</h3>
<p>Desde 1977 publicó ocho discos propios, entre ellos <em>Sugerencias</em>, <em>Cuchichí</em> o <em>Herencia gitana</em>, y participó en películas como <em>La Carmen</em>.</p>
<p>Murió en Madrid el 3 de enero de 2012, a los 61 años.</p>
<p><em>Fuentes: libro «Arte Flamenco», capítulo «Los grandes maestros de la guitarra flamenca»; datos biográficos contrastados en enciclopedias y prensa.</em></p>',
NULL, 'BORRADOR', NULL
FROM categorias_articulos c WHERE c.slug = 'artistas';

-- Pedro Bacán, el toque de Lebrija
INSERT IGNORE INTO articulos (autor_usuario_id, categoria_id, titulo, slug, resumen, contenido, imagen_principal_path, estado, publicado_at)
SELECT NULL, c.id, 'Pedro Bacán, el toque de Lebrija', 'pedro-bacan-el-toque-de-lebrija', 'Guitarrista de Lebrija con raíces en una gran familia gitana del cante, Pedro Bacán llevó el flamenco más hondo a Francia y a medio mundo. Murió en 1997, con 45 años.',
'<p>Pedro Peña, «Pedro Bacán», nació en Lebrija (Sevilla) el 12 de febrero de 1951, en una familia gitana de enorme tradición flamenca. Aprendió de forma casi autodidacta, en las reuniones familiares donde el cante era parte de la vida diaria.</p>
<h3>Un guitarrista de raíz</h3>
<p>Su toque era sobrio y profundo, muy ligado al cante de su tierra. Esa autenticidad le dio un gran prestigio fuera de España, sobre todo en Francia: París llegó a ser su segunda casa.</p>
<h3>Obra</h3>
<ul>
<li>El disco en solitario <em>Aluricán</em>.</li>
<li>Montajes como <em>El clan de los Pininis</em> o <em>Nuestra historia al sur</em>, que acercaron el flamenco familiar de Lebrija a los teatros.</li>
</ul>
<p>Murió en un accidente de tráfico el 26 de enero de 1997, cuando volvía a Lebrija.</p>
<p><em>Fuentes: libro «Arte Flamenco», capítulo «Los grandes maestros de la guitarra flamenca»; datos biográficos contrastados en enciclopedias y prensa.</em></p>',
NULL, 'BORRADOR', NULL
FROM categorias_articulos c WHERE c.slug = 'artistas';

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
