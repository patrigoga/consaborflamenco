-- Con Sabor Flamenco - Disciplinas para directorios y filtros
-- Migracion no destructiva. Ejecutar sobre la base existente cuando se quiera normalizar los filtros.

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

CREATE TABLE IF NOT EXISTS miembro_disciplinas (
    miembro_id BIGINT UNSIGNED NOT NULL,
    disciplina_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (miembro_id, disciplina_id),
    CONSTRAINT fk_miembro_disciplinas_miembro FOREIGN KEY (miembro_id) REFERENCES miembros(id) ON DELETE CASCADE,
    CONSTRAINT fk_miembro_disciplinas_disciplina FOREIGN KEY (disciplina_id) REFERENCES disciplinas(id) ON DELETE CASCADE,
    INDEX idx_miembro_disciplinas_disciplina (disciplina_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS academia_disciplinas (
    academia_id BIGINT UNSIGNED NOT NULL,
    disciplina_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (academia_id, disciplina_id),
    CONSTRAINT fk_academia_disciplinas_miembro FOREIGN KEY (academia_id) REFERENCES miembros(id) ON DELETE CASCADE,
    CONSTRAINT fk_academia_disciplinas_disciplina FOREIGN KEY (disciplina_id) REFERENCES disciplinas(id) ON DELETE CASCADE,
    INDEX idx_academia_disciplinas_disciplina (disciplina_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
