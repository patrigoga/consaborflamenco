<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

function site_db(): ?PDO
{
    try {
        return auth_database();
    } catch (Throwable) {
        return null;
    }
}

function site_ensure_schema(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS services (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS contact_settings (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS contact_messages (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $statement = $pdo->prepare('INSERT IGNORE INTO contact_settings (id, section_title, section_intro, business_name, email, province, phone_button_text, whatsapp_button_text) VALUES (1, :title, :intro, :business, :email, :province, :phone_text, :whatsapp_text)');
    $statement->execute([
        'title' => 'Hablemos de tu proyecto flamenco',
        'intro' => 'Cuéntanos que necesitas y te responderemos para orientar el siguiente paso.',
        'business' => APP_NAME,
        'email' => APP_EMAIL,
        'province' => 'Cordoba',
        'phone_text' => 'Llamar',
        'whatsapp_text' => 'WhatsApp',
    ]);
}

function site_repository_ready(): ?PDO
{
    $pdo = site_db();
    if (!$pdo) {
        return null;
    }

    site_ensure_schema($pdo);
    return $pdo;
}

function site_allowed_service_statuses(): array
{
    return ['ACTIVE', 'INACTIVE'];
}

function site_contact_statuses(): array
{
    return ['NEW', 'READ', 'ANSWERED', 'ARCHIVED', 'SPAM'];
}

function site_inquiry_types(): array
{
    return [
        'SERVICIOS' => 'Servicios digitales',
        'MEMBRESIA' => 'Membresia',
        'PUBLICIDAD' => 'Publicidad',
        'REVISTA' => 'Revista',
        'SOPORTE' => 'Soporte',
        'OTRO' => 'Otro',
    ];
}

function site_sanitize_html(string $html): string
{
    $html = preg_replace('#<(script|style|iframe|object|embed)\b[^>]*>.*?</\1>#is', '', $html) ?? '';
    $html = preg_replace('/\son[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html) ?? '';
    $html = preg_replace('/\s(href)\s*=\s*([\'"])\s*(javascript:|data:)[^\'"]*\2/i', ' href="#"', $html) ?? '';
    $html = strip_tags($html, '<p><h2><h3><ul><ol><li><strong><em><a><br>');
    $html = preg_replace_callback('/<a\b([^>]*)>/i', static function (array $matches): string {
        $attrs = $matches[1] ?? '';
        if (!preg_match('/href\s*=\s*([\'"])(.*?)\1/i', $attrs, $hrefMatch)) {
            return '<a>';
        }
        $href = site_sanitize_url((string) $hrefMatch[2]);
        if ($href === '') {
            return '<a>';
        }
        $rel = preg_match('#^https?://#i', $href) ? ' rel="noopener noreferrer"' : '';
        return '<a href="' . e($href) . '"' . $rel . '>';
    }, $html) ?? '';

    return trim($html);
}

function site_sanitize_url(string $url): string
{
    $url = trim(html_entity_decode($url, ENT_QUOTES, 'UTF-8'));
    if ($url === '') {
        return '';
    }

    if (preg_match('/[\x00-\x1F\x7F]/', $url) === 1) {
        return '';
    }

    if (preg_match('#^(javascript|data|vbscript):#i', $url) === 1) {
        return '';
    }

    if (preg_match('#^(https?://|mailto:|tel:|/|[a-z0-9._/-]+(?:\?[a-z0-9=&._%+\-/:]+)?(?:#[a-z0-9_-]+)?)#i', $url) !== 1) {
        return '';
    }

    return $url;
}

function site_save_image_upload(?array $file, array &$errors, string $prefix): ?string
{
    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        $errors[] = 'No se pudo subir la imagen. Vuelve a intentarlo.';
        return null;
    }

    if (($file['size'] ?? 0) > 3 * 1024 * 1024) {
        $errors[] = 'La imagen no puede superar los 3 MB.';
        return null;
    }

    $imageInfo = @getimagesize((string) ($file['tmp_name'] ?? ''));
    if (!$imageInfo || empty($imageInfo['mime'])) {
        $errors[] = 'La imagen debe ser un archivo valido.';
        return null;
    }

    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];
    $mime = (string) $imageInfo['mime'];
    if (!isset($extensions[$mime])) {
        $errors[] = 'La imagen debe estar en formato JPG, PNG o WebP.';
        return null;
    }

    $safePrefix = preg_replace('/[^a-z0-9_-]+/i', '-', strtolower($prefix)) ?: 'site';
    $filename = $safePrefix . '-' . gmdate('YmdHis') . '-' . bin2hex(random_bytes(6)) . '.' . $extensions[$mime];
    $destination = MEMBER_PHOTOS_DIR . DIRECTORY_SEPARATOR . $filename;
    $tmpName = (string) ($file['tmp_name'] ?? '');
    $moved = is_uploaded_file($tmpName)
        ? move_uploaded_file($tmpName, $destination)
        : rename($tmpName, $destination);

    if (!$moved) {
        $errors[] = 'No se pudo guardar la imagen.';
        return null;
    }

    return csf_media_url('member-photos/' . $filename);
}

function site_unique_service_slug(PDO $pdo, string $baseSlug, ?int $ignoreId = null): string
{
    $slug = $baseSlug !== '' ? $baseSlug : 'servicio';
    $candidate = $slug;
    $counter = 2;
    do {
        $sql = 'SELECT id FROM services WHERE slug = :slug';
        $params = ['slug' => $candidate];
        if ($ignoreId !== null) {
            $sql .= ' AND id <> :id';
            $params['id'] = $ignoreId;
        }
        $statement = $pdo->prepare($sql . ' LIMIT 1');
        $statement->execute($params);
        $exists = (bool) $statement->fetchColumn();
        if (!$exists) {
            return $candidate;
        }
        $candidate = $slug . '-' . $counter++;
    } while ($counter < 1000);

    return $slug . '-' . bin2hex(random_bytes(3));
}

function site_services_all(): array
{
    $pdo = site_repository_ready();
    if (!$pdo) {
        return [];
    }

    $statement = $pdo->query('SELECT s.*, uc.nombre AS created_by_name, uu.nombre AS updated_by_name FROM services s LEFT JOIN usuarios uc ON uc.id = s.created_by LEFT JOIN usuarios uu ON uu.id = s.updated_by ORDER BY s.display_order ASC, s.title ASC');
    return $statement ? $statement->fetchAll(PDO::FETCH_ASSOC) : [];
}

function site_services_active(?bool $featuredOnly = null, int $limit = 0): array
{
    $pdo = site_repository_ready();
    if (!$pdo) {
        return [];
    }

    $sql = "SELECT * FROM services WHERE status = 'ACTIVE'";
    if ($featuredOnly === true) {
        $sql .= ' AND is_featured = 1';
    }
    $sql .= ' ORDER BY is_featured DESC, display_order ASC, updated_at DESC, created_at DESC';
    if ($limit > 0) {
        $sql .= ' LIMIT ' . (int) $limit;
    }
    $statement = $pdo->query($sql);
    return $statement ? $statement->fetchAll(PDO::FETCH_ASSOC) : [];
}

function site_service_by_id(int $id): ?array
{
    $pdo = site_repository_ready();
    if (!$pdo) {
        return null;
    }

    $statement = $pdo->prepare('SELECT * FROM services WHERE id = :id LIMIT 1');
    $statement->execute(['id' => $id]);
    $service = $statement->fetch(PDO::FETCH_ASSOC);
    return $service ?: null;
}

function site_save_service(array $input, array $files, array $user): void
{
    $pdo = site_repository_ready();
    if (!$pdo) {
        throw new RuntimeException('La base de datos no esta disponible.');
    }

    $id = max(0, (int) ($input['service_id'] ?? 0));
    $existing = $id > 0 ? site_service_by_id($id) : null;
    if ($id > 0 && !$existing) {
        throw new RuntimeException('No se encontro el servicio indicado.');
    }

    $title = clean_text((string) ($input['title'] ?? ''));
    $short = clean_text((string) ($input['short_description'] ?? ''));
    $full = site_sanitize_html((string) ($input['full_description'] ?? ''));
    $status = in_array((string) ($input['status'] ?? 'ACTIVE'), site_allowed_service_statuses(), true) ? (string) $input['status'] : 'ACTIVE';
    $buttonUrl = site_sanitize_url((string) ($input['button_url'] ?? ''));
    $buttonText = clean_text((string) ($input['button_text'] ?? ''));
    $priceSuffix = clean_text((string) ($input['price_suffix'] ?? ''));
    $icon = clean_text((string) ($input['icon'] ?? ''));
    $displayOrder = (int) ($input['display_order'] ?? 0);
    $priceRaw = trim((string) ($input['price'] ?? ''));
    $price = null;
    $errors = [];

    if ($title === '') {
        $errors[] = 'El titulo del servicio es obligatorio.';
    }
    if ($short === '') {
        $errors[] = 'La descripcion breve del servicio es obligatoria.';
    }
    if ($buttonUrl === '' && trim((string) ($input['button_url'] ?? '')) !== '') {
        $errors[] = 'La URL del boton no es valida.';
    }
    if ($priceRaw !== '') {
        $normalizedPrice = str_replace(',', '.', $priceRaw);
        if (!is_numeric($normalizedPrice) || (float) $normalizedPrice < 0) {
            $errors[] = 'El precio debe ser un numero decimal positivo.';
        } else {
            $price = number_format((float) $normalizedPrice, 2, '.', '');
        }
    }

    $imagePath = (string) ($existing['image_path'] ?? '');
    $uploadedPath = site_save_image_upload($files['image'] ?? null, $errors, 'service');
    if ($uploadedPath !== null) {
        $imagePath = $uploadedPath;
    }

    if ($errors) {
        throw new InvalidArgumentException(implode(' ', $errors));
    }

    $slugInput = clean_text((string) ($input['slug'] ?? ''));
    $slug = site_unique_service_slug($pdo, slugify($slugInput !== '' ? $slugInput : $title), $id > 0 ? $id : null);
    $userId = (int) ($user['id'] ?? 0) ?: null;
    $params = [
        'title' => $title,
        'slug' => $slug,
        'short_description' => $short,
        'full_description' => $full,
        'image_path' => $imagePath !== '' ? $imagePath : null,
        'icon' => $icon !== '' ? $icon : null,
        'price' => $price,
        'price_suffix' => $priceSuffix !== '' ? $priceSuffix : null,
        'button_text' => $buttonText !== '' ? $buttonText : null,
        'button_url' => $buttonUrl !== '' ? $buttonUrl : null,
        'display_order' => $displayOrder,
        'is_featured' => !empty($input['is_featured']) ? 1 : 0,
        'status' => $status,
        'updated_by' => $userId,
    ];

    if ($id > 0) {
        $params['id'] = $id;
        $statement = $pdo->prepare('UPDATE services SET title = :title, slug = :slug, short_description = :short_description, full_description = :full_description, image_path = :image_path, icon = :icon, price = :price, price_suffix = :price_suffix, button_text = :button_text, button_url = :button_url, display_order = :display_order, is_featured = :is_featured, status = :status, updated_by = :updated_by WHERE id = :id');
        $statement->execute($params);
        return;
    }

    $params['created_by'] = $userId;
    $statement = $pdo->prepare('INSERT INTO services (title, slug, short_description, full_description, image_path, icon, price, price_suffix, button_text, button_url, display_order, is_featured, status, created_by, updated_by) VALUES (:title, :slug, :short_description, :full_description, :image_path, :icon, :price, :price_suffix, :button_text, :button_url, :display_order, :is_featured, :status, :created_by, :updated_by)');
    $statement->execute($params);
}

function site_toggle_service_status(int $id, string $status, array $user): void
{
    if (!in_array($status, site_allowed_service_statuses(), true)) {
        throw new InvalidArgumentException('Estado de servicio no valido.');
    }
    $pdo = site_repository_ready();
    if (!$pdo) {
        throw new RuntimeException('La base de datos no esta disponible.');
    }

    $statement = $pdo->prepare('UPDATE services SET status = :status, updated_by = :updated_by WHERE id = :id');
    $statement->execute(['status' => $status, 'updated_by' => (int) ($user['id'] ?? 0) ?: null, 'id' => $id]);
}

function site_toggle_service_featured(int $id, bool $featured, array $user): void
{
    $pdo = site_repository_ready();
    if (!$pdo) {
        throw new RuntimeException('La base de datos no esta disponible.');
    }

    $statement = $pdo->prepare('UPDATE services SET is_featured = :featured, updated_by = :updated_by WHERE id = :id');
    $statement->execute(['featured' => $featured ? 1 : 0, 'updated_by' => (int) ($user['id'] ?? 0) ?: null, 'id' => $id]);
}

function site_update_service_order(int $id, int $order, array $user): void
{
    $pdo = site_repository_ready();
    if (!$pdo) {
        throw new RuntimeException('La base de datos no esta disponible.');
    }

    $statement = $pdo->prepare('UPDATE services SET display_order = :display_order, updated_by = :updated_by WHERE id = :id');
    $statement->execute(['display_order' => $order, 'updated_by' => (int) ($user['id'] ?? 0) ?: null, 'id' => $id]);
}

function site_delete_service(int $id): void
{
    $pdo = site_repository_ready();
    if (!$pdo) {
        throw new RuntimeException('La base de datos no esta disponible.');
    }

    $statement = $pdo->prepare('DELETE FROM services WHERE id = :id');
    $statement->execute(['id' => $id]);
}

function site_contact_settings(): array
{
    $pdo = site_repository_ready();
    if (!$pdo) {
        return [];
    }

    $statement = $pdo->query('SELECT * FROM contact_settings WHERE id = 1 LIMIT 1');
    $settings = $statement ? $statement->fetch(PDO::FETCH_ASSOC) : false;
    return $settings ?: [];
}

function site_save_contact_settings(array $input, array $files, array $user): void
{
    $pdo = site_repository_ready();
    if (!$pdo) {
        throw new RuntimeException('La base de datos no esta disponible.');
    }

    $existing = site_contact_settings();
    $errors = [];
    $imagePath = (string) ($existing['image_path'] ?? '');
    $uploadedPath = site_save_image_upload($files['image'] ?? null, $errors, 'contact');
    if ($uploadedPath !== null) {
        $imagePath = $uploadedPath;
    }

    $email = trim((string) ($input['email'] ?? ''));
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'El email de contacto no es valido.';
    }

    $urlFields = ['whatsapp_url', 'facebook_url', 'instagram_url', 'youtube_url', 'tiktok_url'];
    $urls = [];
    foreach ($urlFields as $field) {
        $raw = trim((string) ($input[$field] ?? ''));
        $urls[$field] = site_sanitize_url($raw);
        if ($raw !== '' && $urls[$field] === '') {
            $errors[] = 'La URL de ' . $field . ' no es valida.';
        }
    }

    $title = clean_text((string) ($input['section_title'] ?? ''));
    if ($title === '') {
        $errors[] = 'El titulo del area de contacto es obligatorio.';
    }

    if ($errors) {
        throw new InvalidArgumentException(implode(' ', $errors));
    }

    $params = [
        'id' => 1,
        'section_title' => $title,
        'section_intro' => trim((string) ($input['section_intro'] ?? '')),
        'image_path' => $imagePath !== '' ? $imagePath : null,
        'image_alt' => clean_text((string) ($input['image_alt'] ?? '')) ?: null,
        'business_name' => clean_text((string) ($input['business_name'] ?? '')) ?: null,
        'contact_person' => clean_text((string) ($input['contact_person'] ?? '')) ?: null,
        'email' => $email !== '' ? $email : null,
        'phone' => clean_text((string) ($input['phone'] ?? '')) ?: null,
        'whatsapp' => clean_text((string) ($input['whatsapp'] ?? '')) ?: null,
        'whatsapp_url' => $urls['whatsapp_url'] ?: null,
        'address' => clean_text((string) ($input['address'] ?? '')) ?: null,
        'city' => clean_text((string) ($input['city'] ?? '')) ?: null,
        'province' => clean_text((string) ($input['province'] ?? '')) ?: null,
        'postal_code' => clean_text((string) ($input['postal_code'] ?? '')) ?: null,
        'opening_hours' => trim((string) ($input['opening_hours'] ?? '')) ?: null,
        'phone_button_text' => clean_text((string) ($input['phone_button_text'] ?? '')) ?: null,
        'whatsapp_button_text' => clean_text((string) ($input['whatsapp_button_text'] ?? '')) ?: null,
        'facebook_url' => $urls['facebook_url'] ?: null,
        'instagram_url' => $urls['instagram_url'] ?: null,
        'youtube_url' => $urls['youtube_url'] ?: null,
        'tiktok_url' => $urls['tiktok_url'] ?: null,
        'is_enabled' => !empty($input['is_enabled']) ? 1 : 0,
        'show_email' => !empty($input['show_email']) ? 1 : 0,
        'show_phone' => !empty($input['show_phone']) ? 1 : 0,
        'show_whatsapp' => !empty($input['show_whatsapp']) ? 1 : 0,
        'show_address' => !empty($input['show_address']) ? 1 : 0,
        'show_opening_hours' => !empty($input['show_opening_hours']) ? 1 : 0,
        'updated_by' => (int) ($user['id'] ?? 0) ?: null,
    ];

    $statement = $pdo->prepare('REPLACE INTO contact_settings (id, section_title, section_intro, image_path, image_alt, business_name, contact_person, email, phone, whatsapp, whatsapp_url, address, city, province, postal_code, opening_hours, phone_button_text, whatsapp_button_text, facebook_url, instagram_url, youtube_url, tiktok_url, is_enabled, show_email, show_phone, show_whatsapp, show_address, show_opening_hours, updated_by) VALUES (:id, :section_title, :section_intro, :image_path, :image_alt, :business_name, :contact_person, :email, :phone, :whatsapp, :whatsapp_url, :address, :city, :province, :postal_code, :opening_hours, :phone_button_text, :whatsapp_button_text, :facebook_url, :instagram_url, :youtube_url, :tiktok_url, :is_enabled, :show_email, :show_phone, :show_whatsapp, :show_address, :show_opening_hours, :updated_by)');
    $statement->execute($params);
}

function site_public_contact_submit(array $post, array $server): array
{
    $errors = [];
    if (!verify_csrf($post['csrf_token'] ?? null)) {
        $errors[] = 'La sesion ha caducado. Vuelve a intentarlo.';
    }
    if (trim((string) ($post['website'] ?? '')) !== '') {
        return ['ok' => true, 'message' => 'Gracias. Hemos recibido tu mensaje.', 'errors' => []];
    }

    $now = time();
    $_SESSION['contact_form_attempts'] = array_values(array_filter(
        $_SESSION['contact_form_attempts'] ?? [],
        static fn ($timestamp): bool => is_numeric($timestamp) && (int) $timestamp > $now - 60
    ));
    if (count($_SESSION['contact_form_attempts']) >= 3) {
        $errors[] = 'Has enviado varios mensajes seguidos. Espera un minuto antes de intentarlo de nuevo.';
    }

    $name = clean_text((string) ($post['name'] ?? ''));
    $email = trim((string) ($post['email'] ?? ''));
    $phone = clean_text((string) ($post['phone'] ?? ''));
    $type = (string) ($post['inquiry_type'] ?? 'OTRO');
    $subject = clean_text((string) ($post['subject'] ?? ''));
    $message = trim((string) ($post['message'] ?? ''));
    if ($name === '') {
        $errors[] = 'Indica tu nombre.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Indica un email valido.';
    }
    if (!array_key_exists($type, site_inquiry_types())) {
        $type = 'OTRO';
    }
    if ($subject === '') {
        $errors[] = 'Indica el asunto.';
    }
    if (strlen($message) < 10) {
        $errors[] = 'El mensaje debe tener al menos 10 caracteres.';
    }
    if (empty($post['privacy_accepted'])) {
        $errors[] = 'Debes aceptar la politica de privacidad.';
    }

    $pdo = site_repository_ready();
    if (!$pdo) {
        $errors[] = 'No se pudo guardar el mensaje porque la base de datos no esta disponible.';
    }
    if ($errors) {
        return ['ok' => false, 'message' => '', 'errors' => $errors];
    }

    $ipHash = hash('sha256', (string) ($server['REMOTE_ADDR'] ?? '') . '|' . APP_NAME);
    $statement = $pdo->prepare('INSERT INTO contact_messages (name, email, phone, inquiry_type, subject, message, privacy_accepted, status, ip_hash, user_agent) VALUES (:name, :email, :phone, :inquiry_type, :subject, :message, 1, :status, :ip_hash, :user_agent)');
    $statement->execute([
        'name' => $name,
        'email' => $email,
        'phone' => $phone !== '' ? $phone : null,
        'inquiry_type' => $type,
        'subject' => $subject,
        'message' => $message,
        'status' => 'NEW',
        'ip_hash' => $ipHash,
        'user_agent' => substr((string) ($server['HTTP_USER_AGENT'] ?? ''), 0, 255),
    ]);
    $_SESSION['contact_form_attempts'][] = $now;

    site_send_contact_notification(site_contact_settings(), [
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'inquiry_type' => $type,
        'subject' => $subject,
        'message' => $message,
    ]);

    return ['ok' => true, 'message' => 'Gracias. Hemos recibido tu mensaje y te responderemos lo antes posible.', 'errors' => []];
}

function site_send_contact_notification(array $settings, array $message): bool
{
    $to = filter_var((string) ($settings['email'] ?? ''), FILTER_VALIDATE_EMAIL) ? (string) $settings['email'] : APP_EMAIL;
    $subject = 'Nuevo mensaje de contacto - ' . APP_NAME;
    $body = "Nuevo mensaje de contacto\n\n"
        . 'Nombre: ' . ($message['name'] ?? '') . "\n"
        . 'Email: ' . ($message['email'] ?? '') . "\n"
        . 'Telefono: ' . ($message['phone'] ?? '') . "\n"
        . 'Tipo: ' . ($message['inquiry_type'] ?? '') . "\n"
        . 'Asunto: ' . ($message['subject'] ?? '') . "\n\n"
        . (string) ($message['message'] ?? '');
    $fromAddress = csf_env('CSF_MAIL_FROM_ADDRESS', APP_EMAIL);
    $fromName = csf_env('CSF_MAIL_FROM_NAME', APP_NAME);
    $headers = [
        'From: ' . $fromName . ' <' . $fromAddress . '>',
        'Reply-To: ' . (string) ($message['email'] ?? $fromAddress),
        'Content-Type: text/plain; charset=UTF-8',
    ];
    $headerText = implode("\r\n", $headers);
    $sent = false;
    $usedMethod = 'none';

    if (!csf_env_bool('CSF_MAIL_USE_SMTP', false) && function_exists('mail')) {
        $sent = @mail($to, $subject, $body, $headerText, '-f' . escapeshellarg($fromAddress));
        $usedMethod = 'mail';
    }
    if (!$sent && csf_env('CSF_SMTP_HOST')) {
        $sent = smtp_send_email($to, $subject, $body, $headers);
        $usedMethod = 'smtp';
    }

    $logEntry = '[' . gmdate('c') . '] ' . ($sent ? 'SENT' : 'FAILED') . " METHOD: {$usedMethod} To: {$to}\nSubject: {$subject}\nHeaders: {$headerText}\n\n{$body}\n\n";
    if (!is_dir(dirname(MAIL_LOG_FILE))) {
        @mkdir(dirname(MAIL_LOG_FILE), 0775, true);
    }
    @file_put_contents(MAIL_LOG_FILE, $logEntry, FILE_APPEND | LOCK_EX);

    return $sent;
}

function site_contact_messages(int $limit = 100): array
{
    $pdo = site_repository_ready();
    if (!$pdo) {
        return [];
    }

    $statement = $pdo->query('SELECT cm.*, u.nombre AS assigned_to_name FROM contact_messages cm LEFT JOIN usuarios u ON u.id = cm.assigned_to ORDER BY cm.created_at DESC LIMIT ' . max(1, $limit));
    return $statement ? $statement->fetchAll(PDO::FETCH_ASSOC) : [];
}

function site_contact_message_by_id(int $id): ?array
{
    $pdo = site_repository_ready();
    if (!$pdo) {
        return null;
    }

    $statement = $pdo->prepare('SELECT * FROM contact_messages WHERE id = :id LIMIT 1');
    $statement->execute(['id' => $id]);
    $message = $statement->fetch(PDO::FETCH_ASSOC);
    return $message ?: null;
}

function site_update_contact_message_status(int $id, string $status): void
{
    if (!in_array($status, site_contact_statuses(), true)) {
        throw new InvalidArgumentException('Estado de mensaje no valido.');
    }
    $pdo = site_repository_ready();
    if (!$pdo) {
        throw new RuntimeException('La base de datos no esta disponible.');
    }

    $fields = 'status = :status';
    if ($status === 'READ') {
        $fields .= ', read_at = COALESCE(read_at, UTC_TIMESTAMP())';
    }
    if ($status === 'ANSWERED') {
        $fields .= ', read_at = COALESCE(read_at, UTC_TIMESTAMP()), answered_at = COALESCE(answered_at, UTC_TIMESTAMP())';
    }

    $statement = $pdo->prepare('UPDATE contact_messages SET ' . $fields . ' WHERE id = :id');
    $statement->execute(['status' => $status, 'id' => $id]);
}

function site_delete_contact_message(int $id): void
{
    $pdo = site_repository_ready();
    if (!$pdo) {
        throw new RuntimeException('La base de datos no esta disponible.');
    }

    $statement = $pdo->prepare('DELETE FROM contact_messages WHERE id = :id');
    $statement->execute(['id' => $id]);
}
