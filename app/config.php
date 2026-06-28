<?php
declare(strict_types=1);

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

define('DB_HOST', getenv('CSF_DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('CSF_DB_PORT') ?: '3306');
define('DB_NAME', getenv('CSF_DB_NAME') ?: 'consaborflamenco');
define('DB_USER', getenv('CSF_DB_USER') ?: 'root');
define('DB_PASS', getenv('CSF_DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');
define('DEFAULT_ADMIN_EMAIL', getenv('CSF_DEFAULT_ADMIN_EMAIL') ?: 'admin@consaborflamenco.com');
define('DEFAULT_ADMIN_PASSWORD', getenv('CSF_DEFAULT_ADMIN_PASSWORD') ?: 'Admin1234!');

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
