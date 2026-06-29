<?php
declare(strict_types=1);

require_once __DIR__ . '/app/auth.php';
require_once __DIR__ . '/app/layout.php';

$errors = [];
$email = '';
$passwordReset = isset($_GET['password_reset']);

$currentUser = current_user();
if ($currentUser) {
    if (($currentUser['role'] ?? 'user') === 'admin') {
        redirect_to('panel-admin.php');
    }

    redirect_to(user_email_is_verified($currentUser) ? 'panel-usuario.php' : 'verificacion-pendiente.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = normalize_email($_POST['email'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'La sesión ha caducado. Vuelve a intentarlo.';
    }

    if (!$errors) {
        $user = authenticate_user($email, $password);
        if ($user) {
            login_user($user);
            if (($user['role'] ?? 'user') === 'admin') {
                redirect_to('panel-admin.php');
            }

            redirect_to(user_email_is_verified($user) ? 'panel-usuario.php' : 'verificacion-pendiente.php');
        }
        $errors[] = 'Email o contraseña incorrectos.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<?php page_head('Acceso | Con Sabor Flamenco', 'Acceso privado a Con Sabor Flamenco.', false); ?>
<body>
    <?php page_header(); ?>
    <main>
        <section class="page-intro auth-page-intro" data-ad-category="GENERAL">
            <p class="section-kicker">Acceso privado</p>
            <h1>Entra en tu cuenta</h1>
            <p>Vuelve a tu espacio privado para gestionar perfil, tarjeta, servicios y oportunidades dentro de la comunidad.</p>
        </section>

        <section class="auth-section auth-section-register" data-ad-category="GENERAL">
            <div class="auth-visual-card">
                <img src="assets/images/auth/acceso-flamenco.png" alt="Imagen artística de acceso privado en Con Sabor Flamenco" loading="lazy">
                <div class="auth-visual-caption">
                    <span>Área privada</span>
                    <strong>Tu puerta de entrada</strong>
                    <p>Accede a tu panel de miembro y continúa construyendo tu presencia flamenca.</p>
                </div>
            </div>

            <form class="auth-card auth-card-register auth-card-login" method="post" action="acceso.php" novalidate>
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <h2>Acceso</h2>
                <?php if ($passwordReset): ?><div class="form-alert form-alert-success" role="status"><p>Contraseña actualizada. Ya puedes acceder.</p></div><?php endif; ?>
                <?php if ($errors): ?><div class="form-alert form-alert-error" role="alert"><?php foreach ($errors as $error): ?><p><?= e($error) ?></p><?php endforeach; ?></div><?php endif; ?>
                <label for="email">Email</label>
                <input id="email" name="email" type="email" autocomplete="email" value="<?= e($email) ?>" required>
                <label for="password">Contraseña</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required>
                <button class="button button-primary" type="submit">Entrar</button>
                <p class="auth-switch"><a href="recuperar-contrasena.php">He olvidado mi contraseña</a></p>
                <p class="auth-switch">¿Aún no tienes cuenta? <a href="registro.php">Crea una aquí</a>.</p>
            </form>
        </section>
    </main>
    <?php page_footer(); ?>
    <?php province_modal('Así podremos mostrarte contenido y oportunidades relevantes cuando vuelvas a la parte pública.'); ?>
</body>
</html>
