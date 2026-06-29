<?php
declare(strict_types=1);

require_once __DIR__ . '/app/auth.php';
require_once __DIR__ . '/app/layout.php';

$errors = [];
$sentMessage = false;
$username = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = clean_text($_POST['username'] ?? '');
    $email = normalize_email($_POST['email'] ?? '');

    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'La sesión ha caducado. Vuelve a intentarlo.';
    } elseif ($username === '') {
        $errors[] = 'Introduce tu usuario.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Introduce un email válido.';
    } else {
        $user = find_user_by_name_and_email($username, $email);
        if (!$user) {
            $errors[] = 'El usuario y el email no coinciden. Si no recuerdas alguno, escribe a soporte@consaborflamenco.com.';
        } else {
            $token = create_password_reset_token($email);
            if (!$token) {
                $errors[] = 'No se pudo generar el enlace de recuperación. Contacta a soporte@consaborflamenco.com.';
            } elseif (!send_password_reset_email($email, $token)) {
                $errors[] = 'No se pudo enviar el enlace de recuperación. Contacta a soporte@consaborflamenco.com.';
            } else {
                $sentMessage = true;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<?php page_head('Recuperar contraseña | Con Sabor Flamenco', 'Recuperación segura de contraseña en Con Sabor Flamenco.', false); ?>
<body>
    <?php page_header(); ?>
    <main>
        <section class="auth-section auth-section-narrow" data-ad-category="GENERAL">
            <div class="auth-copy">
                <p class="section-kicker">Recuperación</p>
                <h1>Recupera tu contraseña</h1>
                <p>Para proteger tu cuenta necesitamos confirmar tu usuario y tu email registrados en la base de datos.</p>
            </div>
            <form class="auth-card" method="post" action="recuperar-contrasena.php" novalidate>
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <h2>Datos de recuperación</h2>
                <?php if ($sentMessage): ?>
                    <div class="form-alert form-alert-success" role="status"><p>Si el usuario y el email coinciden, recibirás un enlace para restablecer la contraseña.</p></div>
                <?php endif; ?>
                <?php if ($errors): ?>
                    <div class="form-alert form-alert-error" role="alert"><?php foreach ($errors as $error): ?><p><?= e($error) ?></p><?php endforeach; ?></div>
                <?php endif; ?>
                <label for="username">Usuario</label>
                <input id="username" name="username" type="text" autocomplete="username" value="<?= e($username) ?>" required>
                <label for="email">Email</label>
                <input id="email" name="email" type="email" autocomplete="email" value="<?= e($email) ?>" required>
                <p class="field-help">Si no recuerdas tu usuario o email, escribe a <a href="mailto:soporte@consaborflamenco.com">soporte@consaborflamenco.com</a>.</p>
                <button class="button button-primary" type="submit">Enviar enlace</button>
                <p class="auth-switch"><a href="acceso.php">Volver al acceso</a></p>
            </form>
        </section>
    </main>
    <?php page_footer(); ?>
    <?php province_modal('Así podremos mantener tu experiencia localizada también durante el acceso privado.'); ?>
</body>
</html>
