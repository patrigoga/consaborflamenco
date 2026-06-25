<?php
declare(strict_types=1);

require_once __DIR__ . '/app/auth.php';
require_once __DIR__ . '/app/layout.php';

$errors = [];
$values = [
    'name' => '',
    'email' => '',
    'member_type' => 'artista',
    'public_name' => '',
    'short_description' => '',
    'city' => '',
    'province' => '',
];

if (current_user()) {
    redirect_to(($_SESSION['user_role'] ?? 'user') === 'admin' ? 'panel-admin.php' : 'panel-usuario.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $values['name'] = clean_text($_POST['name'] ?? '');
    $values['email'] = normalize_email($_POST['email'] ?? '');
    $values['member_type'] = normalize_member_type((string) ($_POST['member_type'] ?? 'artista'));
    $values['public_name'] = clean_text($_POST['public_name'] ?? $values['name']);
    $values['short_description'] = clean_text($_POST['short_description'] ?? '');
    $values['city'] = clean_text($_POST['city'] ?? '');
    $values['province'] = clean_text($_POST['province'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');
    $acceptedTerms = isset($_POST['terms']);

    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'La sesión ha caducado. Vuelve a intentarlo.';
    }
    if (strlen($values['name']) < 2) {
        $errors[] = 'Indica tu nombre o el nombre de tu proyecto.';
    }
    if (strlen($values['public_name']) < 2) {
        $errors[] = 'Indica el nombre publico de tu espacio artistico.';
    }
    if (strlen($values['short_description']) < 20) {
        $errors[] = 'Describe tu actividad flamenca en al menos 20 caracteres.';
    }
    if ($values['city'] === '' || $values['province'] === '') {
        $errors[] = 'Indica ciudad y provincia para ubicar tu espacio.';
    }
    if (!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Introduce un email válido.';
    }
    if (filter_var($values['email'], FILTER_VALIDATE_EMAIL) && find_user_by_email($values['email'])) {
        $errors[] = 'Ya existe una cuenta con ese email.';
    }
    if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/\d/', $password)) {
        $errors[] = 'La contraseña debe tener al menos 8 caracteres, una mayúscula, una minúscula y un número.';
    }
    if ($password !== $passwordConfirm) {
        $errors[] = 'Las contraseñas no coinciden.';
    }
    if (!$acceptedTerms) {
        $errors[] = 'Debes aceptar los términos y condiciones para crear tu cuenta.';
    }

    $mainPhotoPath = null;
    if (!$errors) {
        $mainPhotoPath = save_member_photo_upload($_FILES['main_photo'] ?? null, $errors, true);
    }

    if (!$errors) {
        try {
            $user = create_user($values['name'], $values['email'], $password, [
                'member_type' => $values['member_type'],
                'public_name' => $values['public_name'],
                'short_description' => $values['short_description'],
                'city' => $values['city'],
                'province' => $values['province'],
                'main_photo_path' => $mainPhotoPath ?? '',
            ]);
            login_user($user);
            redirect_to(($user['role'] ?? 'user') === 'admin' ? 'panel-admin.php' : 'panel-usuario.php');
        } catch (InvalidArgumentException $exception) {
            $errors[] = $exception->getMessage();
        } catch (Throwable $exception) {
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
            <p>Tu acceso privado para configurar perfil, tarjeta de miembro, servicios y oportunidades dentro de la comunidad.</p>
        </section>

        <section class="auth-section auth-section-register" data-ad-category="GENERAL">
            <div class="auth-visual-card">
                <img src="assets/images/auth/registro-flamenco.png" alt="Imagen artística de registro en Con Sabor Flamenco" loading="lazy">
                <div class="auth-visual-caption">
                    <span>Comunidad CSF</span>
                    <strong>Empieza tu espacio privado</strong>
                    <p>Perfil, tarjeta, descuentos y futuras herramientas para miembros.</p>
                </div>
            </div>

            <form class="auth-card auth-card-register" method="post" action="registro.php" enctype="multipart/form-data" novalidate>
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <h2>Datos de acceso</h2>
                <?php if ($errors): ?>
                    <div class="form-alert form-alert-error" role="alert">
                        <?php foreach ($errors as $error): ?><p><?= e($error) ?></p><?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <label for="name">Nombre o proyecto</label>
                <input id="name" name="name" type="text" autocomplete="name" value="<?= e($values['name']) ?>" required>
                <label for="member_type">Tipo de espacio</label>
                <select id="member_type" name="member_type" required>
                    <?php foreach (member_type_options() as $typeValue => $typeLabel): ?>
                        <option value="<?= e($typeValue) ?>" <?= $values['member_type'] === $typeValue ? 'selected' : '' ?>><?= e($typeLabel) ?></option>
                    <?php endforeach; ?>
                </select>
                <label for="public_name">Nombre publico artistico</label>
                <input id="public_name" name="public_name" type="text" value="<?= e($values['public_name']) ?>" placeholder="Ej. Carlos Moreno Flamenco" required>
                <label for="short_description">Descripcion artistica</label>
                <textarea id="short_description" name="short_description" rows="3" maxlength="500" required><?= e($values['short_description']) ?></textarea>
                <div class="form-grid-two">
                    <label for="city">Ciudad<input id="city" name="city" type="text" value="<?= e($values['city']) ?>" required></label>
                    <label for="province">Provincia<input id="province" name="province" type="text" value="<?= e($values['province']) ?>" required></label>
                </div>
                <label for="main_photo">Fotografia principal</label>
                <input id="main_photo" name="main_photo" type="file" accept="image/jpeg,image/png,image/webp" required>
                <p class="field-help">Obligatoria para crear tu espacio. JPG, PNG o WebP, maximo 5 MB.</p>
                <label for="email">Email</label>
                <input id="email" name="email" type="email" autocomplete="email" value="<?= e($values['email']) ?>" required>
                <label for="password">Contraseña</label>
                <input id="password" name="password" type="password" autocomplete="new-password" minlength="8" required>
                <p class="field-help">Mínimo 8 caracteres, con mayúscula, minúscula y número.</p>
                <label for="password_confirm">Repetir contraseña</label>
                <input id="password_confirm" name="password_confirm" type="password" autocomplete="new-password" minlength="8" required>
                <label class="checkbox-field" for="terms"><input id="terms" name="terms" type="checkbox" required><span>Acepto los <a href="terminos-condiciones.php" target="_blank" rel="noopener">términos y condiciones</a>.</span></label>
                <button class="button button-primary" type="submit">Crear cuenta</button>
                <p class="auth-switch">¿Ya tienes cuenta? <a href="acceso.php">Accede aquí</a>.</p>
            </form>
        </section>
    </main>
    <?php page_footer(); ?>
    <?php province_modal('Así podremos mostrarte servicios, comunidad y oportunidades relevantes según tu provincia.'); ?>
</body>
</html>
