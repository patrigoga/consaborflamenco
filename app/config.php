<?php
declare(strict_types=1);

require_once __DIR__ . '/env.php';

const APP_NAME = 'Con Sabor Flamenco';
const APP_EMAIL = 'hola@consaborflamenco.com';
const STORAGE_DIR = __DIR__ . '/../storage';
const USERS_FILE = STORAGE_DIR . '/users.json';
const RESET_TOKENS_FILE = STORAGE_DIR . '/password_resets.json';
const MAIL_LOG_FILE = STORAGE_DIR . '/mail_outbox.log';
const EMAIL_VERIFICATION_TOKENS_FILE = STORAGE_DIR . '/email_verifications.json';
const SESSIONS_DIR = STORAGE_DIR . '/sessions';

function csf_is_absolute_path(string $path): bool
{
    return preg_match('#^(?:[A-Za-z]:[\\\\/]|/|\\\\\\\\)#', $path) === 1;
}

define('APP_ENV', csf_detect_environment());
define('APP_DEBUG', csf_env_bool('CSF_APP_DEBUG', APP_ENV !== 'production'));

$runtimeUploadsDir = trim((string) csf_env('CSF_UPLOADS_DIR', ''));
if ($runtimeUploadsDir === '') {
    $runtimeUploadsDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'csf-uploads';
} elseif (!csf_is_absolute_path($runtimeUploadsDir)) {
    $runtimeUploadsDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . $runtimeUploadsDir;
}

define('RUNTIME_UPLOADS_DIR', rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $runtimeUploadsDir), DIRECTORY_SEPARATOR));
define('MEMBER_PHOTOS_DIR', RUNTIME_UPLOADS_DIR . DIRECTORY_SEPARATOR . 'member-photos');
define('MEMBER_CV_IMAGES_DIR', RUNTIME_UPLOADS_DIR . DIRECTORY_SEPARATOR . 'curriculum-images');

$defaultDbHost = APP_ENV === 'production' ? 'localhost' : '127.0.0.1';
$defaultDbName = APP_ENV === 'production' ? 'u311361615_csf' : 'consaborflamenco';
$defaultDbUser = APP_ENV === 'production' ? 'u311361615_admin' : 'root';

define('DB_HOST', csf_env('CSF_DB_HOST', $defaultDbHost));
define('DB_PORT', csf_env('CSF_DB_PORT', '3306'));
define('DB_NAME', csf_env('CSF_DB_NAME', $defaultDbName));
define('DB_USER', csf_env('CSF_DB_USER', $defaultDbUser));
define('DB_PASS', csf_env('CSF_DB_PASS', ''));
define('DB_CHARSET', 'utf8mb4');
define('DEFAULT_ADMIN_EMAIL', csf_env('CSF_DEFAULT_ADMIN_EMAIL', 'admin@consaborflamenco.com'));
define('DEFAULT_ADMIN_PASSWORD', csf_env('CSF_DEFAULT_ADMIN_PASSWORD', 'Admin1234!'));

if (!is_dir(STORAGE_DIR)) {
    @mkdir(STORAGE_DIR, 0775, true);
}

if (!is_dir(SESSIONS_DIR)) {
    @mkdir(SESSIONS_DIR, 0775, true);
}

if (!is_dir(RUNTIME_UPLOADS_DIR)) {
    @mkdir(RUNTIME_UPLOADS_DIR, 0775, true);
}

if (!is_dir(MEMBER_PHOTOS_DIR)) {
    @mkdir(MEMBER_PHOTOS_DIR, 0775, true);
}

if (!is_dir(MEMBER_CV_IMAGES_DIR)) {
    @mkdir(MEMBER_CV_IMAGES_DIR, 0775, true);
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_save_path(SESSIONS_DIR);
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function app_url(string $path = ''): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    $basePath = $basePath === '/' ? '' : $basePath;

    return $scheme . '://' . $host . $basePath . '/' . ltrim($path, '/');
}

function csf_media_url(string $file): string
{
    $file = csf_normalize_media_file($file);
    if ($file === null) {
        return '';
    }

    return 'media.php?file=' . str_replace('%2F', '/', rawurlencode($file));
}

function csf_normalize_media_file(string $file): ?string
{
    $file = trim(rawurldecode(str_replace('\\', '/', $file)));
    $file = ltrim($file, '/');
    if ($file === '' || str_contains($file, '..')) {
        return null;
    }

    if (preg_match('#^(member-photos|curriculum-images)/[A-Za-z0-9._-]+\.(?:jpe?g|png|webp)$#i', $file) !== 1) {
        return null;
    }

    return $file;
}

function csf_media_file_from_path(string $path): ?string
{
    $path = trim(str_replace('\\', '/', $path));
    if ($path === '') {
        return null;
    }

    $parts = parse_url($path);
    if (is_array($parts) && str_ends_with((string) ($parts['path'] ?? ''), 'media.php')) {
        parse_str((string) ($parts['query'] ?? ''), $query);
        return csf_normalize_media_file((string) ($query['file'] ?? ''));
    }

    if (preg_match('#(?:^|/)media\.php\?file=([^&#]+)#', $path, $matches) === 1) {
        return csf_normalize_media_file((string) $matches[1]);
    }

    return null;
}

function csf_media_file_exists(string $file): bool
{
    $file = csf_normalize_media_file($file);
    if ($file === null) {
        return false;
    }

    $base = realpath(RUNTIME_UPLOADS_DIR);
    $target = realpath(RUNTIME_UPLOADS_DIR . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $file));
    if ($base === false || $target === false) {
        return false;
    }

    return str_starts_with($target, $base . DIRECTORY_SEPARATOR) && is_file($target);
}

function redirect_to(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
