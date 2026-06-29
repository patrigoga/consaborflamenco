<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';

function read_json_file(string $path, array $fallback): array
{
    if (!file_exists($path)) {
        return $fallback;
    }

    $handle = fopen($path, 'rb');
    if ($handle === false) {
        return $fallback;
    }

    flock($handle, LOCK_SH);
    $content = stream_get_contents($handle);
    flock($handle, LOCK_UN);
    fclose($handle);

    $decoded = json_decode($content ?: '', true);
    return is_array($decoded) ? $decoded : $fallback;
}

function write_json_file(string $path, array $data): void
{
    $handle = fopen($path, 'c+b');
    if ($handle === false) {
        throw new RuntimeException('No se pudo abrir el almacenamiento.');
    }

    flock($handle, LOCK_EX);
    ftruncate($handle, 0);
    rewind($handle);
    fwrite($handle, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    fflush($handle);
    flock($handle, LOCK_UN);
    fclose($handle);
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verify_csrf(?string $token): bool
{
    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function clean_text(string $value): string
{
    return trim(preg_replace('/\s+/', ' ', $value) ?? '');
}

function sanitize_css_style(string $style): string
{
    $allowedProperties = [
        'color',
        'background-color',
        'font-size',
        'font-weight',
        'font-style',
        'text-decoration',
        'text-align',
    ];
    $cleanRules = [];
    preg_match_all('/([a-z-]+)\s*:\s*([^;]+);?/i', $style, $matches, PREG_SET_ORDER);
    foreach ($matches as $match) {
        $property = strtolower(trim($match[1]));
        $value = trim($match[2]);
        if (!in_array($property, $allowedProperties, true)) {
            continue;
        }
        if (preg_match('/^(#([0-9a-f]{3}|[0-9a-f]{6})|rgba?\([^\)]+\)|[a-z ]+)$/i', $value)) {
            $cleanRules[] = $property . ': ' . $value;
        }
    }
    return implode('; ', $cleanRules);
}

function sanitize_html(string $html): string
{
    $allowedTags = ['b', 'strong', 'i', 'em', 'u', 'span', 'br', 'p', 'div', 'h3', 'blockquote', 'ul', 'ol', 'li'];
    $html = strip_tags($html, '<b><strong><i><em><u><span><br><p><div><h3><blockquote><ul><ol><li>');

    return preg_replace_callback(
        '/<(\/)?([a-z0-9]+)([^>]*)>/i',
        static function (array $matches) use ($allowedTags): string {
            $slash = $matches[1] ?? '';
            $tag = strtolower($matches[2] ?? '');
            $attrs = $matches[3] ?? '';
            if (!in_array($tag, $allowedTags, true)) {
                return '';
            }
            if (in_array($tag, ['span', 'p', 'div'], true)) {
                $style = '';
                if (preg_match('/style\s*=\s*("([^"]*)"|\'([^\']*)\')/i', $attrs, $styleMatch)) {
                    $style = sanitize_css_style($styleMatch[2] ?? $styleMatch[3] ?? '');
                }
                return '<' . $slash . $tag . ($style !== '' ? ' style="' . htmlspecialchars($style, ENT_QUOTES, 'UTF-8') . '"' : '') . '>';
            }
            return '<' . $slash . $tag . '>';
        },
        $html
    );
}

function clean_html_text(string $value): string
{
    $value = trim($value);
    $value = preg_replace('/\s+/', ' ', $value);

    return sanitize_html($value);
}

function normalize_email(string $email): string
{
    return strtolower(trim($email));
}

function member_type_options(): array
{
    return [
        'artista' => 'Artista',
        'academia' => 'Academia',
        'tienda' => 'Tienda flamenca',
        'pena' => 'Pena flamenca',
        'tablao' => 'Tablao flamenco',
        'festival' => 'Festival',
        'profesional' => 'Profesional flamenco',
    ];
}

function normalize_member_type(string $type): string
{
    return array_key_exists($type, member_type_options()) ? $type : 'artista';
}

function default_member_profile(array $user = []): array
{
    $profile = is_array($user['artistic_profile'] ?? null) ? $user['artistic_profile'] : [];

    return array_merge([
        'member_type' => 'artista',
        'public_name' => $user['name'] ?? '',
        'artistic_headline' => '',
        'short_description' => '',
        'cv_summary' => '',
        'specialties' => '',
        'years_active' => '',
        'availability' => '',
        'birth_place' => '',
        'city' => '',
        'province' => '',
        'phone' => '',
        'website_url' => '',
        'instagram_url' => '',
        'main_photo_path' => '',
        'public_fields' => [],
        'sort_orders' => [],
        'education' => [],
        'experience' => [],
        'teaching' => [],
        'performances' => [],
        'awards' => [],
        'repertoire' => [],
        'social_links' => [],
        'private_notes' => '',
        'completed_at' => null,
    ], $profile);
}

function profile_is_complete(array $profile): bool
{
    return clean_text((string) ($profile['public_name'] ?? '')) !== ''
        && clean_text((string) ($profile['short_description'] ?? '')) !== ''
        && clean_text((string) ($profile['city'] ?? '')) !== ''
        && clean_text((string) ($profile['province'] ?? '')) !== ''
        && clean_text((string) ($profile['main_photo_path'] ?? '')) !== ''
        && (
            !empty($profile['education'])
            || !empty($profile['experience'])
            || !empty($profile['performances'])
        );
}

function member_profile_from_input(array $input, array $existingProfile = []): array
{
    $profile = default_member_profile(['artistic_profile' => $existingProfile]);
    $profile['member_type'] = normalize_member_type((string) ($input['member_type'] ?? $profile['member_type']));
    $profile['public_name'] = clean_text((string) ($input['public_name'] ?? $input['name'] ?? $profile['public_name']));
    $profile['artistic_headline'] = clean_text((string) ($input['artistic_headline'] ?? $profile['artistic_headline']));
    $profile['short_description'] = clean_text((string) ($input['short_description'] ?? $profile['short_description']));
    $profile['cv_summary'] = clean_text((string) ($input['cv_summary'] ?? $profile['cv_summary']));
    $profile['specialties'] = clean_text((string) ($input['specialties'] ?? $profile['specialties']));
    $profile['years_active'] = clean_text((string) ($input['years_active'] ?? $profile['years_active']));
    $profile['availability'] = clean_text((string) ($input['availability'] ?? $profile['availability']));
    $profile['birth_place'] = clean_text((string) ($input['birth_place'] ?? $profile['birth_place']));
    $profile['city'] = clean_text((string) ($input['city'] ?? $profile['city']));
    $profile['province'] = clean_text((string) ($input['province'] ?? $profile['province']));
    $profile['phone'] = clean_text((string) ($input['phone'] ?? $profile['phone']));
    $profile['website_url'] = trim((string) ($input['website_url'] ?? $profile['website_url']));
    $profile['instagram_url'] = trim((string) ($input['instagram_url'] ?? $profile['instagram_url']));
    $profile['private_notes'] = clean_text((string) ($input['private_notes'] ?? $profile['private_notes']));
    $profile['main_photo_path'] = clean_text((string) ($existingProfile['main_photo_path'] ?? $profile['main_photo_path']));
    $profile['completed_at'] = profile_is_complete($profile) ? ($profile['completed_at'] ?? gmdate('c')) : null;

    return $profile;
}

function save_member_photo_upload(?array $file, array &$errors, bool $required = false): ?string
{
    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        if ($required) {
            $errors[] = 'Sube al menos una fotografia principal para crear tu espacio.';
        }
        return null;
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        $errors[] = 'No se pudo subir la fotografia. Vuelve a intentarlo.';
        return null;
    }

    if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
        $errors[] = 'La fotografia no puede superar los 5 MB.';
        return null;
    }

    $imageInfo = @getimagesize((string) ($file['tmp_name'] ?? ''));
    if (!$imageInfo || empty($imageInfo['mime'])) {
        $errors[] = 'La fotografia debe ser una imagen valida.';
        return null;
    }

    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];
    $mime = (string) $imageInfo['mime'];
    if (!isset($extensions[$mime])) {
        $errors[] = 'La fotografia debe estar en formato JPG, PNG o WebP.';
        return null;
    }

    $filename = 'member-' . gmdate('YmdHis') . '-' . bin2hex(random_bytes(6)) . '.' . $extensions[$mime];
    $destination = MEMBER_PHOTOS_DIR . '/' . $filename;
    $tmpName = (string) ($file['tmp_name'] ?? '');
    $moved = is_uploaded_file($tmpName)
        ? move_uploaded_file($tmpName, $destination)
        : rename($tmpName, $destination);

    if (!$moved) {
        $errors[] = 'No se pudo guardar la fotografia en el perfil.';
        return null;
    }

    return MEMBER_PHOTOS_URL . '/' . $filename;
}

