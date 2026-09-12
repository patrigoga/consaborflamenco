-- Con Sabor Flamenco - Mega agenda, bloque 3: reservas simples de tablao.
--
-- Migracion INCREMENTAL y NO DESTRUCTIVA. No borra ni renombra nada existente.
-- Todas las sentencias son idempotentes: el fichero se puede ejecutar tantas
-- veces como haga falta sobre una base con datos.
--
--   php tools/run_migration.php database/20260912_tablao_reservas.sql
--
-- Las mismas tablas/columnas se crean tambien desde db_bootstrap()
-- (app/database.php), asi que en local basta con abrir cualquier pagina.
-- Este fichero existe para poder aplicarlo a mano en produccion.

-- ---------------------------------------------------------------------------
-- 1. Columna nueva en eventos: el tablao decide evento a evento si admite
--    reservas. NOT NULL con DEFAULT FALSE: ningun evento existente cambia de
--    comportamiento, y el codigo que no la usa no se entera de que existe.
-- ---------------------------------------------------------------------------

SET @sql := (SELECT IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'eventos' AND COLUMN_NAME = 'acepta_reservas') > 0,
    'SELECT "eventos.acepta_reservas ya existe"',
    'ALTER TABLE eventos ADD COLUMN acepta_reservas BOOLEAN NOT NULL DEFAULT FALSE'
));
PREPARE csf_stmt FROM @sql; EXECUTE csf_stmt; DEALLOCATE PREPARE csf_stmt;

-- ---------------------------------------------------------------------------
-- 2. Solicitudes de reserva. Una funcion de tablao es simplemente un evento
--    cuyo miembro_id es un tablao: no hay tabla de "funciones" aparte.
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS tablao_reservas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    evento_id BIGINT UNSIGNED NOT NULL,
    miembro_id BIGINT UNSIGNED NOT NULL,
    nombre_solicitante VARCHAR(160) NOT NULL,
    email VARCHAR(190) NOT NULL,
    telefono VARCHAR(60) NULL,
    num_personas SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    mensaje VARCHAR(500) NULL,
    estado ENUM('PENDIENTE','CONFIRMADA','RECHAZADA','CANCELADA') NOT NULL DEFAULT 'PENDIENTE',
    ip_hash CHAR(64) NULL,
    gestionado_por BIGINT UNSIGNED NULL,
    gestionado_at DATETIME NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_tablao_reservas_evento FOREIGN KEY (evento_id) REFERENCES eventos(id) ON DELETE CASCADE,
    CONSTRAINT fk_tablao_reservas_miembro FOREIGN KEY (miembro_id) REFERENCES miembros(id) ON DELETE CASCADE,
    CONSTRAINT fk_tablao_reservas_gestor FOREIGN KEY (gestionado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_tablao_reservas_evento (evento_id, estado),
    INDEX idx_tablao_reservas_miembro (miembro_id, estado, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
