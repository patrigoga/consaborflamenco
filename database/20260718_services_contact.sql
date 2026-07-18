-- Servicios, contacto profesional y mensajes de contacto.
-- Migracion no destructiva: crea tablas solo si no existen.

CREATE TABLE IF NOT EXISTS services (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(180) NOT NULL,
    slug VARCHAR(180) NOT NULL,
    short_description VARCHAR(320) NOT NULL,
    full_description MEDIUMTEXT NULL,
    image_path VARCHAR(500) NULL,
    icon VARCHAR(80) NULL,
    price DECIMAL(10,2) NULL,
    price_suffix VARCHAR(80) NULL,
    button_text VARCHAR(120) NULL,
    button_url VARCHAR(500) NULL,
    display_order INT NOT NULL DEFAULT 0,
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    status ENUM('ACTIVE','INACTIVE') NOT NULL DEFAULT 'ACTIVE',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    UNIQUE KEY uq_services_slug (slug),
    INDEX idx_services_public (status, is_featured, display_order, updated_at),
    INDEX idx_services_order (display_order, title),
    CONSTRAINT fk_services_created_by FOREIGN KEY (created_by) REFERENCES usuarios(id) ON DELETE SET NULL,
    CONSTRAINT fk_services_updated_by FOREIGN KEY (updated_by) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS contact_settings (
    id TINYINT UNSIGNED NOT NULL PRIMARY KEY DEFAULT 1,
    section_title VARCHAR(180) NOT NULL DEFAULT 'Hablemos de tu proyecto flamenco',
    section_intro TEXT NULL,
    image_path VARCHAR(500) NULL,
    image_alt VARCHAR(180) NULL,
    business_name VARCHAR(180) NULL,
    contact_person VARCHAR(180) NULL,
    email VARCHAR(180) NULL,
    phone VARCHAR(80) NULL,
    whatsapp VARCHAR(80) NULL,
    whatsapp_url VARCHAR(500) NULL,
    address VARCHAR(220) NULL,
    city VARCHAR(120) NULL,
    province VARCHAR(120) NULL,
    postal_code VARCHAR(20) NULL,
    opening_hours TEXT NULL,
    phone_button_text VARCHAR(120) NULL,
    whatsapp_button_text VARCHAR(120) NULL,
    facebook_url VARCHAR(500) NULL,
    instagram_url VARCHAR(500) NULL,
    youtube_url VARCHAR(500) NULL,
    tiktok_url VARCHAR(500) NULL,
    is_enabled TINYINT(1) NOT NULL DEFAULT 1,
    show_email TINYINT(1) NOT NULL DEFAULT 1,
    show_phone TINYINT(1) NOT NULL DEFAULT 1,
    show_whatsapp TINYINT(1) NOT NULL DEFAULT 1,
    show_address TINYINT(1) NOT NULL DEFAULT 1,
    show_opening_hours TINYINT(1) NOT NULL DEFAULT 1,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by BIGINT UNSIGNED NULL,
    CONSTRAINT fk_contact_settings_updated_by FOREIGN KEY (updated_by) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO contact_settings (
    id,
    section_title,
    section_intro,
    business_name,
    email,
    province,
    phone_button_text,
    whatsapp_button_text
) VALUES (
    1,
    'Hablemos de tu proyecto flamenco',
    'Cuentanos que necesitas y te responderemos para orientar el siguiente paso.',
    'Con Sabor Flamenco',
    'hola@consaborflamenco.com',
    'Cordoba',
    'Llamar',
    'WhatsApp'
);

CREATE TABLE IF NOT EXISTS contact_messages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(160) NOT NULL,
    email VARCHAR(180) NOT NULL,
    phone VARCHAR(80) NULL,
    inquiry_type VARCHAR(80) NOT NULL,
    subject VARCHAR(180) NOT NULL,
    message TEXT NOT NULL,
    privacy_accepted TINYINT(1) NOT NULL DEFAULT 0,
    status ENUM('NEW','READ','ANSWERED','ARCHIVED','SPAM') NOT NULL DEFAULT 'NEW',
    ip_hash CHAR(64) NULL,
    user_agent VARCHAR(255) NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    read_at DATETIME NULL,
    answered_at DATETIME NULL,
    assigned_to BIGINT UNSIGNED NULL,
    INDEX idx_contact_messages_status_date (status, created_at),
    INDEX idx_contact_messages_type (inquiry_type),
    CONSTRAINT fk_contact_messages_assigned_to FOREIGN KEY (assigned_to) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