function save_member_cv_image_upload(?array $file, array &$errors): ?string
{
    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        $errors[] = 'No se pudo subir una imagen del curriculum. Vuelve a intentarlo.';
        return null;
    }

    if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
        $errors[] = 'Cada imagen del curriculum debe pesar menos de 5 MB.';
        return null;
    }

    $imageInfo = @getimagesize((string) ($file['tmp_name'] ?? ''));
    if (!$imageInfo || empty($imageInfo['mime'])) {
        $errors[] = 'Las imagenes del curriculum deben ser imagenes validas.';
        return null;
    }

    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];
    $mime = (string) $imageInfo['mime'];
    if (!isset($extensions[$mime])) {
        $errors[] = 'Las imagenes del curriculum deben estar en formato JPG, PNG o WebP.';
        return null;
    }

    $filename = 'cv-' . gmdate('YmdHis') . '-' . bin2hex(random_bytes(6)) . '.' . $extensions[$mime];
    $destination = MEMBER_CV_IMAGES_DIR . '/' . $filename;
    $tmpName = (string) ($file['tmp_name'] ?? '');
    $moved = is_uploaded_file($tmpName)
        ? move_uploaded_file($tmpName, $destination)
        : rename($tmpName, $destination);

    if (!$moved) {
        $errors[] = 'No se pudo guardar una imagen del curriculum.';
        return null;
    }

    return MEMBER_CV_IMAGES_URL . '/' . $filename;
}

function all_users(): array
{
    $pdo = auth_database();
    if ($pdo) {
        $statement = $pdo->query(db_user_select_sql() . ' ORDER BY u.created_at DESC, u.id DESC');
        return array_map('db_user_from_row', $statement->fetchAll());
    }

    return read_json_file(USERS_FILE, []);
}

function save_users(array $users): void
{
    write_json_file(USERS_FILE, array_values($users));
}

function find_user_by_email(string $email): ?array
{
    $normalizedEmail = normalize_email($email);
    $pdo = auth_database();
    if ($pdo) {
        $statement = $pdo->prepare(db_user_select_sql() . ' WHERE u.email = :email LIMIT 1');
        $statement->execute(['email' => $normalizedEmail]);
        $row = $statement->fetch();

        return $row ? db_user_from_row($row) : null;
    }

    foreach (all_users() as $user) {
        if (($user['email'] ?? '') === $normalizedEmail) {
            return $user;
        }
    }

    return null;
}

function normalize_name(string $name): string
{
    return mb_strtolower(clean_text($name), 'UTF-8');
}

function find_user_by_name_and_email(string $name, string $email): ?array
{
    $normalizedName = normalize_name($name);
    $user = find_user_by_email($email);
    if (!$user) {
        return null;
    }

    $possibleNames = [
        normalize_name((string) ($user['name'] ?? '')),
        normalize_name((string) ($user['public_name'] ?? '')),
    ];

    if (!in_array($normalizedName, $possibleNames, true)) {
        return null;
    }

    return $user;
}

function find_user_by_id(string $id): ?array
{
    $pdo = auth_database();
    if ($pdo) {
        $statement = $pdo->prepare(db_user_select_sql() . ' WHERE u.uuid = :uuid LIMIT 1');
        $statement->execute(['uuid' => $id]);
        $row = $statement->fetch();

        return $row ? db_user_from_row($row) : null;
    }

    foreach (all_users() as $user) {
        if (($user['id'] ?? '') === $id) {
            return $user;
        }
    }

    return null;
}

