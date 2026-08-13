<?php
declare(strict_types=1);

require_once __DIR__ . '/app/auth.php';
require_once __DIR__ . '/app/layout.php';
require_once __DIR__ . '/app/academia_security.php';

$user = require_login();

// Degrada en silencio si el modulo de academias aun no esta migrado en este entorno.
$academiaPanelLink = false;
$alumnoPanelLink = false;
$panelPdo = db();
if ($panelPdo) {
    try {
        $academiaPanelLink = academia_memberships_for_user($panelPdo, academia_user_id($user), ['RESPONSABLE', 'PROFESOR']) !== [];

        $alumnoCheck = $panelPdo->prepare('SELECT id FROM academia_alumnos WHERE usuario_id = :usuario_id AND estado = "ACTIVO" LIMIT 1');
        $alumnoCheck->execute(['usuario_id' => academia_user_id($user)]);
        $alumnoPanelLink = $alumnoCheck->fetchColumn() !== false;
    } catch (Throwable $exception) {
        error_log('[panel-usuario] Academia links skipped: ' . $exception->getMessage());
    }
}

$userName = $user['name'] ?? 'Miembro';
$memberNumber = member_number_for_user($user);
$memberCode = member_code_for_user($user);
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
$memberCardPublicUrlBase = app_url('tarjeta-miembro.php?m=' . rawurlencode($memberCode) . '&d=');
$memberCardPublicUrl = $memberCardPublicUrlBase . rawurlencode($selectedCardBackground);
$memberCardQrBase = 'qr.php?data=';
$memberCardQrUrl = $memberCardQrBase . rawurlencode($memberCardPublicUrl);
$profileMessages = [];
$profileErrors = [];
$memberProfile = default_member_profile($user);
// Las academias tienen su propia microweb publica en /academia/{slug}, servida
// por academia.php, asi que no usan el constructor de pagina web del panel.
$hasWebPage = ($memberProfile['member_type'] ?? 'artista') !== 'academia';
$publicFieldOptions = [
    'phone' => 'Telefono',
    'birth_place' => 'Lugar de origen',
    'years_active' => 'Trayectoria',
    'availability' => 'Disponibilidad',
    'education' => 'Formacion',
    'experience' => 'Experiencia',
];
$cvSectionConfig = [
    'education' => [
        'title' => 'Formacion',
        'public_field' => 'education',
        'fields' => ['category' => 'Titulo / formacion', 'description' => 'Descripcion', 'date_start' => 'Inicio', 'date_end' => 'Fin', 'location' => 'Centro / maestro'],
        'sortable' => true,
        'requires_title_description' => false,
        'allows_image' => true,
        'default_order' => 1,
    ],
    'experience' => [
        'title' => 'Experiencia profesional',
        'public_field' => 'experience',
        'fields' => ['category' => 'Titulo / cargo', 'description' => 'Descripcion', 'date_start' => 'Inicio', 'date_end' => 'Fin', 'location' => 'Lugar / entidad'],
        'sortable' => true,
        'requires_title_description' => false,
        'allows_image' => true,
        'default_order' => 2,
    ],
    'custom_section' => [
        'title' => $memberProfile['custom_section_title'] ?? 'Seccion personalizada',
        'public_field' => 'custom_section',
        'fields' => ['category' => 'Titulo del articulo', 'description' => 'Descripcion', 'location' => 'Información adicional'],
        'sortable' => true,
        'requires_title_description' => false,
        'allows_image' => true,
        'default_order' => 3,
        'allow_title_edit' => true,
    ],
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

        $entry['is_active'] = isset($row['is_active']) && (string) $row['is_active'] === '1';
        $entry['display_order'] = max(1, (int) ($row['display_order'] ?? ($existingEntries[$rowIndex]['display_order'] ?? ($rowIndex + 1))));

        if ($allowsImage) {
            $existingImagePath = clean_text((string) ($row['image_path'] ?? ($existingEntries[$rowIndex]['image_path'] ?? '')));
            $entry['image_path'] = member_visible_asset_path($existingImagePath);
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

function sort_cv_entries(array $entries, string $order): array
{
    usort($entries, static function (array $left, array $right) use ($order): int {
        if ($order === 'manual') {
            return ((int) ($left['display_order'] ?? 0)) <=> ((int) ($right['display_order'] ?? 0));
        }

        $leftDate = (string) ($left['date_start'] ?? $left['date_end'] ?? '');
        $rightDate = (string) ($right['date_start'] ?? $right['date_end'] ?? '');
        $comparison = strcmp($leftDate, $rightDate);
        return $order === 'asc' ? $comparison : -$comparison;
    });

    return $entries;
}

function normalize_cv_sort_order(mixed $value): string
{
    $value = (string) $value;
    return in_array($value, ['desc', 'asc', 'manual'], true) ? $value : 'desc';
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

function clean_cv_section_settings(array $source, array $existingSettings, array $sectionConfig): array
{
    $settings = [];
    foreach ($sectionConfig as $sectionKey => $config) {
        $sectionInput = is_array($source[$sectionKey] ?? null) ? $source[$sectionKey] : [];
        $existingSection = is_array($existingSettings[$sectionKey] ?? null) ? $existingSettings[$sectionKey] : [];
        $settings[$sectionKey] = [
            'active' => array_key_exists('active', $sectionInput)
                ? (string) $sectionInput['active'] === '1'
                : (bool) ($existingSection['active'] ?? true),
            'order' => max(1, (int) ($sectionInput['order'] ?? ($existingSection['order'] ?? ($config['default_order'] ?? 99)))),
        ];
    }

    return $settings;
}

function cv_section_is_active(array $profile, string $sectionKey): bool
{
    $settings = is_array($profile['section_settings'][$sectionKey] ?? null) ? $profile['section_settings'][$sectionKey] : [];
    return (bool) ($settings['active'] ?? true);
}

function cv_entry_is_active(array $entry): bool
{
    return (bool) ($entry['is_active'] ?? true);
}

function cv_print_sections(array $profile, array $sectionConfig): array
{
    $sections = $sectionConfig;
    uksort($sections, static function (string $leftKey, string $rightKey) use ($profile, $sectionConfig): int {
        $leftSettings = is_array($profile['section_settings'][$leftKey] ?? null) ? $profile['section_settings'][$leftKey] : [];
        $rightSettings = is_array($profile['section_settings'][$rightKey] ?? null) ? $profile['section_settings'][$rightKey] : [];
        return ((int) ($leftSettings['order'] ?? ($sectionConfig[$leftKey]['default_order'] ?? 99))) <=> ((int) ($rightSettings['order'] ?? ($sectionConfig[$rightKey]['default_order'] ?? 99)));
    });

    return $sections;
}

/**
 * Indicadores de una seccion del curriculum para la cabecera de su pantalla.
 * El mismo calculo se repite en JavaScript al crear, editar o borrar entradas,
 * para no tener que recargar la pagina.
 */
function cv_section_metrics(array $entries): array
{
    $years = [];
    $active = 0;
    $withImage = 0;

    foreach ($entries as $entry) {
        if (!is_array($entry)) {
            continue;
        }
        if (cv_entry_is_active($entry)) {
            $active++;
        }
        if (clean_text((string) ($entry['image_path'] ?? '')) !== '') {
            $withImage++;
        }
        foreach (['date_start', 'date_end'] as $dateField) {
            $year = (int) substr(clean_text((string) ($entry[$dateField] ?? '')), 0, 4);
            if ($year > 0) {
                $years[] = $year;
            }
        }
    }

    $period = '—';
    if ($years !== []) {
        $period = min($years) === max($years) ? (string) min($years) : min($years) . '–' . max($years);
    }

    return [
        'total' => count($entries),
        'active' => $active,
        'images' => $withImage,
        'period' => $period,
    ];
}

/**
 * Etiqueta corta con fechas y lugar de una entrada, para el listado de la
 * tarjeta. Se replica en JavaScript al editar sin recargar la pagina.
 */
function cv_entry_meta_label(array $entry): string
{
    $dates = implode(' — ', array_filter([
        cv_print_date((string) ($entry['date_start'] ?? '')),
        cv_print_date((string) ($entry['date_end'] ?? '')),
    ], static fn (string $value): bool => $value !== ''));

    return implode(' · ', array_filter([
        $dates,
        clean_text((string) ($entry['location'] ?? '')),
    ], static fn (string $value): bool => $value !== ''));
}

/**
 * Panel lateral de una entrada del curriculum: cabecera, campos del formulario,
 * vista de solo lectura y pie con las acciones. Vive dentro del formulario de
 * perfil, asi que se envia con el resto del panel aunque se muestre flotando.
 *
 * $rowIndex llega como numero para las entradas guardadas y como marcador
 * __INDEX__ para la plantilla que clona el panel al crear una nueva.
 */
function cv_entry_fields_markup(string $sectionKey, array $sectionConfig, string $rowIndex, array $entry, string $sectionTitle): string
{
    $entryImagePath = member_visible_asset_path((string) ($entry['image_path'] ?? ''));
    $prefix = $sectionKey . '[' . $rowIndex . ']';
    $isActive = (bool) ($entry['is_active'] ?? true);
    ob_start();
    ?>
    <div class="member-entry-fields" data-entry-fields data-entry-mode="edit" hidden>
        <header class="member-entry-drawer-head">
            <div>
                <p class="member-entry-drawer-kicker"><?= e($sectionTitle) ?></p>
                <h4 data-entry-drawer-title>Editar entrada</h4>
            </div>
            <button type="button" class="member-entry-drawer-close" data-entry-close aria-label="Cerrar panel">&times;</button>
        </header>
        <div class="member-entry-drawer-body">
            <div class="member-entry-preview" data-entry-preview hidden></div>
            <div class="member-entry-form">
                <?php if (!empty($sectionConfig['allows_image'])): ?>
                    <label class="cv-entry-image-field">
                        Imagen de la entrada
                        <span class="cv-entry-image-box">
                            <img class="cv-entry-image-preview" <?= $entryImagePath !== '' ? 'src="' . e($entryImagePath) . '"' : '' ?> alt="Imagen de la entrada" loading="lazy" data-cv-image-preview <?= $entryImagePath === '' ? 'hidden' : '' ?>>
                            <span data-cv-image-placeholder <?= $entryImagePath !== '' ? 'hidden' : '' ?>>Sin imagen</span>
                        </span>
                        <input type="hidden" name="<?= e($prefix) ?>[image_path]" value="<?= e($entryImagePath) ?>" data-entry-field="image_path">
                        <input name="<?= e($prefix) ?>[image]" type="file" accept="image/jpeg,image/png,image/webp" data-cv-image-input>
                        <small>Se guarda automaticamente al seleccionar.</small>
                    </label>
                <?php endif; ?>
                <?php foreach ($sectionConfig['fields'] as $fieldName => $fieldLabel): ?>
                    <?php if ($fieldName === 'description'): ?>
                        <div class="cv-editor-field cv-entry-description-field">
                            <span class="field-label"><?= e($fieldLabel) ?></span>
                            <div class="rich-text-toolbar" data-editor-toolbar></div>
                            <div class="rich-text-editor" contenteditable="true" data-rich-editor data-entry-field="description"><?= $entry['description'] ?? '' ?></div>
                            <textarea name="<?= e($prefix) ?>[description]" rows="5" hidden><?= e((string) ($entry['description'] ?? '')) ?></textarea>
                        </div>
                    <?php else: ?>
                        <label class="<?= $fieldName === 'category' ? 'cv-entry-category-field' : '' ?>">
                            <?= e($fieldLabel) ?>
                            <input name="<?= e($prefix) ?>[<?= e($fieldName) ?>]" type="<?= str_starts_with($fieldName, 'date_') ? 'date' : 'text' ?>" value="<?= e((string) ($entry[$fieldName] ?? '')) ?>" data-entry-field="<?= e($fieldName) ?>">
                        </label>
                    <?php endif; ?>
                <?php endforeach; ?>
                <div class="cv-entry-controls">
                    <label class="visibility-toggle">
                        <input type="checkbox" name="<?= e($prefix) ?>[is_active]" value="1" <?= $isActive ? 'checked' : '' ?> data-default-checked="1" data-entry-field="is_active">
                        <span>Articulo activo en PDF</span>
                    </label>
                    <label>Orden
                        <input name="<?= e($prefix) ?>[display_order]" type="number" min="1" step="1" value="<?= e((string) ($entry['display_order'] ?? '')) ?>" data-entry-field="display_order">
                    </label>
                </div>
            </div>
        </div>
        <footer class="member-entry-drawer-foot">
            <button type="button" class="button button-secondary" data-entry-close>Cerrar</button>
            <button type="button" class="button button-secondary" data-entry-to-edit hidden>Editar</button>
            <button type="submit" class="button button-primary" data-entry-submit>Guardar cambios</button>
        </footer>
    </div>
    <?php

    return (string) ob_get_clean();
}

/**
 * Fila del listado de una tarjeta: resumen visible mas el panel lateral con los
 * campos, que permanece oculto hasta que se abre.
 */
function cv_entry_item_markup(string $sectionKey, array $sectionConfig, string $rowIndex, array $entry, string $sectionTitle): string
{
    $entryImagePath = member_visible_asset_path((string) ($entry['image_path'] ?? ''));
    $entryTitle = clean_text((string) ($entry['category'] ?? ''));
    $entryMeta = cv_entry_meta_label($entry);
    $isActive = (bool) ($entry['is_active'] ?? true);
    ob_start();
    ?>
    <article class="member-entry-item" data-entry-item>
        <button type="button" class="member-entry-open" data-entry-edit>
            <span class="member-entry-thumb">
                <img <?= $entryImagePath !== '' ? 'src="' . e($entryImagePath) . '"' : '' ?> alt="" loading="lazy" data-entry-thumb-image <?= $entryImagePath === '' ? 'hidden' : '' ?>>
                <span data-entry-thumb-placeholder <?= $entryImagePath !== '' ? 'hidden' : '' ?>>Sin imagen</span>
            </span>
            <span class="member-entry-text">
                <strong data-entry-title><?= e($entryTitle !== '' ? $entryTitle : 'Entrada sin titulo') ?></strong>
                <span class="member-entry-meta" data-entry-meta><?= e($entryMeta) ?></span>
            </span>
            <span class="member-entry-flag" data-entry-flag <?= $isActive ? 'hidden' : '' ?>>Oculta en el PDF</span>
        </button>
        <div class="member-entry-actions">
            <button type="button" class="member-entry-action" data-entry-view>Ver</button>
            <button type="button" class="member-entry-action" data-entry-edit>Editar</button>
            <button type="button" class="member-entry-action member-entry-action-danger" data-entry-delete>Borrar</button>
        </div>
        <?= cv_entry_fields_markup($sectionKey, $sectionConfig, $rowIndex, $entry, $sectionTitle) ?>
    </article>
    <?php

    return (string) ob_get_clean();
}

function web_gallery_uploaded_file(array $files, int $index): ?array
{
    if (!isset($files['error'][$index])) {
        return null;
    }

    return [
        'name' => $files['name'][$index] ?? '',
        'type' => $files['type'][$index] ?? '',
        'tmp_name' => $files['tmp_name'][$index] ?? '',
        'error' => $files['error'][$index] ?? UPLOAD_ERR_NO_FILE,
        'size' => $files['size'][$index] ?? 0,
    ];
}

function web_slide_uploaded_file(array $files, int $index): ?array
{
    if (!isset($files['error'][$index]['image'])) {
        return null;
    }

    return [
        'name' => $files['name'][$index]['image'] ?? '',
        'type' => $files['type'][$index]['image'] ?? '',
        'tmp_name' => $files['tmp_name'][$index]['image'] ?? '',
        'error' => $files['error'][$index]['image'] ?? UPLOAD_ERR_NO_FILE,
        'size' => $files['size'][$index]['image'] ?? 0,
    ];
}

function web_event_uploaded_file(array $files, int $index): ?array
{
    if (!isset($files['error'][$index]['image'])) {
        return null;
    }

    return [
        'name' => $files['name'][$index]['image'] ?? '',
        'type' => $files['type'][$index]['image'] ?? '',
        'tmp_name' => $files['tmp_name'][$index]['image'] ?? '',
        'error' => $files['error'][$index]['image'] ?? UPLOAD_ERR_NO_FILE,
        'size' => $files['size'][$index]['image'] ?? 0,
    ];
}

function web_news_uploaded_file(array $files, int $index): ?array
{
    if (!isset($files['error'][$index]['image'])) {
        return null;
    }

    return [
        'name' => $files['name'][$index]['image'] ?? '',
        'type' => $files['type'][$index]['image'] ?? '',
        'tmp_name' => $files['tmp_name'][$index]['image'] ?? '',
        'error' => $files['error'][$index]['image'] ?? UPLOAD_ERR_NO_FILE,
        'size' => $files['size'][$index]['image'] ?? 0,
    ];
}

// member_slug_in_use() vive ahora en app/auth.php: el alta (registro.php) tambien
// necesita comprobar que el slug esta libre antes de crear la cuenta.

function user_name_in_use(string $name, string $excludeUserId): bool
{
    $candidate = clean_text($name);
    if ($candidate === '') {
        return false;
    }

    $users = all_users();
    foreach ($users as $existingUser) {
        if (($existingUser['id'] ?? '') === $excludeUserId) {
            continue;
        }
        if (clean_text((string) ($existingUser['name'] ?? '')) === $candidate) {
            return true;
        }
    }

    return false;
}

function member_relative_asset_exists(string $path): bool
{
    $path = clean_text($path);
    if ($path === '') {
        return false;
    }

    $mediaFile = csf_media_file_from_path($path);
    if ($mediaFile !== null) {
        return csf_media_file_exists($mediaFile);
    }

    if (preg_match('#^https?://#i', $path) === 1) {
        return true;
    }

    $normalizedPath = ltrim(str_replace('\\', '/', $path), '/');
    if (str_contains($normalizedPath, '..')) {
        return false;
    }

    return is_file(__DIR__ . '/' . $normalizedPath);
}

function member_visible_asset_path(string $path): string
{
    $path = clean_text($path);
    return member_relative_asset_exists($path) ? $path : '';
}

function member_main_photo_persisted(array $user, string $expectedPath): bool
{
    $expectedPath = clean_text($expectedPath);
    if ($expectedPath === '') {
        return true;
    }

    $pdo = db();
    $userId = (int) ($user['db_id'] ?? 0);
    if (!$pdo || $userId <= 0 || !db_column_exists($pdo, 'miembros', 'foto_principal_path')) {
        return true;
    }

    $columns = 'foto_principal_path';
    if (db_column_exists($pdo, 'miembros', 'perfil_json')) {
        $columns .= ', perfil_json';
    }

    $statement = $pdo->prepare('SELECT ' . $columns . ' FROM miembros WHERE usuario_id = :usuario_id LIMIT 1');
    $statement->execute(['usuario_id' => $userId]);
    $row = $statement->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return false;
    }

    if (clean_text((string) ($row['foto_principal_path'] ?? '')) !== $expectedPath) {
        return false;
    }

    if (!empty($row['perfil_json'])) {
        $decodedProfile = json_decode((string) $row['perfil_json'], true);
        if (is_array($decodedProfile) && clean_text((string) ($decodedProfile['main_photo_path'] ?? '')) !== $expectedPath) {
            return false;
        }
    }

    return true;
}

function cv_profile_curriculum_image_paths(array $profile, array $sectionConfig): array
{
    $paths = [];
    foreach (array_keys($sectionConfig) as $sectionKey) {
        $entries = is_array($profile[$sectionKey] ?? null) ? $profile[$sectionKey] : [];
        foreach ($entries as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $imagePath = member_visible_asset_path((string) ($entry['image_path'] ?? ''));
            if ($imagePath !== '') {
                $paths[] = $imagePath;
            }
        }
    }

    return array_values(array_unique($paths));
}

function cv_curriculum_images_persisted(array $user, array $profile, array $sectionConfig): bool
{
    $expectedPaths = cv_profile_curriculum_image_paths($profile, $sectionConfig);
    if (!$expectedPaths) {
        return true;
    }

    $pdo = db();
    $userId = (int) ($user['db_id'] ?? 0);
    if (!$pdo || $userId <= 0 || !db_column_exists($pdo, 'miembros', 'perfil_json')) {
        return true;
    }

    $statement = $pdo->prepare('SELECT perfil_json FROM miembros WHERE usuario_id = :usuario_id LIMIT 1');
    $statement->execute(['usuario_id' => $userId]);
    $storedProfile = json_decode((string) $statement->fetchColumn(), true);
    if (!is_array($storedProfile)) {
        return false;
    }

    $storedPaths = cv_profile_curriculum_image_paths($storedProfile, $sectionConfig);
    foreach ($expectedPaths as $expectedPath) {
        if (!in_array($expectedPath, $storedPaths, true)) {
            return false;
        }
    }

    return true;
}

function persist_member_profile_snapshot(array $user, array $profile): bool
{
    $pdo = db();
    $userId = (int) ($user['db_id'] ?? 0);
    if (!$pdo || $userId <= 0) {
        return false;
    }

    $assignments = [];
    $params = ['usuario_id' => $userId];
    if (db_column_exists($pdo, 'miembros', 'foto_principal_path')) {
        $assignments[] = 'foto_principal_path = :foto_principal_path';
        $params['foto_principal_path'] = clean_text((string) ($profile['main_photo_path'] ?? ''));
    }
    if (db_column_exists($pdo, 'miembros', 'perfil_json')) {
        $encodedProfile = json_encode($profile, JSON_UNESCAPED_UNICODE);
        if ($encodedProfile !== false) {
            $assignments[] = 'perfil_json = :perfil_json';
            $params['perfil_json'] = $encodedProfile;
        }
    }
    if (db_column_exists($pdo, 'miembros', 'perfil_completo_at')) {
        $assignments[] = 'perfil_completo_at = :perfil_completo_at';
        $params['perfil_completo_at'] = db_nullable_datetime($profile['completed_at'] ?? null);
    }
    if (db_column_exists($pdo, 'miembros', 'updated_at')) {
        $assignments[] = 'updated_at = UTC_TIMESTAMP()';
    }

    if (!$assignments) {
        return false;
    }

    $statement = $pdo->prepare('UPDATE miembros SET ' . implode(', ', $assignments) . ' WHERE usuario_id = :usuario_id');
    return $statement->execute($params);
}

$profileAction = (string) ($_POST['profile_action'] ?? '');
$profileWantsJsonResponse = $_SERVER['REQUEST_METHOD'] === 'POST'
    && $profileAction === 'update_profile_images'
    && (
        str_contains(strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? '')), 'application/json')
        || strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'fetch'
    );
