<?php
declare(strict_types=1);

function csf_load_env_file(string $path, bool $override = false): void
{
    if (!is_file($path) || !is_readable($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!is_array($lines)) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = array_map('trim', explode('=', $line, 2));
        if ($key === '') {
            continue;
        }

        $value = trim($value, "\"'");
        if (!$override && getenv($key) !== false) {
            continue;
        }

        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
        putenv($key . '=' . $value);
    }
}

function csf_env(string $key, ?string $default = null): ?string
{
    $value = getenv($key);
    if ($value !== false) {
        return (string) $value;
    }

    return $_ENV[$key] ?? $_SERVER[$key] ?? $default;
}

function csf_env_bool(string $key, bool $default = false): bool
{
    $value = csf_env($key);
    if ($value === null) {
        return $default;
    }

    return in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true);
}

function csf_detect_environment(): string
{
    $configured = csf_env('CSF_APP_ENV');
    if (is_string($configured) && $configured !== '') {
        return strtolower($configured);
    }

    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
    if ($host === '' || str_contains($host, 'localhost') || str_contains($host, '127.0.0.1')) {
        return 'local';
    }

    return 'production';
}

csf_load_env_file(__DIR__ . '/../.env');
csf_load_env_file(__DIR__ . '/../.env.local', true);