function create_user(string $name, string $email, string $password, array $memberProfile = []): array
{
    $normalizedEmail = normalize_email($email);
    $displayName = clean_text($name) !== '' ? clean_text($name) : name_from_email($normalizedEmail);
    $pdo = auth_database();

    if ($pdo) {
        if (db_email_exists($pdo, $normalizedEmail)) {
            throw new InvalidArgumentException('Ya existe una cuenta con ese email.');
        }

        $now = gmdate('c');
        $profile = member_profile_from_input(['name' => $displayName] + $memberProfile, $memberProfile);
        $user = [
            'id' => bin2hex(random_bytes(16)),
            'name' => $displayName,
            'email' => $normalizedEmail,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role' => 'user',
            'membership_tier' => 'simpatizante',
            'artistic_profile' => $profile,
            'email_verified_at' => null,
            'terms_accepted_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
            'last_login_at' => null,
        ];

        db_insert_legacy_user($pdo, $user);
        return find_user_by_email($normalizedEmail) ?? $user;
    }

    $users = all_users();

    foreach ($users as $user) {
        if (($user['email'] ?? '') === $normalizedEmail) {
            throw new InvalidArgumentException('Ya existe una cuenta con ese email.');
        }
    }

    $now = gmdate('c');
    $user = [
        'id' => bin2hex(random_bytes(16)),
        'name' => $displayName,
        'email' => $normalizedEmail,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'role' => 'user',
        'membership_tier' => 'simpatizante',
        'artistic_profile' => member_profile_from_input(['name' => $displayName] + $memberProfile, $memberProfile),
        'email_verified_at' => null,
        'terms_accepted_at' => $now,
        'created_at' => $now,
        'updated_at' => $now,
        'last_login_at' => null,
    ];

    $users[] = $user;
    save_users($users);

    return $user;
}

function update_user(array $updatedUser): void
{
    $pdo = auth_database();
    if ($pdo) {
        db_update_legacy_user($pdo, $updatedUser);
        return;
    }

    $users = all_users();
    foreach ($users as $index => $user) {
        if (($user['id'] ?? '') === ($updatedUser['id'] ?? null)) {
            $updatedUser['updated_at'] = gmdate('c');
            $users[$index] = $updatedUser;
            save_users($users);
            return;
        }
    }
}

function authenticate_user(string $email, string $password): ?array
{
    $user = find_user_by_email($email);
    if (!$user || !password_verify($password, $user['password_hash'] ?? '')) {
        return null;
    }

    return $user;
}