$storedAccountName = clean_text((string) ($user['name'] ?? ''));
$accountNameLocked = $storedAccountName !== '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($profileAction, ['update_profile', 'update_profile_images'], true)) {
    $isSlugSave = $profileAction === 'update_profile' && (string) ($_POST['slug_action'] ?? '') === 'save_public_slug';

    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $profileErrors[] = 'La sesion ha caducado. Vuelve a intentarlo.';
    }

    $photoPath = null;
    $cvHeaderImagePath = null;
    if (!$profileErrors) {
        $photoPath = save_member_photo_upload($_FILES['main_photo'] ?? null, $profileErrors, false);
        if ($photoPath) {
            $memberProfile['main_photo_path'] = $photoPath;
        }

        $cvHeaderImagePath = save_member_cv_image_upload($_FILES['cv_header_image'] ?? null, $profileErrors);
        if ($cvHeaderImagePath) {
            $memberProfile['cv_header_image_path'] = $cvHeaderImagePath;
        }
    }

    if (!$profileErrors && $isSlugSave) {
        if (member_public_name_is_locked($memberProfile)) {
            // Nombre publico reservado en el alta: solo se cambia bajo solicitud.
            $profileErrors[] = 'Tu URL publica esta reservada. Para cambiarla, solicita autorizacion por correo electronico.';
        } else {
            $rawSlug = clean_text((string) ($_POST['slug'] ?? ''));
            $requestedSlug = $rawSlug !== '' ? slugify($rawSlug) : '';
            if ($requestedSlug === '') {
                $profileErrors[] = 'La URL publica no es valida. Usa solo letras, numeros y guiones.';
            } elseif (member_slug_in_use($requestedSlug, (int) ($user['db_id'] ?? 0))) {
                $profileErrors[] = 'La URL publica ya esta en uso. Elige otro slug.';
            } else {
                $memberProfile['slug'] = $requestedSlug;
                // A partir de ahora queda reservada, como en las altas nuevas.
                $memberProfile['slug_locked_at'] = gmdate('c');
                $user['artistic_profile'] = $memberProfile;
                update_user($user);
                $profileMessages[] = 'URL publica guardada y reservada. Para cambiarla mas adelante tendras que solicitarlo.';
            }
        }
    }

    if (!$profileErrors && $profileAction === 'update_profile_images' && !$photoPath && !$cvHeaderImagePath) {
        $profileErrors[] = 'Selecciona una fotografia principal o un fondo de cabecera para guardar.';
    }

    if (!$profileErrors && $profileAction === 'update_profile') {
        $memberProfile = member_profile_from_input($_POST, $memberProfile);
        $currentSlug = clean_text((string) ($memberProfile['slug'] ?? ''));
        if ($currentSlug === '') {
            $profileErrors[] = 'La URL publica no es valida. Usa solo letras, numeros y guiones.';
        } elseif (member_slug_in_use($currentSlug, (int) ($user['db_id'] ?? 0))) {
            $profileErrors[] = 'La URL publica ya esta en uso. Elige otro slug.';
        }

        $submittedPublicFields = is_array($_POST['public_fields'] ?? null) ? $_POST['public_fields'] : [];
        $publicFields = array_values(array_intersect(array_keys($publicFieldOptions), array_map('strval', $submittedPublicFields)));
        $memberProfile['public_fields'] = $publicFields;
        $submittedSortOrders = is_array($_POST['sort_orders'] ?? null) ? $_POST['sort_orders'] : [];
        $memberProfile['sort_orders'] = array_map(
            static fn ($value): string => normalize_cv_sort_order($value),
            array_intersect_key($submittedSortOrders, $publicFieldOptions)
        );
        $submittedSectionSettings = is_array($_POST['section_settings'] ?? null) ? $_POST['section_settings'] : [];
        $memberProfile['section_settings'] = clean_cv_section_settings(
            $submittedSectionSettings,
            is_array($memberProfile['section_settings'] ?? null) ? $memberProfile['section_settings'] : [],
            $cvSectionConfig
        );
        $customSectionTitle = clean_text((string) ($_POST['custom_section_title'] ?? ''));
        if (!empty($customSectionTitle) && strlen($customSectionTitle) >= 2 && strlen($customSectionTitle) <= 100) {
            $memberProfile['custom_section_title'] = $customSectionTitle;
        }
        $entryMediaOptions = ['requires_title_description' => false, 'allows_image' => true];
        foreach ($cvSectionConfig as $sectionKey => $sectionConfig) {
            $memberProfile[$sectionKey] = clean_cv_entries(
                $_POST,
                $sectionKey,
                array_keys($sectionConfig['fields']),
                $entryMediaOptions + ['title' => $sectionConfig['title']],
                is_array($memberProfile[$sectionKey] ?? null) ? $memberProfile[$sectionKey] : [],
                $_FILES,
                $profileErrors
            );
            $memberProfile[$sectionKey] = sort_cv_entries(
                $memberProfile[$sectionKey],
                $memberProfile['sort_orders'][$sectionKey] ?? 'desc'
            );
        }
        $memberProfile['completed_at'] = profile_is_complete($memberProfile) ? ($memberProfile['completed_at'] ?? gmdate('c')) : null;

        if ($accountNameLocked) {
            $user['name'] = $storedAccountName;
        } else {
            $accountName = clean_text((string) ($_POST['user_name'] ?? $user['name'] ?? ''));
            if (strlen($accountName) < 2 || strlen($accountName) > 160) {
                $profileErrors[] = 'El nombre de usuario debe tener entre 2 y 160 caracteres.';
            } elseif (user_name_in_use($accountName, (string) ($user['id'] ?? ''))) {
                $profileErrors[] = 'Ya existe otro usuario con ese nombre. Usa una variacion.';
            } else {
                $user['name'] = $accountName;
            }
        }
    }

    if (!$profileErrors && !$isSlugSave) {
        $user['artistic_profile'] = $memberProfile;
        update_user($user);
        persist_member_profile_snapshot($user, $memberProfile);

        if ($profileAction === 'update_profile_images' && $photoPath && !member_main_photo_persisted($user, $photoPath)) {
            $profileErrors[] = 'La fotografia se ha subido al servidor, pero no se ha podido confirmar su ruta en la base de datos.';
        }

        if ($profileAction === 'update_profile' && !cv_curriculum_images_persisted($user, $memberProfile, $cvSectionConfig)) {
            $profileErrors[] = 'Una o varias imagenes del curriculum se han subido, pero no se han podido confirmar en la base de datos.';
        }

        if (!$profileErrors) {
            $profileMessages[] = $profileAction === 'update_profile_images'
                ? 'Imagenes actualizadas y guardadas correctamente.'
                : (profile_is_complete($memberProfile)
                    ? 'Perfil artistico actualizado.'
                    : 'Perfil guardado. Sigue pendiente completar nombre artistico, ciudad, provincia, fotografia principal y al menos una formacion, experiencia profesional o actuacion.');
        }
    }
}

