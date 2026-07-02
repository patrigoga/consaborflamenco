<?php
declare(strict_types=1);

function db(): ?PDO
{
    static $pdo = null;
    static $attempted = false;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    if ($attempted) {
        return null;
    }

    $attempted = true;

    try {
        if (APP_ENV !== 'production') {
            $serverDsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';charset=' . DB_CHARSET;
            $server = new PDO($serverDsn, DB_USER, DB_PASS, db_pdo_options());
            $server->exec(
                'CREATE DATABASE IF NOT EXISTS `' . str_replace('`', '``', DB_NAME) . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
            );
        }

        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, db_pdo_options());

        db_bootstrap($pdo);
        return $pdo;
    } catch (Throwable $exception) {
        $GLOBALS['CSF_DB_LAST_ERROR'] = $exception->getMessage();
        $pdo = null;
        return null;
    }
}

function db_pdo_options(): array
{
    return [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 5,
    ];
}

function db_last_error(): ?string
{
    return isset($GLOBALS['CSF_DB_LAST_ERROR']) ? (string) $GLOBALS['CSF_DB_LAST_ERROR'] : null;
}

function db_bootstrap(PDO $pdo): void
{
    static $bootstrapped = false;
    if ($bootstrapped) {
        return;
    }

    $bootstrapped = true;

    $statements = [
        "CREATE TABLE IF NOT EXISTS provincias (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(120) NOT NULL,
            slug VARCHAR(140) NOT NULL UNIQUE,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS tipos_miembro (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(120) NOT NULL UNIQUE,
            slug VARCHAR(140) NOT NULL UNIQUE,
            activo BOOLEAN NOT NULL DEFAULT TRUE,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS usuarios (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            uuid CHAR(32) NOT NULL UNIQUE,
            nombre VARCHAR(160) NOT NULL,
            email VARCHAR(190) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            rol ENUM('ADMIN','MIEMBRO','SETTER') NOT NULL DEFAULT 'MIEMBRO',
            estado ENUM('ACTIVO','INACTIVO','SUSPENDIDO') NOT NULL DEFAULT 'ACTIVO',
            avatar_path VARCHAR(255) NULL,
            provincia_id BIGINT UNSIGNED NULL,
            email_verified_at TIMESTAMP NULL,
            terms_accepted_at TIMESTAMP NULL,
            last_login_at TIMESTAMP NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_usuarios_provincia FOREIGN KEY (provincia_id) REFERENCES provincias(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS miembros (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            usuario_id BIGINT UNSIGNED NOT NULL UNIQUE,
            tipo_miembro_id BIGINT UNSIGNED NULL,
            nombre_publico VARCHAR(180) NOT NULL,
            slug VARCHAR(180) NULL UNIQUE,
            numero_miembro INT UNSIGNED NOT NULL UNIQUE,
            codigo_descuento VARCHAR(40) NOT NULL UNIQUE,
            estado ENUM('SIMPATIZANTE','VIP','INACTIVO','SUSPENDIDO','PENDIENTE') NOT NULL DEFAULT 'SIMPATIZANTE',
            biografia TEXT NULL,
            ciudad VARCHAR(120) NULL,
            provincia_texto VARCHAR(120) NULL,
            telefono VARCHAR(60) NULL,
            foto_principal_path VARCHAR(255) NULL,
            web_url VARCHAR(255) NULL,
            instagram_url VARCHAR(255) NULL,
            facebook_url VARCHAR(255) NULL,
            youtube_url VARCHAR(255) NULL,
            perfil_json LONGTEXT NULL,
            perfil_completo_at TIMESTAMP NULL,
            fecha_alta DATE NULL,
            fecha_baja DATE NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_miembros_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
            CONSTRAINT fk_miembros_tipo FOREIGN KEY (tipo_miembro_id) REFERENCES tipos_miembro(id) ON DELETE SET NULL,
            INDEX idx_miembros_estado (estado),
            INDEX idx_miembros_codigo_estado (codigo_descuento, estado)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS tarjetas_miembro (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            miembro_id BIGINT UNSIGNED NOT NULL UNIQUE,
            fondo_imagen_path VARCHAR(255) NOT NULL,
            nombre_visible VARCHAR(180) NOT NULL,
            numero_visible VARCHAR(40) NOT NULL,
            codigo_visible VARCHAR(40) NOT NULL,
            activa BOOLEAN NOT NULL DEFAULT TRUE,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_tarjetas_miembro FOREIGN KEY (miembro_id) REFERENCES miembros(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS miembros_curriculum_items (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            miembro_id BIGINT UNSIGNED NOT NULL,
            tipo ENUM('FORMACION','EXPERIENCIA','DOCENCIA','ACTUACION','PREMIO','REPERTORIO','RED_SOCIAL') NOT NULL,
            titulo VARCHAR(180) NULL,
            entidad VARCHAR(180) NULL,
            lugar VARCHAR(180) NULL,
            fecha_texto VARCHAR(80) NULL,
            descripcion TEXT NULL,
            imagen_path VARCHAR(255) NULL,
            visible_publico BOOLEAN NOT NULL DEFAULT TRUE,
            orden SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_curriculum_miembro FOREIGN KEY (miembro_id) REFERENCES miembros(id) ON DELETE CASCADE,
            INDEX idx_curriculum_miembro_tipo (miembro_id, tipo, orden),
            INDEX idx_curriculum_visible (visible_publico)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS categorias_articulos (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(120) NOT NULL UNIQUE,
            slug VARCHAR(140) NOT NULL UNIQUE,
            activo BOOLEAN NOT NULL DEFAULT TRUE,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS articulos (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            autor_usuario_id BIGINT UNSIGNED NULL,
            categoria_id BIGINT UNSIGNED NULL,
            titulo VARCHAR(220) NOT NULL,
            slug VARCHAR(240) NOT NULL UNIQUE,
            resumen VARCHAR(320) NULL,
            contenido MEDIUMTEXT NULL,
            imagen_principal_path VARCHAR(255) NULL,
            estado ENUM('BORRADOR','REVISION','PUBLICADO','ARCHIVADO') NOT NULL DEFAULT 'BORRADOR',
            publicado_at TIMESTAMP NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_articulos_autor FOREIGN KEY (autor_usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
            CONSTRAINT fk_articulos_categoria FOREIGN KEY (categoria_id) REFERENCES categorias_articulos(id) ON DELETE SET NULL,
            INDEX idx_articulos_estado_publicado (estado, publicado_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS pagos_stripe (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            usuario_id BIGINT UNSIGNED NOT NULL,
            stripe_customer_id VARCHAR(120) NULL,
            stripe_checkout_session_id VARCHAR(180) NULL UNIQUE,
            stripe_payment_intent_id VARCHAR(180) NULL UNIQUE,
            concepto VARCHAR(160) NOT NULL,
            importe_centimos INT UNSIGNED NOT NULL,
            moneda CHAR(3) NOT NULL DEFAULT 'EUR',
            estado ENUM('PENDIENTE','PAGADO','FALLIDO','REEMBOLSADO','CANCELADO') NOT NULL DEFAULT 'PENDIENTE',
            paid_at TIMESTAMP NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_pagos_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
            INDEX idx_pagos_estado (estado)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS banners_miembro (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            miembro_id BIGINT UNSIGNED NOT NULL,
            pago_id BIGINT UNSIGNED NULL,
            titulo VARCHAR(180) NOT NULL,
            url_destino VARCHAR(255) NOT NULL,
            imagen_path VARCHAR(255) NOT NULL,
            estado ENUM('BORRADOR','PENDIENTE_PAGO','PAGADO','ACTIVO','INACTIVO','CADUCADO','RECHAZADO') NOT NULL DEFAULT 'BORRADOR',
            fecha_inicio_publicacion DATETIME NULL,
            fecha_fin_publicacion DATETIME NULL,
            fecha_inicio_contratacion DATETIME NULL,
            fecha_fin_contratacion DATETIME NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_banners_miembro FOREIGN KEY (miembro_id) REFERENCES miembros(id) ON DELETE CASCADE,
            CONSTRAINT fk_banners_pago FOREIGN KEY (pago_id) REFERENCES pagos_stripe(id) ON DELETE SET NULL,
            INDEX idx_banners_estado_fechas (estado, fecha_inicio_publicacion, fecha_fin_publicacion),
            INDEX idx_banners_miembro_estado (miembro_id, estado)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS appointment_setters (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            usuario_id BIGINT UNSIGNED NOT NULL UNIQUE,
            nombre_comercial VARCHAR(160) NULL,
            estado_cuenta ENUM('PENDIENTE','ACTIVO','PAUSADO','SUSPENDIDO') NOT NULL DEFAULT 'PENDIENTE',
            estado_documentacion ENUM('PENDIENTE','VALIDADA','RECHAZADA') NOT NULL DEFAULT 'PENDIENTE',
            estado_comisiones ENUM('SIN_VENTAS','PENDIENTE_COBRO','AL_DIA','BLOQUEADAS') NOT NULL DEFAULT 'SIN_VENTAS',
            codigo_promocional VARCHAR(60) NULL UNIQUE,
            notas_admin TEXT NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_setters_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
            INDEX idx_setters_estados (estado_cuenta, estado_documentacion, estado_comisiones)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS password_reset_tokens (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            usuario_id BIGINT UNSIGNED NOT NULL,
            token_hash CHAR(64) NOT NULL UNIQUE,
            expires_at DATETIME NOT NULL,
            used_at DATETIME NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_reset_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
            INDEX idx_reset_usuario_estado (usuario_id, expires_at, used_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS usos_codigo_descuento (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            miembro_id BIGINT UNSIGNED NOT NULL,
            codigo_descuento VARCHAR(40) NOT NULL,
            usuario_id BIGINT UNSIGNED NULL,
            contexto VARCHAR(80) NULL,
            importe_base_centimos INT UNSIGNED NULL,
            descuento_centimos INT UNSIGNED NULL,
            usado_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_usos_codigo_miembro FOREIGN KEY (miembro_id) REFERENCES miembros(id) ON DELETE CASCADE,
            CONSTRAINT fk_usos_codigo_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
            INDEX idx_usos_codigo (codigo_descuento),
            INDEX idx_usos_miembro_fecha (miembro_id, usado_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    ];

    foreach ($statements as $statement) {
        $pdo->exec($statement);
    }

    db_add_column_if_missing($pdo, 'miembros', 'perfil_json', 'LONGTEXT NULL');
    db_add_column_if_missing($pdo, 'miembros', 'ciudad', 'VARCHAR(120) NULL');
    db_add_column_if_missing($pdo, 'miembros', 'provincia_texto', 'VARCHAR(120) NULL');
    db_add_column_if_missing($pdo, 'miembros', 'telefono', 'VARCHAR(60) NULL');
    db_add_column_if_missing($pdo, 'miembros', 'foto_principal_path', 'VARCHAR(255) NULL');
    db_add_column_if_missing($pdo, 'miembros', 'perfil_completo_at', 'TIMESTAMP NULL');
    db_add_column_if_missing($pdo, 'banners_miembro', 'fecha_inicio_contratacion', 'DATETIME NULL');
    db_add_column_if_missing($pdo, 'banners_miembro', 'fecha_fin_contratacion', 'DATETIME NULL');
    db_normalize_member_status_column($pdo);

    db_seed_member_types($pdo);
    db_seed_article_categories($pdo);
}

function db_column_exists(PDO $pdo, string $table, string $column): bool
{
    $statement = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND COLUMN_NAME = :column'
    );
    $statement->execute(['table' => $table, 'column' => $column]);

    return (int) $statement->fetchColumn() > 0;
}

function db_add_column_if_missing(PDO $pdo, string $table, string $column, string $definition): void
{
    if (db_column_exists($pdo, $table, $column)) {
        return;
    }

    $pdo->exec('ALTER TABLE `' . str_replace('`', '``', $table) . '` ADD COLUMN `' . str_replace('`', '``', $column) . '` ' . $definition);
}

function db_normalize_member_status_column(PDO $pdo): void
{
    $pdo->exec(
        "ALTER TABLE miembros MODIFY estado ENUM('ACTIVO','SIMPATIZANTE','VIP','INACTIVO','SUSPENDIDO','PENDIENTE') NOT NULL DEFAULT 'SIMPATIZANTE'"
    );
    $pdo->exec("UPDATE miembros SET estado = 'SIMPATIZANTE' WHERE estado = 'ACTIVO'");
    $pdo->exec(
        "ALTER TABLE miembros MODIFY estado ENUM('SIMPATIZANTE','VIP','INACTIVO','SUSPENDIDO','PENDIENTE') NOT NULL DEFAULT 'SIMPATIZANTE'"
    );
}

function db_seed_member_types(PDO $pdo): void
{
    $types = [
        'artista' => 'Artista',
        'academia' => 'Academia',
        'tienda' => 'Tienda flamenca',
        'pena' => 'Pena flamenca',
        'tablao' => 'Tablao flamenco',
        'festival' => 'Festival',
        'profesional' => 'Profesional flamenco',
    ];

    $statement = $pdo->prepare('INSERT IGNORE INTO tipos_miembro (nombre, slug) VALUES (:nombre, :slug)');
    foreach ($types as $slug => $name) {
        $statement->execute(['nombre' => $name, 'slug' => $slug]);
    }
}

function db_seed_article_categories(PDO $pdo): void
{
    $categories = [
        'Actualidad',
        'Entrevistas',
        'Opinion',
        'Eventos',
        'Formacion',
        'Moda flamenca',
        'Comunidad',
    ];

    $statement = $pdo->prepare('INSERT IGNORE INTO categorias_articulos (nombre, slug) VALUES (:nombre, :slug)');
    foreach ($categories as $category) {
        $statement->execute(['nombre' => $category, 'slug' => slugify($category)]);
    }
}

function slugify(string $value): string
{
    $value = trim(mb_strtolower($value, 'UTF-8'));
    $transliterated = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
    $value = $transliterated !== false ? $transliterated : $value;
    $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
    $value = trim($value, '-');

    return $value !== '' ? $value : 'contenido';
}
