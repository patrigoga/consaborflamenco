<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

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
    return read_json_file(USERS_FILE, []);
}

function save_users(array $users): void
{
    write_json_file(USERS_FILE, array_values($users));
}

function find_user_by_email(string $email): ?array
{
    $normalizedEmail = normalize_email($email);
    foreach (all_users() as $user) {
        if (($user['email'] ?? '') === $normalizedEmail) {
            return $user;
        }
    }

    return null;
}

function find_user_by_id(string $id): ?array
{
    foreach (all_users() as $user) {
        if (($user['id'] ?? '') === $id) {
            return $user;
        }
    }

    return null;
}

function create_user(string $name, string $email, string $password, array $memberProfile = []): array
{
    $users = all_users();
    $normalizedEmail = normalize_email($email);

    foreach ($users as $user) {
        if (($user['email'] ?? '') === $normalizedEmail) {
            throw new InvalidArgumentException('Ya existe una cuenta con ese email.');
        }
    }

    $now = gmdate('c');
    $user = [
        'id' => bin2hex(random_bytes(16)),
        'name' => clean_text($name),
        'email' => $normalizedEmail,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'role' => empty($users) ? 'admin' : 'user',
        'membership_tier' => 'simpatizante',
        'artistic_profile' => member_profile_from_input(['name' => $name] + $memberProfile, $memberProfile),
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

    $user['last_login_at'] = gmdate('c');
    update_user($user);

    return $user;
}

function login_user(array $user): void
{
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

    return $user;
}

function reset_tokens(): array
{
    return read_json_file(RESET_TOKENS_FILE, []);
}

function save_reset_tokens(array $tokens): void
{
    write_json_file(RESET_TOKENS_FILE, array_values($tokens));
}

function create_password_reset_token(string $email): ?string
{
    $user = find_user_by_email($email);
    if (!$user) {
        return null;
    }

    $plainToken = bin2hex(random_bytes(32));
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

function send_password_reset_email(string $email, string $plainToken): bool
{
    $resetUrl = app_url('restablecer-contrasena.php?token=' . urlencode($plainToken));
    $subject = 'Recupera tu contraseña en ' . APP_NAME;
    $body = "Hola,\n\nHemos recibido una solicitud para recuperar tu contraseña.\n\nPuedes crear una nueva contraseña en este enlace:\n{$resetUrl}\n\nEl enlace caduca en 1 hora. Si no has solicitado este cambio, puedes ignorar este correo.\n\n" . APP_NAME;
    $headers = [
        'From: ' . APP_NAME . ' <' . APP_EMAIL . '>',
        'Reply-To: ' . APP_EMAIL,
        'Content-Type: text/plain; charset=UTF-8',
    ];

    $sent = @mail($email, $subject, $body, implode("\r\n", $headers));
    if (!$sent) {
        file_put_contents(MAIL_LOG_FILE, '[' . gmdate('c') . "] To: {$email}\nSubject: {$subject}\n{$body}\n\n", FILE_APPEND | LOCK_EX);
    }

    return $sent;
}
