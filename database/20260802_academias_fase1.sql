-- Con Sabor Flamenco - Modulo de Academias, Fase 1
-- Migracion no destructiva: crea tablas solo si no existen.
-- Ejecutar con: php tools/run_migration.php database/20260802_academias_fase1.sql
--
-- DEPENDENCIA: requiere que 20260718_disciplinas.sql se haya ejecutado antes,
-- porque academia_cursos declara una clave foranea contra disciplinas(id).

CREATE TABLE IF NOT EXISTS academias (
    miembro_id BIGINT UNSIGNED PRIMARY KEY,
    razon_social VARCHAR(180) NULL,
    cif VARCHAR(20) NULL,
    plan ENUM('BASICO','PROFESIONAL','AVANZADO') NOT NULL DEFAULT 'BASICO',
    estado ENUM('PENDIENTE','ACTIVA','SUSPENDIDA','BAJA') NOT NULL DEFAULT 'PENDIENTE',
    configuracion_json LONGTEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    CONSTRAINT fk_academias_miembro FOREIGN KEY (miembro_id) REFERENCES miembros(id) ON DELETE CASCADE,
    CONSTRAINT fk_academias_created_by FOREIGN KEY (created_by) REFERENCES usuarios(id) ON DELETE SET NULL,
    CONSTRAINT fk_academias_updated_by FOREIGN KEY (updated_by) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_academias_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS academia_miembros (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    academia_id BIGINT UNSIGNED NOT NULL,
    usuario_id BIGINT UNSIGNED NOT NULL,
    rol ENUM('RESPONSABLE','PROFESOR','ALUMNO','TUTOR') NOT NULL,
    estado ENUM('PENDIENTE','ACTIVO','INACTIVO','SUSPENDIDO') NOT NULL DEFAULT 'PENDIENTE',
    permisos_json LONGTEXT NULL,
    fecha_alta DATE NULL,
    fecha_baja DATE NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    CONSTRAINT fk_academia_miembros_academia FOREIGN KEY (academia_id) REFERENCES academias(miembro_id) ON DELETE CASCADE,
    CONSTRAINT fk_academia_miembros_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT fk_academia_miembros_created_by FOREIGN KEY (created_by) REFERENCES usuarios(id) ON DELETE SET NULL,
    UNIQUE KEY uq_academia_miembros (academia_id, usuario_id, rol),
    INDEX idx_academia_miembros_usuario (usuario_id, rol, estado),
    INDEX idx_academia_miembros_academia (academia_id, rol, estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS academia_profesores (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    academia_miembro_id BIGINT UNSIGNED NOT NULL UNIQUE,
    especialidad VARCHAR(180) NULL,
    biografia_docente TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_academia_profesores_miembro FOREIGN KEY (academia_miembro_id) REFERENCES academia_miembros(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS academia_alumnos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    academia_id BIGINT UNSIGNED NOT NULL,
    usuario_id BIGINT UNSIGNED NULL,
    nombre VARCHAR(160) NOT NULL,
    apellidos VARCHAR(160) NULL,
    fecha_nacimiento DATE NULL,
    email VARCHAR(190) NULL,
    telefono VARCHAR(60) NULL,
    es_menor_edad BOOLEAN NOT NULL DEFAULT FALSE,
    consentimiento_imagen BOOLEAN NOT NULL DEFAULT FALSE,
    consentimiento_fecha TIMESTAMP NULL,
    consentimiento_version VARCHAR(20) NULL,
    estado ENUM('ACTIVO','INACTIVO','BAJA') NOT NULL DEFAULT 'ACTIVO',
    notas_internas TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    CONSTRAINT fk_academia_alumnos_academia FOREIGN KEY (academia_id) REFERENCES academias(miembro_id) ON DELETE CASCADE,
    CONSTRAINT fk_academia_alumnos_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    CONSTRAINT fk_academia_alumnos_created_by FOREIGN KEY (created_by) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_academia_alumnos_academia (academia_id, estado),
    INDEX idx_academia_alumnos_usuario (usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS academia_tutores (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    alumno_id BIGINT UNSIGNED NOT NULL,
    usuario_id BIGINT UNSIGNED NOT NULL,
    parentesco VARCHAR(80) NULL,
    puede_pagar BOOLEAN NOT NULL DEFAULT TRUE,
    puede_autorizar BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_academia_tutores_alumno FOREIGN KEY (alumno_id) REFERENCES academia_alumnos(id) ON DELETE CASCADE,
    CONSTRAINT fk_academia_tutores_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    UNIQUE KEY uq_academia_tutores (alumno_id, usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS academia_niveles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    academia_id BIGINT UNSIGNED NOT NULL,
    nombre VARCHAR(120) NOT NULL,
    orden SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    descripcion VARCHAR(320) NULL,
    estado ENUM('ACTIVO','INACTIVO') NOT NULL DEFAULT 'ACTIVO',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_academia_niveles_academia FOREIGN KEY (academia_id) REFERENCES academias(miembro_id) ON DELETE CASCADE,
    INDEX idx_academia_niveles_academia (academia_id, estado, orden)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS academia_cursos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    academia_id BIGINT UNSIGNED NOT NULL,
    disciplina_id BIGINT UNSIGNED NULL,
    nivel_id BIGINT UNSIGNED NULL,
    nombre VARCHAR(180) NOT NULL,
    descripcion TEXT NULL,
    modalidad ENUM('PRESENCIAL','ONLINE','MIXTA') NOT NULL DEFAULT 'PRESENCIAL',
    fecha_inicio DATE NULL,
    fecha_fin DATE NULL,
    precio DECIMAL(8,2) NULL,
    tipo_cuota VARCHAR(60) NULL,
    estado ENUM('BORRADOR','PUBLICADO','MATRICULA_ABIERTA','COMPLETO','EN_CURSO','FINALIZADO','CANCELADO') NOT NULL DEFAULT 'BORRADOR',
    imagen_path VARCHAR(255) NULL,
    requisitos TEXT NULL,
    material_necesario TEXT NULL,
    visible_publico BOOLEAN NOT NULL DEFAULT FALSE,
    plazas_maximas SMALLINT UNSIGNED NULL,
    periodo_matricula_inicio DATE NULL,
    periodo_matricula_fin DATE NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    CONSTRAINT fk_academia_cursos_academia FOREIGN KEY (academia_id) REFERENCES academias(miembro_id) ON DELETE CASCADE,
    CONSTRAINT fk_academia_cursos_disciplina FOREIGN KEY (disciplina_id) REFERENCES disciplinas(id) ON DELETE SET NULL,
    CONSTRAINT fk_academia_cursos_nivel FOREIGN KEY (nivel_id) REFERENCES academia_niveles(id) ON DELETE SET NULL,
    CONSTRAINT fk_academia_cursos_created_by FOREIGN KEY (created_by) REFERENCES usuarios(id) ON DELETE SET NULL,
    CONSTRAINT fk_academia_cursos_updated_by FOREIGN KEY (updated_by) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_academia_cursos_academia (academia_id, estado),
    INDEX idx_academia_cursos_publico (visible_publico, estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS academia_curso_profesores (
    curso_id BIGINT UNSIGNED NOT NULL,
    academia_miembro_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (curso_id, academia_miembro_id),
    CONSTRAINT fk_academia_curso_profesores_curso FOREIGN KEY (curso_id) REFERENCES academia_cursos(id) ON DELETE CASCADE,
    CONSTRAINT fk_academia_curso_profesores_profesor FOREIGN KEY (academia_miembro_id) REFERENCES academia_miembros(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS academia_grupos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    curso_id BIGINT UNSIGNED NOT NULL,
    academia_id BIGINT UNSIGNED NOT NULL,
    nombre VARCHAR(120) NOT NULL,
    aula_o_ubicacion VARCHAR(180) NULL,
    plazas_maximas SMALLINT UNSIGNED NULL,
    estado ENUM('ACTIVO','CERRADO') NOT NULL DEFAULT 'ACTIVO',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_academia_grupos_curso FOREIGN KEY (curso_id) REFERENCES academia_cursos(id) ON DELETE CASCADE,
    CONSTRAINT fk_academia_grupos_academia FOREIGN KEY (academia_id) REFERENCES academias(miembro_id) ON DELETE CASCADE,
    INDEX idx_academia_grupos_curso (curso_id, estado),
    INDEX idx_academia_grupos_academia (academia_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS academia_horarios_grupo (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    grupo_id BIGINT UNSIGNED NOT NULL,
    dia_semana TINYINT UNSIGNED NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fin TIME NOT NULL,
    aula VARCHAR(120) NULL,
    CONSTRAINT fk_academia_horarios_grupo FOREIGN KEY (grupo_id) REFERENCES academia_grupos(id) ON DELETE CASCADE,
    INDEX idx_academia_horarios_grupo_dia (grupo_id, dia_semana)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS academia_matriculas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    academia_id BIGINT UNSIGNED NOT NULL,
    alumno_id BIGINT UNSIGNED NOT NULL,
    curso_id BIGINT UNSIGNED NOT NULL,
    grupo_id BIGINT UNSIGNED NULL,
    estado ENUM('SOLICITADA','PENDIENTE_DOCUMENTACION','PENDIENTE_PAGO','ACTIVA','PAUSADA','FINALIZADA','CANCELADA','RECHAZADA') NOT NULL DEFAULT 'SOLICITADA',
    fecha_alta DATE NULL,
    fecha_baja DATE NULL,
    descuento_pct DECIMAL(5,2) NULL,
    observaciones_internas TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    CONSTRAINT fk_academia_matriculas_academia FOREIGN KEY (academia_id) REFERENCES academias(miembro_id) ON DELETE CASCADE,
    CONSTRAINT fk_academia_matriculas_alumno FOREIGN KEY (alumno_id) REFERENCES academia_alumnos(id) ON DELETE CASCADE,
    CONSTRAINT fk_academia_matriculas_curso FOREIGN KEY (curso_id) REFERENCES academia_cursos(id) ON DELETE CASCADE,
    CONSTRAINT fk_academia_matriculas_grupo FOREIGN KEY (grupo_id) REFERENCES academia_grupos(id) ON DELETE SET NULL,
    CONSTRAINT fk_academia_matriculas_created_by FOREIGN KEY (created_by) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_academia_matriculas_academia (academia_id, estado),
    INDEX idx_academia_matriculas_alumno (alumno_id, estado),
    INDEX idx_academia_matriculas_curso (curso_id, estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS academia_solicitudes_info (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    academia_id BIGINT UNSIGNED NOT NULL,
    nombre VARCHAR(160) NOT NULL,
    email VARCHAR(190) NOT NULL,
    telefono VARCHAR(60) NULL,
    mensaje TEXT NULL,
    disciplina_interes VARCHAR(60) NULL,
    estado ENUM('NUEVA','LEIDA','RESPONDIDA','DESCARTADA') NOT NULL DEFAULT 'NUEVA',
    ip_hash CHAR(64) NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_academia_solicitudes_info_academia FOREIGN KEY (academia_id) REFERENCES academias(miembro_id) ON DELETE CASCADE,
    INDEX idx_academia_solicitudes_info_academia (academia_id, estado, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS academia_solicitudes_matricula (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    academia_id BIGINT UNSIGNED NOT NULL,
    curso_id BIGINT UNSIGNED NULL,
    nombre_alumno VARCHAR(160) NOT NULL,
    fecha_nacimiento DATE NULL,
    nombre_tutor VARCHAR(160) NULL,
    email VARCHAR(190) NOT NULL,
    telefono VARCHAR(60) NULL,
    mensaje TEXT NULL,
    estado ENUM('NUEVA','EN_PROCESO','CONVERTIDA','RECHAZADA','DESCARTADA') NOT NULL DEFAULT 'NUEVA',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_academia_solicitudes_matricula_academia FOREIGN KEY (academia_id) REFERENCES academias(miembro_id) ON DELETE CASCADE,
    CONSTRAINT fk_academia_solicitudes_matricula_curso FOREIGN KEY (curso_id) REFERENCES academia_cursos(id) ON DELETE SET NULL,
    INDEX idx_academia_solicitudes_matricula_academia (academia_id, estado, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
