<?php
declare(strict_types=1);

require_once __DIR__ . '/app/auth.php';
require_once __DIR__ . '/app/layout.php';

$errors = [];
$sentMessage = false;
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = normalize_email($_POST['email'] ?? '');

    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'La sesión ha caducado. Vuelve a intentarlo.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Introduce un email válido.';
    } else {
        $token = create_password_reset_token($email);
        if ($token) {
            send_password_reset_email($email, $token);
        }
        $sentMessage = true;
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
                <p>Te enviaremos un enlace temporal para crear una contraseña nueva sin mostrar si el email existe o no.</p>
            </div>
            <form class="auth-card" method="post" action="recuperar-contrasena.php" novalidate>
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <h2>Email de recuperación</h2>
                <?php if ($sentMessage): ?>
                    <div class="form-alert form-alert-success" role="status"><p>Si el email existe, recibirás un enlace para restablecer la contraseña.</p></div>
                <?php endif; ?>
                <?php if ($errors): ?>
                    <div class="form-alert form-alert-error" role="alert"><?php foreach ($errors as $error): ?><p><?= e($error) ?></p><?php endforeach; ?></div>
                <?php endif; ?>
                <label for="email">Email</label>
                <input id="email" name="email" type="email" autocomplete="email" value="<?= e($email) ?>" required>
                <button class="button button-primary" type="submit">Enviar enlace</button>
                <p class="auth-switch"><a href="acceso.php">Volver al acceso</a></p>
            </form>
        </section>
    </main>
    <?php page_footer(); ?>
    <?php province_modal('Así podremos mantener tu experiencia localizada también durante el acceso privado.'); ?>
</body>
</html>
