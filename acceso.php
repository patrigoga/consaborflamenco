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

    if (user_email_is_verified($currentUser)) {
        redirect_to('panel-usuario.php');
    }

    $pendingEmail = (string) ($currentUser['email'] ?? '');
    logout_user();
    redirect_to('verificacion-pendiente.php?email=' . urlencode($pendingEmail));
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
            if (($user['role'] ?? 'user') === 'admin') {
                login_user($user);
                redirect_to('panel-admin.php');
            }

            if (!user_email_is_verified($user)) {
                redirect_to('verificacion-pendiente.php?email=' . urlencode((string) ($user['email'] ?? $email)));
            }

            login_user($user);
            redirect_to('panel-usuario.php');
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
                <div class="password-field">
                    <input id="password" name="password" type="password" autocomplete="current-password" required>
                    <button type="button" class="password-toggle" aria-label="Mostrar contraseña" aria-pressed="false">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5c-7.633 0-11 6.818-11 7s3.367 7 11 7 11-6.818 11-7-3.367-7-11-7zm0 12c-2.761 0-5-2.243-5-5 0-2.757 2.239-5 5-5s5 2.243 5 5c0 2.757-2.239 5-5 5zm0-8.5c-1.932 0-3.5 1.568-3.5 3.5s1.568 3.5 3.5 3.5 3.5-1.568 3.5-3.5-1.568-3.5-3.5-3.5z"/></svg>
                    </button>
                </div>
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
