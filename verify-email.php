<?php
declare(strict_types=1);

require_once __DIR__ . '/app/auth.php';
require_once __DIR__ . '/app/layout.php';

$token = (string) ($_GET['token'] ?? '');
$verified = false;
$error = null;
$verifiedUser = null;

if ($token !== '') {
    $verifiedUser = find_user_by_email_verification_token($token);
    if (consume_email_verification_token($token)) {
        $verified = true;
    } else {
        $error = 'El enlace no es válido o ha caducado.';
    }
} else {
    $error = 'Falta el token de verificación.';
}

?>
<!DOCTYPE html>
<html lang="es">
<?php page_head('Verificar correo | Con Sabor Flamenco', 'Verificación de correo electrónico.', false); ?>
<body>
    <?php page_header(); ?>
    <main>
        <section class="auth-section auth-section-narrow" data-ad-category="GENERAL">
            <div class="auth-copy">
                <p class="section-kicker">Verificación</p>
                <h1>Verifica tu correo</h1>
                <?php if ($verified): ?>
                    <?php $welcomeName = clean_text((string) ($verifiedUser['name'] ?? '')); ?>
                    <div class="form-alert form-alert-success"><p><?= $welcomeName !== '' ? 'Bienvenido/a, ' . e($welcomeName) . '. ' : '' ?>Tu correo ha sido verificado. Ya puedes acceder a tu cuenta.</p></div>
                    <p><a class="button" href="acceso.php">Ir a acceso</a></p>
                <?php else: ?>
                    <div class="form-alert form-alert-error"><p><?= e($error) ?></p></div>
                    <p>Si sigues teniendo problemas, escribe a <a href="mailto:soporte@consaborflamenco.com">soporte@consaborflamenco.com</a>.</p>
                <?php endif; ?>
            </div>
        </section>
    </main>
    <?php page_footer(); ?>
    <?php province_modal('Así podremos mantener tu experiencia localizada también durante el acceso privado.'); ?>
</body>
</html>
