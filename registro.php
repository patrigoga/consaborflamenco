<?php
declare(strict_types=1);

require_once __DIR__ . '/app/auth.php';
require_once __DIR__ . '/app/layout.php';

$errors = [];
$values = [
    'email' => '',
    'member_type' => 'artista',
];

if (current_user()) {
    redirect_to(($_SESSION['user_role'] ?? 'user') === 'admin' ? 'panel-admin.php' : 'panel-usuario.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $values['email'] = normalize_email($_POST['email'] ?? '');
    $values['member_type'] = normalize_member_type((string) ($_POST['member_type'] ?? 'artista'));
    $password = (string) ($_POST['password'] ?? '');
    $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');
    $acceptedTerms = isset($_POST['terms']);

    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'La sesion ha caducado. Vuelve a intentarlo.';
    }
    if (!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Introduce un email valido.';
    }
    if (filter_var($values['email'], FILTER_VALIDATE_EMAIL) && find_user_by_email($values['email'])) {
        $errors[] = 'Ya existe una cuenta con ese email.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'La contrasena debe tener al menos 8 caracteres.';
    }
    if ($password !== $passwordConfirm) {
        $errors[] = 'Las contrasenas no coinciden.';
    }
    if (!$acceptedTerms) {
        $errors[] = 'Debes aceptar los terminos y condiciones para crear tu cuenta.';
    }

    if (!$errors) {
        try {
            $user = create_user('', $values['email'], $password, [
                'member_type' => $values['member_type'],
            ]);
            login_user($user);
            redirect_to(($user['role'] ?? 'user') === 'admin' ? 'panel-admin.php' : 'panel-usuario.php');
        } catch (InvalidArgumentException $exception) {
            $errors[] = $exception->getMessage();
        } catch (Throwable) {
            $errors[] = 'No se pudo crear la cuenta en este momento.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<?php page_head('Crear cuenta | Con Sabor Flamenco', 'Registro de miembros de Con Sabor Flamenco.', false); ?>
<body>
    <?php page_header(); ?>
    <main>
        <section class="page-intro auth-page-intro" data-ad-category="GENERAL">
            <p class="section-kicker">Registro de miembros</p>
            <h1>Crea tu cuenta con sabor flamenco</h1>
            <p>Un alta rapida para entrar en tu area privada. El perfil artistico completo se rellena despues, con calma.</p>
        </section>

        <section class="auth-section auth-section-register" data-ad-category="GENERAL">
            <div class="auth-visual-card">
                <img src="assets/images/auth/registro-flamenco.png" alt="Imagen artistica de registro en Con Sabor Flamenco" loading="lazy">
                <div class="auth-visual-caption">
                    <span>Comunidad CSF</span>
                    <strong>Empieza tu espacio privado</strong>
                    <p>Registro ligero, perfil profesional y tarjeta de miembro desde tu panel.</p>
                </div>
            </div>

            <form class="auth-card auth-card-register auth-card-compact" method="post" action="registro.php" novalidate>
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <h2>Datos de acceso</h2>
                <?php if ($errors): ?>
                    <div class="form-alert form-alert-error" role="alert">
                        <?php foreach ($errors as $error): ?><p><?= e($error) ?></p><?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <label for="member_type">Tipo de espacio</label>
                <select id="member_type" name="member_type" required>
                    <?php foreach (member_type_options() as $typeValue => $typeLabel): ?>
                        <option value="<?= e($typeValue) ?>" <?= $values['member_type'] === $typeValue ? 'selected' : '' ?>><?= e($typeLabel) ?></option>
                    <?php endforeach; ?>
                </select>

                <label for="email">Email</label>
                <input id="email" name="email" type="email" autocomplete="email" value="<?= e($values['email']) ?>" required>

                <label for="password">Contrasena</label>
                <input id="password" name="password" type="password" autocomplete="new-password" minlength="8" required>
                <p class="field-help">Minimo 8 caracteres.</p>

                <label for="password_confirm">Repetir contrasena</label>
                <input id="password_confirm" name="password_confirm" type="password" autocomplete="new-password" minlength="8" required>

                <label class="checkbox-field" for="terms">
                    <input id="terms" name="terms" type="checkbox" required>
                    <span>Acepto los <a href="terminos-condiciones.php" target="_blank" rel="noopener">terminos y condiciones</a>.</span>
                </label>

                <button class="button button-primary" type="submit">Crear cuenta</button>
                <p class="auth-switch">Ya tienes cuenta? <a href="acceso.php">Accede aqui</a>.</p>
            </form>
        </section>
    </main>
    <?php page_footer(); ?>
    <?php province_modal('Asi podremos mostrarte servicios, comunidad y oportunidades relevantes segun tu provincia.'); ?>
</body>
</html>
