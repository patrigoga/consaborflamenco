<?php
declare(strict_types=1);

require_once __DIR__ . '/app/auth.php';
require_once __DIR__ . '/app/layout.php';

$user = require_login();
$userName = $user['name'] ?? 'Miembro';
$memberNumber = !empty($user['member_number'])
    ? str_pad((string) $user['member_number'], 6, '0', STR_PAD_LEFT)
    : str_pad((string) ((hexdec(substr((string) ($user['id'] ?? '000000'), 0, 6)) % 90000) + 10000), 6, '0', STR_PAD_LEFT);
$memberCode = !empty($user['member_code'])
    ? (string) $user['member_code']
    : 'CSF-' . strtoupper(substr(hash('sha1', (string) ($user['id'] ?? '') . ($user['email'] ?? '')), 0, 8));
$memberTier = strtolower((string) ($user['membership_tier'] ?? 'simpatizante'));
$isVipMember = $memberTier === 'vip';
$memberStatus = $isVipMember ? 'Miembro VIP' : 'Miembro simpatizante';
$vipMembershipPrice = '80 €/año';
$discountStatus = $isVipMember ? 'Descuentos activos' : 'Sin descuentos';
$discountStatusClass = $isVipMember ? 'status-pill-active' : 'status-pill-pending';
$availableCardBackgrounds = [
    'tarjeta-bailaora.png' => [
        'path' => 'assets/images/member-cards/tarjeta-bailaora.png',
        'figure' => 'woman',
    ],
    'tarjeta-bailaor.png' => [
        'path' => 'assets/images/member-cards/tarjeta-bailaor.png',
        'figure' => 'man',
    ],
];
$selectedCardBackground = (string) ($_GET['card_background'] ?? 'tarjeta-bailaora.png');
if (!isset($availableCardBackgrounds[$selectedCardBackground])) {
    $selectedCardBackground = 'tarjeta-bailaora.png';
}
$cardBackground = $availableCardBackgrounds[$selectedCardBackground]['path'];
$cardFigure = $availableCardBackgrounds[$selectedCardBackground]['figure'];
$profileMessages = [];
$profileErrors = [];
$memberProfile = default_member_profile($user);
$publicFieldOptions = [
    'phone' => 'Telefono',
    'birth_place' => 'Lugar de origen',
    'years_active' => 'Trayectoria',
    'availability' => 'Disponibilidad',
    'experience' => 'Experiencia',
];

function is_public_field(array $profile, string $field): bool
{
    $publicFields = is_array($profile['public_fields'] ?? null) ? $profile['public_fields'] : [];
    return in_array($field, $publicFields, true);
}

function cv_uploaded_file(array $files, string $section, int $rowIndex): ?array
{
    if (!isset($files[$section]['error'][$rowIndex]['image'])) {
        return null;
    }

    return [
        'name' => $files[$section]['name'][$rowIndex]['image'] ?? '',
        'type' => $files[$section]['type'][$rowIndex]['image'] ?? '',
        'tmp_name' => $files[$section]['tmp_name'][$rowIndex]['image'] ?? '',
        'error' => $files[$section]['error'][$rowIndex]['image'] ?? UPLOAD_ERR_NO_FILE,
        'size' => $files[$section]['size'][$rowIndex]['image'] ?? 0,
    ];
}

function clean_cv_entries(
    array $source,
    string $section,
    array $fields,
    array $options = [],
    array $existingEntries = [],
    array $files = [],
    array &$errors = []
): array
{
    $rows = is_array($source[$section] ?? null) ? $source[$section] : [];
    $entries = [];
    $requiresTitleDescription = !empty($options['requires_title_description']);
    $allowsImage = !empty($options['allows_image']);
    $sectionLabel = (string) ($options['title'] ?? $section);
    $hasRequiredError = false;

    foreach ($rows as $rowIndex => $row) {
        if (!is_array($row)) {
            continue;
        }

        $entry = [];
        $hasContent = false;
        foreach ($fields as $field) {
            $rawValue = (string) ($row[$field] ?? '');
            $value = in_array($field, ['entry_description', 'description'], true)
                ? clean_html_text($rawValue)
                : clean_text($rawValue);
            $entry[$field] = $value;
            $hasContent = $hasContent || $value !== '';
        }

        if ($allowsImage) {
            $entry['image_path'] = clean_text((string) ($existingEntries[$rowIndex]['image_path'] ?? ''));
            $uploadedImagePath = save_member_cv_image_upload(cv_uploaded_file($files, $section, (int) $rowIndex), $errors);
            if ($uploadedImagePath) {
                $entry['image_path'] = $uploadedImagePath;
            }
            $hasContent = $hasContent || $entry['image_path'] !== '';
        }

        if ($hasContent) {
            if (
                $requiresTitleDescription
                && (clean_text((string) ($entry['entry_title'] ?? '')) === '' || clean_text((string) ($entry['entry_description'] ?? '')) === '')
                && !$hasRequiredError
            ) {
                $errors[] = $sectionLabel . ': cada entrada con contenido necesita titulo y descripcion.';
                $hasRequiredError = true;
            }
            $entries[] = $entry;
        }
    }

    return $entries;
}

function cv_public_badge(array $profile, string $field): string
{
    return is_public_field($profile, $field) ? 'Publico' : 'Privado';
}

