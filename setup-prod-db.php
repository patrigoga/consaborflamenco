<?php
declare(strict_types=1);

$rootDir = __DIR__;
$envPath = $rootDir . '/.env';
$allowPath = $rootDir . '/storage/ALLOW_PROD_SETUP';
$messages = [];
$errors = [];
$installed = false;

function setup_e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function setup_existing_env(string $path): array
{
    if (!is_file($path) || !is_readable($path)) {
        return [];
    }

    $values = [];
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!is_array($lines)) {
        return [];
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = array_map('trim', explode('=', $line, 2));
        $values[$key] = trim($value, "\"'");
    }

    return $values;
}

function setup_env_value(string $value): string
{
    return str_replace(["\r", "\n"], '', trim($value));
}

function setup_write_env(string $path, array $values): bool
{
    $content = "# Con Sabor Flamenco - produccion\n";
    foreach ($values as $key => $value) {
        $content .= $key . '=' . setup_env_value((string) $value) . "\n";
    }

    return file_put_contents($path, $content, LOCK_EX) !== false;
}

$existing = setup_existing_env($envPath);
$values = [
    'CSF_APP_ENV' => 'production',
    'CSF_APP_DEBUG' => '0',
    'CSF_DB_HOST' => $existing['CSF_DB_HOST'] ?? 'localhost',
    'CSF_DB_PORT' => $existing['CSF_DB_PORT'] ?? '3306',
    'CSF_DB_NAME' => $existing['CSF_DB_NAME'] ?? 'u311361615_csf',
    'CSF_DB_USER' => $existing['CSF_DB_USER'] ?? 'u311361615_admin',
    'CSF_DB_PASS' => '',
    'CSF_DEFAULT_ADMIN_EMAIL' => $existing['CSF_DEFAULT_ADMIN_EMAIL'] ?? 'admin@consaborflamenco.com',
    'CSF_DEFAULT_ADMIN_PASSWORD' => '',
];

