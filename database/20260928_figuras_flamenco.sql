-- Con Sabor Flamenco - Figuras del flamenco (fichas editoriales de artistas).
--
-- Migracion INCREMENTAL y NO DESTRUCTIVA. No borra ni renombra nada existente.
-- Idempotente: se puede ejecutar tantas veces como haga falta. Las fichas se
-- insertan con INSERT IGNORE sobre el slug, asi que una ficha ya existente
-- (y las correcciones hechas sobre ella) no se pisa.
--
--   php tools/run_migration.php database/20260928_figuras_flamenco.sql
--
-- Las mismas tablas se crean tambien desde db_bootstrap() (app/database.php).
--
-- Que es una "figura": la ficha de un artista del flamenco (vivo o no) que
-- escribe la redaccion de la web, NO un miembro. Solo la crea administracion.
-- Si el artista tiene ademas cuenta de miembro, miembro_id enlaza ambas.
--
--   figuras_flamenco    La ficha: identidad, fechas, textos, imagen y estado.
--   figura_disciplinas  Cante / baile / toque / percusion (tabla disciplinas,
--                       la misma que usan los miembros), para filtrar.
--   figura_fuentes      De donde sale cada dato (libro y paginas, web...).
--                       Sirve para saber que material ya esta procesado y
--                       para citar fuentes en la ficha publica.
--
-- Fechas: nacimiento_anio / fallecimiento_anio se rellenan siempre que se
-- sepa el ano; *_fecha solo cuando se conoce el dia exacto (efemerides).
-- Lo que no esta confirmado va a NULL y se anota en por_verificar.
--
-- Carga inicial: 10 guitarristas del capitulo "Los grandes maestros de la
-- guitarra flamenca" (Arte Flamenco, pp. 105-108), en BORRADOR. Texto propio.

