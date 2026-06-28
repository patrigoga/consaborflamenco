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
        $errors[] = 'La sesion ha caducado. Vuelve a intentarlo.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'La contrasena debe tener al menos 8 caracteres.';
    }
    if ($password !== $passwordConfirm) {
        $errors[] = 'Las contrasenas no coinciden.';
    }

    if (!$errors && consume_password_reset_token($token, $password)) {
        redirect_to('acceso.php?password_reset=1');
    }

    if (!$errors) {
        $errors[] = 'El enlace no es valido o ha caducado.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<?php page_head('Nueva contrasena | Con Sabor Flamenco', 'Crea una nueva contrasena en Con Sabor Flamenco.', false); ?>
<body>
    <?php page_header(); ?>
    <main>
        <section class="auth-section auth-section-narrow" data-ad-category="GENERAL">
            <div class="auth-copy">
                <p class="section-kicker">Seguridad</p>
                <h1>Crea una nueva contrasena</h1>
                <p>El enlace de recuperacion es temporal y solo puede usarse una vez para proteger tu cuenta.</p>
            </div>
            <form class="auth-card" method="post" action="restablecer-contrasena.php" novalidate>
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="token" value="<?= e($token) ?>">
                <h2>Nueva contrasena</h2>
                <?php if (!$token): ?><div class="form-alert form-alert-error" role="alert"><p>Falta el token de recuperacion.</p></div><?php endif; ?>
                <?php if ($errors): ?><div class="form-alert form-alert-error" role="alert"><?php foreach ($errors as $error): ?><p><?= e($error) ?></p><?php endforeach; ?></div><?php endif; ?>
                <label for="password">Nueva contrasena</label>
                <input id="password" name="password" type="password" autocomplete="new-password" minlength="8" required>
                <p class="field-help">Minimo 8 caracteres.</p>
                <label for="password_confirm">Repetir nueva contrasena</label>
                <input id="password_confirm" name="password_confirm" type="password" autocomplete="new-password" minlength="8" required>
                <button class="button button-primary" type="submit" <?= !$token ? 'disabled' : '' ?>>Guardar contrasena</button>
                <p class="auth-switch"><a href="acceso.php">Volver al acceso</a></p>
            </form>
        </section>
    </main>
    <?php page_footer(); ?>
    <?php province_modal('Asi podremos mantener tu experiencia localizada tambien durante el acceso privado.'); ?>
</body>
</html>