function login_user(array $user): void
{
    $user['last_login_at'] = gmdate('c');
    update_user($user);

    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_role'] = $user['role'] ?? 'user';
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function current_user(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    return find_user_by_id((string) $_SESSION['user_id']);
}

function require_login(): array
{
    $user = current_user();
    if (!$user) {
        redirect_to('acceso.php');
    }

    if (!user_email_is_verified($user)) {
        $email = (string) ($user['email'] ?? '');
        logout_user();
        redirect_to('verificacion-pendiente.php?email=' . urlencode($email));
    }

    return $user;
}

function user_email_is_verified(array $user): bool
{
    if (($user['role'] ?? 'user') === 'admin') {
        return true;
    }

    return clean_text((string) ($user['email_verified_at'] ?? '')) !== '';
}

function reset_tokens(): array
{
    return read_json_file(RESET_TOKENS_FILE, []);
}

function save_reset_tokens(array $tokens): void
{
    write_json_file(RESET_TOKENS_FILE, array_values($tokens));
}

function verification_tokens(): array
{
    return read_json_file(EMAIL_VERIFICATION_TOKENS_FILE, []);
}

function save_verification_tokens(array $tokens): void
{
    write_json_file(EMAIL_VERIFICATION_TOKENS_FILE, array_values($tokens));
}

function create_email_verification_token(string $email): ?string
{
    $user = find_user_by_email($email);
    if (!$user) {
        return null;
    }

    $plainToken = bin2hex(random_bytes(24));
    $tokens = array_filter(verification_tokens(), static function (array $token): bool {
        return strtotime($token['expires_at'] ?? '') > time() && empty($token['used_at']);
    });

    $tokens[] = [
        'email' => $user['email'],
        'token_hash' => hash('sha256', $plainToken),
        'expires_at' => gmdate('c', time() + 24 * 3600),
        'created_at' => gmdate('c'),
        'used_at' => null,
    ];

    save_verification_tokens($tokens);

    return $plainToken;
}

function consume_email_verification_token(string $plainToken): bool
{
    $tokenHash = hash('sha256', $plainToken);
    $pdo = auth_database();
    if ($pdo) {
        $statement = $pdo->prepare('SELECT prt.*, u.uuid, u.id AS usuario_id, u.email FROM password_reset_tokens prt INNER JOIN usuarios u ON u.id = prt.usuario_id WHERE prt.token_hash = :token_hash LIMIT 1');
        // We don't have a dedicated DB table for email verification tokens; use JSON tokens below.
    }

    $tokens = verification_tokens();
    foreach ($tokens as $index => $token) {
        $isValid = empty($token['used_at'])
            && strtotime($token['expires_at'] ?? '') > time()
            && hash_equals($token['token_hash'] ?? '', $tokenHash);

        if (!$isValid) {
            continue;
        }

        $user = find_user_by_email($token['email'] ?? '');
        if (!$user) {
            return false;
        }

        // mark verified
        $user['email_verified_at'] = gmdate('c');
        update_user($user);
        $tokens[$index]['used_at'] = gmdate('c');
        save_verification_tokens($tokens);

        return true;
    }

    return false;
}

function create_password_reset_token(string $email): ?string
{
    $user = find_user_by_email($email);
    if (!$user) {
        return null;
    }

    $plainToken = bin2hex(random_bytes(32));
    $pdo = auth_database();
    if ($pdo && !empty($user['db_id'])) {
        $statement = $pdo->prepare(
            'INSERT INTO password_reset_tokens (usuario_id, token_hash, expires_at) VALUES (:usuario_id, :token_hash, :expires_at)'
        );
        $statement->execute([
            'usuario_id' => (int) $user['db_id'],
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => db_datetime(gmdate('c', time() + 3600)),
        ]);

        return $plainToken;
    }

    $tokens = array_filter(reset_tokens(), static function (array $token): bool {
        return strtotime($token['expires_at'] ?? '') > time() && empty($token['used_at']);
    });

    $tokens[] = [
        'email' => $user['email'],
        'token_hash' => hash('sha256', $plainToken),
        'expires_at' => gmdate('c', time() + 3600),
        'created_at' => gmdate('c'),
        'used_at' => null,
    ];

    save_reset_tokens($tokens);

    return $plainToken;
}

function consume_password_reset_token(string $plainToken, string $newPassword): bool
{
    $tokenHash = hash('sha256', $plainToken);
    $pdo = auth_database();
    if ($pdo) {
        $statement = $pdo->prepare(
            'SELECT prt.*, u.uuid FROM password_reset_tokens prt INNER JOIN usuarios u ON u.id = prt.usuario_id WHERE prt.token_hash = :token_hash AND prt.used_at IS NULL AND prt.expires_at > UTC_TIMESTAMP() LIMIT 1'
        );
        $statement->execute(['token_hash' => $tokenHash]);
        $token = $statement->fetch();

        if (!$token) {
            return false;
        }

        $pdo->beginTransaction();
        $updateUser = $pdo->prepare('UPDATE usuarios SET password_hash = :password_hash, updated_at = UTC_TIMESTAMP() WHERE id = :id');
        $updateUser->execute([
            'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
            'id' => (int) $token['usuario_id'],
        ]);
        $updateToken = $pdo->prepare('UPDATE password_reset_tokens SET used_at = UTC_TIMESTAMP() WHERE id = :id');
        $updateToken->execute(['id' => (int) $token['id']]);
        $pdo->commit();

        return true;
    }

    $tokens = reset_tokens();

    foreach ($tokens as $index => $token) {
        $isValid = empty($token['used_at'])
            && strtotime($token['expires_at'] ?? '') > time()
            && hash_equals($token['token_hash'] ?? '', $tokenHash);

        if (!$isValid) {
            continue;
        }

        $user = find_user_by_email($token['email'] ?? '');
        if (!$user) {
            return false;
        }

        $user['password_hash'] = password_hash($newPassword, PASSWORD_DEFAULT);
        update_user($user);
        $tokens[$index]['used_at'] = gmdate('c');
        save_reset_tokens($tokens);

        return true;
    }

    return false;
}

function auth_database(): ?PDO
{
    static $ready = false;
    static $available = null;

    $pdo = db();
    if (!$pdo) {
        return null;
    }

    if ($ready && $available) {
        return $pdo;
    }

    migrate_json_users_to_database($pdo);
    ensure_default_admin_user($pdo);
    $ready = true;
    $available = true;

    return $pdo;
}

function db_user_select_sql(): string
{
    return "SELECT
        u.*,
        m.id AS miembro_id,
        m.nombre_publico,
        m.numero_miembro,
        m.codigo_descuento,
        m.estado AS miembro_estado,
        m.biografia,
        m.ciudad,
        m.provincia_texto,
        m.telefono,
        m.foto_principal_path,
        m.web_url,
        m.instagram_url,
        m.perfil_json,
        m.perfil_completo_at,
        tm.slug AS tipo_miembro_slug,
        tm.nombre AS tipo_miembro_nombre
    FROM usuarios u
    LEFT JOIN miembros m ON m.usuario_id = u.id
    LEFT JOIN tipos_miembro tm ON tm.id = m.tipo_miembro_id";
}

function db_user_from_row(array $row): array
{
    $profileData = [];
    if (!empty($row['perfil_json'])) {
        $decoded = json_decode((string) $row['perfil_json'], true);
        $profileData = is_array($decoded) ? $decoded : [];
    }

    $profile = default_member_profile([
        'name' => (string) ($row['nombre'] ?? ''),
        'artistic_profile' => $profileData,
    ]);

    if (!empty($row['tipo_miembro_slug'])) {
        $profile['member_type'] = (string) $row['tipo_miembro_slug'];
    }
    if (($profile['public_name'] ?? '') === '' && !empty($row['nombre_publico'])) {
        $profile['public_name'] = (string) $row['nombre_publico'];
    }
    if (($profile['short_description'] ?? '') === '' && !empty($row['biografia'])) {
        $profile['short_description'] = (string) $row['biografia'];
    }
    if (($profile['city'] ?? '') === '' && !empty($row['ciudad'])) {
        $profile['city'] = (string) $row['ciudad'];
    }
    if (($profile['province'] ?? '') === '' && !empty($row['provincia_texto'])) {
        $profile['province'] = (string) $row['provincia_texto'];
    }
    if (($profile['phone'] ?? '') === '' && !empty($row['telefono'])) {
        $profile['phone'] = (string) $row['telefono'];
    }
    if (($profile['main_photo_path'] ?? '') === '' && !empty($row['foto_principal_path'])) {
        $profile['main_photo_path'] = (string) $row['foto_principal_path'];
    }
    if (($profile['website_url'] ?? '') === '' && !empty($row['web_url'])) {
        $profile['website_url'] = (string) $row['web_url'];
    }
    if (($profile['instagram_url'] ?? '') === '' && !empty($row['instagram_url'])) {
        $profile['instagram_url'] = (string) $row['instagram_url'];
    }

    $role = match ((string) ($row['rol'] ?? 'MIEMBRO')) {
        'ADMIN' => 'admin',
        'SETTER' => 'setter',
        default => 'user',
    };
    $memberState = strtolower((string) ($row['miembro_estado'] ?? 'SIMPATIZANTE'));

    return [
        'id' => (string) ($row['uuid'] ?? ''),
        'db_id' => (int) ($row['id'] ?? 0),
        'member_db_id' => isset($row['miembro_id']) ? (int) $row['miembro_id'] : null,
        'name' => (string) ($row['nombre'] ?? ''),
        'email' => (string) ($row['email'] ?? ''),
        'password_hash' => (string) ($row['password_hash'] ?? ''),
        'role' => $role,
        'account_status' => strtolower((string) ($row['estado'] ?? 'ACTIVO')),
        'membership_tier' => $memberState === 'vip' ? 'vip' : 'simpatizante',
        'member_number' => isset($row['numero_miembro']) ? (string) $row['numero_miembro'] : null,
        'member_code' => isset($row['codigo_descuento']) ? (string) $row['codigo_descuento'] : null,
        'artistic_profile' => $profile,
        'email_verified_at' => db_to_iso($row['email_verified_at'] ?? null),
        'terms_accepted_at' => db_to_iso($row['terms_accepted_at'] ?? null),
        'created_at' => db_to_iso($row['created_at'] ?? null),
        'updated_at' => db_to_iso($row['updated_at'] ?? null),
        'last_login_at' => db_to_iso($row['last_login_at'] ?? null),
    ];
}

function db_email_exists(PDO $pdo, string $email): bool
{
    $statement = $pdo->prepare('SELECT COUNT(*) FROM usuarios WHERE email = :email');
    $statement->execute(['email' => normalize_email($email)]);

    return (int) $statement->fetchColumn() > 0;
}

function db_find_user_id_by_uuid(PDO $pdo, string $uuid): ?int
{
    $statement = $pdo->prepare('SELECT id FROM usuarios WHERE uuid = :uuid LIMIT 1');
    $statement->execute(['uuid' => $uuid]);
    $id = $statement->fetchColumn();

    return $id ? (int) $id : null;
}

function db_insert_legacy_user(PDO $pdo, array $user): void
{
    $role = db_role_from_legacy((string) ($user['role'] ?? 'user'));
    $pdo->beginTransaction();

    try {
        $statement = $pdo->prepare(
            'INSERT INTO usuarios (uuid, nombre, email, password_hash, rol, estado, email_verified_at, terms_accepted_at, last_login_at, created_at, updated_at)
            VALUES (:uuid, :nombre, :email, :password_hash, :rol, :estado, :email_verified_at, :terms_accepted_at, :last_login_at, :created_at, :updated_at)'
        );
        $statement->execute([
            'uuid' => (string) ($user['id'] ?? bin2hex(random_bytes(16))),
            'nombre' => clean_text((string) ($user['name'] ?? name_from_email((string) ($user['email'] ?? '')))),
            'email' => normalize_email((string) ($user['email'] ?? '')),
            'password_hash' => (string) ($user['password_hash'] ?? ''),
            'rol' => $role,
            'estado' => 'ACTIVO',
            'email_verified_at' => db_nullable_datetime($user['email_verified_at'] ?? null),
            'terms_accepted_at' => db_nullable_datetime($user['terms_accepted_at'] ?? null),
            'last_login_at' => db_nullable_datetime($user['last_login_at'] ?? null),
            'created_at' => db_nullable_datetime($user['created_at'] ?? null) ?? db_datetime(),
            'updated_at' => db_nullable_datetime($user['updated_at'] ?? null) ?? db_datetime(),
        ]);

        $userId = (int) $pdo->lastInsertId();
        if ($role !== 'ADMIN') {
            db_upsert_member_for_user($pdo, $userId, $user);
        }

        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}

function db_update_legacy_user(PDO $pdo, array $updatedUser): void
{
    $uuid = (string) ($updatedUser['id'] ?? '');
    $userId = db_find_user_id_by_uuid($pdo, $uuid);
    if (!$userId) {
        return;
    }

    $statement = $pdo->prepare(
        'UPDATE usuarios SET nombre = :nombre, email = :email, password_hash = :password_hash, rol = :rol, email_verified_at = :email_verified_at, last_login_at = :last_login_at, updated_at = UTC_TIMESTAMP() WHERE id = :id'
    );
    $statement->execute([
        'nombre' => clean_text((string) ($updatedUser['name'] ?? name_from_email((string) ($updatedUser['email'] ?? '')))),
        'email' => normalize_email((string) ($updatedUser['email'] ?? '')),
        'password_hash' => (string) ($updatedUser['password_hash'] ?? ''),
        'rol' => db_role_from_legacy((string) ($updatedUser['role'] ?? 'user')),
        'email_verified_at' => db_nullable_datetime($updatedUser['email_verified_at'] ?? null),
        'last_login_at' => db_nullable_datetime($updatedUser['last_login_at'] ?? null),
        'id' => $userId,
    ]);

    if (($updatedUser['role'] ?? 'user') !== 'admin') {
        db_upsert_member_for_user($pdo, $userId, $updatedUser);
    }
}

function db_upsert_member_for_user(PDO $pdo, int $userId, array $user): void
{
    $profile = default_member_profile($user);
    $memberTypeId = db_member_type_id($pdo, (string) ($profile['member_type'] ?? 'artista'));
    $memberNumber = db_member_number_for_user($pdo, $userId);
    $memberCode = (string) ($user['member_code'] ?? '');
    if ($memberCode === '') {
        $memberCode = 'CSF-' . strtoupper(substr(hash('sha1', (string) ($user['id'] ?? '') . (string) ($user['email'] ?? '')), 0, 8));
    }

    $statement = $pdo->prepare(
        'INSERT INTO miembros (
            usuario_id, tipo_miembro_id, nombre_publico, numero_miembro, codigo_descuento, estado, biografia,
            ciudad, provincia_texto, telefono, foto_principal_path, web_url, instagram_url, perfil_json, perfil_completo_at
        ) VALUES (
            :usuario_id, :tipo_miembro_id, :nombre_publico, :numero_miembro, :codigo_descuento, :estado, :biografia,
            :ciudad, :provincia_texto, :telefono, :foto_principal_path, :web_url, :instagram_url, :perfil_json, :perfil_completo_at
        ) ON DUPLICATE KEY UPDATE
            tipo_miembro_id = VALUES(tipo_miembro_id),
            nombre_publico = VALUES(nombre_publico),
            estado = VALUES(estado),
            biografia = VALUES(biografia),
            ciudad = VALUES(ciudad),
            provincia_texto = VALUES(provincia_texto),
            telefono = VALUES(telefono),
            foto_principal_path = VALUES(foto_principal_path),
            web_url = VALUES(web_url),
            instagram_url = VALUES(instagram_url),
            perfil_json = VALUES(perfil_json),
            perfil_completo_at = VALUES(perfil_completo_at),
            updated_at = UTC_TIMESTAMP()'
    );

    $statement->execute([
        'usuario_id' => $userId,
        'tipo_miembro_id' => $memberTypeId,
        'nombre_publico' => clean_text((string) ($profile['public_name'] ?? $user['name'] ?? 'Miembro')),
        'numero_miembro' => $memberNumber,
        'codigo_descuento' => $memberCode,
        'estado' => strtolower((string) ($user['membership_tier'] ?? 'simpatizante')) === 'vip' ? 'VIP' : 'SIMPATIZANTE',
        'biografia' => clean_text((string) ($profile['short_description'] ?? '')),
        'ciudad' => clean_text((string) ($profile['city'] ?? '')),
        'provincia_texto' => clean_text((string) ($profile['province'] ?? '')),
        'telefono' => clean_text((string) ($profile['phone'] ?? '')),
        'foto_principal_path' => clean_text((string) ($profile['main_photo_path'] ?? '')),
        'web_url' => trim((string) ($profile['website_url'] ?? '')),
        'instagram_url' => trim((string) ($profile['instagram_url'] ?? '')),
        'perfil_json' => json_encode($profile, JSON_UNESCAPED_UNICODE),
        'perfil_completo_at' => db_nullable_datetime($profile['completed_at'] ?? null),
    ]);
}

function db_member_type_id(PDO $pdo, string $type): int
{
    $slug = normalize_member_type($type);
    $statement = $pdo->prepare('SELECT id FROM tipos_miembro WHERE slug = :slug LIMIT 1');
    $statement->execute(['slug' => $slug]);
    $id = $statement->fetchColumn();

    if ($id) {
        return (int) $id;
    }

    $label = member_type_options()[$slug] ?? 'Artista';
    $insert = $pdo->prepare('INSERT INTO tipos_miembro (nombre, slug) VALUES (:nombre, :slug)');
    $insert->execute(['nombre' => $label, 'slug' => $slug]);

    return (int) $pdo->lastInsertId();
}

function db_member_number_for_user(PDO $pdo, int $userId): int
{
    $statement = $pdo->prepare('SELECT numero_miembro FROM miembros WHERE usuario_id = :usuario_id LIMIT 1');
    $statement->execute(['usuario_id' => $userId]);
    $current = $statement->fetchColumn();
    if ($current) {
        return (int) $current;
    }

    $max = (int) $pdo->query('SELECT COALESCE(MAX(numero_miembro), 40000) FROM miembros')->fetchColumn();
    return $max + 1;
}

function db_role_from_legacy(string $role): string
{
    return match ($role) {
        'admin' => 'ADMIN',
        'setter' => 'SETTER',
        default => 'MIEMBRO',
    };
}

function migrate_json_users_to_database(PDO $pdo): void
{
    static $migrated = false;
    if ($migrated || !file_exists(USERS_FILE)) {
        return;
    }
    $migrated = true;

    foreach (read_json_file(USERS_FILE, []) as $user) {
        if (!is_array($user) || empty($user['email']) || db_email_exists($pdo, (string) $user['email'])) {
            continue;
        }

        db_insert_legacy_user($pdo, $user);
    }
}

function ensure_default_admin_user(PDO $pdo): void
{
    $adminCount = (int) $pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'ADMIN'")->fetchColumn();
    if ($adminCount > 0) {
        return;
    }

    $now = gmdate('c');
    db_insert_legacy_user($pdo, [
        'id' => bin2hex(random_bytes(16)),
        'name' => 'Administrador',
        'email' => DEFAULT_ADMIN_EMAIL,
        'password_hash' => password_hash(DEFAULT_ADMIN_PASSWORD, PASSWORD_DEFAULT),
        'role' => 'admin',
        'terms_accepted_at' => $now,
        'created_at' => $now,
        'updated_at' => $now,
        'last_login_at' => null,
    ]);
}

function name_from_email(string $email): string
{
    $localPart = strtok($email, '@');
    $name = clean_text(str_replace(['.', '_', '-'], ' ', (string) $localPart));

    return $name !== '' ? mb_convert_case($name, MB_CASE_TITLE, 'UTF-8') : 'Miembro';
}

function db_datetime(?string $value = null): string
{
    $timestamp = $value ? strtotime($value) : time();
    return gmdate('Y-m-d H:i:s', $timestamp ?: time());
}

function db_nullable_datetime(mixed $value): ?string
{
    $value = is_string($value) ? trim($value) : '';
    return $value !== '' ? db_datetime($value) : null;
}

function db_to_iso(mixed $value): ?string
{
    $value = is_string($value) ? trim($value) : '';
    if ($value === '') {
        return null;
    }

    $timestamp = strtotime($value);
    return $timestamp ? gmdate('c', $timestamp) : $value;
}

function smtp_send_email(string $to, string $subject, string $body, array $headers): bool
{
    $host = csf_env('CSF_SMTP_HOST');
    if (!is_string($host) || $host === '') {
        return false;
    }

    $port = (int) csf_env('CSF_SMTP_PORT', '587');
    $encryption = strtolower((string) csf_env('CSF_SMTP_ENCRYPTION', 'tls'));
    $username = csf_env('CSF_SMTP_USERNAME');
    $password = csf_env('CSF_SMTP_PASSWORD');
    $timeout = 15;
    $transport = $encryption === 'ssl' ? 'ssl://' : '';
    $socket = @stream_socket_client(sprintf('%s%s:%d', $transport, $host, $port), $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT);
    if (!$socket) {
        error_log(sprintf('[%s] [CSF SMTP] Connection failed: %s (%s:%d)', gmdate('c'), $errstr ?: 'unknown', $host, $port));
        return false;
    }

    stream_set_timeout($socket, $timeout);
    $readResponse = static function () use ($socket): string {
        $response = '';
        while (($line = fgets($socket, 512)) !== false) {
            $response .= $line;
            if (preg_match('/^[0-9]{3} /', $line)) {
                break;
            }
        }
        return trim($response);
    };

    $sendCommand = static function (string $command) use ($socket): void {
        fwrite($socket, $command . "\r\n");
    };

    $expected = $readResponse();
    if (!str_starts_with($expected, '220')) {
        fclose($socket);
        error_log(sprintf('[%s] [CSF SMTP] Server did not respond with 220: %s', gmdate('c'), $expected));
        return false;
    }

    $hostname = gethostname() ?: 'localhost';
    $sendCommand('EHLO ' . $hostname);
    $response = $readResponse();
    if ($encryption === 'tls') {
        if (!str_contains($response, 'STARTTLS')) {
            fclose($socket);
            error_log(sprintf('[%s] [CSF SMTP] STARTTLS unsupported by server: %s', gmdate('c'), $response));
            return false;
        }
        $sendCommand('STARTTLS');
        $response = $readResponse();
        if (!str_starts_with($response, '220')) {
            fclose($socket);
            error_log(sprintf('[%s] [CSF SMTP] STARTTLS failed: %s', gmdate('c'), $response));
            return false;
        }
        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            fclose($socket);
            error_log(sprintf('[%s] [CSF SMTP] TLS handshake failed', gmdate('c')));
            return false;
        }
        $sendCommand('EHLO ' . $hostname);
        $response = $readResponse();
    }

    if ($username !== null && $username !== '' && $password !== null && $password !== '') {
        $sendCommand('AUTH LOGIN');
        $response = $readResponse();
        if (!str_starts_with($response, '334')) {
            fclose($socket);
            error_log(sprintf('[%s] [CSF SMTP] AUTH LOGIN rejected: %s', gmdate('c'), $response));
            return false;
        }
        $sendCommand(base64_encode($username));
        $response = $readResponse();
        if (!str_starts_with($response, '334')) {
            fclose($socket);
            error_log(sprintf('[%s] [CSF SMTP] Username rejected: %s', gmdate('c'), $response));
            return false;
        }
        $sendCommand(base64_encode($password));
        $response = $readResponse();
        if (!str_starts_with($response, '235')) {
            fclose($socket);
            error_log(sprintf('[%s] [CSF SMTP] Authentication failed: %s', gmdate('c'), $response));
            return false;
        }
    }

    $fromAddress = csf_env('CSF_MAIL_FROM_ADDRESS', APP_EMAIL);
    $sendCommand('MAIL FROM:<' . $fromAddress . '>');
    $response = $readResponse();
    if (!str_starts_with($response, '250')) {
        fclose($socket);
        error_log(sprintf('[%s] [CSF SMTP] MAIL FROM rejected: %s', gmdate('c'), $response));
        return false;
    }

    $sendCommand('RCPT TO:<' . $to . '>');
    $response = $readResponse();
    if (!str_starts_with($response, '250') && !str_starts_with($response, '251')) {
        fclose($socket);
        error_log(sprintf('[%s] [CSF SMTP] RCPT TO rejected: %s', gmdate('c'), $response));
        return false;
    }

    $sendCommand('DATA');
    $response = $readResponse();
    if (!str_starts_with($response, '354')) {
        fclose($socket);
        error_log(sprintf('[%s] [CSF SMTP] DATA command rejected: %s', gmdate('c'), $response));
        return false;
    }

    $message = "To: {$to}\r\nSubject: {$subject}\r\n" . implode("\r\n", $headers) . "\r\n\r\n";
    $bodyLines = explode("\n", str_replace(["\r\n", "\r"], "\n", $body));
    foreach ($bodyLines as $line) {
        if (str_starts_with($line, '.')) {
            $message .= '.';
        }
        $message .= $line . "\r\n";
    }
    $message .= ".\r\n";
    fwrite($socket, $message);

    $response = $readResponse();
    $sendCommand('QUIT');
    fclose($socket);

    return str_starts_with($response, '250');
}

