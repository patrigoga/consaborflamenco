<?php
declare(strict_types=1);

require_once __DIR__ . '/env.php';

const APP_NAME = 'Con Sabor Flamenco';
const APP_EMAIL = 'hola@consaborflamenco.com';
const STORAGE_DIR = __DIR__ . '/../storage';
const USERS_FILE = STORAGE_DIR . '/users.json';
const RESET_TOKENS_FILE = STORAGE_DIR . '/password_resets.json';
const MAIL_LOG_FILE = STORAGE_DIR . '/mail_outbox.log';
const SESSIONS_DIR = STORAGE_DIR . '/sessions';
const MEMBER_PHOTOS_DIR = __DIR__ . '/../assets/uploads/member-photos';
const MEMBER_PHOTOS_URL = 'assets/uploads/member-photos';
const MEMBER_CV_IMAGES_DIR = __DIR__ . '/../assets/uploads/curriculum-images';
const MEMBER_CV_IMAGES_URL = 'assets/uploads/curriculum-images';

define('APP_ENV', csf_detect_environment());
define('APP_DEBUG', csf_env_bool('CSF_APP_DEBUG', APP_ENV !== 'production'));

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
    mkdir(STORAGE_DIR, 0775, true);
}

if (!is_dir(SESSIONS_DIR)) {
    mkdir(SESSIONS_DIR, 0775, true);
}

if (!is_dir(MEMBER_PHOTOS_DIR)) {
    mkdir(MEMBER_PHOTOS_DIR, 0775, true);
}

if (!is_dir(MEMBER_CV_IMAGES_DIR)) {
    mkdir(MEMBER_CV_IMAGES_DIR, 0775, true);
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

function redirect_to(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
