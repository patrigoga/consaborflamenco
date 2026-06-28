-- Con Sabor Flamenco - Esquema inicial previsto
-- Motor recomendado: MySQL 8 / MariaDB 10.6+

CREATE TABLE provincias (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(120) NOT NULL,
    slug VARCHAR(140) NOT NULL UNIQUE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE usuarios (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE tipos_miembro (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(120) NOT NULL UNIQUE,
    slug VARCHAR(140) NOT NULL UNIQUE,
    activo BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE miembros (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id BIGINT UNSIGNED NOT NULL UNIQUE,
    tipo_miembro_id BIGINT UNSIGNED NULL,
    nombre_publico VARCHAR(180) NOT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE tarjetas_miembro (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE miembros_curriculum_items (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE categorias_articulos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(120) NOT NULL UNIQUE,
    slug VARCHAR(140) NOT NULL UNIQUE,
    activo BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE articulos (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE pagos_stripe (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE banners_miembro (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE appointment_setters (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE password_reset_tokens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id BIGINT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_reset_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_reset_usuario_estado (usuario_id, expires_at, used_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE usos_codigo_descuento (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