function sort_cv_entries_by_date(array $entries, string $order): array
{
    usort($entries, static function (array $left, array $right) use ($order): int {
        $leftDate = (string) ($left['date_start'] ?? $left['date_end'] ?? '');
        $rightDate = (string) ($right['date_start'] ?? $right['date_end'] ?? '');
        $comparison = strcmp($leftDate, $rightDate);
        return $order === 'asc' ? $comparison : -$comparison;
    });

    return $entries;
}

function cv_print_date(string $date): string
{
    $date = clean_text($date);
    if ($date === '') {
        return '';
    }

    $timestamp = strtotime($date);
    return $timestamp ? date('d/m/Y', $timestamp) : $date;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['profile_action'] ?? '') === 'update_profile') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $profileErrors[] = 'La sesion ha caducado. Vuelve a intentarlo.';
    }

    if (!$profileErrors) {
        $photoPath = save_member_photo_upload($_FILES['main_photo'] ?? null, $profileErrors, empty($memberProfile['main_photo_path']));
        if ($photoPath) {
            $memberProfile['main_photo_path'] = $photoPath;
        }
    }

    if (!$profileErrors) {
        $memberProfile = member_profile_from_input($_POST, $memberProfile);
        $submittedPublicFields = is_array($_POST['public_fields'] ?? null) ? $_POST['public_fields'] : [];
        $publicFields = array_values(array_intersect(array_keys($publicFieldOptions), array_map('strval', $submittedPublicFields)));
        $memberProfile['public_fields'] = $publicFields;
        $submittedSortOrders = is_array($_POST['sort_orders'] ?? null) ? $_POST['sort_orders'] : [];
        $memberProfile['sort_orders'] = array_map(
            static fn ($value): string => $value === 'asc' ? 'asc' : 'desc',
            array_intersect_key($submittedSortOrders, $publicFieldOptions)
        );
        $entryMediaOptions = ['requires_title_description' => false, 'allows_image' => true];
        $memberProfile['experience'] = clean_cv_entries(
            $_POST,
            'experience',
            ['category', 'description', 'date_start', 'date_end', 'location'],
            $entryMediaOptions + ['title' => 'Experiencia profesional'],
            $memberProfile['experience'],
            $_FILES,
            $profileErrors
        );
        $memberProfile['experience'] = sort_cv_entries_by_date(
            $memberProfile['experience'],
            $memberProfile['sort_orders']['experience'] ?? 'desc'
        );
        $memberProfile['completed_at'] = profile_is_complete($memberProfile) ? ($memberProfile['completed_at'] ?? gmdate('c')) : null;
    }

    if (!$profileErrors) {
        $user['artistic_profile'] = $memberProfile;
        update_user($user);
        $profileMessages[] = profile_is_complete($memberProfile)
            ? 'Perfil artistico actualizado.'
            : 'Perfil guardado. Sigue pendiente completar nombre publico, descripcion, ciudad, provincia, fotografia principal y al menos una experiencia profesional.';
    }
}

