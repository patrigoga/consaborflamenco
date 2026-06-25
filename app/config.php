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