-- SET NAMES: el fichero esta en UTF-8. Sin esto, el cliente mysql de consola
-- lo lee como latin1 y guarda las tildes y enes corrompidas.
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS figuras_flamenco (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(180) NOT NULL UNIQUE,
    nombre_artistico VARCHAR(160) NOT NULL,
    nombre_real VARCHAR(200) NULL,
    otros_nombres VARCHAR(255) NULL,
    rol VARCHAR(120) NULL,
    nacimiento_anio SMALLINT UNSIGNED NULL,
    nacimiento_fecha DATE NULL,
    nacimiento_lugar VARCHAR(160) NULL,
    nacimiento_provincia_id BIGINT UNSIGNED NULL,
    fallecimiento_anio SMALLINT UNSIGNED NULL,
    fallecimiento_fecha DATE NULL,
    fallecimiento_lugar VARCHAR(160) NULL,
    resumen VARCHAR(320) NULL,
    trayectoria MEDIUMTEXT NULL,
    aportaciones TEXT NULL,
    obra_destacada TEXT NULL,
    premios TEXT NULL,
    anecdotas TEXT NULL,
    imagen_path VARCHAR(255) NULL,
    imagen_credito VARCHAR(255) NULL,
    miembro_id BIGINT UNSIGNED NULL UNIQUE,
    por_verificar VARCHAR(500) NULL,
    estado ENUM('BORRADOR','REVISION','PUBLICADO','ARCHIVADO') NOT NULL DEFAULT 'BORRADOR',
    destacada BOOLEAN NOT NULL DEFAULT FALSE,
    vistas INT UNSIGNED NOT NULL DEFAULT 0,
    publicado_at TIMESTAMP NULL,
    creado_por_usuario_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_figuras_provincia FOREIGN KEY (nacimiento_provincia_id) REFERENCES provincias(id) ON DELETE SET NULL,
    CONSTRAINT fk_figuras_miembro FOREIGN KEY (miembro_id) REFERENCES miembros(id) ON DELETE SET NULL,
    CONSTRAINT fk_figuras_autor FOREIGN KEY (creado_por_usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_figuras_estado (estado, publicado_at),
    INDEX idx_figuras_provincia (nacimiento_provincia_id),
    FULLTEXT KEY ft_figuras_nombres (nombre_artistico, nombre_real, otros_nombres)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS figura_disciplinas (
    figura_id BIGINT UNSIGNED NOT NULL,
    disciplina_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (figura_id, disciplina_id),
    CONSTRAINT fk_figura_disciplinas_figura FOREIGN KEY (figura_id) REFERENCES figuras_flamenco(id) ON DELETE CASCADE,
    CONSTRAINT fk_figura_disciplinas_disciplina FOREIGN KEY (disciplina_id) REFERENCES disciplinas(id) ON DELETE CASCADE,
    INDEX idx_figura_disciplinas_disciplina (disciplina_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS figura_fuentes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    figura_id BIGINT UNSIGNED NOT NULL,
    fuente VARCHAR(255) NOT NULL,
    detalle VARCHAR(255) NULL,
    url VARCHAR(500) NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_figura_fuentes_figura FOREIGN KEY (figura_id) REFERENCES figuras_flamenco(id) ON DELETE CASCADE,
    INDEX idx_figura_fuentes_figura (figura_id),
    INDEX idx_figura_fuentes_fuente (fuente)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Sabicas
INSERT IGNORE INTO figuras_flamenco (slug, nombre_artistico, nombre_real, otros_nombres, rol,
    nacimiento_anio, nacimiento_fecha, nacimiento_lugar, nacimiento_provincia_id,
    fallecimiento_anio, fallecimiento_fecha, fallecimiento_lugar,
    resumen, trayectoria, aportaciones, obra_destacada, premios, anecdotas, por_verificar, estado)
VALUES ('sabicas', 'Sabicas', 'Agustín Castellón Campos', NULL, 'Guitarrista',
    1912, '1912-03-16', 'Pamplona', (SELECT id FROM provincias WHERE slug = 'navarra'),
    1990, '1990-04-14', 'Nueva York (Estados Unidos)',
    'El guitarrista navarro que llevó la guitarra flamenca solista a los escenarios de todo el mundo desde América. Sin su revolución técnica no se entiende a Paco de Lucía.',
    '<p>Empezó a tocar siendo muy niño y con siete años ya actuaba en público. Nunca estudió solfeo y presumía de no haber tenido maestros.</p><p>En 1936, con la Guerra Civil, salió de España junto a Carmen Amaya. Vivió en México y después en Nueva York, donde se asentó y desarrolló casi toda su carrera. No volvió a España hasta 1967.</p><p>Desde Estados Unidos grabó decenas de discos y dio conciertos por todo el mundo como guitarrista solista, algo casi inédito para un flamenco de su época.</p>',
    '<ul><li>Picado en todas las cuerdas, con una limpieza y velocidad nuevas.</li><li>Alzapúa con el pulgar como recurso protagonista.</li><li>Arpegios, trémolos y ligados de enorme dificultad.</li><li>Aires folclóricos de otras tradiciones hispanas incorporados al flamenco.</li></ul>',
    '<ul><li>Tres discos a dúo con Mario Escudero (1957-1959).</li><li><em>Flamenco Virtuoso</em> (1961).</li></ul>',
    NULL,
    '<p>Se atribuía el «ochenta por ciento» de lo que se tocaba en el mundo, aunque antes que él Ramón Montoya ya había dado conciertos en París.</p><p>Paco de Lucía contó que, hasta conocerle, creía que lo más alto era Niño Ricardo.</p>',
    NULL, 'BORRADOR');
INSERT IGNORE INTO figura_disciplinas (figura_id, disciplina_id)
SELECT fg.id, d.id FROM figuras_flamenco fg JOIN disciplinas d ON d.slug = 'toque' WHERE fg.slug = 'sabicas';
INSERT INTO figura_fuentes (figura_id, fuente, detalle, url)
SELECT fg.id, 'Arte Flamenco', 'Cap. Los grandes maestros de la guitarra flamenca, pp. 105-108', NULL FROM figuras_flamenco fg
WHERE fg.slug = 'sabicas' AND NOT EXISTS (
    SELECT 1 FROM figura_fuentes x WHERE x.figura_id = fg.id AND x.fuente = 'Arte Flamenco');
INSERT INTO figura_fuentes (figura_id, fuente, detalle, url)
SELECT fg.id, 'Wikipedia (inglés): Sabicas', NULL, 'https://en.wikipedia.org/wiki/Sabicas' FROM figuras_flamenco fg
WHERE fg.slug = 'sabicas' AND NOT EXISTS (
    SELECT 1 FROM figura_fuentes x WHERE x.figura_id = fg.id AND x.fuente = 'Wikipedia (inglés): Sabicas');

-- Serranito
INSERT IGNORE INTO figuras_flamenco (slug, nombre_artistico, nombre_real, otros_nombres, rol,
    nacimiento_anio, nacimiento_fecha, nacimiento_lugar, nacimiento_provincia_id,
    fallecimiento_anio, fallecimiento_fecha, fallecimiento_lugar,
    resumen, trayectoria, aportaciones, obra_destacada, premios, anecdotas, por_verificar, estado)
VALUES ('serranito', 'Serranito', 'Víctor Monge', 'Víctor Monge Serranito', 'Guitarrista concertista',
    1942, NULL, 'Madrid', (SELECT id FROM provincias WHERE slug = 'madrid'),
    NULL, NULL, NULL,
    'Madrileño, pasó por todos los oficios del toque antes de convertirse en uno de los grandes concertistas de la guitarra flamenca. Le llaman «el guitarrista de guitarristas».',
    '<p>Empezó a estudiar música con ocho años y con doce ya trabajaba con su hermano en el grupo familiar Los Serranos, de donde le viene el nombre artístico.</p><p>Acompañó al cante y al baile en tablaos y agrupaciones de todo tipo antes de dar el paso al concierto en solitario, cuando en España casi nadie lo hacía.</p><p>En 2021 emprendió su gira de despedida de los escenarios.</p>',
    '<p>Música compleja y muy elaborada, sin perder ternura. Figura clave de la generación entre Sabicas y Paco de Lucía.</p>',
    '<p>Una veintena de grabaciones a lo largo de más de sesenta años de carrera.</p>',
    '<ul><li>Medalla de Oro al Mérito en las Bellas Artes (2019).</li></ul>',
    NULL,
    'Fecha exacta de nacimiento.', 'BORRADOR');
INSERT IGNORE INTO figura_disciplinas (figura_id, disciplina_id)
SELECT fg.id, d.id FROM figuras_flamenco fg JOIN disciplinas d ON d.slug = 'toque' WHERE fg.slug = 'serranito';
INSERT INTO figura_fuentes (figura_id, fuente, detalle, url)
SELECT fg.id, 'Arte Flamenco', 'Cap. Los grandes maestros de la guitarra flamenca, pp. 105-108', NULL FROM figuras_flamenco fg
WHERE fg.slug = 'serranito' AND NOT EXISTS (
    SELECT 1 FROM figura_fuentes x WHERE x.figura_id = fg.id AND x.fuente = 'Arte Flamenco');
INSERT INTO figura_fuentes (figura_id, fuente, detalle, url)
SELECT fg.id, 'Suma Flamenca 2021 (Comunidad de Madrid)', NULL, 'https://www.madrid.org/sumaflamenca/2021/serranito.html' FROM figuras_flamenco fg
WHERE fg.slug = 'serranito' AND NOT EXISTS (
    SELECT 1 FROM figura_fuentes x WHERE x.figura_id = fg.id AND x.fuente = 'Suma Flamenca 2021 (Comunidad de Madrid)');

-- Manuel Cano
INSERT IGNORE INTO figuras_flamenco (slug, nombre_artistico, nombre_real, otros_nombres, rol,
    nacimiento_anio, nacimiento_fecha, nacimiento_lugar, nacimiento_provincia_id,
    fallecimiento_anio, fallecimiento_fecha, fallecimiento_lugar,
    resumen, trayectoria, aportaciones, obra_destacada, premios, anecdotas, por_verificar, estado)
VALUES ('manuel-cano', 'Manuel Cano', 'Manuel Cano Tamayo', NULL, 'Guitarrista, catedrático e investigador',
    NULL, NULL, 'Granada', (SELECT id FROM provincias WHERE slug = 'granada'),
    1990, '1990-01-12', 'Granada',
    'Concertista granadino que dedicó su vida a dignificar la guitarra flamenca: la llevó al conservatorio y escribió un libro pionero sobre su historia.',
    '<p>Creció en una casa de músicos: su padre tocaba la guitarra y su madre el laúd. Aun así, no se dedicó profesionalmente al toque hasta pasados los treinta años.</p><p>Ocupó la cátedra de guitarra flamenca del Conservatorio Superior de Córdoba y combinó la enseñanza con los conciertos y la composición.</p>',
    '<p>Defendió que la guitarra flamenca tuviera la misma categoría que cualquier otra música. Solía decir que Bach o Scarlatti hicieron música magnífica, pero que en el flamenco había algo más.</p>',
    '<ul><li>Una veintena de discos, entre ellos un homenaje a la guitarra de Ramón Montoya.</li><li>Libro <em>La guitarra. Historia, estudios y aportaciones al flamenco</em> (1986).</li></ul>',
    '<ul><li>Premio de guitarra del Concurso Nacional de Arte Flamenco de Córdoba (1965).</li><li>Medalla de Andalucía, a título póstumo (1992).</li></ul>',
    NULL,
    'Año de nacimiento: el libro dice 1926 y otras fuentes 1925.', 'BORRADOR');
INSERT IGNORE INTO figura_disciplinas (figura_id, disciplina_id)
SELECT fg.id, d.id FROM figuras_flamenco fg JOIN disciplinas d ON d.slug = 'toque' WHERE fg.slug = 'manuel-cano';
INSERT INTO figura_fuentes (figura_id, fuente, detalle, url)
SELECT fg.id, 'Arte Flamenco', 'Cap. Los grandes maestros de la guitarra flamenca, pp. 105-108', NULL FROM figuras_flamenco fg
WHERE fg.slug = 'manuel-cano' AND NOT EXISTS (
    SELECT 1 FROM figura_fuentes x WHERE x.figura_id = fg.id AND x.fuente = 'Arte Flamenco');
INSERT INTO figura_fuentes (figura_id, fuente, detalle, url)
SELECT fg.id, 'Wikipedia (inglés): Manuel Cano Tamayo', NULL, 'https://en.wikipedia.org/wiki/Manuel_Cano_Tamayo' FROM figuras_flamenco fg
WHERE fg.slug = 'manuel-cano' AND NOT EXISTS (
    SELECT 1 FROM figura_fuentes x WHERE x.figura_id = fg.id AND x.fuente = 'Wikipedia (inglés): Manuel Cano Tamayo');

-- Andrés Batista
INSERT IGNORE INTO figuras_flamenco (slug, nombre_artistico, nombre_real, otros_nombres, rol,
    nacimiento_anio, nacimiento_fecha, nacimiento_lugar, nacimiento_provincia_id,
    fallecimiento_anio, fallecimiento_fecha, fallecimiento_lugar,
    resumen, trayectoria, aportaciones, obra_destacada, premios, anecdotas, por_verificar, estado)
VALUES ('andres-batista', 'Andrés Batista', NULL, NULL, 'Guitarrista y pedagogo',
    1937, '1937-10-12', 'Barcelona', (SELECT id FROM provincias WHERE slug = 'barcelona'),
    NULL, NULL, NULL,
    'Guitarrista barcelonés que acompañó a Carmen Amaya en sus últimos años y dedicó después su carrera a enseñar con métodos progresivos.',
    '<p>Se formó en guitarra clásica y flamenca. En lo flamenco tuvo como referente a Miguel Borrull, con quien trabajó en los tablaos barceloneses.</p><p>Fue guitarrista de Carmen Amaya en sus últimas giras por Estados Unidos y Sudamérica y la acompañó en su última actuación. También trabajó en compañías de teatro flamenco como la de Vicente Escudero.</p><p>Después se centró en la enseñanza y abrió academia en Madrid.</p>',
    '<p>Métodos y partituras pensados para aprender la guitarra flamenca de forma ordenada y progresiva. Defensor del guitarrista como músico con años de estudio, no como oficio al margen.</p>',
    '<ul><li><em>Método de guitarra flamenca</em> (1979).</li><li>Varios volúmenes de <em>Apuntes flamencos</em>.</li></ul>',
    NULL,
    '<p>Estuvo presente en el rodaje de <em>Los Tarantos</em> (Barcelona, 1963), poco antes de la muerte de Carmen Amaya.</p>',
    'Si vive o fecha de fallecimiento.', 'BORRADOR');
INSERT IGNORE INTO figura_disciplinas (figura_id, disciplina_id)
SELECT fg.id, d.id FROM figuras_flamenco fg JOIN disciplinas d ON d.slug = 'toque' WHERE fg.slug = 'andres-batista';
INSERT INTO figura_fuentes (figura_id, fuente, detalle, url)
SELECT fg.id, 'Arte Flamenco', 'Cap. Los grandes maestros de la guitarra flamenca, pp. 105-108', NULL FROM figuras_flamenco fg
WHERE fg.slug = 'andres-batista' AND NOT EXISTS (
    SELECT 1 FROM figura_fuentes x WHERE x.figura_id = fg.id AND x.fuente = 'Arte Flamenco');
INSERT INTO figura_fuentes (figura_id, fuente, detalle, url)
SELECT fg.id, 'Flamenco Barcelona: Andrés Batista', NULL, 'https://www.flamencobarcelona.org/andres-batista' FROM figuras_flamenco fg
WHERE fg.slug = 'andres-batista' AND NOT EXISTS (
    SELECT 1 FROM figura_fuentes x WHERE x.figura_id = fg.id AND x.fuente = 'Flamenco Barcelona: Andrés Batista');

-- Luis Maravilla
INSERT IGNORE INTO figuras_flamenco (slug, nombre_artistico, nombre_real, otros_nombres, rol,
    nacimiento_anio, nacimiento_fecha, nacimiento_lugar, nacimiento_provincia_id,
    fallecimiento_anio, fallecimiento_fecha, fallecimiento_lugar,
    resumen, trayectoria, aportaciones, obra_destacada, premios, anecdotas, por_verificar, estado)
VALUES ('luis-maravilla', 'Luis Maravilla', 'Luis López Tejera', NULL, 'Guitarrista',
    1914, '1914-06-01', 'Sevilla', (SELECT id FROM provincias WHERE slug = 'sevilla'),
    2000, '2000-07-10', NULL,
    'Guitarrista sevillano que empezó de niño en el colmao Villa Rosa de Madrid y acabó en las grandes compañías de baile de su época.',
    '<p>Hijo del cantaor Niño de las Marianas, cambió pronto la voz por la guitarra. Siendo todavía un niño ya tocaba en el colmao madrileño Villa Rosa.</p><p>En los años treinta recorrió América y durante la Guerra Civil se estableció en Francia. De vuelta en España trabajó en formaciones teatrales, sobre todo con Pilar López, y con artistas como Estrellita Castro.</p><p>En 1957 dejó los escenarios para dedicarse a la enseñanza.</p>',
    NULL,
    NULL,
    NULL,
    NULL,
    'Lugar de fallecimiento.', 'BORRADOR');
INSERT IGNORE INTO figura_disciplinas (figura_id, disciplina_id)
SELECT fg.id, d.id FROM figuras_flamenco fg JOIN disciplinas d ON d.slug = 'toque' WHERE fg.slug = 'luis-maravilla';
INSERT INTO figura_fuentes (figura_id, fuente, detalle, url)
SELECT fg.id, 'Arte Flamenco', 'Cap. Los grandes maestros de la guitarra flamenca, pp. 105-108', NULL FROM figuras_flamenco fg
WHERE fg.slug = 'luis-maravilla' AND NOT EXISTS (
    SELECT 1 FROM figura_fuentes x WHERE x.figura_id = fg.id AND x.fuente = 'Arte Flamenco');
INSERT INTO figura_fuentes (figura_id, fuente, detalle, url)
SELECT fg.id, 'Wikipedia (inglés): Luis Maravilla', NULL, 'https://en.wikipedia.org/wiki/Luis_Maravilla' FROM figuras_flamenco fg
WHERE fg.slug = 'luis-maravilla' AND NOT EXISTS (
    SELECT 1 FROM figura_fuentes x WHERE x.figura_id = fg.id AND x.fuente = 'Wikipedia (inglés): Luis Maravilla');

-- Mario Escudero
INSERT IGNORE INTO figuras_flamenco (slug, nombre_artistico, nombre_real, otros_nombres, rol,
    nacimiento_anio, nacimiento_fecha, nacimiento_lugar, nacimiento_provincia_id,
    fallecimiento_anio, fallecimiento_fecha, fallecimiento_lugar,
    resumen, trayectoria, aportaciones, obra_destacada, premios, anecdotas, por_verificar, estado)
VALUES ('mario-escudero', 'Mario Escudero', NULL, 'El Niño de Alicante', 'Guitarrista concertista',
    1928, NULL, 'Alicante', (SELECT id FROM provincias WHERE slug = 'alicante'),
    2004, '2004-11-19', 'Miami (Estados Unidos)',
    'El alicantino que siguió el camino americano de Sabicas, triunfó en el Carnegie Hall y grabó con él tres discos a dúo.',
    '<p>Empezó a actuar muy joven y a comienzos de los años cincuenta se trasladó a Estados Unidos, donde hizo el grueso de su carrera.</p><p>Su concierto de 1955 en el Carnegie Hall de Nueva York le abrió las puertas del público norteamericano. Trabajó con figuras como Carmen Amaya.</p>',
    '<p>Contribuyó a asentar la guitarra flamenca como instrumento de concierto.</p>',
    '<ul><li>Tres discos a dúo con Sabicas (1957-1959).</li><li>Más de treinta discos de larga duración.</li><li>Más de 250 composiciones propias.</li></ul>',
    NULL,
    '<p>Tocó en la Casa Blanca durante la presidencia de John F. Kennedy. Ramón Montoya llegó a llamarle el mejor guitarrista de la nueva generación.</p>',
    'Fecha exacta de nacimiento.', 'BORRADOR');
INSERT IGNORE INTO figura_disciplinas (figura_id, disciplina_id)
SELECT fg.id, d.id FROM figuras_flamenco fg JOIN disciplinas d ON d.slug = 'toque' WHERE fg.slug = 'mario-escudero';
INSERT INTO figura_fuentes (figura_id, fuente, detalle, url)
SELECT fg.id, 'Arte Flamenco', 'Cap. Los grandes maestros de la guitarra flamenca, pp. 105-108', NULL FROM figuras_flamenco fg
WHERE fg.slug = 'mario-escudero' AND NOT EXISTS (
    SELECT 1 FROM figura_fuentes x WHERE x.figura_id = fg.id AND x.fuente = 'Arte Flamenco');
INSERT INTO figura_fuentes (figura_id, fuente, detalle, url)
SELECT fg.id, 'Wikipedia (inglés): Mario Escudero', NULL, 'https://en.wikipedia.org/wiki/Mario_Escudero' FROM figuras_flamenco fg
WHERE fg.slug = 'mario-escudero' AND NOT EXISTS (
    SELECT 1 FROM figura_fuentes x WHERE x.figura_id = fg.id AND x.fuente = 'Wikipedia (inglés): Mario Escudero');

-- Juan Habichuela
INSERT IGNORE INTO figuras_flamenco (slug, nombre_artistico, nombre_real, otros_nombres, rol,
    nacimiento_anio, nacimiento_fecha, nacimiento_lugar, nacimiento_provincia_id,
    fallecimiento_anio, fallecimiento_fecha, fallecimiento_lugar,
    resumen, trayectoria, aportaciones, obra_destacada, premios, anecdotas, por_verificar, estado)
VALUES ('juan-habichuela', 'Juan Habichuela', 'Juan Carmona Carmona', NULL, 'Guitarrista de acompañamiento',
    1933, NULL, 'Granada', (SELECT id FROM provincias WHERE slug = 'granada'),
    2016, '2016-06-30', 'Madrid',
    'El mayor de la saga granadina de los Habichuela, guitarrista de confianza de voces como Juanito Valderrama y Enrique Morente.',
    '<p>Empezó como bailaor y pasó a la guitarra de la mano de su padre. Nieto de Habichuela el Viejo, fundador de la saga.</p><p>Se convirtió en uno de los acompañantes más respetados del cante, al lado de Juanito Valderrama y Enrique Morente.</p>',
    '<p>Referente del toque de acompañamiento. Padre de Juan José y Antonio Carmona, fundadores de Ketama.</p>',
    NULL,
    '<ul><li>Premio a la trayectoria de los Grammy Latinos (2012).</li></ul>',
    NULL,
    'Fecha exacta de nacimiento.', 'BORRADOR');
INSERT IGNORE INTO figura_disciplinas (figura_id, disciplina_id)
SELECT fg.id, d.id FROM figuras_flamenco fg JOIN disciplinas d ON d.slug = 'toque' WHERE fg.slug = 'juan-habichuela';
INSERT INTO figura_fuentes (figura_id, fuente, detalle, url)
SELECT fg.id, 'Arte Flamenco', 'Cap. Los grandes maestros de la guitarra flamenca, pp. 105-108', NULL FROM figuras_flamenco fg
WHERE fg.slug = 'juan-habichuela' AND NOT EXISTS (
    SELECT 1 FROM figura_fuentes x WHERE x.figura_id = fg.id AND x.fuente = 'Arte Flamenco');
INSERT INTO figura_fuentes (figura_id, fuente, detalle, url)
SELECT fg.id, 'Wikipedia (inglés): Juan Habichuela', NULL, 'https://en.wikipedia.org/wiki/Juan_Habichuela' FROM figuras_flamenco fg
WHERE fg.slug = 'juan-habichuela' AND NOT EXISTS (
    SELECT 1 FROM figura_fuentes x WHERE x.figura_id = fg.id AND x.fuente = 'Wikipedia (inglés): Juan Habichuela');

-- Pepe Habichuela
INSERT IGNORE INTO figuras_flamenco (slug, nombre_artistico, nombre_real, otros_nombres, rol,
    nacimiento_anio, nacimiento_fecha, nacimiento_lugar, nacimiento_provincia_id,
    fallecimiento_anio, fallecimiento_fecha, fallecimiento_lugar,
    resumen, trayectoria, aportaciones, obra_destacada, premios, anecdotas, por_verificar, estado)
VALUES ('pepe-habichuela', 'Pepe Habichuela', NULL, NULL, 'Guitarrista',
    1944, NULL, 'Granada', (SELECT id FROM provincias WHERE slug = 'granada'),
    2026, '2026-08-04', 'Madrid',
    'Guitarrista granadino que formó con Enrique Morente una pareja histórica y abrió el flamenco al jazz y a la música de la India.',
    '<p>Hermano de Juan Habichuela, desde mediados de los setenta formó con Enrique Morente una de las parejas más fecundas del flamenco.</p><p>Construyó una de las carreras más internacionales del toque, con el apoyo de su hijo, el guitarrista Josemi Carmona.</p>',
    '<p>Llevó la guitarra flamenca al encuentro con otras músicas: jazz, músicas del mundo y la tradición de la India.</p>',
    '<ul><li><em>Yerbabuena</em> (2001), con la orquesta Bollywood Strings.</li><li>Colaboraciones con Don Cherry, Jaco Pastorius y Dave Holland.</li></ul>',
    NULL,
    NULL,
    'Nombre real completo y fecha exacta de nacimiento.', 'BORRADOR');
INSERT IGNORE INTO figura_disciplinas (figura_id, disciplina_id)
SELECT fg.id, d.id FROM figuras_flamenco fg JOIN disciplinas d ON d.slug = 'toque' WHERE fg.slug = 'pepe-habichuela';
INSERT INTO figura_fuentes (figura_id, fuente, detalle, url)
SELECT fg.id, 'Arte Flamenco', 'Cap. Los grandes maestros de la guitarra flamenca, pp. 105-108', NULL FROM figuras_flamenco fg
WHERE fg.slug = 'pepe-habichuela' AND NOT EXISTS (
    SELECT 1 FROM figura_fuentes x WHERE x.figura_id = fg.id AND x.fuente = 'Arte Flamenco');
INSERT INTO figura_fuentes (figura_id, fuente, detalle, url)
SELECT fg.id, 'elDiario.es: muere Pepe Habichuela', NULL, 'https://www.eldiario.es/cultura/muere-guitarrista-flamenco-pepe-habichuela-82-anos_1_13428665.html' FROM figuras_flamenco fg
WHERE fg.slug = 'pepe-habichuela' AND NOT EXISTS (
    SELECT 1 FROM figura_fuentes x WHERE x.figura_id = fg.id AND x.fuente = 'elDiario.es: muere Pepe Habichuela');

-- Enrique de Melchor
INSERT IGNORE INTO figuras_flamenco (slug, nombre_artistico, nombre_real, otros_nombres, rol,
    nacimiento_anio, nacimiento_fecha, nacimiento_lugar, nacimiento_provincia_id,
    fallecimiento_anio, fallecimiento_fecha, fallecimiento_lugar,
    resumen, trayectoria, aportaciones, obra_destacada, premios, anecdotas, por_verificar, estado)
VALUES ('enrique-de-melchor', 'Enrique de Melchor', 'Enrique Jiménez Ramírez', NULL, 'Guitarrista',
    1950, '1950-07-15', 'Marchena', (SELECT id FROM provincias WHERE slug = 'sevilla'),
    2012, '2012-01-03', 'Madrid',
    'Hijo de Melchor de Marchena, acompañó a Camarón, Mairena o Morente y giró dos temporadas con Paco de Lucía.',
    '<p>Aprendió el toque en casa, con su padre, el guitarrista Melchor de Marchena, cuyo nombre adoptó como apellido artístico.</p><p>Fue un acompañante muy buscado: Antonio Mairena, El Lebrijano, José Menese, Enrique Morente, Rocío Jurado o Camarón de la Isla. Durante dos temporadas formó parte del grupo de Paco de Lucía.</p>',
    NULL,
    '<ul><li>Ocho discos en solitario entre 1977 y 2005, como <em>Sugerencias</em>, <em>Cuchichí</em> o <em>Herencia gitana</em>.</li><li>Películas: <em>La Carmen</em> (1975) y <em>Una pasión singular</em> (2002).</li></ul>',
    NULL,
    NULL,
    NULL, 'BORRADOR');
INSERT IGNORE INTO figura_disciplinas (figura_id, disciplina_id)
SELECT fg.id, d.id FROM figuras_flamenco fg JOIN disciplinas d ON d.slug = 'toque' WHERE fg.slug = 'enrique-de-melchor';
INSERT INTO figura_fuentes (figura_id, fuente, detalle, url)
SELECT fg.id, 'Arte Flamenco', 'Cap. Los grandes maestros de la guitarra flamenca, pp. 105-108', NULL FROM figuras_flamenco fg
WHERE fg.slug = 'enrique-de-melchor' AND NOT EXISTS (
    SELECT 1 FROM figura_fuentes x WHERE x.figura_id = fg.id AND x.fuente = 'Arte Flamenco');
INSERT INTO figura_fuentes (figura_id, fuente, detalle, url)
SELECT fg.id, 'Wikipedia: Enrique de Melchor', NULL, 'https://es.wikipedia.org/wiki/Enrique_de_Melchor' FROM figuras_flamenco fg
WHERE fg.slug = 'enrique-de-melchor' AND NOT EXISTS (
    SELECT 1 FROM figura_fuentes x WHERE x.figura_id = fg.id AND x.fuente = 'Wikipedia: Enrique de Melchor');

-- Pedro Bacán
INSERT IGNORE INTO figuras_flamenco (slug, nombre_artistico, nombre_real, otros_nombres, rol,
    nacimiento_anio, nacimiento_fecha, nacimiento_lugar, nacimiento_provincia_id,
    fallecimiento_anio, fallecimiento_fecha, fallecimiento_lugar,
    resumen, trayectoria, aportaciones, obra_destacada, premios, anecdotas, por_verificar, estado)
VALUES ('pedro-bacan', 'Pedro Bacán', NULL, NULL, 'Guitarrista',
    1951, '1951-02-12', 'Lebrija', (SELECT id FROM provincias WHERE slug = 'sevilla'),
    1997, '1997-01-26', 'Los Palacios y Villafranca',
    'Guitarrista de Lebrija, de una gran familia gitana del cante, que llevó el flamenco más hondo a Francia y a medio mundo.',
    '<p>Aprendió de forma casi autodidacta en las reuniones familiares de Lebrija, donde el cante formaba parte de la vida diaria.</p><p>Tuvo gran prestigio fuera de España, sobre todo en Francia: París llegó a ser su segunda casa.</p>',
    '<p>Toque sobrio y profundo, muy ligado al cante de su tierra.</p>',
    '<ul><li>Disco en solitario <em>Aluricán</em>.</li><li>Montajes como <em>El clan de los Pininis</em> y <em>Nuestra historia al sur</em>.</li></ul>',
    NULL,
    NULL,
    'Nombre real completo.', 'BORRADOR');
INSERT IGNORE INTO figura_disciplinas (figura_id, disciplina_id)
SELECT fg.id, d.id FROM figuras_flamenco fg JOIN disciplinas d ON d.slug = 'toque' WHERE fg.slug = 'pedro-bacan';
INSERT INTO figura_fuentes (figura_id, fuente, detalle, url)
SELECT fg.id, 'Arte Flamenco', 'Cap. Los grandes maestros de la guitarra flamenca, pp. 105-108', NULL FROM figuras_flamenco fg
WHERE fg.slug = 'pedro-bacan' AND NOT EXISTS (
    SELECT 1 FROM figura_fuentes x WHERE x.figura_id = fg.id AND x.fuente = 'Arte Flamenco');
INSERT INTO figura_fuentes (figura_id, fuente, detalle, url)
SELECT fg.id, 'Wikipedia: Pedro Bacán', NULL, 'https://es.wikipedia.org/wiki/Pedro_Bac%C3%A1n' FROM figuras_flamenco fg
WHERE fg.slug = 'pedro-bacan' AND NOT EXISTS (
    SELECT 1 FROM figura_fuentes x WHERE x.figura_id = fg.id AND x.fuente = 'Wikipedia: Pedro Bacán');