$memberTypeLabel = member_type_options()[$memberProfile['member_type']] ?? 'Artista';
$profileStatus = profile_is_complete($memberProfile) ? 'Perfil completo' : 'Perfil pendiente';
$profileStatusClass = profile_is_complete($memberProfile) ? 'status-pill-active' : 'status-pill-pending';
$displayName = $memberProfile['public_name'] !== '' ? $memberProfile['public_name'] : $userName;
$cardHeadline = clean_text((string) ($memberProfile['artistic_headline'] ?? ''));
$cardSpecialties = clean_text((string) ($memberProfile['specialties'] ?? ''));
$profileRequiredFields = [
    $memberProfile['public_name'] ?? '',
    $memberProfile['short_description'] ?? '',
    $memberProfile['city'] ?? '',
    $memberProfile['province'] ?? '',
    $memberProfile['main_photo_path'] ?? '',
    !empty($memberProfile['experience']) ? 'experience' : '',
];
$completedProfileFields = count(array_filter($profileRequiredFields, static fn ($value): bool => clean_text((string) $value) !== ''));
$profileCompletion = (int) round(($completedProfileFields / count($profileRequiredFields)) * 100);
$cvSectionConfig = [
    'experience' => [
        'title' => 'Experiencia profesional',
        'public_field' => 'experience',
        'fields' => ['category' => 'Categoria / cargo', 'description' => 'Descripcion', 'date_start' => 'Inicio', 'date_end' => 'Fin', 'location' => 'Lugar / entidad'],
        'sortable' => true,
        'requires_title_description' => false,
        'allows_image' => true,
    ],
];
?>
<!DOCTYPE html>
<html lang="es">
<?php page_head('Panel de miembro | Con Sabor Flamenco', 'Área privada de miembros de Con Sabor Flamenco.', false); ?>
<body>
    <?php page_header(); ?>
    <main>
        <section class="page-intro" data-ad-category="GENERAL">
            <p class="section-kicker">Área privada</p>
            <h1>Panel de miembro</h1>
            <p>Bienvenido/a, <?= e($userName) ?>. Desde aquí configurarás tu perfil, tarjeta de miembro y espacios de banner.</p>
        </section>

        <section class="member-panel">
            <aside class="member-sidebar" aria-label="Menú del panel de miembro">
                <div class="member-sidebar-card">
                    <span class="profile-avatar profile-avatar-large"><?= e(strtoupper(substr($displayName, 0, 1))) ?></span>
                    <strong><?= e($displayName) ?></strong>
                    <span><?= e($memberStatus) ?> · Nº <?= e($memberNumber) ?></span>
                </div>
                <nav class="member-sidebar-nav">
                    <a href="#perfil">Perfil</a>
                    <a href="#tarjeta-miembro">Tarjeta de miembro</a>
                    <a href="#banners">Banners</a>
                    <a href="#seguridad">Seguridad</a>
                </nav>
            </aside>

            <div class="member-panel-content">
                <div class="member-panel-tabs" role="tablist" aria-label="Secciones del panel de miembro">
                    <button type="button" class="tab-button panel-tab-button active" data-tab-target="perfil">Ficha artistica</button>
                    <button type="button" class="tab-button panel-tab-button" data-tab-target="tarjeta-miembro">Tarjeta de miembro</button>
                    <button type="button" class="tab-button panel-tab-button" data-tab-target="banners">Banners</button>
                    <button type="button" class="tab-button panel-tab-button" data-tab-target="seguridad">Seguridad</button>
                </div>
                <section class="member-dashboard-hero" aria-label="Resumen del espacio">
                    <div class="member-dashboard-identity">
                        <?php if (!empty($memberProfile['main_photo_path'])): ?>
                            <img src="<?= e($memberProfile['main_photo_path']) ?>" alt="Fotografia principal de <?= e($displayName) ?>" loading="lazy" data-main-photo-preview>
                        <?php else: ?>
                            <img alt="Fotografia principal de <?= e($displayName) ?>" loading="lazy" data-main-photo-preview hidden>
                            <div class="member-dashboard-photo-placeholder" data-main-photo-placeholder><?= e(strtoupper(substr($displayName, 0, 1))) ?></div>
                        <?php endif; ?>
                        <div>
                            <span><?= e($memberTypeLabel) ?></span>
                            <h2><?= e($displayName) ?></h2>
                            <p><?= e($memberProfile['city']) ?><?= $memberProfile['city'] && $memberProfile['province'] ? ', ' : '' ?><?= e($memberProfile['province']) ?></p>
                        </div>
                    </div>
                    <div class="member-dashboard-actions">
                        <span class="status-pill <?= e($profileStatusClass) ?>"><?= e($profileStatus) ?></span>
                        <button class="button button-primary" type="button" onclick="window.print()">Imprimir curriculum PDF</button>
                    </div>
                </section>

                <section id="perfil" class="content-section member-panel-section active">
                    <div class="section-heading">
                        <div class="section-heading-content">
                            <p class="section-kicker">Perfil</p>
                            <h2>Ficha artistica</h2>
                            <p>Este bloque quedará conectado a la tabla de miembros para editar avatar, nombre público, tipo de miembro, biografía, provincia y redes.</p>
                        </div>
                        <span class="status-pill <?= e($profileStatusClass) ?>"><?= e($profileStatus) ?></span>
                    </div>
                    <div class="member-summary-grid">
                        <article class="member-summary-card">
                            <span>Nombre público</span>
                            <strong><?= e($displayName) ?></strong>
                        </article>
                        <article class="member-summary-card">
                            <span>Email</span>
                            <strong><?= e($user['email'] ?? '') ?></strong>
                        </article>
                        <article class="member-summary-card">
                            <span>Tipo de membresia</span>
                            <strong><?= e($memberStatus) ?></strong>
                        </article>
                    </div>
                    <div class="member-profile-editor">
                        <?php if ($profileErrors): ?>
                            <div class="form-alert form-alert-error" role="alert">
                                <?php foreach ($profileErrors as $error): ?><p><?= e($error) ?></p><?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($profileMessages): ?>
                            <div class="form-alert form-alert-success" role="status">
                                <?php foreach ($profileMessages as $message): ?><p><?= e($message) ?></p><?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <article class="member-profile-preview">
                            <div class="member-profile-photo">
                                <?php if (!empty($memberProfile['main_photo_path'])): ?>
                                    <img src="<?= e($memberProfile['main_photo_path']) ?>" alt="Fotografia principal de <?= e($displayName) ?>" loading="lazy" data-main-photo-preview>
                                <?php else: ?>
                                    <img alt="Fotografia principal de <?= e($displayName) ?>" loading="lazy" data-main-photo-preview hidden>
                                    <div class="member-photo-placeholder" data-main-photo-placeholder>Foto pendiente</div>
                                <?php endif; ?>
                                <button type="button" class="button button-secondary button-small member-photo-edit-button" data-main-photo-trigger>Editar imagen</button>
                            </div>
                            <div>
                                <span><?= e($memberTypeLabel) ?></span>
                                <strong><?= e($displayName) ?></strong>
                                <p><?= e($memberProfile['city']) ?><?= $memberProfile['city'] && $memberProfile['province'] ? ', ' : '' ?><?= e($memberProfile['province']) ?></p>
                            </div>
                            <span class="status-pill <?= e($profileStatusClass) ?>"><?= e($profileStatus) ?></span>
                        </article>
                        <form class="member-profile-form cv-editor" action="panel-usuario.php#perfil" method="post" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="profile_action" value="update_profile">
                            <div class="cv-editor-actions">
                                <button class="button button-primary" type="submit">Guardar curriculum</button>
                                <button class="button button-secondary" type="button" onclick="window.print()">Imprimir / guardar PDF</button>
                            </div>

                            <fieldset class="cv-fieldset profile-tab-panel active" data-profile-tab="artistica">
                                <legend>Identidad artistica</legend>
                                <div class="form-grid-two">
                                    <label for="member_type">Tipo de espacio
                                        <select id="member_type" name="member_type" required>
                                            <?php foreach (member_type_options() as $typeValue => $typeLabel): ?>
                                                <option value="<?= e($typeValue) ?>" <?= $memberProfile['member_type'] === $typeValue ? 'selected' : '' ?>><?= e($typeLabel) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </label>
                                    <label for="public_name">Nombre publico
                                        <input id="public_name" name="public_name" type="text" value="<?= e($displayName) ?>" required>
                                    </label>
                                </div>
                                <div class="form-grid-two">
                                    <label for="artistic_headline">Especialidad o titular artistico
                                        <input id="artistic_headline" name="artistic_headline" type="text" value="<?= e($memberProfile['artistic_headline']) ?>" placeholder="Ej. Bailaor flamenco y profesor de compas">
                                    </label>
                                    <label for="specialties">Especialidades
                                        <input id="specialties" name="specialties" type="text" value="<?= e($memberProfile['specialties']) ?>" placeholder="Baile, cante, guitarra, palmas, coreografia...">
                                    </label>
                                </div>
                                <label for="short_description">Descripcion breve publica
                                    <textarea id="short_description" name="short_description" rows="3" maxlength="700" required><?= e($memberProfile['short_description']) ?></textarea>
                                </label>
                                <label for="cv_summary">Biografia / resumen curricular
                                    <textarea id="cv_summary" name="cv_summary" rows="6" maxlength="1600" placeholder="Trayectoria, lenguaje artistico, experiencia principal y enfoque profesional."><?= e($memberProfile['cv_summary']) ?></textarea>
                                </label>
                            </fieldset>

                            <fieldset class="cv-fieldset profile-tab-panel" data-profile-tab="datos">
                                <legend>Datos de perfil e imagen</legend>
                                <p class="field-help">Aqui se rellenan ciudad, provincia, lugar de origen y fotografia principal. Estas opciones definen tu perfil visible.</p>
                                <div class="form-grid-three">
                                    <label for="city">Ciudad
                                        <input id="city" name="city" type="text" value="<?= e($memberProfile['city']) ?>" required>
                                    </label>
                                    <label for="province">Provincia
                                        <input id="province" name="province" type="text" value="<?= e($memberProfile['province']) ?>" required>
                                    </label>
                                    <label for="birth_place">Lugar de origen
                                        <input id="birth_place" name="birth_place" type="text" value="<?= e($memberProfile['birth_place']) ?>">
                                    </label>
                                </div>
                                <div class="form-grid-three">
                                    <label for="years_active">Anos de trayectoria
                                        <input id="years_active" name="years_active" type="text" value="<?= e($memberProfile['years_active']) ?>" placeholder="Ej. Desde 2012">
                                    </label>
                                    <label for="availability">Disponibilidad
                                        <input id="availability" name="availability" type="text" value="<?= e($memberProfile['availability']) ?>" placeholder="Clases, tablaos, festivales, eventos...">
                                    </label>
                                    <label for="phone">Telefono / WhatsApp
                                        <input id="phone" name="phone" type="text" value="<?= e($memberProfile['phone']) ?>">
                                    </label>
                                </div>
                                <div class="form-grid-two">
                                    <label for="website_url">Web
                                        <input id="website_url" name="website_url" type="url" value="<?= e($memberProfile['website_url']) ?>" placeholder="https://...">
                                    </label>
                                    <label for="instagram_url">Instagram
                                        <input id="instagram_url" name="instagram_url" type="url" value="<?= e($memberProfile['instagram_url']) ?>" placeholder="https://instagram.com/...">
                                    </label>
                                </div>
                                <div class="main-photo-field">
                                    <label for="main_photo">Fotografia principal</label>
                                    <input id="main_photo" name="main_photo" type="file" accept="image/jpeg,image/png,image/webp" <?= empty($memberProfile['main_photo_path']) ? 'required' : '' ?> data-main-photo-input hidden>
                                    <button type="button" class="button button-secondary button-small" data-main-photo-trigger>Seleccionar imagen</button>
                                </div>
                                <p class="field-help">Cada espacio debe tener al menos una fotografia principal. JPG, PNG o WebP, maximo 5 MB.</p>
                                <div class="visibility-grid" aria-label="Campos visibles en perfil publico">
                                    <?php foreach ($publicFieldOptions as $fieldValue => $fieldLabel): ?>
                                        <label class="visibility-toggle">
                                            <input type="checkbox" name="public_fields[]" value="<?= e($fieldValue) ?>" <?= is_public_field($memberProfile, $fieldValue) ? 'checked' : '' ?>>
                                            <span>Mostrar <?= e($fieldLabel) ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </fieldset>

                            <?php foreach ($cvSectionConfig as $sectionKey => $sectionConfig): ?>
                                <fieldset class="cv-fieldset cv-repeat-section">
                                    <div class="cv-section-heading">
                                        <legend><?= e($sectionConfig['title']) ?></legend>
                                        <div class="cv-section-tools">
                                            <?php if (!empty($sectionConfig['sortable'])): ?>
                                                <label>Orden
                                                    <select name="sort_orders[<?= e($sectionKey) ?>]">
                                                        <?php $sortOrder = ($memberProfile['sort_orders'][$sectionKey] ?? 'desc') === 'asc' ? 'asc' : 'desc'; ?>
                                                        <option value="desc" <?= $sortOrder === 'desc' ? 'selected' : '' ?>>Mas reciente primero</option>
                                                        <option value="asc" <?= $sortOrder === 'asc' ? 'selected' : '' ?>>Mas antiguo primero</option>
                                                    </select>
                                                </label>
                                            <?php endif; ?>
                                            <span><?= e(cv_public_badge($memberProfile, $sectionConfig['public_field'])) ?></span>
                                        </div>
                                    </div>
                                    <?php if (!empty($sectionConfig['requires_title_description'])): ?>
                                        <p class="field-help cv-section-note">Titulo y descripcion son obligatorios cuando anades una entrada a esta seccion.</p>
                                    <?php endif; ?>
                                    <div class="cv-repeat-list" data-repeat-list="<?= e($sectionKey) ?>">
                                    <?php $sectionRows = !empty($memberProfile[$sectionKey]) ? $memberProfile[$sectionKey] : [[]]; ?>
                                    <?php foreach ($sectionRows as $rowIndex => $entry): ?>
                                        <?php $entry = is_array($memberProfile[$sectionKey][$rowIndex] ?? null) ? $memberProfile[$sectionKey][$rowIndex] : []; ?>
                                        <div class="cv-repeat-row <?= !empty($sectionConfig['allows_image']) ? 'cv-repeat-row-with-media' : '' ?>">
                                            <?php if (!empty($sectionConfig['allows_image'])): ?>
                                                <label class="cv-entry-image-field">
                                                    Imagen de la entrada
                                                    <span class="cv-entry-image-box">
                                                        <img class="cv-entry-image-preview" src="<?= e((string) ($entry['image_path'] ?? '')) ?>" alt="Imagen guardada de <?= e($sectionConfig['title']) ?>" loading="lazy" data-cv-image-preview <?= empty($entry['image_path']) ? 'hidden' : '' ?>>
                                                        <span data-cv-image-placeholder <?= !empty($entry['image_path']) ? 'hidden' : '' ?>>Sin imagen</span>
                                                    </span>
                                                    <input name="<?= e($sectionKey) ?>[<?= e((string) $rowIndex) ?>][image]" type="file" accept="image/jpeg,image/png,image/webp" data-cv-image-input>
                                                </label>
                                            <?php endif; ?>
                                            <?php foreach ($sectionConfig['fields'] as $fieldName => $fieldLabel): ?>
                                                <?php
                                                $fieldClass = match ($fieldName) {
                                                    'category' => 'cv-entry-category-field',
                                                    'description' => 'cv-entry-description-field',
                                                    default => '',
                                                };
                                                ?>
                                                <label class="<?= e($fieldClass) ?>">
                                                    <?= e($fieldLabel) ?>
                                                    <?php if ($fieldName === 'description'): ?>
                                                        <div class="rich-text-toolbar" data-editor-toolbar></div>
                                                        <div class="rich-text-editor" contenteditable="true" data-rich-editor><?= $entry['description'] ?? '' ?></div>
                                                        <textarea name="<?= e($sectionKey) ?>[<?= e((string) $rowIndex) ?>][<?= e($fieldName) ?>]" rows="5" hidden><?= e((string) ($entry[$fieldName] ?? '')) ?></textarea>
                                                    <?php else: ?>
                                                        <input name="<?= e($sectionKey) ?>[<?= e((string) $rowIndex) ?>][<?= e($fieldName) ?>]" type="<?= str_starts_with($fieldName, 'date_') ? 'date' : 'text' ?>" value="<?= e((string) ($entry[$fieldName] ?? '')) ?>">
                                                    <?php endif; ?>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endforeach; ?>
                                    </div>
                                    <button class="button button-secondary cv-add-row" type="button" data-repeat-add="<?= e($sectionKey) ?>">Añadir <?= e(strtolower($sectionConfig['title'])) ?></button>
                                </fieldset>
                            <?php endforeach; ?>

                            <fieldset class="cv-fieldset">
                                <legend>Notas privadas</legend>
                                <label for="private_notes">Notas internas que no se publican
                                    <textarea id="private_notes" name="private_notes" rows="4" maxlength="1200" placeholder="Objetivos, contactos pendientes, condiciones, preferencias o informacion que no quieras publicar."><?= e($memberProfile['private_notes']) ?></textarea>
                                </label>
                            </fieldset>

                            <div class="cv-editor-actions">
                                <button class="button button-primary" type="submit">Guardar curriculum</button>
                                <button class="button button-secondary" type="button" onclick="window.print()">Imprimir / guardar PDF</button>
                            </div>
                        </form>
                        <section class="cv-print-document" aria-label="Curriculum imprimible">
                            <header class="cv-print-header">
                                <?php if (!empty($memberProfile['main_photo_path'])): ?>
                                    <img src="<?= e($memberProfile['main_photo_path']) ?>" alt="Fotografia principal de <?= e($displayName) ?>">
                                <?php endif; ?>
                                <div>
                                    <span><?= e($memberTypeLabel) ?></span>
                                    <h1><?= e($displayName) ?></h1>
                                    <?php if ($memberProfile['artistic_headline']): ?><p><?= e($memberProfile['artistic_headline']) ?></p><?php endif; ?>
                                    <p><?= e($memberProfile['city']) ?><?= $memberProfile['city'] && $memberProfile['province'] ? ', ' : '' ?><?= e($memberProfile['province']) ?></p>
                                </div>
                            </header>
                            <?php if ($memberProfile['cv_summary'] || $memberProfile['short_description']): ?>
                                <section>
                                    <h2>Perfil artistico</h2>
                                    <p><?= e($memberProfile['cv_summary'] ?: $memberProfile['short_description']) ?></p>
                                </section>
                            <?php endif; ?>
                            <section>
                                <h2>Datos profesionales</h2>
                                <dl>
                                    <?php if ($memberProfile['specialties']): ?><div><dt>Especialidades</dt><dd><?= e($memberProfile['specialties']) ?></dd></div><?php endif; ?>
                                    <?php if ($memberProfile['years_active']): ?><div><dt>Trayectoria</dt><dd><?= e($memberProfile['years_active']) ?></dd></div><?php endif; ?>
                                    <?php if ($memberProfile['availability']): ?><div><dt>Disponibilidad</dt><dd><?= e($memberProfile['availability']) ?></dd></div><?php endif; ?>
                                    <?php if ($memberProfile['website_url']): ?><div><dt>Web</dt><dd><?= e($memberProfile['website_url']) ?></dd></div><?php endif; ?>
                                    <?php if ($memberProfile['instagram_url']): ?><div><dt>Instagram</dt><dd><?= e($memberProfile['instagram_url']) ?></dd></div><?php endif; ?>
                                </dl>
                            </section>
                            <?php foreach ($cvSectionConfig as $sectionKey => $sectionConfig): ?>
                                <?php if (!empty($memberProfile[$sectionKey])): ?>
                                    <section>
                                        <h2><?= e($sectionConfig['title']) ?></h2>
                                        <div class="cv-print-list">
                                            <?php foreach ($memberProfile[$sectionKey] as $entry): ?>
                                                <?php
                                                $entryDescription = clean_html_text((string) ($entry['description'] ?? ''));
                                                $entryStart = cv_print_date((string) ($entry['date_start'] ?? ''));
                                                $entryEnd = cv_print_date((string) ($entry['date_end'] ?? ''));
                                                ?>
                                                <article class="cv-print-entry <?= !empty($entry['image_path']) ? 'cv-print-entry-with-image' : '' ?>">
                                                    <?php if (!empty($entry['image_path'])): ?>
                                                        <img class="cv-print-entry-image" src="<?= e((string) $entry['image_path']) ?>" alt="Imagen de <?= e($sectionConfig['title']) ?>">
                                                    <?php endif; ?>
                                                    <div class="cv-print-entry-main">
                                                        <?php if (!empty($entry['category'])): ?>
                                                            <p class="cv-print-entry-title"><?= e((string) $entry['category']) ?></p>
                                                        <?php endif; ?>
                                                        <?php if ($entryDescription !== ''): ?>
                                                            <div class="cv-print-entry-description"><?= $entryDescription ?></div>
                                                        <?php endif; ?>
                                                        <dl class="cv-print-entry-meta">
                                                            <?php if ($entryStart !== '' || $entryEnd !== ''): ?>
                                                                <div class="cv-print-entry-dates">
                                                                    <dt>Fechas</dt>
                                                                    <dd>
                                                                        <?php if ($entryStart !== ''): ?><span>Inicio: <?= e($entryStart) ?></span><?php endif; ?>
                                                                        <?php if ($entryEnd !== ''): ?><span>Fin: <?= e($entryEnd) ?></span><?php endif; ?>
                                                                    </dd>
                                                                </div>
                                                            <?php endif; ?>
                                                            <?php if (!empty($entry['location'])): ?>
                                                                <div>
                                                                    <dt>Lugar / entidad</dt>
                                                                    <dd><?= e((string) $entry['location']) ?></dd>
                                                                </div>
                                                            <?php endif; ?>
                                                        </dl>
                                                    </div>
                                                </article>
                                            <?php endforeach; ?>
                                        </div>
                                    </section>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            <footer class="cv-print-footer">
                                <img src="assets/images/member-cards/pegatina-con-sabor-flamenco.png" alt="Con Sabor Flamenco">
                                <span>Creado con <strong>consaborflamenco.com</strong></span>
                            </footer>
                        </section>
                    </div>
                </section>

                <section id="tarjeta-miembro" class="content-section member-panel-section">
                    <div class="section-heading">
                        <div class="section-heading-content">
                            <p class="section-kicker">Tarjeta identificativa</p>
                            <h2>Tu tarjeta de miembro</h2>
                            <p>La tarjeta identifica al miembro. Los descuentos solo se activan al pagar la membresia VIP anual de <?= e($vipMembershipPrice) ?>.</p>
                        </div>
                        <span class="status-pill <?= e($discountStatusClass) ?>"><?= e($discountStatus) ?></span>
                    </div>

                    <div class="member-card-layout">
                        <div class="member-card-preview member-card-preview-<?= e($cardFigure) ?>" data-card-preview>
                            <img src="<?= e($cardBackground) ?>" alt="Fondo de tarjeta de miembro" loading="lazy" data-card-image>
                            <img class="member-card-seal" src="assets/images/member-cards/pegatina-con-sabor-flamenco.png" alt="Sello Con Sabor Flamenco" loading="lazy">
                            <div class="member-card-overlay">
                                <span class="member-card-space"><?= e($memberTypeLabel) ?></span>
                                <strong><?= e($displayName) ?></strong>
                                <?php if ($cardHeadline !== ''): ?><span class="member-card-headline"><?= e($cardHeadline) ?></span><?php endif; ?>
                                <?php if ($cardSpecialties !== ''): ?><span class="member-card-specialties"><?= e($cardSpecialties) ?></span><?php endif; ?>
                                <?php if ($isVipMember): ?><code><?= e($memberCode) ?></code><?php endif; ?>
                            </div>
                            <div class="member-card-footer">
                                <span><?= e($memberStatus) ?></span>
                                <strong><span>con</span><em>sabor</em><span>flamenco</span><small>.com</small></strong>
                            </div>
                        </div>

                        <div class="member-config-card">
                            <h3>Configurar tarjeta</h3>
                            <p>La bailaora coloca los datos arriba a la izquierda; el bailaor los coloca arriba a la derecha.</p>
                            <div class="card-background-options" aria-label="Fondos disponibles">
                                <label>
                                    <input type="radio" name="card_background" value="tarjeta-bailaora.png" data-card-option data-card-figure="woman" data-card-src="assets/images/member-cards/tarjeta-bailaora.png" <?= $selectedCardBackground === 'tarjeta-bailaora.png' ? 'checked' : '' ?>>
                                    <img src="assets/images/member-cards/tarjeta-bailaora.png" alt="Fondo tarjeta bailaora">
                                </label>
                                <label>
                                    <input type="radio" name="card_background" value="tarjeta-bailaor.png" data-card-option data-card-figure="man" data-card-src="assets/images/member-cards/tarjeta-bailaor.png" <?= $selectedCardBackground === 'tarjeta-bailaor.png' ? 'checked' : '' ?>>
                                    <img src="assets/images/member-cards/tarjeta-bailaor.png" alt="Fondo tarjeta bailaor">
                                </label>
                            </div>
                            <p class="field-help">El diseno se actualiza al seleccionar una opcion.</p>
                        </div>
                    </div>
                </section>

                <section id="banners" class="content-section member-panel-section">
                    <div class="section-heading">
                        <div class="section-heading-content">
                            <p class="section-kicker">Publicidad</p>
                            <h2>Contratar y activar banners</h2>
                            <p>Cuando Stripe confirme el pago, el banner pasará a activo durante las fechas contratadas y dejará de verse al caducar.</p>
                        </div>
                        <span class="status-pill status-pill-pending">Stripe pendiente</span>
                    </div>

                    <div class="banner-dashboard-grid">
                        <article class="banner-status-card">
                            <span>Estado de espacio</span>
                            <strong>Sin banner activo</strong>
                            <p>Los campos de configuracion apareceran cuando contrates un banner. La fecha de inicio y fin se elegira durante la contratacion.</p>
                            <button class="button button-primary" type="button" disabled>Contratar banner proximamente</button>
                        </article>
                    </div>
                </section>

                <section id="seguridad" class="content-section member-panel-section">
                    <div class="section-heading">
                        <div class="section-heading-content">
                            <p class="section-kicker">Seguridad</p>
                            <h2>Cuenta y contraseña</h2>
                            <p>Desde aquí enlazaremos el cambio de contraseña y ajustes sensibles de la cuenta.</p>
                        </div>
                        <a class="section-enter-link" href="recuperar-contrasena.php">Cambiar contraseña</a>
                    </div>
                </section>
            </div>
        </section>
    </main>
    <?php page_footer(); ?>
    <?php province_modal('Así podremos mostrarte oportunidades y servicios relevantes para tu provincia.'); ?>
    <script>
        const originalDocumentTitle = document.title;
        window.addEventListener('beforeprint', () => {
            document.title = ' ';
        });
        window.addEventListener('afterprint', () => {
            document.title = originalDocumentTitle;
        });

        document.querySelectorAll('[data-repeat-add]').forEach((button) => {
            button.addEventListener('click', () => {
                const section = button.dataset.repeatAdd;
                const list = document.querySelector(`[data-repeat-list="${section}"]`);
                const firstRow = list?.querySelector('.cv-repeat-row');
                if (!list || !firstRow) {
                    return;
                }

                const nextIndex = list.querySelectorAll('.cv-repeat-row').length;
                const row = firstRow.cloneNode(true);
                row.querySelectorAll('[data-cv-image-preview]').forEach((image) => {
                    image.removeAttribute('src');
                    image.hidden = true;
                });
                row.querySelectorAll('[data-cv-image-placeholder]').forEach((placeholder) => {
                    placeholder.hidden = false;
                });
                row.querySelectorAll('input, textarea, select').forEach((input) => {
                    input.value = '';
                    if (input.name) {
                        input.name = input.name.replace(/\[\d+\]/, `[${nextIndex}]`);
                    }
                });
                const richEditor = row.querySelector('[data-rich-editor]');
                const textarea = row.querySelector('textarea[hidden]');
                if (richEditor && textarea) {
                    richEditor.innerHTML = '';
                    textarea.value = '';
                }
                row.querySelectorAll('[data-editor-toolbar]').forEach((toolbar) => {
                    toolbar.innerHTML = '';
                    delete toolbar.dataset.editorReady;
                });
                list.appendChild(row);
                initializeRichTextEditors(row);
            });
        });

        document.addEventListener('change', (event) => {
            const input = event.target;
            if (!(input instanceof HTMLInputElement)) {
                return;
            }

            if (input.matches('[data-cv-image-input]') && input.files?.[0]) {
                const field = input.closest('.cv-entry-image-field');
                const preview = field?.querySelector('[data-cv-image-preview]');
                const placeholder = field?.querySelector('[data-cv-image-placeholder]');
                if (preview instanceof HTMLImageElement) {
                    preview.src = URL.createObjectURL(input.files[0]);
                    preview.hidden = false;
                }
                if (placeholder instanceof HTMLElement) {
                    placeholder.hidden = true;
                }
            }

            if (input.matches('#main_photo') && input.files?.[0]) {
                const fileUrl = URL.createObjectURL(input.files[0]);
                const previewImages = document.querySelectorAll('[data-main-photo-preview]');
                const placeholders = document.querySelectorAll('[data-main-photo-placeholder]');
                previewImages.forEach((previewImage) => {
                    if (previewImage instanceof HTMLImageElement) {
                        previewImage.src = fileUrl;
                        previewImage.hidden = false;
                    }
                });
                placeholders.forEach((placeholder) => {
                    placeholder.hidden = true;
                });
            }
        });

        document.querySelectorAll('.profile-tab-button').forEach((button) => {
            button.addEventListener('click', () => {
                const target = button.dataset.profileTab;
                if (!target) {
                    return;
                }

                document.querySelectorAll('.profile-tab-button').forEach((tab) => tab.classList.toggle('active', tab === button));
                document.querySelectorAll('.profile-tab-panel').forEach((panel) => {
                    panel.classList.toggle('active', panel.dataset.profileTab === target);
                });
            });
        });

        function initializeRichTextEditors(scope = document) {
            scope.querySelectorAll('[data-editor-toolbar]').forEach((toolbar) => {
                if (toolbar.dataset.editorReady === '1') {
                    return;
                }

                const editor = toolbar.parentElement?.querySelector('[data-rich-editor]');
                const textarea = toolbar.parentElement?.querySelector('textarea[hidden]');
                if (!(editor instanceof HTMLElement) || !(textarea instanceof HTMLTextAreaElement)) {
                    return;
                }

                toolbar.dataset.editorReady = '1';
                toolbar.innerHTML = '';

                const controls = [
                    { label: 'B', title: 'Negrita', command: 'bold' },
                    { label: 'I', title: 'Cursiva', command: 'italic' },
                    { label: 'U', title: 'Subrayado', command: 'underline' },
                    { label: 'T', title: 'Titulo corto', command: 'formatBlock', value: 'h3' },
                    { label: 'P', title: 'Parrafo', command: 'formatBlock', value: 'p' },
                    { label: 'Q', title: 'Cita destacada', command: 'formatBlock', value: 'blockquote' },
                    { label: 'UL', title: 'Lista', command: 'insertUnorderedList' },
                    { label: 'OL', title: 'Lista numerada', command: 'insertOrderedList' },
                    { label: 'L', title: 'Alinear izquierda', command: 'justifyLeft' },
                    { label: 'C', title: 'Centrar', command: 'justifyCenter' },
                    { label: 'R', title: 'Color rojo', command: 'foreColor', value: '#c94f5c', color: '#c94f5c' },
                    { label: 'A', title: 'Color negro', command: 'foreColor', value: '#111114', color: '#111114' },
                    { label: 'X', title: 'Limpiar formato', command: 'removeFormat' },
                ];

                const syncEditor = () => {
                    textarea.value = editor.innerHTML;
                };

                controls.forEach((control) => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'rich-text-button';
                    button.textContent = control.label;
                    button.title = control.title;
                    button.setAttribute('aria-label', control.title);
                    if (control.color) {
                        button.style.color = control.color;
                    }
                    button.addEventListener('click', () => {
                        editor.focus();
                        document.execCommand('styleWithCSS', false, true);
                        document.execCommand(control.command, false, control.value || null);
                        syncEditor();
                    });
                    toolbar.appendChild(button);
                });

                editor.addEventListener('input', syncEditor);
                editor.addEventListener('blur', syncEditor);
                syncEditor();

                const form = toolbar.closest('form');
                if (form && form.dataset.richEditorSubmitBound !== '1') {
                    form.dataset.richEditorSubmitBound = '1';
                    form.addEventListener('submit', () => {
                        form.querySelectorAll('[data-rich-editor]').forEach((formEditor) => {
                            const formTextarea = formEditor.parentElement?.querySelector('textarea[hidden]');
                            if (formTextarea instanceof HTMLTextAreaElement) {
                                formTextarea.value = formEditor.innerHTML;
                            }
                        });
                    });
                }
            });
        }

        initializeRichTextEditors();

        const cardPreview = document.querySelector('[data-card-preview]');
        const cardImage = document.querySelector('[data-card-image]');
        document.querySelectorAll('[data-card-option]').forEach((input) => {
            input.addEventListener('change', () => {
                if (!cardPreview || !cardImage || !input.checked) {
                    return;
                }

                cardImage.src = input.dataset.cardSrc || cardImage.src;
                cardPreview.classList.toggle('member-card-preview-woman', input.dataset.cardFigure === 'woman');
                cardPreview.classList.toggle('member-card-preview-man', input.dataset.cardFigure === 'man');
            });
        });

        document.querySelectorAll('[data-main-photo-trigger]').forEach((button) => {
            button.addEventListener('click', () => {
                const input = document.querySelector('#main_photo');
                if (input instanceof HTMLInputElement) {
                    input.click();
                }
            });
        });

        document.querySelectorAll('.panel-tab-button').forEach((button) => {
            button.addEventListener('click', () => {
                const target = button.dataset.tabTarget;
                if (!target) {
                    return;
                }
                document.querySelectorAll('.panel-tab-button').forEach((tab) => tab.classList.toggle('active', tab === button));
                document.querySelectorAll('.member-panel-section').forEach((section) => {
                    section.style.display = section.id === target ? 'block' : 'none';
                });
            });
        });

    </script>
</body>
</html>
