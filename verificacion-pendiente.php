<?php
declare(strict_types=1);

require_once __DIR__ . '/app/auth.php';
require_once __DIR__ . '/app/layout.php';

$user = current_user();
if (!$user) {
    redirect_to('acceso.php');
}

if (($user['role'] ?? 'user') === 'admin') {
    redirect_to('panel-admin.php');
}

if (user_email_is_verified($user)) {
    redirect_to('panel-usuario.php');
}

$messages = [];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'La sesion ha caducado. Vuelve a intentarlo.';
    } else {
        $token = create_email_verification_token((string) ($user['email'] ?? ''));
        if ($token && send_email_verification((string) ($user['email'] ?? ''), $token, (string) ($user['name'] ?? ''))) {
            $messages[] = 'Te hemos enviado un nuevo correo de verificacion.';
        } else {
            $errors[] = 'No se pudo reenviar el correo ahora mismo. Revisa el email y vuelve a intentarlo en unos minutos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<?php page_head('Verifica tu email | Con Sabor Flamenco', 'Verificacion necesaria para acceder al area privada.', false); ?>
<body>
    <?php page_header(); ?>
    <main>
        <section class="auth-section auth-section-narrow" data-ad-category="GENERAL">
            <div class="auth-copy">
                <p class="section-kicker">Cuenta pendiente</p>
                <h1>Verifica tu email</h1>
                <p>Antes de entrar en tu area de usuario necesitamos confirmar tu correo. Te hemos enviado un enlace de verificacion a <strong><?= e((string) ($user['email'] ?? '')) ?></strong>.</p>
                <?php if ($messages): ?><div class="form-alert form-alert-success" role="status"><?php foreach ($messages as $message): ?><p><?= e($message) ?></p><?php endforeach; ?></div><?php endif; ?>
                <?php if ($errors): ?><div class="form-alert form-alert-error" role="alert"><?php foreach ($errors as $error): ?><p><?= e($error) ?></p><?php endforeach; ?></div><?php endif; ?>
                <form method="post" action="verificacion-pendiente.php">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <button class="button button-primary" type="submit">Reenviar correo de verificacion</button>
                    <a class="button button-secondary" href="cerrar-sesion.php">Salir</a>
                </form>
            </div>
        </section>
    </main>
    <?php page_footer(); ?>
    <?php province_modal('Asi podremos mostrarte contenido y oportunidades relevantes cuando vuelvas a la parte publica.'); ?>
</body>
</html>