$isAllowed = is_file($allowPath);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$isAllowed) {
        $errors[] = 'Instalador bloqueado. Crea primero el archivo storage/ALLOW_PROD_SETUP.';
    } else {
        foreach ($values as $key => $default) {
            $values[$key] = setup_env_value((string) ($_POST[$key] ?? $default));
        }

        if ($values['CSF_DB_PASS'] === '' && !empty($existing['CSF_DB_PASS'])) {
            $values['CSF_DB_PASS'] = (string) $existing['CSF_DB_PASS'];
        }
        if ($values['CSF_DEFAULT_ADMIN_PASSWORD'] === '' && !empty($existing['CSF_DEFAULT_ADMIN_PASSWORD'])) {
            $values['CSF_DEFAULT_ADMIN_PASSWORD'] = (string) $existing['CSF_DEFAULT_ADMIN_PASSWORD'];
        }
        if ($values['CSF_DEFAULT_ADMIN_PASSWORD'] === '') {
            $values['CSF_DEFAULT_ADMIN_PASSWORD'] = 'Admin1234!';
        }

        foreach (['CSF_DB_HOST', 'CSF_DB_PORT', 'CSF_DB_NAME', 'CSF_DB_USER', 'CSF_DB_PASS'] as $requiredKey) {
            if ($values[$requiredKey] === '') {
                $errors[] = 'Falta el valor de ' . $requiredKey . '.';
            }
        }
        if (strlen($values['CSF_DEFAULT_ADMIN_PASSWORD']) < 8) {
            $errors[] = 'La contrasena admin por defecto debe tener al menos 8 caracteres.';
        }

        if (!$errors && !setup_write_env($envPath, $values)) {
            $errors[] = 'No se pudo escribir el archivo .env. Revisa permisos de escritura.';
        }

        if (!$errors) {
            require_once $rootDir . '/app/auth.php';
            $pdo = auth_database();
            if (!$pdo) {
                $errors[] = 'No se pudo conectar con MySQL: ' . (function_exists('db_last_error') ? (db_last_error() ?? 'error desconocido') : 'error desconocido');
            } else {
                $installed = true;
                $messages[] = 'Base de datos conectada y tablas preparadas.';
                $messages[] = 'Usuarios migrados/sembrados: ' . (string) $pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
                $messages[] = 'Miembros disponibles: ' . (string) $pdo->query('SELECT COUNT(*) FROM miembros')->fetchColumn();
                $messages[] = 'Categorias editoriales: ' . (string) $pdo->query('SELECT COUNT(*) FROM categorias_articulos')->fetchColumn();
                @unlink($allowPath);
                $isAllowed = false;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup produccion DB | Con Sabor Flamenco</title>
    <style>
        body { margin: 0; font-family: Arial, sans-serif; color: #17171b; background: #f7f1e8; }
        main { width: min(920px, calc(100% - 32px)); margin: 40px auto; }
        section { padding: 28px; border: 1px solid #ddd2c6; border-radius: 12px; background: #fffaf2; }
        h1 { margin: 0 0 10px; font-size: 32px; }
        p { line-height: 1.5; }
        form { display: grid; gap: 14px; margin-top: 22px; }
        label { display: grid; gap: 7px; font-weight: 700; }
        input { min-height: 42px; padding: 0 12px; border: 1px solid #cfc4b9; border-radius: 8px; font: inherit; }
        button { min-height: 46px; border: 0; border-radius: 8px; color: #fff; background: #c94f5c; font-weight: 800; cursor: pointer; }
        code { padding: 2px 5px; border-radius: 5px; background: #efe6dc; }
        .grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
        .alert { margin: 16px 0; padding: 14px 16px; border-radius: 8px; }
        .error { color: #7f1d1d; background: #fee2e2; }
        .success { color: #14532d; background: #dcfce7; }
        .muted { color: #6f6b66; }
        @media (max-width: 680px) { .grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <main>
        <section>
            <h1>Configurar base de datos de produccion</h1>
            <p class="muted">Este archivo guarda un <code>.env</code> privado, conecta con MySQL, crea/actualiza tablas, siembra datos base y migra <code>storage/users.json</code> si existe en produccion.</p>

            <?php if (!$isAllowed && !$installed): ?>
                <div class="alert error">
                    <strong>Instalador bloqueado.</strong>
                    <p>Para activarlo, crea temporalmente el archivo <code>storage/ALLOW_PROD_SETUP</code> en produccion y recarga esta pagina. Al terminar correctamente, el archivo se elimina solo.</p>
                </div>
            <?php endif; ?>

            <?php if ($messages): ?>
                <div class="alert success">
                    <?php foreach ($messages as $message): ?><p><?= setup_e($message) ?></p><?php endforeach; ?>
                    <p><strong>Ahora elimina <code>setup-prod-db.php</code> del servidor o deja el instalador bloqueado sin <code>storage/ALLOW_PROD_SETUP</code>.</strong></p>
                </div>
            <?php endif; ?>

            <?php if ($errors): ?>
                <div class="alert error">
                    <?php foreach ($errors as $error): ?><p><?= setup_e($error) ?></p><?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($isAllowed): ?>
                <form method="post" action="setup-prod-db.php">
                    <div class="grid">
                        <label>Host MySQL
                            <input name="CSF_DB_HOST" value="<?= setup_e($values['CSF_DB_HOST']) ?>" required>
                        </label>
                        <label>Puerto MySQL
                            <input name="CSF_DB_PORT" value="<?= setup_e($values['CSF_DB_PORT']) ?>" required>
                        </label>
                        <label>Base de datos
                            <input name="CSF_DB_NAME" value="<?= setup_e($values['CSF_DB_NAME']) ?>" required>
                        </label>
                        <label>Usuario MySQL
                            <input name="CSF_DB_USER" value="<?= setup_e($values['CSF_DB_USER']) ?>" required>
                        </label>
                    </div>
                    <label>Contrasena MySQL
                        <input name="CSF_DB_PASS" type="password" autocomplete="new-password" placeholder="<?= !empty($existing['CSF_DB_PASS']) ? 'Usar la ya guardada si lo dejas vacio' : '' ?>">
                    </label>
                    <div class="grid">
                        <label>Email admin por defecto
                            <input name="CSF_DEFAULT_ADMIN_EMAIL" type="email" value="<?= setup_e($values['CSF_DEFAULT_ADMIN_EMAIL']) ?>" required>
                        </label>
                        <label>Contrasena admin por defecto
                            <input name="CSF_DEFAULT_ADMIN_PASSWORD" type="password" autocomplete="new-password" placeholder="<?= !empty($existing['CSF_DEFAULT_ADMIN_PASSWORD']) ? 'Usar la ya guardada si lo dejas vacio' : 'Minimo 8 caracteres' ?>">
                        </label>
                    </div>
                    <button type="submit">Guardar configuracion y preparar BD</button>
                </form>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
