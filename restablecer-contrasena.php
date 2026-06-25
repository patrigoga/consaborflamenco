<?php
declare(strict_types=1);

require_once __DIR__ . '/app/auth.php';
require_once __DIR__ . '/app/layout.php';

$errors = [];
$token = (string) ($_GET['token'] ?? $_POST['token'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = (string) ($_POST['password'] ?? '');
    $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');

    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'La sesión ha caducado. Vuelve a intentarlo.';
    }
    if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/\d/', $password)) {
        $errors[] = 'La contraseña debe tener al menos 8 caracteres, una mayúscula, una minúscula y un número.';
    }
    if ($password !== $passwordConfirm) {
        $errors[] = 'Las contraseñas no coinciden.';
    }

    if (!$errors && consume_password_reset_token($token, $password)) {
        redirect_to('acceso.php?password_reset=1');
    }

    if (!$errors) {
        $errors[] = 'El enlace no es válido o ha caducado.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<?php page_head('Nueva contraseña | Con Sabor Flamenco', 'Crea una nueva contraseña en Con Sabor Flamenco.', false); ?>
<body>
    <?php page_header(); ?>
    <main>
        <section class="auth-section auth-section-narrow" data-ad-category="GENERAL">
            <div class="auth-copy">
                <p class="section-kicker">Seguridad</p>
                <h1>Crea una nueva contraseña</h1>
                <p>El enlace de recuperación es temporal y solo puede usarse una vez para proteger tu cuenta.</p>
            </div>
            <form class="auth-card" method="post" action="restablecer-contrasena.php" novalidate>
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="token" value="<?= e($token) ?>">
                <h2>Nueva contraseña</h2>
                <?php if (!$token): ?><div class="form-alert form-alert-error" role="alert"><p>Falta el token de recuperación.</p></div><?php endif; ?>
                <?php if ($errors): ?><div class="form-alert form-alert-error" role="alert"><?php foreach ($errors as $error): ?><p><?= e($error) ?></p><?php endforeach; ?></div><?php endif; ?>
                <label for="password">Nueva contraseña</label>
                <input id="password" name="password" type="password" autocomplete="new-password" required>
                <label for="password_confirm">Repetir nueva contraseña</label>
                <input id="password_confirm" name="password_confirm" type="password" autocomplete="new-password" required>
                <button class="button button-primary" type="submit" <?= !$token ? 'disabled' : '' ?>>Guardar contraseña</button>
                <p class="auth-switch"><a href="acceso.php">Volver al acceso</a></p>
            </form>
        </section>
    </main>
    <?php page_footer(); ?>
    <?php province_modal('Así podremos mantener tu experiencia localizada también durante el acceso privado.'); ?>
</body>
</html>