function send_password_reset_email(string $email, string $plainToken): bool
{
    $resetUrl = app_url('restablecer-contrasena.php?token=' . urlencode($plainToken));
    $subject = 'Recupera tu contraseña en ' . APP_NAME;
    $body = "Hola,\n\nHemos recibido una solicitud para recuperar tu contraseña.\n\nPuedes crear una nueva contraseña en este enlace:\n{$resetUrl}\n\nEl enlace caduca en 1 hora. Si no has solicitado este cambio, puedes ignorar este correo.\n\n" . APP_NAME;
    $fromAddress = csf_env('CSF_MAIL_FROM_ADDRESS', APP_EMAIL);
    $fromName = csf_env('CSF_MAIL_FROM_NAME', APP_NAME);
    $headers = [
        'From: ' . $fromName . ' <' . $fromAddress . '>',
        'Reply-To: ' . $fromAddress,
        'Content-Type: text/plain; charset=UTF-8',
    ];
    $headerText = implode("\r\n", $headers);
    $sent = false;
    $usedMethod = 'none';

    if (!csf_env_bool('CSF_MAIL_USE_SMTP', false) && function_exists('mail')) {
        $sendParams = '-f' . escapeshellarg($fromAddress);
        $sent = @mail($email, $subject, $body, $headerText, $sendParams);
        $usedMethod = 'mail';
    }

    if (!$sent && csf_env('CSF_SMTP_HOST')) {
        $sent = smtp_send_email($email, $subject, $body, $headers);
        $usedMethod = 'smtp';
    }

    $logEntry = '[' . gmdate('c') . '] ' . ($sent ? 'SENT' : 'FAILED') . " METHOD: {$usedMethod} To: {$email}\nSubject: {$subject}\nHeaders: {$headerText}\n\n{$body}\n\n";
    if (!is_dir(dirname(MAIL_LOG_FILE))) {
        @mkdir(dirname(MAIL_LOG_FILE), 0775, true);
    }
    @file_put_contents(MAIL_LOG_FILE, $logEntry, FILE_APPEND | LOCK_EX);

    if (!$sent) {
        error_log(sprintf('[%s] [CSF MAIL] Password reset email FAILED for %s', gmdate('c'), $email));
    }

    return $sent;
}

