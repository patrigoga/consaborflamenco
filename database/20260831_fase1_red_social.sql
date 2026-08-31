-- Con Sabor Flamenco - Fase 1 red social: perfil de artista, eventos, agenda y puntos.
--
-- Migracion INCREMENTAL y NO DESTRUCTIVA. No borra ni renombra nada existente.
-- Todas las sentencias son idempotentes: el fichero se puede ejecutar tantas
-- veces como haga falta sobre una base con datos.
--
--   php tools/run_migration.php database/20260831_fase1_red_social.sql
--
-- Las mismas tablas se crean tambien desde db_bootstrap() (app/database.php),
-- asi que en local basta con abrir cualquier pagina. Este fichero existe para
-- poder aplicarlo a mano en produccion.


-- ---------------------------------------------------------------------------
-- 1. Geografia normalizada: provincias (ya existia) + municipios.
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS provincias (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(120) NOT NULL,
    slug VARCHAR(140) NOT NULL UNIQUE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Las 52 provincias. Coinciden con la lista que ya usa assets/js/advertising.js
-- para la publicidad por provincia, para que ambas hablen del mismo territorio.
INSERT IGNORE INTO provincias (nombre, slug) VALUES
('A Coruña', 'a-coruna'), ('Álava', 'alava'), ('Albacete', 'albacete'),
('Alicante', 'alicante'), ('Almería', 'almeria'), ('Asturias', 'asturias'),
('Ávila', 'avila'), ('Badajoz', 'badajoz'), ('Barcelona', 'barcelona'),
('Bizkaia', 'bizkaia'), ('Burgos', 'burgos'), ('Cáceres', 'caceres'),
('Cádiz', 'cadiz'), ('Cantabria', 'cantabria'), ('Castellón', 'castellon'),
('Ceuta', 'ceuta'), ('Ciudad Real', 'ciudad-real'), ('Córdoba', 'cordoba'),
('Cuenca', 'cuenca'), ('Gipuzkoa', 'gipuzkoa'), ('Girona', 'girona'),
('Granada', 'granada'), ('Guadalajara', 'guadalajara'), ('Huelva', 'huelva'),
('Huesca', 'huesca'), ('Illes Balears', 'illes-balears'), ('Jaén', 'jaen'),
('La Rioja', 'la-rioja'), ('Las Palmas', 'las-palmas'), ('León', 'leon'),
('Lleida', 'lleida'), ('Lugo', 'lugo'), ('Madrid', 'madrid'),
('Málaga', 'malaga'), ('Melilla', 'melilla'), ('Murcia', 'murcia'),
('Navarra', 'navarra'), ('Ourense', 'ourense'), ('Palencia', 'palencia'),
('Pontevedra', 'pontevedra'), ('Salamanca', 'salamanca'),
('Santa Cruz de Tenerife', 'santa-cruz-de-tenerife'), ('Segovia', 'segovia'),
('Sevilla', 'sevilla'), ('Soria', 'soria'), ('Tarragona', 'tarragona'),
('Teruel', 'teruel'), ('Toledo', 'toledo'), ('Valencia', 'valencia'),
('Valladolid', 'valladolid'), ('Zamora', 'zamora'), ('Zaragoza', 'zaragoza');

-- Los municipios no se precargan: se dan de alta segun se usan (find-or-create
-- al guardar un evento o un perfil). El slug es unico dentro de la provincia,
-- no a nivel nacional, porque hay municipios homonimos en provincias distintas.
CREATE TABLE IF NOT EXISTS municipios (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    provincia_id BIGINT UNSIGNED NOT NULL,
    nombre VARCHAR(160) NOT NULL,
    slug VARCHAR(180) NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_municipios_provincia FOREIGN KEY (provincia_id) REFERENCES provincias(id) ON DELETE CASCADE,
    UNIQUE KEY uq_municipios_provincia_slug (provincia_id, slug),
    INDEX idx_municipios_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------------
-- 2. Disciplinas. Ya se definieron en 20260718_disciplinas.sql; se repiten aqui
--    porque hay entornos donde aquella migracion no llego a ejecutarse.
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS disciplinas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(80) NOT NULL UNIQUE,
    nombre VARCHAR(120) NOT NULL,
    estado ENUM('ACTIVA','INACTIVA') NOT NULL DEFAULT 'ACTIVA',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO disciplinas (slug, nombre) VALUES
('baile', 'Baile'),
('cante', 'Cante'),
('toque', 'Toque'),
('percusion', 'Percusión');

-- Relacion N:M: un artista puede pertenecer a varias disciplinas.
CREATE TABLE IF NOT EXISTS miembro_disciplinas (
    miembro_id BIGINT UNSIGNED NOT NULL,
    disciplina_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (miembro_id, disciplina_id),
    CONSTRAINT fk_miembro_disciplinas_miembro FOREIGN KEY (miembro_id) REFERENCES miembros(id) ON DELETE CASCADE,
    CONSTRAINT fk_miembro_disciplinas_disciplina FOREIGN KEY (disciplina_id) REFERENCES disciplinas(id) ON DELETE CASCADE,
    INDEX idx_miembro_disciplinas_disciplina (disciplina_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------------
-- 3. Sistema de puntos.
--
--    puntos_movimientos es la fuente de verdad (libro mayor, solo se anade).
--    puntos_saldos es el saldo materializado: se lee con SELECT ... FOR UPDATE
--    y se escribe SIEMPRE dentro de la misma transaccion que inserta el
--    movimiento, asi que no puede desincronizarse.
--
--    saldo es UNSIGNED a proposito: aunque la comprobacion de saldo suficiente
--    se hace en PHP, la propia columna impide fisicamente un saldo negativo.
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS puntos_saldos (
    usuario_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
    saldo INT UNSIGNED NOT NULL DEFAULT 0,
    total_ingresado INT UNSIGNED NOT NULL DEFAULT 0,
    total_gastado INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_puntos_saldos_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS puntos_movimientos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id BIGINT UNSIGNED NOT NULL,
    puntos INT NOT NULL,
    tipo ENUM('INICIAL','COMPRA','CONSUMO','DEVOLUCION','PROMOCION','ENLACE_SOCIAL','ADMINISTRACION','PROMOCIONAL') NOT NULL,
    concepto VARCHAR(190) NOT NULL,
    referencia_tipo VARCHAR(40) NULL,
    referencia_id BIGINT UNSIGNED NULL,
    saldo_posterior INT UNSIGNED NOT NULL,
    pago_id BIGINT UNSIGNED NULL,
    -- Permite reintentar seeds y webhooks de pago sin duplicar movimientos.
    clave_idempotencia VARCHAR(120) NULL UNIQUE,
    creado_por_usuario_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_puntos_mov_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT fk_puntos_mov_pago FOREIGN KEY (pago_id) REFERENCES pagos_stripe(id) ON DELETE SET NULL,
    CONSTRAINT fk_puntos_mov_actor FOREIGN KEY (creado_por_usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_puntos_mov_usuario_fecha (usuario_id, created_at),
    INDEX idx_puntos_mov_referencia (referencia_tipo, referencia_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------------
-- 4. Eventos.
--
--    Se guardan a la vez el id normalizado (provincia_id / municipio_id) y el
--    texto (provincia_texto / municipio_texto). El id es para filtrar; el texto
--    es la foto de lo que escribio el artista y sobrevive aunque la ficha
--    geografica cambie. Nunca se muestra el texto si hay id.
--
--    Borrado: soft delete con deleted_at. No se borra nunca una fila.
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS eventos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    miembro_id BIGINT UNSIGNED NOT NULL,
    usuario_id BIGINT UNSIGNED NOT NULL,
    titulo VARCHAR(200) NOT NULL,
    slug VARCHAR(220) NOT NULL UNIQUE,
    descripcion TEXT NULL,
    imagen_path VARCHAR(255) NULL,
    video_url VARCHAR(255) NULL,
    fecha DATE NOT NULL,
    hora TIME NULL,
    fecha_fin DATE NULL,
    lugar VARCHAR(190) NULL,
    direccion VARCHAR(255) NULL,
    provincia_id BIGINT UNSIGNED NULL,
    municipio_id BIGINT UNSIGNED NULL,
    provincia_texto VARCHAR(120) NULL,
    municipio_texto VARCHAR(160) NULL,
    enlace_url VARCHAR(255) NULL,
    estado ENUM('BORRADOR','PUBLICADO','CANCELADO','ARCHIVADO') NOT NULL DEFAULT 'PUBLICADO',
    promocionado BOOLEAN NOT NULL DEFAULT FALSE,
    promocionado_at DATETIME NULL,
    promocion_expira_at DATETIME NULL,
    -- Campos preparados para fases posteriores. Sin interfaz todavia.
    precio_centimos INT UNSIGNED NULL,
    entradas_url VARCHAR(255) NULL,
    categoria VARCHAR(80) NULL,
    latitud DECIMAL(10,7) NULL,
    longitud DECIMAL(10,7) NULL,
    vistas INT UNSIGNED NOT NULL DEFAULT 0,
    deleted_at DATETIME NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_eventos_miembro FOREIGN KEY (miembro_id) REFERENCES miembros(id) ON DELETE CASCADE,
    CONSTRAINT fk_eventos_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT fk_eventos_provincia FOREIGN KEY (provincia_id) REFERENCES provincias(id) ON DELETE SET NULL,
    CONSTRAINT fk_eventos_municipio FOREIGN KEY (municipio_id) REFERENCES municipios(id) ON DELETE SET NULL,
    -- Indice de la agenda publica: estado + no borrado + orden cronologico.
    INDEX idx_eventos_agenda (estado, deleted_at, fecha, hora),
    INDEX idx_eventos_destacados (promocionado, fecha),
    INDEX idx_eventos_miembro_fecha (miembro_id, fecha),
    INDEX idx_eventos_provincia_fecha (provincia_id, fecha),
    INDEX idx_eventos_municipio_fecha (municipio_id, fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------------
-- 5. Redes sociales del miembro.
--
--    `red` es VARCHAR y no ENUM para poder anadir redes nuevas sin ALTER TABLE.
--    Los valores validos los decide csf_social_networks() en PHP.
--
--    visible        -> se muestra en el perfil publico (gratis, siempre)
--    enlace_activo  -> ademas es clicable (el primero gratis, los demas 2 puntos)
--
--    Una vez activado un enlace NO se desactiva ni se reembolsa: coste_puntos y
--    activado_at quedan como registro permanente de la operacion.
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS miembro_redes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    miembro_id BIGINT UNSIGNED NOT NULL,
    red VARCHAR(30) NOT NULL,
    url VARCHAR(255) NULL,
    handle VARCHAR(120) NULL,
    visible BOOLEAN NOT NULL DEFAULT TRUE,
    enlace_activo BOOLEAN NOT NULL DEFAULT FALSE,
    activado_at DATETIME NULL,
    coste_puntos SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    movimiento_id BIGINT UNSIGNED NULL,
    orden SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_miembro_redes_miembro FOREIGN KEY (miembro_id) REFERENCES miembros(id) ON DELETE CASCADE,
    CONSTRAINT fk_miembro_redes_movimiento FOREIGN KEY (movimiento_id) REFERENCES puntos_movimientos(id) ON DELETE SET NULL,
    UNIQUE KEY uq_miembro_redes (miembro_id, red),
    INDEX idx_miembro_redes_activas (miembro_id, enlace_activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------------
-- 6. Auditoria de operaciones importantes.
--    Alta/edicion/baja de eventos, promociones, consumo de puntos y activacion
--    de enlaces. Solo se anade; nunca se actualiza ni se borra.
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS registro_actividad (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id BIGINT UNSIGNED NULL,
    entidad VARCHAR(40) NOT NULL,
    entidad_id BIGINT UNSIGNED NULL,
    accion VARCHAR(40) NOT NULL,
    detalle_json TEXT NULL,
    ip VARCHAR(45) NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_registro_actividad_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_registro_entidad (entidad, entidad_id),
    INDEX idx_registro_usuario_fecha (usuario_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------------
-- 7. Columnas nuevas en tablas existentes.
--
--    Se anaden solo si faltan, comprobandolo contra information_schema, para que
--    el fichero se pueda reejecutar. Todas admiten NULL: ninguna fila existente
--    queda invalida y ningun codigo actual deja de funcionar.
-- ---------------------------------------------------------------------------

SET @sql := (SELECT IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'miembros' AND COLUMN_NAME = 'provincia_id') > 0,
    'SELECT "miembros.provincia_id ya existe"',
    'ALTER TABLE miembros ADD COLUMN provincia_id BIGINT UNSIGNED NULL AFTER provincia_texto'
));
PREPARE csf_stmt FROM @sql; EXECUTE csf_stmt; DEALLOCATE PREPARE csf_stmt;

SET @sql := (SELECT IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'miembros' AND COLUMN_NAME = 'municipio_id') > 0,
    'SELECT "miembros.municipio_id ya existe"',
    'ALTER TABLE miembros ADD COLUMN municipio_id BIGINT UNSIGNED NULL AFTER provincia_id'
));
PREPARE csf_stmt FROM @sql; EXECUTE csf_stmt; DEALLOCATE PREPARE csf_stmt;

SET @sql := (SELECT IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'miembros' AND INDEX_NAME = 'idx_miembros_geo') > 0,
    'SELECT "idx_miembros_geo ya existe"',
    'ALTER TABLE miembros ADD INDEX idx_miembros_geo (provincia_id, municipio_id)'
));
PREPARE csf_stmt FROM @sql; EXECUTE csf_stmt; DEALLOCATE PREPARE csf_stmt;
