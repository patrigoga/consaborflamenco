-- Con Sabor Flamenco - Mega agenda, bloque 4: catalogo de tienda.
--
-- Migracion INCREMENTAL y NO DESTRUCTIVA. No borra ni renombra nada existente.
-- Idempotente: se puede ejecutar tantas veces como haga falta.
--
--   php tools/run_migration.php database/20260912_tienda_productos.sql
--
-- La misma tabla se crea tambien desde db_bootstrap() (app/database.php), asi
-- que en local basta con abrir cualquier pagina. Este fichero existe para
-- poder aplicarlo a mano en produccion.
--
-- Escaparate sin carrito: cada fila es una ficha de producto con foto, precio
-- orientativo y un enlace o contacto externo para comprar, no una compra
-- dentro de la web. El limite de fichas activas (5 en el plan gratuito, 20 en
-- VIP) vive en codigo (csf_tienda_limite_productos(), app/tienda_repository.php)
-- reutilizando el mismo indicador de nivel que ya usa la microweb
-- (member_tier_has_high_limits(), app/auth.php), nunca en esta tabla.

CREATE TABLE IF NOT EXISTS tienda_productos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    miembro_id BIGINT UNSIGNED NOT NULL,
    titulo VARCHAR(160) NOT NULL,
    descripcion TEXT NULL,
    precio_centimos INT UNSIGNED NULL,
    imagen_path VARCHAR(255) NULL,
    enlace_externo VARCHAR(255) NULL,
    estado ENUM('ACTIVO','PAUSADO') NOT NULL DEFAULT 'ACTIVO',
    orden SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    deleted_at DATETIME NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_tienda_productos_miembro FOREIGN KEY (miembro_id) REFERENCES miembros(id) ON DELETE CASCADE,
    INDEX idx_tienda_productos_miembro (miembro_id, estado, deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
