-- Con Sabor Flamenco - Nivel de membresia "Artista destacado"
--
-- Migracion INCREMENTAL y NO DESTRUCTIVA. Anade un valor al ENUM de
-- `miembros.estado`. Ampliar un ENUM no toca ninguna fila existente: los
-- miembros actuales conservan su SIMPATIZANTE o su VIP.
--
--   php tools/run_migration.php database/20260901_nivel_artista_destacado.sql
--
-- Se puede ejecutar tantas veces como haga falta.
--
-- Contexto: `miembros.estado` guarda a la vez el nivel de membresia
-- (SIMPATIZANTE, VIP, DESTACADO) y estados de cuenta que no son niveles
-- (INACTIVO, SUSPENDIDO, PENDIENTE). La conversion de uno a otro la hace
-- member_tier_from_estado() en app/auth.php.
--
-- DESTACADO es el artista escaparate que da de alta la administracion: el unico
-- nivel que ve las pantallas de curriculum y de microweb publica.

ALTER TABLE miembros
    MODIFY estado ENUM('SIMPATIZANTE','VIP','DESTACADO','INACTIVO','SUSPENDIDO','PENDIENTE')
    NOT NULL DEFAULT 'SIMPATIZANTE';