if ($profileWantsJsonResponse) {
    http_response_code($profileErrors ? 422 : 200);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode([
        'ok' => !$profileErrors,
        'messages' => $profileMessages,
        'errors' => $profileErrors,
        'main_photo_path' => member_visible_asset_path((string) ($memberProfile['main_photo_path'] ?? '')),
        'cv_header_image_path' => member_visible_asset_path((string) ($memberProfile['cv_header_image_path'] ?? '')),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $profileAction === 'upload_cv_entry_image') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $profileErrors[] = 'La sesion ha caducado. Vuelve a intentarlo.';
    }

    $uploadedEntryImagePath = null;
    if (!$profileErrors) {
        $uploadedEntryImagePath = save_member_cv_image_upload($_FILES['cv_entry_image'] ?? null, $profileErrors);
        if (!$uploadedEntryImagePath) {
            $profileErrors[] = 'Selecciona una imagen valida para esta entrada.';
        }
    }

    http_response_code($profileErrors ? 422 : 200);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode([
        'ok' => !$profileErrors,
        'errors' => $profileErrors,
        'image_path' => member_visible_asset_path((string) $uploadedEntryImagePath),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($hasWebPage && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['profile_action'] ?? '') === 'update_web_page') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $profileErrors[] = 'La sesion ha caducado. Vuelve a intentarlo.';
    }

    if (!$profileErrors) {
        $webPage = default_member_web_page(is_array($memberProfile['web_page'] ?? null) ? $memberProfile['web_page'] : []);
        $webPage['header_title'] = clean_text((string) ($_POST['web_header_title'] ?? ''));
        $webPage['header_subtitle'] = clean_text((string) ($_POST['web_header_subtitle'] ?? ''));

        $webHeaderImagePath = save_member_cv_image_upload($_FILES['web_header_image'] ?? null, $profileErrors);
        if ($webHeaderImagePath) {
            $webPage['header_image_path'] = $webHeaderImagePath;
        }

        $submittedSlides = is_array($_POST['web_slides'] ?? null) ? $_POST['web_slides'] : [];
        $existingSlides = is_array($webPage['hero_slides'] ?? null) ? $webPage['hero_slides'] : [];
        $slideUploads = is_array($_FILES['web_slides'] ?? null) ? $_FILES['web_slides'] : [];
        $heroSlides = [];
        for ($slideIndex = 0; $slideIndex < 3; $slideIndex++) {
            $slideInput = is_array($submittedSlides[$slideIndex] ?? null) ? $submittedSlides[$slideIndex] : [];
            $existingSlide = is_array($existingSlides[$slideIndex] ?? null) ? $existingSlides[$slideIndex] : [];
            $slideImagePath = member_visible_asset_path((string) ($slideInput['image_path'] ?? ($existingSlide['image_path'] ?? '')));
            $uploadedSlideImage = $slideUploads ? save_member_cv_image_upload(web_slide_uploaded_file($slideUploads, $slideIndex), $profileErrors) : null;
            if ($uploadedSlideImage) {
                $slideImagePath = $uploadedSlideImage;
            }

            $heroSlides[] = [
                'image_path' => $slideImagePath,
                'title' => clean_text((string) ($slideInput['title'] ?? '')),
                'description' => clean_text((string) ($slideInput['description'] ?? '')),
                'cta_label' => clean_text((string) ($slideInput['cta_label'] ?? '')),
                'cta_url' => trim((string) ($slideInput['cta_url'] ?? '')),
            ];
        }
        $webPage['hero_slides'] = $heroSlides;

        $removeGallery = array_map('intval', is_array($_POST['remove_web_gallery'] ?? null) ? $_POST['remove_web_gallery'] : []);
        $gallery = array_values(array_filter(
            $webPage['gallery'],
            static fn ($path, $index): bool => !in_array((int) $index, $removeGallery, true),
            ARRAY_FILTER_USE_BOTH
        ));

        $galleryUploads = is_array($_FILES['web_gallery_images'] ?? null) ? $_FILES['web_gallery_images'] : null;
        if ($galleryUploads) {
            $uploadCount = is_array($galleryUploads['error'] ?? null) ? count($galleryUploads['error']) : 0;
            for ($index = 0; $index < $uploadCount; $index++) {
                if (count($gallery) >= 9) {
                    $profileErrors[] = 'La galeria de la pagina web permite un maximo de 9 imagenes.';
                    break;
                }

                $upload = web_gallery_uploaded_file($galleryUploads, $index);
                if (!$upload || (int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                    continue;
                }

                $uploadedPath = save_member_cv_image_upload($upload, $profileErrors);
                if ($uploadedPath) {
                    $gallery[] = $uploadedPath;
                }
            }
        }

        $webPage['gallery'] = array_slice($gallery, 0, 9);
        $webPage['contact_fields'] = array_values(array_intersect(
            ['email', 'phone', 'website', 'instagram'],
            array_map('strval', is_array($_POST['web_contact_fields'] ?? null) ? $_POST['web_contact_fields'] : [])
        ));

        $submittedVideos = is_array($_POST['web_videos'] ?? null) ? $_POST['web_videos'] : [];
        $removeVideos = array_map('intval', is_array($_POST['remove_web_videos'] ?? null) ? $_POST['remove_web_videos'] : []);
        $maxVideos = $isVipMember ? 12 : 3;
        $videos = [];
        foreach (array_slice($submittedVideos, 0, $maxVideos) as $videoIdx => $videoInput) {
            if (!is_array($videoInput) || in_array((int) $videoIdx, $removeVideos, true)) {
                continue;
            }

            $videoTitle = clean_text((string) ($videoInput['title'] ?? ''));
            $videoDescription = clean_text((string) ($videoInput['description'] ?? ''));
            $videoUrl = trim((string) ($videoInput['url'] ?? ''));
            if ($videoTitle === '' && $videoDescription === '' && $videoUrl === '') {
                continue;
            }
            if ($videoUrl === '') {
                $profileErrors[] = 'Cada video con contenido necesita una URL.';
                continue;
            }

            $videos[] = [
                'title' => $videoTitle,
                'description' => $videoDescription,
                'url' => $videoUrl,
            ];
        }
        $webPage['videos'] = $videos;

        // Procesar eventos
        $submittedEvents = is_array($_POST['web_events'] ?? null) ? $_POST['web_events'] : [];
        $existingEvents = is_array($webPage['events'] ?? null) ? $webPage['events'] : [];
        $eventUploads = is_array($_FILES['web_events'] ?? null) ? $_FILES['web_events'] : [];
        $maxEvents = $isVipMember ? 20 : 3;
        $events = [];
        foreach (array_slice($submittedEvents, 0, $maxEvents) as $evIdx => $evInput) {
            if (!is_array($evInput)) {
                continue;
            }

            $existingEvent = is_array($existingEvents[$evIdx] ?? null) ? $existingEvents[$evIdx] : [];
            $evImagePath = member_visible_asset_path((string) ($evInput['image_path'] ?? ($existingEvent['image_path'] ?? '')));
            $uploadedEvImage = $eventUploads ? save_member_cv_image_upload(web_event_uploaded_file($eventUploads, (int) $evIdx), $profileErrors) : null;
            if ($uploadedEvImage) {
                $evImagePath = $uploadedEvImage;
            }

            $evTitle = clean_text((string) ($evInput['title'] ?? ''));
            $evDate = clean_text((string) ($evInput['date'] ?? ''));
            if ($evImagePath === '' && $evTitle === '' && $evDate === '') {
                continue;
            }

            $events[] = [
                'title' => $evTitle,
                'description' => clean_text((string) ($evInput['description'] ?? '')),
                'image_path' => $evImagePath,
                'date' => $evDate,
                'time' => clean_text((string) ($evInput['time'] ?? '')),
                'url' => trim((string) ($evInput['url'] ?? '')),
            ];
        }
        $webPage['events'] = $events;

        $submittedNews = is_array($_POST['web_news'] ?? null) ? $_POST['web_news'] : [];
        $existingNews = is_array($webPage['news'] ?? null) ? $webPage['news'] : [];
        $newsUploads = is_array($_FILES['web_news'] ?? null) ? $_FILES['web_news'] : [];
        $removeNews = array_map('intval', is_array($_POST['remove_web_news'] ?? null) ? $_POST['remove_web_news'] : []);
        $maxNews = $isVipMember ? 20 : 5;
        $news = [];
        foreach (array_slice($submittedNews, 0, $maxNews) as $newsIdx => $newsInput) {
            if (!is_array($newsInput) || in_array((int) $newsIdx, $removeNews, true)) {
                continue;
            }

            $existingNewsItem = is_array($existingNews[$newsIdx] ?? null) ? $existingNews[$newsIdx] : [];
            $newsImagePath = member_visible_asset_path((string) ($newsInput['image_path'] ?? ($existingNewsItem['image_path'] ?? '')));
            $uploadedNewsImage = $newsUploads ? save_member_cv_image_upload(web_news_uploaded_file($newsUploads, (int) $newsIdx), $profileErrors) : null;
            if ($uploadedNewsImage) {
                $newsImagePath = $uploadedNewsImage;
            }

            $newsTitle = clean_text((string) ($newsInput['title'] ?? ''));
            $newsSummary = clean_text((string) ($newsInput['summary'] ?? ''));
            $newsDate = clean_text((string) ($newsInput['date'] ?? ''));
            $newsUrl = trim((string) ($newsInput['url'] ?? ''));
            if ($newsImagePath === '' && $newsTitle === '' && $newsSummary === '' && $newsDate === '' && $newsUrl === '') {
                continue;
            }

            $news[] = [
                'title' => $newsTitle,
                'summary' => $newsSummary,
                'image_path' => $newsImagePath,
                'date' => $newsDate,
                'url' => $newsUrl,
            ];
        }
        $webPage['news'] = $news;

        // Procesar redes sociales
        $allowedNetworks = ['instagram', 'facebook', 'youtube', 'tiktok', 'spotify', 'twitter'];
        $submittedSocial = is_array($_POST['web_social_links'] ?? null) ? $_POST['web_social_links'] : [];
        $socialLinks = [];
        foreach ($allowedNetworks as $network) {
            $url = trim((string) ($submittedSocial[$network] ?? ''));
            if ($url !== '') {
                $socialLinks[$network] = $url;
            }
        }
        $webPage['social_links'] = $socialLinks;

        if (!$profileErrors) {
            $memberProfile['web_page'] = default_member_web_page($webPage);
            $user['artistic_profile'] = $memberProfile;
            update_user($user);
            persist_member_profile_snapshot($user, $memberProfile);
            $profileMessages[] = 'Pagina web actualizada.';
        }
    }
}

$userName = $user['name'] ?? 'Miembro';
$accountNameLocked = clean_text((string) ($user['name'] ?? '')) !== '';

$memberTypeLabel = member_type_options()[$memberProfile['member_type']] ?? 'Artista';
$profileStatus = profile_is_complete($memberProfile) ? 'Perfil completo' : 'Perfil pendiente';
$profileStatusClass = profile_is_complete($memberProfile) ? 'status-pill-active' : 'status-pill-pending';
$displayName = $memberProfile['public_name'] !== '' ? $memberProfile['public_name'] : $userName;
$publicSlug = clean_text((string) ($memberProfile['slug'] ?? slugify($displayName)));
$publicSlug = $publicSlug !== '' ? $publicSlug : slugify($displayName);
$publicNameLocked = member_public_name_is_locked($memberProfile);
$memberTypePrefix = member_type_url_prefix((string) ($memberProfile['member_type'] ?? 'artista'));
$publicProfileUrl = member_public_url((string) ($memberProfile['member_type'] ?? 'artista'), $publicSlug);
$webPage = default_member_web_page(is_array($memberProfile['web_page'] ?? null) ? $memberProfile['web_page'] : []);
$webSlides = is_array($webPage['hero_slides'] ?? null) ? array_slice($webPage['hero_slides'], 0, 3) : [];
$webGallery = array_slice($webPage['gallery'], 0, 9);
$webContactFields = is_array($webPage['contact_fields'] ?? null) ? $webPage['contact_fields'] : [];
$webVideos = is_array($webPage['videos'] ?? null) ? $webPage['videos'] : [];
$webEvents = is_array($webPage['events'] ?? null) ? $webPage['events'] : [];
$webNews = is_array($webPage['news'] ?? null) ? $webPage['news'] : [];
$webSocialLinks = is_array($webPage['social_links'] ?? null) ? $webPage['social_links'] : [];
$maxWebVideos = $isVipMember ? 12 : 3;
$maxWebEvents = $isVipMember ? 20 : 3;
$maxWebNews = $isVipMember ? 20 : 5;
$socialNetworkLabels = ['instagram' => 'Instagram', 'facebook' => 'Facebook', 'youtube' => 'YouTube', 'tiktok' => 'TikTok', 'spotify' => 'Spotify', 'twitter' => 'Twitter / X'];
$socialNetworkIcons = [
    'instagram' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><circle cx="12" cy="12" r="4.5"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/></svg>',
    'facebook'  => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>',
    'youtube'   => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M22.54 6.42A2.78 2.78 0 0 0 20.6 4.46C18.88 4 12 4 12 4s-6.88 0-8.6.46A2.78 2.78 0 0 0 1.46 6.42 29 29 0 0 0 1 12a29 29 0 0 0 .46 5.58A2.78 2.78 0 0 0 3.4 19.54C5.12 20 12 20 12 20s6.88 0 8.6-.46a2.78 2.78 0 0 0 1.94-1.96A29 29 0 0 0 23 12a29 29 0 0 0-.46-5.58z"/><polygon fill="#fff" points="9.75 15.02 15.5 12 9.75 8.98 9.75 15.02"/></svg>',
    'tiktok'    => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-2.88 2.5 2.89 2.89 0 0 1-2.89-2.89 2.89 2.89 0 0 1 2.89-2.89c.28 0 .54.04.79.1V9.01a6.33 6.33 0 0 0-.79-.05 6.34 6.34 0 0 0-6.34 6.34 6.34 6.34 0 0 0 6.34 6.34 6.34 6.34 0 0 0 6.33-6.34V8.69a8.15 8.15 0 0 0 4.77 1.52V6.73a4.86 4.86 0 0 1-1-.04z"/></svg>',
    'spotify'   => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path fill="#fff" d="M16.5 16.5a.75.75 0 0 1-.41-.12 8.27 8.27 0 0 0-8.18 0 .75.75 0 0 1-.82-1.26 9.77 9.77 0 0 1 9.82 0 .75.75 0 0 1-.41 1.38zm1.25-2.75a.75.75 0 0 1-.41-.12 10.52 10.52 0 0 0-10.68 0 .75.75 0 0 1-.82-1.26 12 12 0 0 1 12.32 0 .75.75 0 0 1-.41 1.38zm1.25-2.75a.75.75 0 0 1-.41-.12 12.77 12.77 0 0 0-13.18 0 .75.75 0 1 1-.82-1.26 14.27 14.27 0 0 1 14.82 0 .75.75 0 0 1-.41 1.38z"/></svg>',
    'twitter'   => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.744l7.736-8.849L2.25 2.25h6.883l4.254 5.621zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>',
];
$webHeaderImage = clean_text((string) ($webPage['header_image_path'] ?: ($memberProfile['cv_header_image_path'] ?? '')));
$mainPhotoPath = clean_text((string) ($memberProfile['main_photo_path'] ?? ''));
$mainPhotoVisiblePath = member_visible_asset_path($mainPhotoPath);
$cvHeaderBackground = clean_text((string) ($memberProfile['cv_header_image_path'] ?? ''));
$cvHeaderVisibleBackground = member_visible_asset_path($cvHeaderBackground);
if ($mainPhotoPath !== '' && $mainPhotoVisiblePath === '') {
    $memberProfile['main_photo_path'] = '';
    $user['artistic_profile'] = $memberProfile;
    update_user($user);
    persist_member_profile_snapshot($user, $memberProfile);
    $mainPhotoPath = '';
    $profileMessages[] = 'La ruta antigua de la fotografia no existia en el servidor y se ha limpiado. Sube la fotografia de nuevo para dejarla guardada correctamente.';
}
$cardHeadline = clean_text((string) ($memberProfile['artistic_headline'] ?? ''));
$profileRequiredFields = [
    $memberProfile['public_name'] ?? '',
    $memberProfile['city'] ?? '',
    $memberProfile['province'] ?? '',
    $mainPhotoVisiblePath,
    (!empty($memberProfile['education']) || !empty($memberProfile['experience']) || !empty($memberProfile['performances'])) ? 'curriculum' : '',
];
$completedProfileFields = count(array_filter($profileRequiredFields, static fn ($value): bool => clean_text((string) $value) !== ''));
$profileCompletion = (int) round(($completedProfileFields / count($profileRequiredFields)) * 100);
$cvHeaderStyle = $cvHeaderVisibleBackground !== ''
    ? "background-image: linear-gradient(135deg, rgba(17, 17, 20, 0.82), rgba(32, 56, 71, 0.74)), url('" . $cvHeaderVisibleBackground . "');"
    : '';

// Cada seccion del curriculum es una pantalla propia del panel, con su ancla y
// su tarjeta en la portada.
$cvSectionAnchors = [
    'education' => 'formacion',
    'experience' => 'experiencia',
    'custom_section' => 'seccion-personalizada',
];
$cvSectionNotes = [
    'education' => 'Titulos, cursos y maestros con los que te has formado.',
    'experience' => 'Companias, tablaos, giras y trabajos que has firmado.',
    'custom_section' => 'Un apartado libre para lo que no encaja en los anteriores.',
];
$cvSectionImages = [
    'education' => 'assets/images/community/academia-flamenca.webp',
    'experience' => 'assets/images/community/evento-flamenco.webp',
    'custom_section' => 'assets/images/community/pena-flamenca.webp',
];
$cvSectionEntries = [];
$cvSectionMetrics = [];
foreach ($cvSectionConfig as $sectionKey => $sectionConfig) {
    $cvSectionEntries[$sectionKey] = is_array($memberProfile[$sectionKey] ?? null)
        ? array_values($memberProfile[$sectionKey])
        : [];
    $cvSectionMetrics[$sectionKey] = cv_section_metrics($cvSectionEntries[$sectionKey]);
}
$totalCvEntries = array_sum(array_column($cvSectionMetrics, 'total'));

$panelNavCards = [
    [
        'target' => 'perfil',
        'title' => 'Mi perfil',
        'note' => 'Identidad publica, ubicacion, contacto e imagenes de tu ficha.',
        'image' => $mainPhotoVisiblePath !== '' ? $mainPhotoVisiblePath : 'assets/images/community/artista-bailaora.webp',
        'metric' => $profileCompletion . '% completo',
    ],
];
foreach ($cvSectionConfig as $sectionKey => $sectionConfig) {
    $panelNavCards[] = [
        'target' => $cvSectionAnchors[$sectionKey],
        'title' => (string) $sectionConfig['title'],
        'note' => $cvSectionNotes[$sectionKey],
        'image' => $cvSectionImages[$sectionKey],
        'metric' => $cvSectionMetrics[$sectionKey]['total'] === 1 ? '1 entrada' : $cvSectionMetrics[$sectionKey]['total'] . ' entradas',
        'metric_section' => $sectionKey,
    ];
}
if ($hasWebPage) {
    $webBlocksWithContent = count(array_filter([
        $webSlides,
        $webGallery,
        $webVideos,
        $webEvents,
        $webNews,
    ]));
    $panelNavCards[] = [
        'target' => 'pagina-web',
        'title' => 'Pagina web',
        'note' => 'Los bloques de tu web publica de una sola pagina.',
        'image' => 'assets/images/slider/presencia-web-flamenca.svg',
        'metric' => $webBlocksWithContent === 1 ? '1 bloque con contenido' : $webBlocksWithContent . ' bloques con contenido',
    ];
}
$panelNavCards[] = [
    'target' => 'tarjeta-miembro',
    'title' => 'Tarjeta de miembro',
    'note' => 'Tu carnet digital, su diseno y el QR para compartirlo.',
    'image' => 'assets/images/member-cards/tarjeta-bailaora.png',
    'metric' => $memberStatus,
];
$panelNavCards[] = [
    'target' => 'banners',
    'title' => 'Banners',
    'note' => 'Espacios publicitarios contratables en tu provincia.',
    'image' => 'assets/images/flamenco-header-art.png',
];
$panelNavCards[] = [
    'target' => 'seguridad',
    'title' => 'Seguridad',
    'note' => 'Contrasena de acceso y cierre de sesion.',
    'image' => 'assets/images/auth/acceso-flamenco.png',
];
if ($academiaPanelLink) {
    $panelNavCards[] = [
        'href' => 'panel-academia.php',
        'title' => 'Mi academia',
        'note' => 'Alumnos, profesores, cursos, grupos y matriculas.',
        'image' => 'assets/images/slider/comunidad-flamenca-esquema.svg',
    ];
}
if ($alumnoPanelLink) {
    $panelNavCards[] = [
        'href' => 'panel-alumno.php',
        'title' => 'Mis clases',
        'note' => 'Los cursos en los que estas matriculado.',
        'image' => 'assets/images/auth/registro-flamenco.png',
    ];
}
?>
<!DOCTYPE html>
<html lang="es">
<?php page_head('Panel de miembro | Con Sabor Flamenco', 'Área privada de miembros de Con Sabor Flamenco.', false); ?>
<body>
    <?php page_header(); ?>
    <main>
        <section class="member-dashboard-hero member-dashboard-hero-page" aria-label="Tu espacio en Con Sabor Flamenco" data-ad-category="GENERAL">
            <div class="member-dashboard-identity">
                <button type="button" class="member-dashboard-photo-edit" data-main-photo-trigger aria-label="Editar fotografia principal">
                    <?php if ($mainPhotoVisiblePath !== ''): ?>
                        <img src="<?= e($mainPhotoVisiblePath) ?>" alt="Fotografia principal de <?= e($displayName) ?>" loading="lazy" data-main-photo-preview>
                    <?php else: ?>
                        <img alt="Fotografia principal de <?= e($displayName) ?>" loading="lazy" data-main-photo-preview hidden>
                        <div class="member-dashboard-photo-placeholder" data-main-photo-placeholder><?= e(strtoupper(substr($displayName, 0, 1))) ?></div>
                    <?php endif; ?>
                    <span>Editar imagen</span>
                </button>
                <div>
                    <span><?= e($memberTypeLabel) ?></span>
                    <h1><?= e($displayName) ?></h1>
                    <p><?= e($memberProfile['city']) ?><?= $memberProfile['city'] && $memberProfile['province'] ? ', ' : '' ?><?= e($memberProfile['province']) ?></p>
                </div>
            </div>
            <div class="member-dashboard-actions">
                <a class="member-card-qr-link member-dashboard-qr-link" href="<?= e($memberCardPublicUrl) ?>" target="_blank" rel="noopener" data-member-card-link data-card-url-base="<?= e($memberCardPublicUrlBase) ?>">
                    <img src="<?= e($memberCardQrUrl) ?>" alt="Codigo QR para ver la tarjeta de miembro" loading="lazy" data-member-card-qr data-qr-base="<?= e($memberCardQrBase) ?>">
                    <span>
                        <strong>QR tarjeta</strong>
                        <small>Ver / imprimir</small>
                    </span>
                </a>
            </div>
        </section>

        <section class="member-panel">
            <aside class="member-sidebar" aria-label="Menú del panel de miembro">
                <div class="member-sidebar-card">
                    <span class="profile-avatar profile-avatar-large">
                        <?php if ($mainPhotoVisiblePath !== ''): ?>
                            <img src="<?= e($mainPhotoVisiblePath) ?>" alt="Fotografia principal de <?= e($displayName) ?>" loading="lazy" data-main-photo-preview>
                        <?php else: ?>
                            <img alt="Fotografia principal de <?= e($displayName) ?>" loading="lazy" data-main-photo-preview hidden>
                            <span class="profile-avatar-initial" data-main-photo-placeholder><?= e(strtoupper(substr($displayName, 0, 1))) ?></span>
                        <?php endif; ?>
                    </span>
                    <strong><?= e($displayName) ?></strong>
                    <span><?= e($memberStatus) ?> · Nº <?= e($memberNumber) ?></span>
                </div>
                <div class="member-sidebar-progress" aria-label="Estado del perfil">
                    <div>
                        <span>Perfil completo</span>
                        <strong><?= e((string) $profileCompletion) ?>%</strong>
                    </div>
                    <meter min="0" max="100" value="<?= e((string) $profileCompletion) ?>"><?= e((string) $profileCompletion) ?>%</meter>
                </div>
                <button class="member-sidebar-print" type="button" onclick="window.print()">Imprimir curriculum PDF</button>
                <nav class="member-sidebar-nav">
                    <a class="active" href="#inicio" data-panel-link="inicio">Inicio</a>
                    <a href="#perfil" data-panel-link="perfil">Mi perfil</a>
                    <?php foreach ($cvSectionConfig as $sectionKey => $sectionConfig): ?>
                        <a href="#<?= e($cvSectionAnchors[$sectionKey]) ?>" data-panel-link="<?= e($cvSectionAnchors[$sectionKey]) ?>"><?= e((string) $sectionConfig['title']) ?></a>
                    <?php endforeach; ?>
                    <?php if ($hasWebPage): ?><a href="#pagina-web" data-panel-link="pagina-web">Pagina web</a><?php endif; ?>
                    <a href="#tarjeta-miembro" data-panel-link="tarjeta-miembro">Tarjeta de miembro</a>
                    <a href="#banners" data-panel-link="banners">Banners</a>
                    <a href="#seguridad" data-panel-link="seguridad">Seguridad</a>
                    <?php if ($academiaPanelLink): ?><a href="panel-academia.php">Mi academia</a><?php endif; ?>
                    <?php if ($alumnoPanelLink): ?><a href="panel-alumno.php">Mis clases</a><?php endif; ?>
                </nav>
            </aside>

            <div class="member-panel-content">
                <p class="member-panel-back" data-panel-back hidden>
                    <a href="#inicio" data-panel-link="inicio">&larr; Volver al panel</a>
                </p>
                <?php if ($profileErrors || $profileMessages): ?>
                    <div class="member-panel-alerts">
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
                    </div>
                <?php endif; ?>

                <section id="inicio" class="content-section member-panel-section active">
                    <div class="member-panel-heading">
                        <div class="member-panel-heading-main">
                            <div>
                                <p class="section-kicker">Tu panel</p>
                                <h2>¿Que quieres hacer hoy?</h2>
                                <p>Cada tarjeta abre una pantalla del panel. Puedes volver aqui en cualquier momento.</p>
                            </div>
                        </div>
                    </div>
                    <div class="member-nav-grid">
                        <?php foreach ($panelNavCards as $navCard): ?>
                            <?php $navIsExternal = isset($navCard['href']); ?>
                            <a class="member-nav-card"
                               href="<?= e($navIsExternal ? $navCard['href'] : '#' . $navCard['target']) ?>"
                               <?= $navIsExternal ? '' : 'data-panel-link="' . e($navCard['target']) . '"' ?>>
                                <span class="member-nav-card-media">
                                    <img src="<?= e($navCard['image']) ?>" alt="" loading="lazy">
                                </span>
                                <span class="member-nav-card-body">
                                    <strong><?= e($navCard['title']) ?></strong>
                                    <span class="member-nav-card-note"><?= e($navCard['note']) ?></span>
                                    <?php if (isset($navCard['metric'])): ?>
                                        <span class="member-nav-card-metric"<?= isset($navCard['metric_section']) ? ' data-nav-metric="' . e($navCard['metric_section']) . '"' : '' ?>><?= e($navCard['metric']) ?></span>
                                    <?php endif; ?>
                                </span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>

                <div class="member-profile-editor">
                    <form class="member-profile-form member-profile-cards cv-editor" id="member-profile-form" action="panel-usuario.php#perfil" method="post" enctype="multipart/form-data" data-profile-form hidden>
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="profile_action" value="update_profile">

                        <section id="perfil" class="content-section member-panel-section">
                            <div class="member-panel-heading">
                                <div class="member-panel-heading-main">
                                    <div>
                                        <p class="section-kicker">Mi perfil</p>
                                        <h2>Datos de tu espacio</h2>
                                        <p>Identidad publica, ubicacion, contacto e imagenes de tu ficha.</p>
                                    </div>
                                </div>
                                <div class="member-kpi-grid">
                                    <article class="member-kpi">
                                        <span>Perfil completo</span>
                                        <strong><?= e((string) $profileCompletion) ?>%</strong>
                                    </article>
                                    <article class="member-kpi">
                                        <span>Fotografia principal</span>
                                        <strong><?= $mainPhotoVisiblePath !== '' ? 'Subida' : 'Pendiente' ?></strong>
                                    </article>
                                    <article class="member-kpi">
                                        <span>Entradas de curriculum</span>
                                        <strong><?= e((string) $totalCvEntries) ?></strong>
                                    </article>
                                    <article class="member-kpi">
                                        <span>Tipo de membresia</span>
                                        <strong><?= e($memberStatus) ?></strong>
                                    </article>
                                </div>
                            </div>
                            <div class="member-summary-grid">
                                <article class="member-summary-card">
                                    <span>Nombre artistico</span>
                                    <strong><?= e($displayName) ?></strong>
                                </article>
                                <article class="member-summary-card">
                                    <span>Email</span>
                                    <strong><?= e($user['email'] ?? '') ?></strong>
                                </article>
                                <article class="member-summary-card">
                                    <span>URL publica</span>
                                    <strong><?= e($memberTypePrefix) ?>/<?= e($publicSlug) ?></strong>
                                </article>
                            </div>
                            <fieldset class="cv-fieldset profile-core-fieldset">
	                                <legend>
	                                    <span>Perfil publico</span>
	                                    <strong>Identidad artistica</strong>
	                                    <em>Define como se presentara tu espacio en la web, la tarjeta y el curriculum.</em>
	                                </legend>
	                                <div class="profile-identity-layout">
	                                    <div class="profile-identity-fields">
	                                <div class="form-grid-two">
                                    <label for="user_name">Nombre de usuario (cuenta)
                                        <input id="user_name" name="user_name" type="text" value="<?= e((string) ($user['name'] ?? '')) ?>" maxlength="160" <?= $accountNameLocked ? 'readonly aria-readonly="true" data-account-name-locked="1"' : 'required' ?>>
                                        <span class="field-help"><?= $accountNameLocked ? 'Nombre reservado. Para cambiarlo, solicita autorizacion por correo electronico.' : 'Se comprobara que este libre al guardar el perfil.' ?></span>
                                    </label>
                                    <label for="user_email">Email de acceso
                                        <input id="user_email" type="email" value="<?= e((string) ($user['email'] ?? '')) ?>" readonly disabled aria-readonly="true">
                                    </label>
                                </div>
                                <p class="field-help">El email de acceso no se puede cambiar desde este panel.</p>
                                <label for="artistic_headline">Especialidad o titular artistico
                                    <input id="artistic_headline" name="artistic_headline" type="text" value="<?= e($memberProfile['artistic_headline']) ?>" placeholder="Ej. Bailaor flamenco, cantaora, guitarrista, profesora de baile">
                                </label>
                                <div class="form-grid-two">
                                    <label for="member_type">Tipo de espacio
                                        <input id="member_type" type="text" value="<?= e($memberTypeLabel) ?>" readonly aria-readonly="true">
                                        <span class="field-help">Se elige al crear la cuenta y no se puede cambiar desde aquí: define tu URL pública y el directorio en el que apareces. Para cambiarlo, solicita autorizacion por correo electronico.</span>
                                    </label>
                                    <label for="public_name">Nombre publico
                                        <input id="public_name" name="public_name" type="text" value="<?= e($displayName) ?>" <?= $publicNameLocked ? 'readonly aria-readonly="true"' : 'required' ?>>
                                        <?php if ($publicNameLocked): ?>
                                            <span class="field-help">Nombre reservado al crear la cuenta. Para cambiarlo, solicita autorizacion por correo electronico.</span>
                                        <?php endif; ?>
                                    </label>
                                </div>
                                <div class="public-url-control">
                                    <label for="slug">URL pública (slug)
	                                        <input id="slug" name="slug" type="text" value="<?= e($publicSlug) ?>" placeholder="nombre-artista" pattern="[a-z0-9-]+" autocomplete="off" spellcheck="false" data-slug-input data-public-profile-base="<?= e(app_url('')) ?>" data-public-profile-prefix="<?= e($memberTypePrefix) ?>" <?= $publicNameLocked ? 'readonly aria-readonly="true"' : 'required' ?>>
                                    </label>
	                                    <?php if (!$publicNameLocked): ?>
	                                    <button class="button button-secondary public-url-save" type="submit" name="slug_action" value="save_public_slug" formnovalidate>Guardar URL</button>
	                                    <?php endif; ?>
		                                    <a class="public-url-cta" href="<?= e($publicProfileUrl) ?>" target="_blank" rel="noopener" data-public-url-cta>
		                                        <span>Ver URL publica</span>
		                                        <strong data-public-url-text><?= e($publicProfileUrl) ?></strong>
		                                    </a>
	                                </div>
	                                <?php if ($publicNameLocked): ?>
	                                    <p class="field-help">Tu URL publica quedo reservada al crear la cuenta, con el tipo de espacio que elegiste. Para cambiar cualquiera de los dos, solicita autorizacion por correo electronico.</p>
	                                <?php endif; ?>
	                                    </div>
	                                </div>
	                            </fieldset>

                            <fieldset class="cv-fieldset profile-data-fieldset">
                                <legend>
                                    <span>Datos visibles</span>
                                    <strong>Perfil e imagen</strong>
                                    <em>Ubicacion, contacto y recursos visuales para mantener tu presencia publica cuidada.</em>
                                </legend>
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
                                    <input id="main_photo" name="main_photo" type="file" accept="image/jpeg,image/png,image/webp" data-main-photo-input hidden>
                                    <p class="field-help">Haz clic sobre la imagen de la cabecera para cambiarla.</p>
                                    <p class="field-help">Al seleccionar una imagen, se guarda automaticamente.</p>
                                </div>
                                <p class="field-help">Cada espacio debe tener al menos una fotografia principal. JPG, PNG o WebP, maximo 5 MB.</p>
                                <label class="cv-header-background-field" for="cv_header_image">Fondo de cabecera del curriculum PDF
                                    <span class="cv-header-background-preview" <?= $cvHeaderVisibleBackground !== '' ? 'style="background-image: linear-gradient(135deg, rgba(17, 17, 20, 0.72), rgba(32, 56, 71, 0.68)), url(' . e($cvHeaderVisibleBackground) . ');"' : '' ?>>
                                        <strong><?= $cvHeaderBackground !== '' ? 'Fondo actual' : 'Sin fondo personalizado' ?></strong>
                                        <em>Cambiar fondo</em>
                                    </span>
                                    <input id="cv_header_image" name="cv_header_image" type="file" accept="image/jpeg,image/png,image/webp" hidden>
                                </label>
                                <p class="field-help">El fondo de cabecera tambien se guarda automaticamente al seleccionarlo.</p>
                                <label class="visibility-toggle compact-toggle">
                                    <input type="hidden" name="print_professional_data" value="0">
                                    <input type="checkbox" name="print_professional_data" value="1" <?= !empty($memberProfile['print_professional_data']) ? 'checked' : '' ?>>
                                    <span>Imprimir estos datos profesionales en PDF</span>
                                </label>
                            </fieldset>
                        </section>

                        <?php foreach ($cvSectionConfig as $sectionKey => $sectionConfig): ?>
                            <?php
                            $sectionSettings = is_array($memberProfile['section_settings'][$sectionKey] ?? null) ? $memberProfile['section_settings'][$sectionKey] : [];
                            $sectionActive = (bool) ($sectionSettings['active'] ?? true);
                            $sectionDisplayOrder = (int) ($sectionSettings['order'] ?? ($sectionConfig['default_order'] ?? 1));
                            $isCustomSection = $sectionKey === 'custom_section';
                            $sectionTitle = (string) $sectionConfig['title'];
                            $sectionEntries = $cvSectionEntries[$sectionKey];
                            $sectionMetrics = $cvSectionMetrics[$sectionKey];
                            $createLabel = 'Crear ' . mb_strtolower($sectionTitle, 'UTF-8');
                            ?>
                            <section id="<?= e($cvSectionAnchors[$sectionKey]) ?>" class="content-section member-panel-section member-entry-section" data-entry-card="<?= e($sectionKey) ?>">
                                <div class="member-panel-heading">
                                    <div class="member-panel-heading-main">
                                        <div>
                                            <p class="section-kicker">Curriculum</p>
                                            <?php if ($isCustomSection): ?>
                                                <h2><input type="text" name="custom_section_title" value="<?= e($sectionTitle) ?>" placeholder="Nombre de la seccion" class="cv-section-title-input" maxlength="100" aria-label="Nombre de la seccion personalizada"></h2>
                                            <?php else: ?>
                                                <h2><?= e($sectionTitle) ?></h2>
                                            <?php endif; ?>
                                            <p><?= e($cvSectionNotes[$sectionKey]) ?></p>
                                        </div>
                                        <button type="button" class="button button-primary member-card-cta" data-entry-create="<?= e($sectionKey) ?>"><?= e($createLabel) ?></button>
                                    </div>
                                    <div class="member-kpi-grid">
                                        <article class="member-kpi">
                                            <span>Entradas</span>
                                            <strong data-kpi="total"><?= e((string) $sectionMetrics['total']) ?></strong>
                                        </article>
                                        <article class="member-kpi">
                                            <span>Activas en el PDF</span>
                                            <strong data-kpi="active"><?= e((string) $sectionMetrics['active']) ?></strong>
                                        </article>
                                        <article class="member-kpi">
                                            <span>Con imagen</span>
                                            <strong data-kpi="images"><?= e((string) $sectionMetrics['images']) ?></strong>
                                        </article>
                                        <article class="member-kpi">
                                            <span>Periodo</span>
                                            <strong data-kpi="period"><?= e($sectionMetrics['period']) ?></strong>
                                        </article>
                                    </div>
                                </div>

                                <details class="member-settings">
                                    <summary class="member-settings-summary">
                                        <span class="member-settings-summary-main">
                                            <strong>Ajustes de la seccion</strong>
                                            <small>Como se comporta <?= e($sectionTitle) ?> en el curriculum PDF</small>
                                        </span>
                                        <span class="member-settings-state"><?= $sectionActive ? 'Se imprime' : 'No se imprime' ?></span>
                                    </summary>
                                    <div class="member-settings-grid">
                                        <input type="hidden" name="section_settings[<?= e($sectionKey) ?>][active]" value="0">
                                        <label class="member-switch" for="section-active-<?= e($sectionKey) ?>">
                                            <input id="section-active-<?= e($sectionKey) ?>" type="checkbox" name="section_settings[<?= e($sectionKey) ?>][active]" value="1" <?= $sectionActive ? 'checked' : '' ?>>
                                            <span class="member-switch-track" aria-hidden="true"></span>
                                            <span class="member-switch-text">
                                                <strong>Incluir en el PDF</strong>
                                                <small>Si lo desactivas, la seccion entera desaparece del curriculum impreso.</small>
                                            </span>
                                        </label>
                                        <div class="member-settings-row">
                                            <div class="member-settings-field">
                                                <label for="section-order-<?= e($sectionKey) ?>">Posicion en el PDF</label>
                                                <input id="section-order-<?= e($sectionKey) ?>" name="section_settings[<?= e($sectionKey) ?>][order]" type="number" min="1" step="1" value="<?= e((string) $sectionDisplayOrder) ?>">
                                                <small>1 la coloca arriba del todo, por delante del resto de secciones.</small>
                                            </div>
                                            <?php if (!empty($sectionConfig['sortable'])): ?>
                                                <div class="member-settings-field">
                                                    <label for="section-sort-<?= e($sectionKey) ?>">Orden de las entradas</label>
                                                    <select id="section-sort-<?= e($sectionKey) ?>" name="sort_orders[<?= e($sectionKey) ?>]">
                                                        <?php $sortOrder = normalize_cv_sort_order($memberProfile['sort_orders'][$sectionKey] ?? 'desc'); ?>
                                                        <option value="desc" <?= $sortOrder === 'desc' ? 'selected' : '' ?>>Mas reciente primero</option>
                                                        <option value="asc" <?= $sortOrder === 'asc' ? 'selected' : '' ?>>Mas antiguo primero</option>
                                                        <option value="manual" <?= $sortOrder === 'manual' ? 'selected' : '' ?>>Orden manual</option>
                                                    </select>
                                                    <small>Con orden manual mandan los numeros que pongas en cada entrada.</small>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </details>

                                <div class="member-entry-list" data-entry-list="<?= e($sectionKey) ?>" data-entry-next="<?= e((string) count($sectionEntries)) ?>">
                                    <?php foreach ($sectionEntries as $rowIndex => $entry): ?>
                                        <?= cv_entry_item_markup($sectionKey, $sectionConfig, (string) $rowIndex, is_array($entry) ? $entry : [], $sectionTitle) ?>
                                    <?php endforeach; ?>
                                </div>

                                <div class="member-entry-empty" data-entry-empty <?= $sectionEntries ? 'hidden' : '' ?>>
                                    <p>Todavia no has creado ninguna entrada en <strong><?= e($sectionTitle) ?></strong>.</p>
                                    <button type="button" class="button button-secondary" data-entry-create="<?= e($sectionKey) ?>"><?= e($createLabel) ?></button>
                                </div>

                                <template data-entry-template="<?= e($sectionKey) ?>"><?= cv_entry_item_markup($sectionKey, $sectionConfig, '__INDEX__', [], $sectionTitle) ?></template>
                            </section>
                        <?php endforeach; ?>

                        <div class="member-entry-backdrop" data-entry-backdrop hidden></div>

                        <div class="member-form-savebar">
                            <div>
                                <strong>Guardar cambios del perfil</strong>
                                <span data-savebar-message>Actualiza identidad, datos profesionales y secciones del curriculum.</span>
                            </div>
                            <button class="button button-primary member-save-button" type="submit">Guardar cambios</button>
                        </div>
                    </form>
                        <section class="cv-print-document" aria-label="Curriculum imprimible">
                            <header class="cv-print-header" <?= $cvHeaderStyle !== '' ? 'style="' . e($cvHeaderStyle) . '"' : '' ?>>
                                <?php if ($mainPhotoVisiblePath !== ''): ?>
                                    <img src="<?= e($mainPhotoVisiblePath) ?>" alt="Fotografia principal de <?= e($displayName) ?>">
                                <?php endif; ?>
                                <div>
                                    <h1><?= e($cardHeadline !== '' ? $cardHeadline : $displayName) ?></h1>
                                    <?php if ($cardHeadline !== '' && $displayName !== ''): ?><p class="cv-print-name"><?= e($displayName) ?></p><?php endif; ?>
                                    <p><?= e($memberProfile['city']) ?><?= $memberProfile['city'] && $memberProfile['province'] ? ' ' : '' ?><?= e($memberProfile['province']) ?></p>
                                </div>
                            </header>
                            <?php
                            $hasProfessionalData = !empty($memberProfile['years_active'])
                                || !empty($memberProfile['availability'])
                                || !empty($memberProfile['website_url'])
                                || !empty($memberProfile['instagram_url']);
                            ?>
                            <?php if (!empty($memberProfile['print_professional_data']) && $hasProfessionalData): ?>
                                <section>
                                    <h2>Datos profesionales</h2>
                                    <dl>
                                        <?php if ($memberProfile['years_active']): ?><div><dt>Trayectoria</dt><dd><?= e($memberProfile['years_active']) ?></dd></div><?php endif; ?>
                                        <?php if ($memberProfile['availability']): ?><div><dt>Disponibilidad</dt><dd><?= e($memberProfile['availability']) ?></dd></div><?php endif; ?>
                                        <?php if ($memberProfile['website_url']): ?><div><dt>Web</dt><dd><?= e($memberProfile['website_url']) ?></dd></div><?php endif; ?>
                                        <?php if ($memberProfile['instagram_url']): ?><div><dt>Instagram</dt><dd><?= e($memberProfile['instagram_url']) ?></dd></div><?php endif; ?>
                                    </dl>
                                </section>
                            <?php endif; ?>
                            <?php foreach (cv_print_sections($memberProfile, $cvSectionConfig) as $sectionKey => $sectionConfig): ?>
                                <?php $printEntries = array_values(array_filter(is_array($memberProfile[$sectionKey] ?? null) ? $memberProfile[$sectionKey] : [], 'cv_entry_is_active')); ?>
                                <?php if (cv_section_is_active($memberProfile, $sectionKey) && $printEntries): ?>
                                    <section>
                                        <h2><?= e($sectionConfig['title']) ?></h2>
                                        <div class="cv-print-list">
                                            <?php foreach ($printEntries as $entry): ?>
                                                <?php
                                                $entryDescription = clean_html_text((string) ($entry['description'] ?? ''));
                                                $entryStart = cv_print_date((string) ($entry['date_start'] ?? ''));
                                                $entryEnd = cv_print_date((string) ($entry['date_end'] ?? ''));
                                                $entryImagePath = member_visible_asset_path((string) ($entry['image_path'] ?? ''));
                                                ?>
                                                <article class="cv-print-entry <?= $entryImagePath !== '' ? 'cv-print-entry-with-image' : '' ?>">
                                                    <?php if ($entryImagePath !== ''): ?>
                                                        <img class="cv-print-entry-image" src="<?= e($entryImagePath) ?>" alt="Imagen de <?= e($sectionConfig['title']) ?>">
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

                <?php if ($hasWebPage): ?>
                <section id="pagina-web" class="content-section member-panel-section">
                    <div class="section-heading">
                        <div class="section-heading-content">
                            <p class="section-kicker">Pagina web</p>
                            <h2>Web de una sola pagina</h2>
                            <p>Configura los bloques que apareceran en tu pagina publica. El menu solo mostrara las secciones que tengan contenido guardado.</p>
                        </div>
                        <a class="section-enter-link" href="<?= e($publicProfileUrl) ?>" target="_blank" rel="noopener">Ver pagina publica</a>
                    </div>
                    <a class="public-url-cta member-web-public-url" href="<?= e($publicProfileUrl) ?>" target="_blank" rel="noopener" data-public-url-cta>
                        <span>URL publica de tu pagina web</span>
                        <strong data-public-url-text><?= e($publicProfileUrl) ?></strong>
                    </a>

                    <form class="member-profile-form member-web-form" action="panel-usuario.php#pagina-web" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="profile_action" value="update_web_page">

                        <div class="member-website-grid">
                            <article class="member-config-card">
                                <h3>Slider de cabecera</h3>
                                <p>Configura hasta 3 imagenes. El titulo, descripcion y boton solo apareceran cuando tengan contenido.</p>
                                <input type="hidden" name="web_header_title" value="<?= e((string) ($webPage['header_title'] ?? '')) ?>">
                                <input type="hidden" name="web_header_subtitle" value="<?= e((string) ($webPage['header_subtitle'] ?? '')) ?>">
                                <?php for ($slideIndex = 0; $slideIndex < 3; $slideIndex++): ?>
                                    <?php
                                    $slide = is_array($webSlides[$slideIndex] ?? null) ? $webSlides[$slideIndex] : [];
                                    $slideImage = member_visible_asset_path((string) ($slide['image_path'] ?? ''));
                                    ?>
                                    <div class="website-slide-editor">
                                        <div class="website-slide-preview" <?= $slideImage !== '' ? 'style="' . e("background-image: linear-gradient(135deg, rgba(17, 17, 20, 0.42), rgba(17, 17, 20, 0.2)), url('" . $slideImage . "');") . '"' : '' ?>>
                                            <span><?= $slideImage !== '' ? 'Imagen del slide ' . e((string) ($slideIndex + 1)) : 'Sin imagen' ?></span>
                                        </div>
                                        <div class="website-slide-fields">
                                            <strong>Slide <?= e((string) ($slideIndex + 1)) ?></strong>
                                            <input type="hidden" name="web_slides[<?= e((string) $slideIndex) ?>][image_path]" value="<?= e($slideImage) ?>">
                                            <label>Imagen
                                                <input name="web_slides[<?= e((string) $slideIndex) ?>][image]" type="file" accept="image/jpeg,image/png,image/webp">
                                            </label>
                                            <label>Titulo
                                                <input name="web_slides[<?= e((string) $slideIndex) ?>][title]" type="text" value="<?= e((string) ($slide['title'] ?? '')) ?>" maxlength="140">
                                            </label>
                                            <label>Descripcion
                                                <textarea name="web_slides[<?= e((string) $slideIndex) ?>][description]" rows="3" maxlength="320"><?= e((string) ($slide['description'] ?? '')) ?></textarea>
                                            </label>
                                            <div class="form-grid-two">
                                                <label>Texto boton
                                                    <input name="web_slides[<?= e((string) $slideIndex) ?>][cta_label]" type="text" value="<?= e((string) ($slide['cta_label'] ?? '')) ?>" maxlength="80" placeholder="Ver mas">
                                                </label>
                                                <label>URL boton
                                                    <input name="web_slides[<?= e((string) $slideIndex) ?>][cta_url]" type="url" value="<?= e((string) ($slide['cta_url'] ?? '')) ?>" placeholder="https://...">
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                <?php endfor; ?>
                            </article>

                            <article class="member-config-card">
                                <h3>Galeria</h3>
                                <p>Sube hasta 9 imagenes. Si no hay imagenes, la seccion Galeria no aparecera en la web publica.</p>
                                <div class="website-gallery-grid">
                                    <?php if ($webGallery): ?>
                                        <?php foreach ($webGallery as $galleryIndex => $galleryImage): ?>
                                            <label class="website-gallery-item">
                                                <img src="<?= e((string) $galleryImage) ?>" alt="Imagen de galeria <?= e((string) ($galleryIndex + 1)) ?>" loading="lazy">
                                                <span><input type="checkbox" name="remove_web_gallery[]" value="<?= e((string) $galleryIndex) ?>"> Quitar</span>
                                            </label>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p class="field-help website-empty-state">Todavia no hay imagenes en la galeria.</p>
                                    <?php endif; ?>
                                </div>
                                <label for="web_gallery_images">Anadir imagenes
                                    <input id="web_gallery_images" name="web_gallery_images[]" type="file" accept="image/jpeg,image/png,image/webp" multiple>
                                </label>
                            </article>

                            <article class="member-config-card">
                                <h3>Videos</h3>
                                <p>Anade enlaces de YouTube, Vimeo u otra plataforma. Si no hay videos, la seccion Videos no aparecera en la web publica.</p>
                                <div class="website-simple-repeat-list">
                                    <?php for ($videoIdx = 0; $videoIdx < $maxWebVideos; $videoIdx++): ?>
                                        <?php $video = is_array($webVideos[$videoIdx] ?? null) ? $webVideos[$videoIdx] : []; ?>
                                        <div class="website-simple-repeat-row">
                                            <div class="card-header compact-card-header">
                                                <strong>Video <?= e((string) ($videoIdx + 1)) ?></strong>
                                                <?php if (!empty($video)): ?><label class="remove-inline"><input type="checkbox" name="remove_web_videos[]" value="<?= e((string) $videoIdx) ?>"> Quitar</label><?php endif; ?>
                                            </div>
                                            <label>Titulo
                                                <input name="web_videos[<?= e((string) $videoIdx) ?>][title]" type="text" value="<?= e((string) ($video['title'] ?? '')) ?>" maxlength="140" placeholder="Ej. Bulerias en directo">
                                            </label>
                                            <label>URL del video
                                                <input name="web_videos[<?= e((string) $videoIdx) ?>][url]" type="url" value="<?= e((string) ($video['url'] ?? '')) ?>" placeholder="https://www.youtube.com/watch?v=...">
                                            </label>
                                            <label>Descripcion
                                                <textarea name="web_videos[<?= e((string) $videoIdx) ?>][description]" rows="3" maxlength="500" placeholder="Contexto, pieza, compania o lugar."><?= e((string) ($video['description'] ?? '')) ?></textarea>
                                            </label>
                                        </div>
                                    <?php endfor; ?>
                                </div>
                            </article>

                            <article class="member-config-card" id="web-eventos">
                                <div class="card-header">
                                    <div>
                                        <h3>Eventos</h3>
                                        <p><?= $isVipMember ? 'Como miembro VIP puedes publicar hasta 20 eventos.' : 'Como miembro simpatizante puedes publicar hasta 3 eventos.' ?></p>
                                    </div>
                                    <span class="event-counter" data-event-count="<?= count($webEvents) ?>">
                                        <span class="event-count"><?= count($webEvents) ?></span> / <span class="event-max"><?= $maxWebEvents ?></span>
                                    </span>
                                </div>
                                <div class="member-web-repeat-list event-list-container" data-web-repeat-list="events" data-web-max="<?= e((string) $maxWebEvents) ?>">
                                    <?php foreach ($webEvents as $evIdx => $ev): ?>
                                        <div class="member-web-repeat-row event-row-card" data-web-repeat-row>
                                            <input type="hidden" name="web_events[<?= e((string) $evIdx) ?>][image_path]" value="<?= e((string) ($ev['image_path'] ?? '')) ?>">
                                            
                                            <div class="event-row-header">
                                                <div class="event-row-title-group">
                                                    <label class="event-field-label">Titulo del evento<span class="required">*</span>
                                                        <input name="web_events[<?= e((string) $evIdx) ?>][title]" type="text" value="<?= e((string) ($ev['title'] ?? '')) ?>" maxlength="140" placeholder="Ej: Gala de Flamenco" class="event-title-input">
                                                    </label>
                                                </div>
                                                <button type="button" class="button-remove-event" data-web-remove-row title="Eliminar evento">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                                                </button>
                                            </div>

                                            <div class="event-row-content">
                                                <div class="event-image-section">
                                                    <label class="event-image-upload">
                                                        <div class="event-image-preview-container">
                                                            <?php if (!empty($ev['image_path'])): ?>
                                                                <img src="<?= e((string) $ev['image_path']) ?>" alt="Evento" loading="lazy" class="event-image-preview">
                                                            <?php else: ?>
                                                                <div class="event-image-placeholder">
                                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
                                                                    <span>Sube una imagen</span>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                        <input name="web_events[<?= e((string) $evIdx) ?>][image]" type="file" accept="image/jpeg,image/png,image/webp" hidden class="event-image-input">
                                                        <span class="image-help">JPG, PNG o WebP (obligatorio)</span>
                                                    </label>
                                                </div>

                                                <div class="event-fields-section">
                                                    <div class="event-date-time-group">
                                                        <label class="event-field-label">Fecha<span class="required">*</span>
                                                            <input name="web_events[<?= e((string) $evIdx) ?>][date]" type="date" value="<?= e((string) ($ev['date'] ?? '')) ?>" class="event-date-input">
                                                        </label>
                                                        <label class="event-field-label">Hora
                                                            <input name="web_events[<?= e((string) $evIdx) ?>][time]" type="time" value="<?= e((string) ($ev['time'] ?? '')) ?>" class="event-time-input">
                                                        </label>
                                                    </div>

                                                    <label class="event-field-label">Descripcion
                                                        <textarea name="web_events[<?= e((string) $evIdx) ?>][description]" rows="3" maxlength="700" placeholder="Lugar, programa, artistas invitados, detalles..." class="event-description-input"><?= e((string) ($ev['description'] ?? '')) ?></textarea>
                                                        <span class="char-count"><span class="current">0</span>/700</span>
                                                    </label>

                                                    <label class="event-field-label">Link del evento
                                                        <input name="web_events[<?= e((string) $evIdx) ?>][url]" type="url" value="<?= e((string) ($ev['url'] ?? '')) ?>" placeholder="https://..." class="event-url-input">
                                                        <span class="field-hint">Link a plataforma de venta o info (opcional)</span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <div class="member-web-repeat-actions event-actions">
                                    <button type="button" class="button button-primary" data-web-add="events">+ Añadir evento</button>
                                    <p class="events-info">
                                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/></svg>
                                        Los eventos aparecerán en tu página pública con la imagen obligatoria.
                                    </p>
                                </div>
                            </article>

                            <article class="member-config-card">
                                <h3>Actualidad</h3>
                                <p>Publica noticias, comunicados o novedades. Si no hay elementos, la seccion Actualidad no aparecera en la web publica.</p>
                                <div class="website-simple-repeat-list">
                                    <?php for ($newsIdx = 0; $newsIdx < $maxWebNews; $newsIdx++): ?>
                                        <?php
                                        $newsItem = is_array($webNews[$newsIdx] ?? null) ? $webNews[$newsIdx] : [];
                                        $newsImage = member_visible_asset_path((string) ($newsItem['image_path'] ?? ''));
                                        ?>
                                        <div class="website-simple-repeat-row">
                                            <input type="hidden" name="web_news[<?= e((string) $newsIdx) ?>][image_path]" value="<?= e($newsImage) ?>">
                                            <div class="card-header compact-card-header">
                                                <strong>Actualidad <?= e((string) ($newsIdx + 1)) ?></strong>
                                                <?php if (!empty($newsItem)): ?><label class="remove-inline"><input type="checkbox" name="remove_web_news[]" value="<?= e((string) $newsIdx) ?>"> Quitar</label><?php endif; ?>
                                            </div>
                                            <div class="website-news-editor-grid">
                                                <label class="website-news-image-field">
                                                    <?php if ($newsImage !== ''): ?>
                                                        <img src="<?= e($newsImage) ?>" alt="Imagen de actualidad <?= e((string) ($newsIdx + 1)) ?>" loading="lazy">
                                                    <?php else: ?>
                                                        <span>Sin imagen</span>
                                                    <?php endif; ?>
                                                    <input name="web_news[<?= e((string) $newsIdx) ?>][image]" type="file" accept="image/jpeg,image/png,image/webp">
                                                </label>
                                                <div class="website-slide-fields">
                                                    <label>Titulo
                                                        <input name="web_news[<?= e((string) $newsIdx) ?>][title]" type="text" value="<?= e((string) ($newsItem['title'] ?? '')) ?>" maxlength="160" placeholder="Ej. Nuevo curso intensivo">
                                                    </label>
                                                    <div class="form-grid-two">
                                                        <label>Fecha
                                                            <input name="web_news[<?= e((string) $newsIdx) ?>][date]" type="date" value="<?= e((string) ($newsItem['date'] ?? '')) ?>">
                                                        </label>
                                                        <label>URL relacionada
                                                            <input name="web_news[<?= e((string) $newsIdx) ?>][url]" type="url" value="<?= e((string) ($newsItem['url'] ?? '')) ?>" placeholder="https://...">
                                                        </label>
                                                    </div>
                                                    <label>Texto
                                                        <textarea name="web_news[<?= e((string) $newsIdx) ?>][summary]" rows="4" maxlength="900" placeholder="Cuenta la novedad, convocatoria o informacion relevante."><?= e((string) ($newsItem['summary'] ?? '')) ?></textarea>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endfor; ?>
                                </div>
                            </article>

                            <article class="member-config-card social-networks-card" id="web-redes-sociales">
                                <div class="card-header">
                                    <div>
                                        <h3>Redes sociales</h3>
                                        <p>Introduce las URLs de tus perfiles para conectar con tu audiencia</p>
                                    </div>
                                </div>
                                <div class="social-networks-grid">
                                    <?php foreach ($socialNetworkLabels as $network => $label): ?>
                                        <label class="social-network-field" data-network="<?= e($network) ?>">
                                            <span class="social-network-icon-label">
                                                <span class="social-network-label"><?= e($label) ?></span>
                                                <span class="social-network-icon"><?= $socialNetworkIcons[$network] ?? '' ?></span>
                                            </span>
                                            <input name="web_social_links[<?= e($network) ?>]" type="url" value="<?= e((string) ($webSocialLinks[$network] ?? '')) ?>" placeholder="https://..." class="social-network-input">
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                                <p class="social-networks-info">
                                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm3.5-9c.83 0 1.5-.67 1.5-1.5S16.33 8 15.5 8 14 8.67 14 9.5s.67 1.5 1.5 1.5zm-7 0c.83 0 1.5-.67 1.5-1.5S9.33 8 8.5 8 7 8.67 7 9.5 7.67 11 8.5 11zm3.5 6.5c2.33 0 4.31-1.46 5.11-3.5H6.89c.8 2.04 2.78 3.5 5.11 3.5z"/></svg>
                                    Los iconos aparecerán en la barra lateral de tu página pública
                                </p>
                            </article>

                            <article class="member-config-card">
                                <h3>Contacto</h3>
                                <p>Elige que datos se mostraran. Si no seleccionas ningun dato con contenido, Contacto no aparecera en el menu publico.</p>
                                <div class="website-contact-options">
                                    <label><input type="checkbox" name="web_contact_fields[]" value="email" <?= in_array('email', $webContactFields, true) ? 'checked' : '' ?>> Email</label>
                                    <label><input type="checkbox" name="web_contact_fields[]" value="phone" <?= in_array('phone', $webContactFields, true) ? 'checked' : '' ?>> Telefono</label>
                                    <label><input type="checkbox" name="web_contact_fields[]" value="website" <?= in_array('website', $webContactFields, true) ? 'checked' : '' ?>> Web</label>
                                    <label><input type="checkbox" name="web_contact_fields[]" value="instagram" <?= in_array('instagram', $webContactFields, true) ? 'checked' : '' ?>> Instagram</label>
                                </div>
                            </article>
                        </div>

                        <div class="cv-editor-actions">
                            <button class="button button-primary" type="submit">Guardar pagina web</button>
                        </div>
                    </form>
                </section>
                <?php endif; ?>

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
                            <img class="member-card-access-qr" src="<?= e($memberCardQrUrl) ?>" alt="QR de acceso de <?= e($displayName) ?>" loading="lazy" data-member-card-qr data-qr-base="<?= e($memberCardQrBase) ?>">
                            <div class="member-card-overlay">
                                <span class="member-card-space"><?= e($memberTypeLabel) ?></span>
                                <strong><?= e($displayName) ?></strong>
                                <?php if ($cardHeadline !== ''): ?><span class="member-card-headline"><?= e($cardHeadline) ?></span><?php endif; ?>
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
        const memberProfileForm = document.getElementById('member-profile-form');
        const profileActionInput = memberProfileForm?.querySelector('input[name="profile_action"]');
        const csrfInput = memberProfileForm?.querySelector('input[name="csrf_token"]');
        const saveBar = document.querySelector('.member-form-savebar');
        const saveBarMessage = saveBar?.querySelector('[data-savebar-message]');

        const syncRichTextEditors = (form = memberProfileForm) => {
            if (!(form instanceof HTMLFormElement)) {
                return;
            }
            form.querySelectorAll('[data-rich-editor]').forEach((formEditor) => {
                if (!(formEditor instanceof HTMLElement)) {
                    return;
                }
                const formTextarea = formEditor.parentElement?.querySelector('textarea[hidden]');
                if (formTextarea instanceof HTMLTextAreaElement) {
                    formTextarea.value = formEditor.innerHTML;
                }
            });
        };

        const markProfilePendingSave = (message = 'Hay cambios pendientes. Pulsa Guardar cambios para dejarlos persistentes.') => {
            if (saveBar instanceof HTMLElement) {
                saveBar.classList.add('member-form-savebar-pending');
            }
            if (saveBarMessage instanceof HTMLElement) {
                saveBarMessage.textContent = message;
            }
        };

        const cacheBustedAssetPath = (path) => {
            if (!path) {
                return '';
            }
            const separator = path.includes('?') ? '&' : '?';
            return `${path}${separator}v=${Date.now()}`;
        };

        const normalizeSlugValue = (value) => value
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '')
            .replace(/-{2,}/g, '-');

        const syncPublicUrlCta = () => {
            const slugInput = document.querySelector('[data-slug-input]');
            const publicUrlCtas = document.querySelectorAll('[data-public-url-cta]');
            if (!(slugInput instanceof HTMLInputElement) || publicUrlCtas.length === 0) {
                return;
            }

            const normalizedSlug = normalizeSlugValue(slugInput.value);
            if (slugInput.value !== normalizedSlug) {
                slugInput.value = normalizedSlug;
            }

            // El prefijo lo fija el tipo de espacio (/artista/, /academia/,
            // /asociacion/...), que no se puede cambiar desde el panel, asi que
            // viene resuelto del servidor y aqui solo varia el slug.
            const baseUrl = slugInput.dataset.publicProfileBase || '';
            const prefix = slugInput.dataset.publicProfilePrefix || 'artista';
            const nextUrl = `${baseUrl}${prefix}/${normalizedSlug || 'nombre-artista'}`;
            publicUrlCtas.forEach((publicUrlCta) => {
                if (!(publicUrlCta instanceof HTMLAnchorElement)) {
                    return;
                }
                publicUrlCta.href = nextUrl;
                const publicUrlText = publicUrlCta.querySelector('[data-public-url-text]');
                if (publicUrlText instanceof HTMLElement) {
                    publicUrlText.textContent = nextUrl;
                }
            });
        };

        document.querySelector('[data-slug-input]')?.addEventListener('input', syncPublicUrlCta);
        document.querySelector('[data-slug-input]')?.addEventListener('blur', syncPublicUrlCta);
        syncPublicUrlCta();

        const submitIsolatedImageUpdate = async (input) => {
            if (!(input instanceof HTMLInputElement) || !input.files?.[0] || !(csrfInput instanceof HTMLInputElement)) {
                return;
            }

            const formData = new FormData();
            formData.append('profile_action', 'update_profile_images');
            formData.append('csrf_token', csrfInput.value);
            formData.append(input.name, input.files[0], input.files[0].name);
            markProfilePendingSave('Guardando imagen en base de datos...');

            try {
                const response = await fetch('panel-usuario.php', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'fetch',
                    },
                    credentials: 'same-origin',
                });
                let payload = null;
                try {
                    payload = await response.json();
                } catch (parseError) {
                    throw new Error('No se pudo confirmar el guardado de la imagen. Vuelve a iniciar sesion si el problema continua.');
                }
                if (!response.ok || !payload?.ok) {
                    throw new Error(payload?.errors?.[0] || 'No se pudo guardar la imagen en base de datos.');
                }

                if (input.name === 'main_photo' && payload.main_photo_path) {
                    const persistedPath = cacheBustedAssetPath(payload.main_photo_path);
                    document.querySelectorAll('[data-main-photo-preview]').forEach((previewImage) => {
                        if (previewImage instanceof HTMLImageElement) {
                            previewImage.src = persistedPath;
                            previewImage.hidden = false;
                        }
                    });
                    document.querySelectorAll('[data-main-photo-placeholder]').forEach((placeholder) => {
                        placeholder.hidden = true;
                    });
                }

                if (input.name === 'cv_header_image' && payload.cv_header_image_path) {
                    const preview = document.querySelector('.cv-header-background-preview');
                    if (preview instanceof HTMLElement) {
                        preview.style.backgroundImage = `linear-gradient(135deg, rgba(17, 17, 20, 0.72), rgba(32, 56, 71, 0.68)), url("${cacheBustedAssetPath(payload.cv_header_image_path)}")`;
                    }
                }

                if (saveBar instanceof HTMLElement) {
                    saveBar.classList.remove('member-form-savebar-pending');
                }
                if (saveBarMessage instanceof HTMLElement) {
                    saveBarMessage.textContent = payload.messages?.[0] || 'Imagen guardada en tu cuenta.';
                }
                input.value = '';
            } catch (error) {
                const message = error instanceof Error ? error.message : 'No se pudo guardar la imagen.';
                if (saveBarMessage instanceof HTMLElement) {
                    saveBarMessage.textContent = message;
                }
                alert(message);
            }
        };

        const submitProfileForEntryImage = () => {
            if (!(memberProfileForm instanceof HTMLFormElement)) {
                return;
            }
            syncRichTextEditors(memberProfileForm);
            if (profileActionInput instanceof HTMLInputElement) {
                profileActionInput.value = 'update_profile';
            }
            memberProfileForm.submit();
        };

        window.addEventListener('beforeprint', () => {
            document.title = ' ';
        });
        window.addEventListener('afterprint', () => {
            document.title = originalDocumentTitle;
        });

        // El formulario de perfil abarca varias pantallas del panel (Mi perfil y
        // cada seccion del curriculum). Si el navegador encuentra un campo
        // obligatorio en una pantalla que no esta a la vista no puede enfocarlo
        // y el envio se queda mudo, asi que saltamos a esa pantalla.
        memberProfileForm?.addEventListener('invalid', (event) => {
            const section = event.target instanceof Element ? event.target.closest('.member-panel-section') : null;
            if (section instanceof HTMLElement && !section.classList.contains('active')) {
                activateMemberPanel(section.id);
            }
        }, true);

        // Tarjetas del curriculum: listado de entradas y panel lateral derecho
        // para verlas, crearlas y editarlas. Los campos siguen dentro del
        // formulario de perfil, asi que se guardan con el resto del panel.
        const entryBackdrop = document.querySelector('[data-entry-backdrop]');

        const entryFieldValue = (fields, field) => {
            const node = fields?.querySelector(`[data-entry-field="${field}"]`);
            if (!(node instanceof HTMLElement)) {
                return '';
            }
            if (node.hasAttribute('data-rich-editor')) {
                return node.innerHTML.trim();
            }
            if (node instanceof HTMLInputElement && node.type === 'checkbox') {
                return node.checked ? '1' : '';
            }
            return String(node.value ?? '').trim();
        };

        const formatEntryDate = (value) => {
            const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value);
            return match ? `${match[3]}/${match[2]}/${match[1]}` : value;
        };

        const entryMetaLabel = (fields) => {
            const dates = [
                formatEntryDate(entryFieldValue(fields, 'date_start')),
                formatEntryDate(entryFieldValue(fields, 'date_end')),
            ].filter(Boolean).join(' — ');

            return [dates, entryFieldValue(fields, 'location')].filter(Boolean).join(' · ');
        };

        const escapeEntryText = (value) => String(value).replace(/[&<>"']/g, (char) => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;',
        }[char]));

        const refreshEntryItem = (item) => {
            if (!(item instanceof HTMLElement)) {
                return;
            }
            const fields = item.querySelector('[data-entry-fields]');
            const title = item.querySelector('[data-entry-title]');
            const meta = item.querySelector('[data-entry-meta]');
            const flag = item.querySelector('[data-entry-flag]');
            const thumbImage = item.querySelector('[data-entry-thumb-image]');
            const thumbPlaceholder = item.querySelector('[data-entry-thumb-placeholder]');
            const imagePath = entryFieldValue(fields, 'image_path');

            if (title instanceof HTMLElement) {
                title.textContent = entryFieldValue(fields, 'category') || 'Entrada sin titulo';
            }
            if (meta instanceof HTMLElement) {
                meta.textContent = entryMetaLabel(fields);
            }
            if (flag instanceof HTMLElement) {
                flag.hidden = entryFieldValue(fields, 'is_active') === '1';
            }
            if (thumbImage instanceof HTMLImageElement) {
                if (imagePath) {
                    thumbImage.src = imagePath;
                    thumbImage.hidden = false;
                } else {
                    thumbImage.removeAttribute('src');
                    thumbImage.hidden = true;
                }
            }
            if (thumbPlaceholder instanceof HTMLElement) {
                thumbPlaceholder.hidden = imagePath !== '';
            }
        };

        // Mismo calculo que cv_section_metrics() en PHP, para no recargar la
        // pagina cada vez que se crea, edita o borra una entrada.
        const entrySectionMetrics = (items) => {
            const years = [];
            let active = 0;
            let images = 0;

            items.forEach((item) => {
                const fields = item.querySelector('[data-entry-fields]');
                if (entryFieldValue(fields, 'is_active') === '1') {
                    active += 1;
                }
                if (entryFieldValue(fields, 'image_path') !== '') {
                    images += 1;
                }
                ['date_start', 'date_end'].forEach((field) => {
                    const year = Number.parseInt(entryFieldValue(fields, field).slice(0, 4), 10);
                    if (Number.isInteger(year) && year > 0) {
                        years.push(year);
                    }
                });
            });

            let period = '—';
            if (years.length > 0) {
                const min = Math.min(...years);
                const max = Math.max(...years);
                period = min === max ? String(min) : `${min}–${max}`;
            }

            return { total: items.length, active, images, period };
        };

        const refreshEntryCard = (card) => {
            if (!(card instanceof HTMLElement)) {
                return;
            }
            const list = card.querySelector('[data-entry-list]');
            const items = list ? Array.from(list.querySelectorAll('[data-entry-item]')) : [];
            const metrics = entrySectionMetrics(items);
            const empty = card.querySelector('[data-entry-empty]');

            Object.entries(metrics).forEach(([key, value]) => {
                const node = card.querySelector(`[data-kpi="${key}"]`);
                if (node instanceof HTMLElement) {
                    node.textContent = String(value);
                }
            });

            const navMetric = document.querySelector(`[data-nav-metric="${card.dataset.entryCard}"]`);
            if (navMetric instanceof HTMLElement) {
                navMetric.textContent = metrics.total === 1 ? '1 entrada' : `${metrics.total} entradas`;
            }
            if (empty instanceof HTMLElement) {
                empty.hidden = metrics.total > 0;
            }
            if (list instanceof HTMLElement) {
                list.hidden = metrics.total === 0;
            }
        };

        const entryPreviewMarkup = (fields) => {
            const imagePath = entryFieldValue(fields, 'image_path');
            const meta = entryMetaLabel(fields);
            const description = entryFieldValue(fields, 'description');
            const blocks = [];

            if (imagePath) {
                blocks.push(`<img class="member-entry-preview-image" src="${escapeEntryText(imagePath)}" alt="">`);
            }
            blocks.push(`<h5>${escapeEntryText(entryFieldValue(fields, 'category') || 'Entrada sin titulo')}</h5>`);
            if (meta) {
                blocks.push(`<p class="member-entry-preview-meta">${escapeEntryText(meta)}</p>`);
            }
            blocks.push(description
                ? `<div class="member-entry-preview-text">${description}</div>`
                : '<p class="member-entry-preview-empty">Esta entrada todavia no tiene descripcion.</p>');
            if (entryFieldValue(fields, 'is_active') !== '1') {
                blocks.push('<p class="member-entry-preview-flag">Esta entrada no se imprime en el curriculum PDF.</p>');
            }

            return blocks.join('');
        };

        const setEntryDrawerMode = (fields, mode) => {
            const isView = mode === 'view';
            const preview = fields.querySelector('[data-entry-preview]');
            const entryForm = fields.querySelector('.member-entry-form');
            const editButton = fields.querySelector('[data-entry-to-edit]');
            const submitButton = fields.querySelector('[data-entry-submit]');
            const drawerTitle = fields.querySelector('[data-entry-drawer-title]');

            fields.dataset.entryMode = isView ? 'view' : 'edit';
            if (preview instanceof HTMLElement) {
                preview.hidden = !isView;
                if (isView) {
                    preview.innerHTML = entryPreviewMarkup(fields);
                }
            }
            if (entryForm instanceof HTMLElement) {
                entryForm.hidden = isView;
            }
            if (editButton instanceof HTMLElement) {
                editButton.hidden = !isView;
            }
            if (submitButton instanceof HTMLElement) {
                submitButton.hidden = isView;
            }
            if (drawerTitle instanceof HTMLElement) {
                drawerTitle.textContent = isView
                    ? 'Vista de la entrada'
                    : (fields.dataset.entryNew === '1' ? 'Nueva entrada' : 'Editar entrada');
            }
        };

        const closeEntryDrawer = () => {
            document.querySelectorAll('[data-entry-fields]:not([hidden])').forEach((fields) => {
                fields.hidden = true;
                const item = fields.closest('[data-entry-item]');
                if (item instanceof HTMLElement) {
                    item.classList.remove('is-open');
                    refreshEntryItem(item);
                    refreshEntryCard(item.closest('[data-entry-card]'));
                }
            });
            if (entryBackdrop instanceof HTMLElement) {
                entryBackdrop.hidden = true;
            }
            document.body.classList.remove('member-entry-drawer-open');
        };

        const openEntryDrawer = (item, mode) => {
            if (!(item instanceof HTMLElement)) {
                return;
            }
            const fields = item.querySelector('[data-entry-fields]');
            if (!(fields instanceof HTMLElement)) {
                return;
            }

            closeEntryDrawer();
            setEntryDrawerMode(fields, mode);
            fields.hidden = false;
            item.classList.add('is-open');
            if (entryBackdrop instanceof HTMLElement) {
                entryBackdrop.hidden = false;
            }
            document.body.classList.add('member-entry-drawer-open');
            fields.scrollTop = 0;

            if (mode !== 'view') {
                const firstField = fields.querySelector('input[type="text"], input[type="date"]');
                if (firstField instanceof HTMLElement) {
                    window.setTimeout(() => firstField.focus(), 60);
                }
            }
        };

        const createEntry = (sectionKey) => {
            const card = document.querySelector(`[data-entry-card="${sectionKey}"]`);
            const list = card?.querySelector('[data-entry-list]');
            const template = card?.querySelector(`[data-entry-template="${sectionKey}"]`);
            if (!(card instanceof HTMLElement) || !(list instanceof HTMLElement) || !(template instanceof HTMLTemplateElement)) {
                return;
            }

            const nextIndex = Number.parseInt(list.dataset.entryNext || '0', 10) || 0;
            const item = template.content.cloneNode(true).querySelector('[data-entry-item]');
            if (!(item instanceof HTMLElement)) {
                return;
            }

            item.querySelectorAll('[name]').forEach((input) => {
                input.name = input.name.replace('__INDEX__', String(nextIndex));
            });
            const order = item.querySelector('[data-entry-field="display_order"]');
            if (order instanceof HTMLInputElement) {
                order.value = String(list.querySelectorAll('[data-entry-item]').length + 1);
            }
            const fields = item.querySelector('[data-entry-fields]');
            if (fields instanceof HTMLElement) {
                fields.dataset.entryNew = '1';
            }

            list.appendChild(item);
            list.dataset.entryNext = String(nextIndex + 1);
            initializeRichTextEditors(item);
            refreshEntryItem(item);
            refreshEntryCard(card);
            openEntryDrawer(item, 'edit');
            markProfilePendingSave('Completa la entrada nueva y pulsa Guardar cambios.');
        };

        const deleteEntry = (item) => {
            const card = item.closest('[data-entry-card]');
            const title = item.querySelector('[data-entry-title]')?.textContent?.trim() || 'esta entrada';
            if (!window.confirm(`¿Seguro que quieres borrar "${title}"? Se eliminara al guardar los cambios.`)) {
                return;
            }

            closeEntryDrawer();
            item.remove();
            refreshEntryCard(card);
            markProfilePendingSave('Entrada eliminada. Pulsa Guardar cambios para confirmarlo.');
        };

        document.addEventListener('click', (event) => {
            const target = event.target;
            if (!(target instanceof Element)) {
                return;
            }

            const createButton = target.closest('[data-entry-create]');
            if (createButton instanceof HTMLElement) {
                createEntry(createButton.dataset.entryCreate || '');
                return;
            }

            if (target.closest('[data-entry-close]') || target.closest('[data-entry-backdrop]')) {
                closeEntryDrawer();
                return;
            }

            const toEditButton = target.closest('[data-entry-to-edit]');
            if (toEditButton) {
                const fields = toEditButton.closest('[data-entry-fields]');
                if (fields instanceof HTMLElement) {
                    setEntryDrawerMode(fields, 'edit');
                }
                return;
            }

            const deleteButton = target.closest('[data-entry-delete]');
            if (deleteButton) {
                const item = deleteButton.closest('[data-entry-item]');
                if (item instanceof HTMLElement) {
                    deleteEntry(item);
                }
                return;
            }

            const viewButton = target.closest('[data-entry-view]');
            if (viewButton) {
                openEntryDrawer(viewButton.closest('[data-entry-item]'), 'view');
                return;
            }

            const editButton = target.closest('[data-entry-edit]');
            if (editButton) {
                openEntryDrawer(editButton.closest('[data-entry-item]'), 'edit');
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeEntryDrawer();
            }
        });

        document.querySelectorAll('[data-entry-card]').forEach((card) => refreshEntryCard(card));

        // El resumen plegado dice si la seccion se imprime, asi que sigue al
        // interruptor aunque el bloque de ajustes este cerrado.
        document.querySelectorAll('.member-settings').forEach((settings) => {
            const toggle = settings.querySelector('.member-switch input');
            const state = settings.querySelector('.member-settings-state');
            if (!(toggle instanceof HTMLInputElement) || !(state instanceof HTMLElement)) {
                return;
            }
            toggle.addEventListener('change', () => {
                state.textContent = toggle.checked ? 'Se imprime' : 'No se imprime';
                state.classList.toggle('member-settings-state-off', !toggle.checked);
                markProfilePendingSave();
            });
            state.classList.toggle('member-settings-state-off', !toggle.checked);
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
                markProfilePendingSave('Guardando imagen en tu perfil...');
                if (!(csrfInput instanceof HTMLInputElement)) {
                    submitProfileForEntryImage();
                    return;
                }

                const uploadData = new FormData();
                uploadData.append('profile_action', 'upload_cv_entry_image');
                uploadData.append('csrf_token', csrfInput.value);
                uploadData.append('cv_entry_image', input.files[0], input.files[0].name);

                fetch('panel-usuario.php', {
                    method: 'POST',
                    body: uploadData,
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'fetch',
                    },
                    credentials: 'same-origin',
                })
                    .then(async (response) => {
                        let payload = null;
                        try {
                            payload = await response.json();
                        } catch (error) {
                            throw new Error('No se pudo confirmar la subida de la imagen.');
                        }
                        if (!response.ok || !payload?.ok || !payload.image_path) {
                            throw new Error(payload?.errors?.[0] || 'No se pudo guardar la imagen de la entrada.');
                        }
                        return payload.image_path;
                    })
                    .then((persistedImagePath) => {
                        const hiddenPath = field?.querySelector('input[type="hidden"][name$="[image_path]"]');
                        if (hiddenPath instanceof HTMLInputElement) {
                            hiddenPath.value = persistedImagePath;
                        }
                        if (preview instanceof HTMLImageElement) {
                            preview.src = cacheBustedAssetPath(persistedImagePath);
                            preview.hidden = false;
                        }
                        if (placeholder instanceof HTMLElement) {
                            placeholder.hidden = true;
                        }
                        input.value = '';
                        submitProfileForEntryImage();
                    })
                    .catch((error) => {
                        const message = error instanceof Error ? error.message : 'No se pudo guardar la imagen de la entrada.';
                        if (saveBarMessage instanceof HTMLElement) {
                            saveBarMessage.textContent = message;
                        }
                        alert(message);
                    });
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
                submitIsolatedImageUpdate(input);
            }

            if (input.matches('#cv_header_image') && input.files?.[0]) {
                const preview = document.querySelector('.cv-header-background-preview');
                if (preview instanceof HTMLElement) {
                    preview.style.backgroundImage = `linear-gradient(135deg, rgba(17, 17, 20, 0.72), rgba(32, 56, 71, 0.68)), url("${URL.createObjectURL(input.files[0])}")`;
                    const title = preview.querySelector('strong');
                    const action = preview.querySelector('em');
                    if (title) {
                        title.textContent = 'Nuevo fondo seleccionado';
                    }
                    if (action) {
                        action.textContent = 'Cambiar fondo';
                    }
                }
                submitIsolatedImageUpdate(input);
            }
        });

        if (memberProfileForm instanceof HTMLFormElement) {
            memberProfileForm.addEventListener('submit', () => {
                if (profileActionInput instanceof HTMLInputElement && profileActionInput.value !== 'update_profile_images') {
                    profileActionInput.value = 'update_profile';
                }
                syncRichTextEditors(memberProfileForm);
            });
        }

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
                let savedRange = null;

                const saveSelection = () => {
                    const selection = window.getSelection();
                    if (!selection || selection.rangeCount === 0) {
                        return;
                    }
                    const anchorNode = selection.anchorNode;
                    if (anchorNode && editor.contains(anchorNode)) {
                        savedRange = selection.getRangeAt(0).cloneRange();
                    }
                };

                const restoreSelection = () => {
                    if (!savedRange) {
                        return;
                    }
                    const selection = window.getSelection();
                    if (!selection) {
                        return;
                    }
                    selection.removeAllRanges();
                    selection.addRange(savedRange);
                };

                const controls = [
                    {
                        kind: 'select',
                        title: 'Fuente',
                        defaultLabel: 'Inter',
                        command: 'fontName',
                        options: [
                            ['Inter', 'Inter'],
                            ['Georgia', 'Georgia'],
                            ['Arial', 'Arial'],
                            ['Playfair', 'Playfair Display'],
                        ],
                    },
                    {
                        kind: 'select',
                        title: 'Tamano',
                        defaultLabel: 'Normal',
                        command: 'fontSize',
                        options: [
                            ['Normal', '3'],
                            ['Grande', '4'],
                            ['Destacado', '5'],
                            ['Pequeno', '2'],
                        ],
                    },
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

                const sizeMap = {
                    1: '0.82rem',
                    2: '0.94rem',
                    3: '1rem',
                    4: '1.16rem',
                    5: '1.34rem',
                    6: '1.55rem',
                    7: '1.85rem',
                };
                const normalizeLegacyEditorTags = (targetEditor = editor) => {
                    targetEditor.querySelectorAll('font').forEach((fontTag) => {
                        const span = document.createElement('span');
                        const styles = [];
                        const size = fontTag.getAttribute('size');
                        const face = fontTag.getAttribute('face');
                        const color = fontTag.getAttribute('color');
                        if (size && sizeMap[size]) {
                            styles.push(`font-size: ${sizeMap[size]}`);
                        }
                        if (face) {
                            styles.push(`font-family: ${face}`);
                        }
                        if (color) {
                            styles.push(`color: ${color}`);
                        }
                        if (styles.length) {
                            span.setAttribute('style', styles.join('; '));
                        }
                        while (fontTag.firstChild) {
                            span.appendChild(fontTag.firstChild);
                        }
                        fontTag.replaceWith(span);
                    });
                };

                const syncEditor = () => {
                    normalizeLegacyEditorTags();
                    textarea.value = editor.innerHTML;
                };

                controls.forEach((control) => {
                    if (control.kind === 'select') {
                        const select = document.createElement('select');
                        select.className = 'rich-text-select';
                        select.title = control.title;
                        select.setAttribute('aria-label', control.title);
                        select.innerHTML = `<option value="">${control.defaultLabel || control.title}</option>`;
                        control.options.forEach(([label, value]) => {
                            const option = document.createElement('option');
                            option.value = value;
                            option.textContent = label;
                            select.appendChild(option);
                        });
                        select.addEventListener('change', () => {
                            if (!select.value) {
                                return;
                            }
                            editor.focus();
                            restoreSelection();
                            document.execCommand('styleWithCSS', false, true);
                            document.execCommand(control.command, false, select.value);
                            normalizeLegacyEditorTags();
                            syncEditor();
                            select.selectedIndex = 0;
                        });
                        toolbar.appendChild(select);
                        return;
                    }

                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'rich-text-button';
                    button.textContent = control.label;
                    button.title = control.title;
                    button.setAttribute('aria-label', control.title);
                    if (control.color) {
                        button.style.color = control.color;
                    }
                    button.addEventListener('mousedown', (event) => {
                        event.preventDefault();
                        restoreSelection();
                    });
                    button.addEventListener('click', () => {
                        editor.focus();
                        restoreSelection();
                        document.execCommand('styleWithCSS', false, true);
                        document.execCommand(control.command, false, control.value || null);
                        normalizeLegacyEditorTags();
                        syncEditor();
                    });
                    toolbar.appendChild(button);
                });

                editor.addEventListener('input', syncEditor);
                editor.addEventListener('blur', syncEditor);
                editor.addEventListener('keyup', saveSelection);
                editor.addEventListener('mouseup', saveSelection);
                editor.addEventListener('focus', saveSelection);
                syncEditor();

                const form = toolbar.closest('form');
                if (form && form.dataset.richEditorSubmitBound !== '1') {
                    form.dataset.richEditorSubmitBound = '1';
                    form.addEventListener('submit', () => {
                        form.querySelectorAll('[data-rich-editor]').forEach((formEditor) => {
                            normalizeLegacyEditorTags(formEditor);
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
        const memberCardLink = document.querySelector('[data-member-card-link]');
        const memberCardQrs = document.querySelectorAll('[data-member-card-qr]');
        document.querySelectorAll('[data-card-option]').forEach((input) => {
            input.addEventListener('change', () => {
                if (!cardPreview || !cardImage || !input.checked) {
                    return;
                }

                cardImage.src = input.dataset.cardSrc || cardImage.src;
                cardPreview.classList.toggle('member-card-preview-woman', input.dataset.cardFigure === 'woman');
                cardPreview.classList.toggle('member-card-preview-man', input.dataset.cardFigure === 'man');

                if (memberCardLink instanceof HTMLAnchorElement && memberCardQrs.length > 0) {
                    const publicUrlBase = memberCardLink.dataset.cardUrlBase || '';
                    const publicUrl = `${publicUrlBase}${encodeURIComponent(input.value)}`;
                    memberCardLink.href = publicUrl;
                    memberCardQrs.forEach((qr) => {
                        if (qr instanceof HTMLImageElement) {
                            const qrBase = qr.dataset.qrBase || 'qr.php?data=';
                            qr.src = `${qrBase}${encodeURIComponent(publicUrl)}`;
                        }
                    });
                }
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

        function activateMemberPanel(target) {
            if (!target || !document.getElementById(target)) {
                return;
            }
            document.querySelectorAll('[data-panel-link]').forEach((link) => link.classList.toggle('active', link.dataset.panelLink === target));
            document.querySelectorAll('.member-panel-section').forEach((section) => {
                section.classList.toggle('active', section.id === target);
            });

            // El formulario de perfil envuelve varias pantallas (Mi perfil y cada
            // seccion del curriculum): se muestra, con su barra de guardado, solo
            // cuando la pantalla activa es una de ellas.
            if (memberProfileForm instanceof HTMLFormElement) {
                const ownsTarget = document.getElementById(target)?.closest('form') === memberProfileForm;
                memberProfileForm.hidden = !ownsTarget;
                if (ownsTarget) {
                    memberProfileForm.action = 'panel-usuario.php#' + target;
                }
            }

            const backLink = document.querySelector('[data-panel-back]');
            if (backLink instanceof HTMLElement) {
                backLink.hidden = target === 'inicio';
            }
        }

        document.querySelectorAll('[data-panel-link]').forEach((link) => {
            link.addEventListener('click', () => {
                activateMemberPanel(link.dataset.panelLink);
            });
        });

        if (window.location.hash) {
            const initialTarget = window.location.hash.replace('#', '');
            if (document.getElementById(initialTarget)) {
                activateMemberPanel(initialTarget);
            }
        }

        // Logica de filas repetibles para eventos de pagina web
        function updateEventCounter() {
            const eventsList = document.querySelector('[data-web-repeat-list="events"]');
            if (!eventsList) return;
            const rows = eventsList.querySelectorAll('[data-web-repeat-row]');
            const counter = document.querySelector('.event-counter');
            if (counter) {
                const currentCount = counter.querySelector('.event-count');
                if (currentCount) currentCount.textContent = rows.length;
            }
        }

        function initEventCharCounter(textarea) {
            const updateCount = () => {
                const count = textarea.value.length;
                const max = textarea.maxLength;
                const display = textarea.parentElement?.querySelector('.char-count .current');
                if (display) display.textContent = count;
            };
            textarea.addEventListener('input', updateCount);
            updateCount();
        }

        function initEventImageUpload(fileInput) {
            fileInput.addEventListener('change', function() {
                if (this.files?.[0]) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        const container = this.closest('.event-image-upload')?.querySelector('.event-image-preview-container');
                        if (container && e.target?.result) {
                            container.innerHTML = `<img src="${e.target.result}" alt="Preview" loading="lazy" class="event-image-preview">`;
                        }
                    };
                    reader.readAsDataURL(this.files[0]);
                }
            });
        }

        document.querySelectorAll('[data-web-add]').forEach((button) => {
            button.addEventListener('click', () => {
                const listKey = button.dataset.webAdd;
                const list = document.querySelector(`[data-web-repeat-list="${listKey}"]`);
                if (!list) return;
                const max = parseInt(list.dataset.webMax || '99', 10);
                const rows = list.querySelectorAll('[data-web-repeat-row]');
                if (rows.length >= max) {
                    alert(`Has alcanzado el limite de ${max} eventos.`);
                    return;
                }
                const nextIndex = rows.length;
                const template = rows[0];
                if (!template) {
                    // Sin filas existentes: crear una fila nueva desde cero
                    const row = document.createElement('div');
                    row.className = 'member-web-repeat-row event-row-card';
                    row.dataset.webRepeatRow = '';
                    row.innerHTML = `
                        <input type="hidden" name="web_events[${nextIndex}][image_path]" value="">
                        <div class="event-row-header">
                            <div class="event-row-title-group">
                                <label class="event-field-label">Titulo del evento<span class="required">*</span>
                                    <input name="web_events[${nextIndex}][title]" type="text" value="" maxlength="140" placeholder="Ej: Gala de Flamenco" class="event-title-input">
                                </label>
                            </div>
                            <button type="button" class="button-remove-event" data-web-remove-row title="Eliminar evento">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                            </button>
                        </div>
                        <div class="event-row-content">
                            <div class="event-image-section">
                                <label class="event-image-upload">
                                    <div class="event-image-preview-container">
                                        <div class="event-image-placeholder">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
                                            <span>Sube una imagen</span>
                                        </div>
                                    </div>
                                    <input name="web_events[${nextIndex}][image]" type="file" accept="image/jpeg,image/png,image/webp" hidden class="event-image-input">
                                    <span class="image-help">JPG, PNG o WebP (obligatorio)</span>
                                </label>
                            </div>
                            <div class="event-fields-section">
                                <div class="event-date-time-group">
                                    <label class="event-field-label">Fecha<span class="required">*</span>
                                        <input name="web_events[${nextIndex}][date]" type="date" value="" class="event-date-input">
                                    </label>
                                    <label class="event-field-label">Hora
                                        <input name="web_events[${nextIndex}][time]" type="time" value="" class="event-time-input">
                                    </label>
                                </div>
                                <label class="event-field-label">Descripcion
                                    <textarea name="web_events[${nextIndex}][description]" rows="3" maxlength="700" placeholder="Lugar, programa, artistas invitados, detalles..." class="event-description-input"></textarea>
                                    <span class="char-count"><span class="current">0</span>/700</span>
                                </label>
                                <label class="event-field-label">Link del evento
                                    <input name="web_events[${nextIndex}][url]" type="url" value="" placeholder="https://..." class="event-url-input">
                                    <span class="field-hint">Link a plataforma de venta o info (opcional)</span>
                                </label>
                            </div>
                        </div>`;
                    list.appendChild(row);
                    
                    // Inicializar controles del nuevo evento
                    const newEventImage = row.querySelector('.event-image-input');
                    const newEventTextarea = row.querySelector('.event-description-input');
                    if (newEventImage) initEventImageUpload(newEventImage);
                    if (newEventTextarea) initEventCharCounter(newEventTextarea);
                    
                    updateEventCounter();
                    return;
                }
                const newRow = template.cloneNode(true);
                newRow.querySelectorAll('input, textarea').forEach((input) => {
                    input.name = input.name.replace(/\[\d+\]/, `[${nextIndex}]`);
                    if (input.type !== 'hidden') input.value = '';
                });
                newRow.querySelectorAll('img').forEach((img) => img.remove());
                const placeholder = newRow.querySelector('.event-image-placeholder');
                if (placeholder) placeholder.closest('.event-image-preview-container').innerHTML = placeholder.parentElement.innerHTML;
                
                // Inicializar controles
                const eventImage = newRow.querySelector('.event-image-input');
                const eventTextarea = newRow.querySelector('.event-description-input');
                if (eventImage) initEventImageUpload(eventImage);
                if (eventTextarea) initEventCharCounter(eventTextarea);
                
                list.appendChild(newRow);
                updateEventCounter();
            });
        });

        // Inicializar contadores de eventos existentes
        document.querySelectorAll('.event-description-input').forEach(initEventCharCounter);
        document.querySelectorAll('.event-image-input').forEach(initEventImageUpload);
        updateEventCounter();

        document.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-web-remove-row]');
            if (!btn) return;
            const row = btn.closest('[data-web-repeat-row]');
            if (row) {
                row.remove();
                updateEventCounter();
            }
        });

    </script>
</body>
</html>
