-- Con Sabor Flamenco - Documentos legales administrables
-- Migracion no destructiva.

CREATE TABLE IF NOT EXISTS legal_documents (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    document_key ENUM('terms','legal_notice','privacy','cookies') NOT NULL,
    title VARCHAR(180) NOT NULL,
    slug VARCHAR(120) NOT NULL,
    content MEDIUMTEXT NOT NULL,
    status ENUM('DRAFT','PUBLISHED') NOT NULL DEFAULT 'DRAFT',
    version INT UNSIGNED NOT NULL DEFAULT 1,
    published_at DATETIME NULL,
    visible_updated_at DATE NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by BIGINT UNSIGNED NULL,
    UNIQUE KEY uq_legal_documents_key (document_key),
    UNIQUE KEY uq_legal_documents_slug (slug),
    INDEX idx_legal_documents_status (status),
    CONSTRAINT fk_legal_documents_updated_by FOREIGN KEY (updated_by) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS legal_document_versions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    legal_document_id BIGINT UNSIGNED NOT NULL,
    version INT UNSIGNED NOT NULL,
    title VARCHAR(180) NOT NULL,
    content MEDIUMTEXT NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    CONSTRAINT fk_legal_versions_document FOREIGN KEY (legal_document_id) REFERENCES legal_documents(id) ON DELETE CASCADE,
    CONSTRAINT fk_legal_versions_user FOREIGN KEY (created_by) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_legal_versions_document (legal_document_id, version)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO legal_documents (document_key, title, slug, content, status, version, published_at, visible_updated_at)
VALUES
('terms', 'Términos y condiciones', 'terminos', '<h2>Datos pendientes de completar</h2><p>Este documento es una base administrable y debe ser revisado antes de publicarse como texto definitivo.</p><ul><li>Titular o razón social pendiente.</li><li>NIF/CIF pendiente.</li><li>Domicilio pendiente.</li><li>Condiciones de contratación, precios, renovaciones y cancelaciones pendientes.</li><li>Normas aplicables a miembros, anunciantes y servicios digitales pendientes.</li></ul><h2>Objeto</h2><p>Con Sabor Flamenco ofrece una plataforma digital orientada a la difusión, promoción y gestión de contenidos, perfiles y servicios relacionados con el flamenco.</p>', 'PUBLISHED', 1, UTC_TIMESTAMP(), CURRENT_DATE()),
('legal_notice', 'Aviso legal', 'aviso-legal', '<h2>Datos identificativos pendientes</h2><p>El titular de la web debe completar y validar estos datos antes de publicar el aviso legal como definitivo.</p><ul><li>Titular o razón social pendiente.</li><li>NIF/CIF pendiente.</li><li>Domicilio pendiente.</li><li>Datos registrales pendientes, si corresponden.</li><li>Correo de contacto: hola@consaborflamenco.com.</li></ul><h2>Responsabilidad</h2><p>Con Sabor Flamenco trabaja para mantener información actualizada, pero los contenidos editoriales, perfiles y servicios pueden requerir revisión o confirmación adicional.</p>', 'PUBLISHED', 1, UTC_TIMESTAMP(), CURRENT_DATE()),
('privacy', 'Política de privacidad', 'privacidad', '<h2>Información pendiente de completar</h2><p>Esta política debe completarse con los datos reales del responsable del tratamiento y revisarse legalmente.</p><ul><li>Responsable del tratamiento pendiente.</li><li>NIF/CIF y domicilio pendientes.</li><li>Bases jurídicas detalladas pendientes.</li><li>Plazos de conservación pendientes.</li><li>Destinatarios, encargados y proveedores pendientes.</li><li>Transferencias internacionales pendientes.</li><li>Procedimiento para ejercer derechos pendiente.</li></ul><h2>Datos tratados actualmente</h2><p>La plataforma puede tratar datos de cuenta, acceso, perfil público, provincia seleccionada, comunicaciones necesarias y datos asociados a servicios solicitados.</p>', 'PUBLISHED', 1, UTC_TIMESTAMP(), CURRENT_DATE()),
('cookies', 'Política de cookies', 'cookies', '<h2>Qué son las cookies</h2><p>Las cookies y tecnologías similares permiten recordar información técnica o preferencias de navegación.</p><h2>Auditoría actual</h2><p>En la versión actual se han detectado cookies necesarias de sesión PHP y almacenamiento local para preferencias funcionales como provincia y consentimiento. No se han detectado Google Analytics, Meta Pixel ni publicidad personalizada cargada por terceros.</p><h2>Categorías</h2><ul><li><strong>Necesarias:</strong> sesión, seguridad, CSRF y funcionamiento básico.</li><li><strong>Preferencias:</strong> provincia o ajustes elegidos por la persona usuaria.</li><li><strong>Analítica:</strong> desactivada hasta consentimiento y sin proveedor configurado actualmente.</li><li><strong>Publicidad:</strong> desactivada hasta consentimiento y sin proveedor externo configurado actualmente.</li></ul><h2>Modificar consentimiento</h2><p>Puedes cambiar o retirar tu elección desde el enlace Configurar cookies del footer.</p>', 'PUBLISHED', 1, UTC_TIMESTAMP(), CURRENT_DATE());