function send_email_verification(string $email, string $plainToken, string $name = ''): bool
{
    $verifyUrl = app_url('verify-email.php?token=' . urlencode($plainToken));
    $subject = 'Bienvenido a ' . APP_NAME;
    $recipientName = $name !== '' ? $name : 'Miembro';
    $brand = APP_NAME;
    $headerImage = app_url('assets/images/flamenco-header-art.png');
    $profileUrl = app_url('panel-usuario.php');

    $plainText = "Hola {$recipientName},\n\n"
        . "Gracias por registrarte en {$brand}.\n\n"
        . "Activa tu cuenta aquí:\n{$verifyUrl}\n\n"
        . "El enlace caduca en 24 horas.\n\n"
        . "{$brand}";

    $htmlBody = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenido a {$brand}</title>
</head>
<body style="margin:0;padding:0;font-family:Arial,Helvetica,sans-serif;background:#0f1720;color:#111114;">
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background:#0f1720;padding:32px 0;">
        <tr>
            <td align="center">
                <table width="680" cellpadding="0" cellspacing="0" role="presentation" style="background:#111114;border-radius:28px;overflow:hidden;box-shadow:0 30px 90px rgba(0,0,0,0.35);">
                    <tr>
                        <td style="padding:0;">
                            <img src="{$headerImage}" alt="{$brand}" width="680" height="170" style="display:block;width:100%;height:170px;object-fit:cover;object-position:center 42%;">
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:36px 44px 28px;">
                            <p style="margin:0 0 10px;color:#c9a45a;font-size:0.85rem;letter-spacing:0.16em;text-transform:uppercase;">Bienvenido a tu espacio flamenco</p>
                            <h1 style="margin:0 0 20px;font-size:2.4rem;line-height:1.1;color:#ffffff;">Hola {$recipientName}, estamos felices de tenerte aquí</h1>
                            <p style="margin:0 0 22px;font-size:1rem;line-height:1.7;color:rgba(255,255,255,0.86);max-width:600px;">Gracias por unirte a <strong>{$brand}</strong>. Ya puedes empezar a crear tu perfil artístico y mostrar tu esencia flamenca.</p>
                            <table cellpadding="0" cellspacing="0" role="presentation" style="margin:0 auto 28px;">
                                <tr>
                                    <td align="center" style="border-radius:999px;background:linear-gradient(135deg,#c94f5c,#5f8fb8);">
                                        <a href="{$verifyUrl}" target="_blank" style="display:inline-block;padding:16px 28px;font-size:1rem;color:#ffffff;text-decoration:none;font-weight:700;border-radius:999px;">Verificar mi correo</a>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin:0 0 20px;font-size:0.95rem;line-height:1.75;color:rgba(255,255,255,0.72);">Verifica tu correo para activar tu cuenta y acceder al panel de miembro.</p>
                            <div style="padding:22px 24px;border-radius:20px;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);">
                                <p style="margin:0 0 10px;font-size:0.95rem;color:#ffffff;font-weight:700;">Tu panel incluye</p>
                                <ul style="margin:0;padding-left:20px;color:rgba(255,255,255,0.72);font-size:0.95rem;line-height:1.7;">
                                    <li>Perfil con foto, biografía y datos de contacto.</li>
                                    <li>Acceso a promoción y oportunidades en la comunidad.</li>
                                    <li>Control de tu imagen y contenidos flamencos.</li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px 48px 32px;background:rgba(255,255,255,0.03);color:rgba(255,255,255,0.68);font-size:0.92rem;line-height:1.7;">
                            <p style="margin:0 0 10px;font-weight:700;">Ya casi estás listo</p>
                            <p style="margin:0;color:rgba(255,255,255,0.66);">Si el botón no funciona, copia y pega este enlace en tu navegador:</p>
                            <p style="margin:6px 0 0;color:#c9a45a;word-break:break-all;"><a href="{$verifyUrl}" target="_blank" style="color:#c9a45a;text-decoration:none;">{$verifyUrl}</a></p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:20px 48px 34px;background:#0d111a;color:rgba(255,255,255,0.55);font-size:0.9rem;line-height:1.7;">
                            <p style="margin:0;">Gracias por elegir {$brand}. Si necesitas ayuda, responde este correo o visita nuestra web.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;

    $fromAddress = csf_env('CSF_MAIL_FROM_ADDRESS', APP_EMAIL);
    $fromName = csf_env('CSF_MAIL_FROM_NAME', APP_NAME);
    $headers = [
        'From: ' . $fromName . ' <' . $fromAddress . '>',
        'Reply-To: ' . $fromAddress,
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
    ];

    $headerText = implode("\r\n", $headers);
    $sent = false;
    $usedMethod = 'none';

    if (!csf_env_bool('CSF_MAIL_USE_SMTP', false) && function_exists('mail')) {
        $sendParams = '-f' . escapeshellarg($fromAddress);
        $sent = @mail($email, $subject, $htmlBody, $headerText, $sendParams);
        $usedMethod = 'mail';
    }

    if (!$sent && csf_env('CSF_SMTP_HOST')) {
        $sent = smtp_send_email($email, $subject, $htmlBody, $headers);
        $usedMethod = 'smtp';
    }

    $logEntry = '[' . gmdate('c') . '] ' . ($sent ? 'SENT' : 'FAILED') . " METHOD: {$usedMethod} To: {$email}\nSubject: {$subject}\nHeaders: {$headerText}\n\n{$plainText}\n\n";
    if (!is_dir(dirname(MAIL_LOG_FILE))) {
        @mkdir(dirname(MAIL_LOG_FILE), 0775, true);
    }
    @file_put_contents(MAIL_LOG_FILE, $logEntry, FILE_APPEND | LOCK_EX);

    if (!$sent) {
        error_log(sprintf('[%s] [CSF MAIL] Email verification FAILED for %s', gmdate('c'), $email));
    }

    return $sent;
}
