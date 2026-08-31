<?php
declare(strict_types=1);

require_once __DIR__ . '/app/auth.php';
require_once __DIR__ . '/app/layout.php';
require_once __DIR__ . '/app/academia_security.php';
// Fase 1 red social: agenda de eventos, cartera de puntos y redes sociales.
require_once __DIR__ . '/app/events_ui.php';
require_once __DIR__ . '/app/points_ui.php';
require_once __DIR__ . '/app/social_links_repository.php';

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
/**
 * Bloque "Inicio" de la microweb: se gestiona con el mismo listado y panel
 * lateral que las secciones del curriculum, pero se guarda aparte porque no
 * forma parte del PDF ni tiene ajustes de seccion.
 */
$introSectionKey = 'intro_articles';
$introSectionTitle = 'Inicio';
$introSectionConfig = [
    'title' => $introSectionTitle,
    'fields' => ['title' => 'Titulo', 'year' => 'Año', 'description' => 'Descripcion'],
    'field_types' => ['year' => 'number'],
    'allows_image' => true,
    'summary_title' => 'title',
    'summary_meta' => ['year'],
    'active_label' => 'Visible en la web publica',
    'hidden_label' => 'Oculto en la web',
    'toggle_action' => true,
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
    // Premios: la clave 'awards' ya existia en el perfil sin pantalla que la
    // usara, asi que solo hace falta declararla como una seccion mas.
    'awards' => [
        'title' => 'Premios y reconocimientos',
        'public_field' => 'awards',
        'fields' => ['category' => 'Premio o reconocimiento', 'description' => 'Descripcion', 'date_start' => 'Fecha', 'location' => 'Entidad que lo concede'],
        'sortable' => true,
        'requires_title_description' => false,
        'allows_image' => true,
        'default_order' => 3,
    ],
    'custom_section' => [
        'title' => $memberProfile['custom_section_title'] ?? 'Seccion personalizada',
        'public_field' => 'custom_section',
        'fields' => ['category' => 'Titulo del articulo', 'description' => 'Descripcion', 'location' => 'Información adicional'],
        'sortable' => true,
        'requires_title_description' => false,
        'allows_image' => true,
        'default_order' => 4,
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
        // El bloque Inicio guarda un ano suelto en lugar de un par de fechas.
        foreach (['date_start', 'date_end', 'year'] as $dateField) {
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
 * Etiqueta corta de una entrada para el listado de la tarjeta. Por defecto usa
 * el par de fechas y el lugar del curriculum; las secciones que se resumen con
 * otros campos (el bloque Inicio, por ejemplo) lo indican en su configuracion.
 * El mismo calculo se repite en JavaScript al editar sin recargar la pagina.
 */
function cv_entry_meta_label(array $entry, array $sectionConfig = []): string
{
    $metaFields = is_array($sectionConfig['summary_meta'] ?? null) ? $sectionConfig['summary_meta'] : [];
    if ($metaFields !== []) {
        return implode(' · ', array_filter(
            array_map(static fn (string $field): string => clean_text((string) ($entry[$field] ?? '')), $metaFields),
            static fn (string $value): bool => $value !== ''
        ));
    }

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
                        <?php
                        $fieldType = (string) ($sectionConfig['field_types'][$fieldName] ?? (str_starts_with($fieldName, 'date_') ? 'date' : 'text'));
                        $fieldExtra = $fieldType === 'number' ? ' min="1900" max="2999" step="1" inputmode="numeric"' : '';
                        ?>
                        <label class="<?= $fieldName === ($sectionConfig['summary_title'] ?? 'category') ? 'cv-entry-category-field' : '' ?>">
                            <?= e($fieldLabel) ?>
                            <input name="<?= e($prefix) ?>[<?= e($fieldName) ?>]" type="<?= e($fieldType) ?>"<?= $fieldExtra ?> value="<?= e((string) ($entry[$fieldName] ?? '')) ?>" data-entry-field="<?= e($fieldName) ?>">
                        </label>
                    <?php endif; ?>
                <?php endforeach; ?>
                <div class="cv-entry-controls">
                    <label class="visibility-toggle">
                        <input type="checkbox" name="<?= e($prefix) ?>[is_active]" value="1" <?= $isActive ? 'checked' : '' ?> data-default-checked="1" data-entry-field="is_active">
                        <span><?= e((string) ($sectionConfig['active_label'] ?? 'Articulo activo en PDF')) ?></span>
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
    $titleField = (string) ($sectionConfig['summary_title'] ?? 'category');
    $hiddenLabel = (string) ($sectionConfig['hidden_label'] ?? 'Oculta en el PDF');
    $metaFields = is_array($sectionConfig['summary_meta'] ?? null) ? $sectionConfig['summary_meta'] : [];
    $entryImagePath = member_visible_asset_path((string) ($entry['image_path'] ?? ''));
    $entryTitle = clean_text((string) ($entry[$titleField] ?? ''));
    $entryMeta = cv_entry_meta_label($entry, $sectionConfig);
    $isActive = (bool) ($entry['is_active'] ?? true);
    ob_start();
    ?>
    <article class="member-entry-item" data-entry-item data-entry-title-field="<?= e($titleField) ?>" data-entry-meta-fields="<?= e(implode(',', $metaFields)) ?>" data-entry-hidden-label="<?= e($hiddenLabel) ?>">
        <button type="button" class="member-entry-open" data-entry-edit>
            <span class="member-entry-thumb">
                <img <?= $entryImagePath !== '' ? 'src="' . e($entryImagePath) . '"' : '' ?> alt="" loading="lazy" data-entry-thumb-image <?= $entryImagePath === '' ? 'hidden' : '' ?>>
                <span data-entry-thumb-placeholder <?= $entryImagePath !== '' ? 'hidden' : '' ?>>Sin imagen</span>
            </span>
            <span class="member-entry-text">
                <strong data-entry-title><?= e($entryTitle !== '' ? $entryTitle : 'Entrada sin titulo') ?></strong>
                <span class="member-entry-meta" data-entry-meta><?= e($entryMeta) ?></span>
            </span>
            <span class="member-entry-flag" data-entry-flag <?= $isActive ? 'hidden' : '' ?>><?= e($hiddenLabel) ?></span>
        </button>
        <div class="member-entry-actions">
            <button type="button" class="member-entry-action" data-entry-view>Ver</button>
            <button type="button" class="member-entry-action" data-entry-edit>Editar</button>
            <?php if (!empty($sectionConfig['toggle_action'])): ?>
                <button type="button" class="member-entry-action" data-entry-toggle><?= $isActive ? 'Ocultar' : 'Mostrar' ?></button>
            <?php endif; ?>
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

        // Articulos del bloque Inicio. Se reconstruyen siempre a partir de lo que
        // llega en el POST, que es de este usuario y solo de este usuario: el
        // perfil se lee de la sesion, nunca de un id del formulario, asi que no
        // hay forma de tocar los articulos de otro miembro manipulando indices.
        $memberProfile[$introSectionKey] = clean_cv_entries(
            $_POST,
            $introSectionKey,
            array_keys($introSectionConfig['fields']),
            $entryMediaOptions + ['title' => $introSectionTitle],
            is_array($memberProfile[$introSectionKey] ?? null) ? $memberProfile[$introSectionKey] : [],
            $_FILES,
            $profileErrors
        );
        $memberProfile[$introSectionKey] = sort_cv_entries($memberProfile[$introSectionKey], 'manual');

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

        if ($profileAction === 'update_profile' && !cv_curriculum_images_persisted($user, $memberProfile, $cvSectionConfig + [$introSectionKey => $introSectionConfig])) {
            $profileErrors[] = 'Una o varias imagenes del curriculum se han subido, pero no se han podido confirmar en la base de datos.';
        }

        if (!$profileErrors) {
            $profileMessages[] = $profileAction === 'update_profile_images'
                ? 'Imagenes actualizadas y guardadas correctamente.'
                : (profile_is_complete($memberProfile)
                    ? 'Perfil artistico actualizado.'
                    : 'Perfil guardado. Sigue pendiente completar nombre artistico, ciudad, provincia, fotografia principal y al menos una formacion, experiencia profesional o actuacion.');
        }

        /* Fase 1: ademas del texto de siempre (que se sigue guardando igual en
           perfil_json y en miembros.ciudad / provincia_texto), se resuelve la
           ubicacion contra las tablas normalizadas y se guardan las disciplinas.
           Es lo que permite filtrar el directorio por
           Artistas > Cordoba > Montilla > Baile.

           Va en su propio try: si falla, el perfil ya esta guardado y lo unico
           que se pierde es la clasificacion, no los datos del artista. */
        $perfilMiembroId = (int) ($user['member_db_id'] ?? 0);
        if ($panelPdo instanceof PDO && $perfilMiembroId > 0 && $profileAction === 'update_profile') {
            try {
                $geoPerfil = csf_geo_resolver(
                    $panelPdo,
                    (string) ($memberProfile['province'] ?? ''),
                    (string) ($memberProfile['city'] ?? '')
                );
                $panelPdo->prepare(
                    'UPDATE miembros SET provincia_id = :provincia_id, municipio_id = :municipio_id WHERE id = :id'
                )->execute([
                    'provincia_id' => $geoPerfil['provincia_id'],
                    'municipio_id' => $geoPerfil['municipio_id'],
                    'id' => $perfilMiembroId,
                ]);

                // Disciplinas: relacion N:M, se reescribe entera con lo marcado.
                $disciplinasEnviadas = array_map(
                    'strval',
                    is_array($_POST['disciplinas'] ?? null) ? $_POST['disciplinas'] : []
                );
                $panelPdo->prepare('DELETE FROM miembro_disciplinas WHERE miembro_id = :id')
                    ->execute(['id' => $perfilMiembroId]);

                if ($disciplinasEnviadas !== []) {
                    $marcadores = implode(',', array_fill(0, count($disciplinasEnviadas), '?'));
                    $consulta = $panelPdo->prepare(
                        'SELECT id FROM disciplinas WHERE estado = "ACTIVA" AND slug IN (' . $marcadores . ')'
                    );
                    $consulta->execute($disciplinasEnviadas);

                    $insertar = $panelPdo->prepare(
                        'INSERT IGNORE INTO miembro_disciplinas (miembro_id, disciplina_id) VALUES (:miembro_id, :disciplina_id)'
                    );
                    foreach ($consulta->fetchAll(PDO::FETCH_COLUMN) as $disciplinaId) {
                        $insertar->execute(['miembro_id' => $perfilMiembroId, 'disciplina_id' => (int) $disciplinaId]);
                    }
                }
            } catch (Throwable $excepcionGeo) {
                error_log('[panel-usuario fase1] clasificacion del perfil omitida: ' . $excepcionGeo->getMessage());
            }
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

/* =========================================================================
   FASE 1 RED SOCIAL — Eventos, puntos y redes sociales
   -------------------------------------------------------------------------
   Bloque autonomo: si no hay base de datos o el usuario todavia no tiene ficha
   de miembro, $fase1Activa queda a false y ni las tarjetas ni las pantallas se
   pintan. Nada de lo anterior cambia de comportamiento.
   ========================================================================= */

$miembroDbId = (int) ($user['member_db_id'] ?? 0);
$usuarioDbId = (int) ($user['db_id'] ?? 0);
$fase1Activa = $panelPdo instanceof PDO && $miembroDbId > 0 && $usuarioDbId > 0;

/**
 * Mensajes de una accion que termina en redireccion.
 *
 * Las acciones de puntos siguen patron POST-Redirect-GET: sin el, recargar la
 * pagina despues de promocionar reenviaria el formulario. Como el redirect
 * pierde las variables, el aviso viaja por sesion.
 */
function panel_flash_guardar(string $tipo, string $mensaje): void
{
    $_SESSION['csf_panel_flash'][] = ['tipo' => $tipo, 'mensaje' => $mensaje];
}

/**
 * @return array<int, array{tipo:string, mensaje:string}>
 */
function panel_flash_consumir(): array
{
    $avisos = is_array($_SESSION['csf_panel_flash'] ?? null) ? $_SESSION['csf_panel_flash'] : [];
    unset($_SESSION['csf_panel_flash']);

    return $avisos;
}

foreach (panel_flash_consumir() as $aviso) {
    if ($aviso['tipo'] === 'error') {
        $profileErrors[] = $aviso['mensaje'];
    } else {
        $profileMessages[] = $aviso['mensaje'];
    }
}

$panelAction = (string) ($_POST['panel_action'] ?? '');
$fase1Acciones = ['evento_guardar', 'evento_eliminar', 'evento_promocionar', 'redes_guardar', 'red_activar', 'puntos_comprar'];

if ($fase1Activa && $_SERVER['REQUEST_METHOD'] === 'POST' && in_array($panelAction, $fase1Acciones, true)) {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        panel_flash_guardar('error', 'La sesión ha caducado. Vuelve a intentarlo.');
        redirect_to('panel-usuario.php#inicio');
    }

    // Ancla a la que se vuelve tras la accion, para no perder el contexto.
    $destino = match ($panelAction) {
        'redes_guardar', 'red_activar' => 'mis-redes',
        'puntos_comprar' => 'mis-puntos',
        default => 'mis-eventos',
    };

    try {
        switch ($panelAction) {
            case 'evento_guardar':
                $eventoId = (int) ($_POST['evento_id'] ?? 0);
                $datosEvento = [
                    'titulo' => (string) ($_POST['titulo'] ?? ''),
                    'descripcion' => (string) ($_POST['descripcion'] ?? ''),
                    'fecha' => (string) ($_POST['fecha'] ?? ''),
                    'hora' => (string) ($_POST['hora'] ?? ''),
                    'fecha_fin' => (string) ($_POST['fecha_fin'] ?? ''),
                    'lugar' => (string) ($_POST['lugar'] ?? ''),
                    'direccion' => (string) ($_POST['direccion'] ?? ''),
                    'provincia' => (string) ($_POST['provincia'] ?? ''),
                    'municipio' => (string) ($_POST['municipio'] ?? ''),
                    'enlace_url' => (string) ($_POST['enlace_url'] ?? ''),
                    'video_url' => (string) ($_POST['video_url'] ?? ''),
                    'estado' => (string) ($_POST['estado'] ?? 'PUBLICADO'),
                ];

                $erroresEvento = csf_evento_validar($datosEvento);

                // La imagen se sube antes de decidir, pero solo si la validacion
                // paso: asi un formulario invalido no deja ficheros huerfanos.
                if (!$erroresEvento) {
                    $cartel = csf_evento_guardar_cartel($_FILES['imagen'] ?? null, $erroresEvento);
                    $datosEvento['imagen_path'] = $cartel ?? clean_text((string) ($_POST['imagen_actual'] ?? ''));
                }

                if ($erroresEvento) {
                    foreach ($erroresEvento as $errorEvento) {
                        panel_flash_guardar('error', $errorEvento);
                    }
                    redirect_to('panel-usuario.php?evento=' . ($eventoId > 0 ? $eventoId : 'nuevo') . '#evento-form');
                }

                $guardadoId = csf_evento_guardar(
                    $panelPdo,
                    $miembroDbId,
                    $usuarioDbId,
                    $eventoId > 0 ? $eventoId : null,
                    $datosEvento
                );
                panel_flash_guardar('ok', $eventoId > 0
                    ? 'Evento actualizado.'
                    : 'Evento creado. Ya aparece en la agenda de Con Sabor Flamenco.');
                break;

            case 'evento_eliminar':
                $eliminado = csf_evento_eliminar(
                    $panelPdo,
                    (int) ($_POST['evento_id'] ?? 0),
                    $miembroDbId,
                    $usuarioDbId
                );
                panel_flash_guardar(
                    $eliminado ? 'ok' : 'error',
                    $eliminado ? 'Evento eliminado.' : 'No se pudo eliminar el evento.'
                );
                break;

            case 'evento_promocionar':
                // El coste NO viene del formulario: lo pone csf_evento_promocionar().
                $promocion = csf_evento_promocionar(
                    $panelPdo,
                    (int) ($_POST['evento_id'] ?? 0),
                    $miembroDbId,
                    $usuarioDbId
                );
                panel_flash_guardar('ok', sprintf(
                    'Evento promocionado por %s. Te quedan %s.',
                    csf_puntos_formato($promocion['puntos']),
                    csf_puntos_formato($promocion['saldo'])
                ));
                break;

            case 'redes_guardar':
                $erroresRedes = [];
                csf_redes_guardar(
                    $panelPdo,
                    $miembroDbId,
                    $usuarioDbId,
                    is_array($_POST['redes'] ?? null) ? $_POST['redes'] : [],
                    $erroresRedes
                );
                foreach ($erroresRedes as $errorRed) {
                    panel_flash_guardar('error', $errorRed);
                }
                if (!$erroresRedes) {
                    panel_flash_guardar('ok', 'Redes sociales guardadas.');
                }
                break;

            case 'red_activar':
                // Igual que arriba: gratis el primero, tarifa a partir del segundo,
                // y lo decide el servidor.
                $activacion = csf_redes_activar_enlace(
                    $panelPdo,
                    $miembroDbId,
                    $usuarioDbId,
                    (string) ($_POST['red'] ?? '')
                );
                panel_flash_guardar('ok', $activacion['gratis']
                    ? 'Enlace de ' . csf_red_nombre($activacion['red']) . ' activado sin coste. Era tu enlace gratuito.'
                    : sprintf(
                        'Enlace de %s activado por %s. Te quedan %s.',
                        csf_red_nombre($activacion['red']),
                        csf_puntos_formato($activacion['puntos']),
                        csf_puntos_formato($activacion['saldo'])
                    ));
                break;

            case 'puntos_comprar':
                // Solo registra la intencion. NO acredita puntos: eso ocurrira
                // cuando Stripe confirme el pago de verdad.
                $intento = csf_puntos_crear_intento_compra(
                    $panelPdo,
                    $usuarioDbId,
                    (int) ($_POST['paquete'] ?? 0)
                );
                panel_flash_guardar('ok', sprintf(
                    'Solicitud guardada: %s por %s. Te avisaremos en cuanto el pago con Stripe esté disponible. Todavía no se ha realizado ningún cargo.',
                    csf_puntos_formato($intento['puntos']),
                    csf_puntos_formato_euros($intento['centimos'])
                ));
                break;
        }
    } catch (Throwable $excepcion) {
        panel_flash_guardar('error', $excepcion->getMessage());
        error_log('[panel-usuario fase1] ' . $panelAction . ': ' . $excepcion->getMessage());
    }

    redirect_to('panel-usuario.php#' . $destino);
}

// --- Datos de las pantallas nuevas ---------------------------------------

$puntosSaldo = 0;
$puntosResumen = ['saldo' => 0, 'total_ingresado' => 0, 'total_gastado' => 0];
$puntosMovimientos = [];
$eventosProximos = [];
$eventosPasados = [];
$eventosTotales = 0;
$proximosCount = 0;
$redesMiembro = [];
$redesActivas = 0;
$redesCosteSiguiente = 0;
$eventoEnEdicion = null;
$provinciasLista = [];
$municipiosLista = [];
$municipiosPerfil = [];
$disciplinasCatalogo = [];
$disciplinasMiembro = [];

if ($fase1Activa) {
    try {
        // Los puntos de bienvenida se abonan la primera vez que se abre el panel.
        // Es idempotente, asi que da igual cuantas veces se recargue.
        csf_puntos_asegurar_alta($panelPdo, $usuarioDbId, $memberTier);

        $puntosResumen = csf_puntos_resumen($panelPdo, $usuarioDbId);
        $puntosSaldo = $puntosResumen['saldo'];
        $puntosMovimientos = csf_puntos_movimientos($panelPdo, $usuarioDbId, 30);

        $eventosProximos = csf_evento_listar_miembro($panelPdo, $miembroDbId, 'proximos');
        $eventosPasados = csf_evento_listar_miembro($panelPdo, $miembroDbId, 'pasados');
        $eventosTotales = count($eventosProximos) + count($eventosPasados);
        $proximosCount = csf_evento_contar_proximos($panelPdo, $miembroDbId);

        $redesMiembro = csf_redes_de_miembro($panelPdo, $miembroDbId);
        $redesActivas = csf_redes_contar_activas($panelPdo, $miembroDbId);
        $redesCosteSiguiente = csf_redes_coste_activacion($panelPdo, $miembroDbId);

        $provinciasLista = csf_geo_provincias($panelPdo);

        // Catalogo de disciplinas y las que tiene marcadas este miembro.
        $disciplinasCatalogo = $panelPdo
            ->query('SELECT slug, nombre FROM disciplinas WHERE estado = "ACTIVA" ORDER BY nombre ASC')
            ->fetchAll(PDO::FETCH_ASSOC);
        $consultaDisciplinas = $panelPdo->prepare(
            'SELECT d.slug FROM miembro_disciplinas md
             INNER JOIN disciplinas d ON d.id = md.disciplina_id
             WHERE md.miembro_id = :miembro_id'
        );
        $consultaDisciplinas->execute(['miembro_id' => $miembroDbId]);
        $disciplinasMiembro = $consultaDisciplinas->fetchAll(PDO::FETCH_COLUMN);

        // Municipios ya conocidos de la provincia del perfil, para el autocompletado.
        $provinciaPerfil = csf_geo_provincia_por_texto($panelPdo, (string) ($memberProfile['province'] ?? ''));
        if ($provinciaPerfil !== null) {
            $municipiosPerfil = csf_geo_municipios($panelPdo, $provinciaPerfil['id']);
        }

        // ?evento=123 abre el formulario con ese evento cargado; ?evento=nuevo
        // (o cualquier otro valor) lo abre vacio.
        $eventoSolicitado = (int) ($_GET['evento'] ?? 0);
        if ($eventoSolicitado > 0) {
            $eventoEnEdicion = csf_evento_obtener_de_miembro($panelPdo, $eventoSolicitado, $miembroDbId);
        }

        $provinciaFormulario = $eventoEnEdicion !== null
            ? (int) $eventoEnEdicion['provincia_id']
            : 0;
        if ($provinciaFormulario > 0) {
            $municipiosLista = csf_geo_municipios($panelPdo, $provinciaFormulario);
        }
    } catch (Throwable $excepcion) {
        // Un entorno sin migrar no debe tumbar el panel entero.
        $fase1Activa = false;
        error_log('[panel-usuario fase1] datos no disponibles: ' . $excepcion->getMessage());
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
    'awards' => 'premios',
    'custom_section' => 'seccion-personalizada',
];
$cvSectionNotes = [
    'education' => 'Titulos, cursos y maestros con los que te has formado.',
    'experience' => 'Companias, tablaos, giras y trabajos que has firmado.',
    'awards' => 'Premios, distinciones y reconocimientos recibidos.',
    'custom_section' => 'Un apartado libre para lo que no encaja en los anteriores.',
];
$cvSectionImages = [
    'education' => 'assets/images/community/academia-flamenca.webp',
    'experience' => 'assets/images/community/evento-flamenco.webp',
    'awards' => 'assets/images/member-cards/pegatina-con-sabor-flamenco.png',
    'custom_section' => 'assets/images/community/pena-flamenca.webp',
];
$introEntries = is_array($memberProfile[$introSectionKey] ?? null) ? array_values($memberProfile[$introSectionKey]) : [];
$introMetrics = cv_section_metrics($introEntries);
$introVisibleCount = $introMetrics['active'];

$cvSectionEntries = [];
$cvSectionMetrics = [];
foreach ($cvSectionConfig as $sectionKey => $sectionConfig) {
    $cvSectionEntries[$sectionKey] = is_array($memberProfile[$sectionKey] ?? null)
        ? array_values($memberProfile[$sectionKey])
        : [];
    $cvSectionMetrics[$sectionKey] = cv_section_metrics($cvSectionEntries[$sectionKey]);
}
$totalCvEntries = array_sum(array_column($cvSectionMetrics, 'total'));

// Un slide cuenta como creado cuando tiene imagen o algun texto: la estructura
// reserva siempre tres huecos, pero vacios no son contenido.
$webSlidesFilled = count(array_filter(
    $webSlides,
    static fn ($slide): bool => is_array($slide) && implode('', array_map('strval', $slide)) !== ''
));
$webBlocksWithContent = count(array_filter([
    $webSlidesFilled,
    count($webGallery),
    count($webVideos),
    count($webEvents),
    count($webNews),
]));

/**
 * Cabecera de una pantalla de la web publica, con su contador y la vuelta al
 * hub. Misma composicion que el resto de pantallas del panel.
 */
function web_section_heading(string $title, string $note, int $total, string $one, string $many): string
{
    ob_start();
    ?>
    <div class="member-panel-heading">
        <div class="member-panel-heading-main">
            <div>
                <p class="section-kicker">Mi pagina web</p>
                <h2><?= e($title) ?></h2>
                <p><?= e($note) ?></p>
            </div>
            <span class="member-heading-count"><?= e($total . ' ' . ($total === 1 ? $one : $many)) ?></span>
        </div>
    </div>
    <?php

    return (string) ob_get_clean();
}

/**
 * Iconos de las tarjetas del panel. Trazo simple sobre la rejilla de 24 del
 * resto del sitio, sin dependencias ni ficheros nuevos.
 */
function panel_icon(string $name): string
{
    $paths = [
        'perfil'    => '<circle cx="12" cy="8.5" r="3.7"/><path d="M4.8 20.2c1-3.7 4-5.6 7.2-5.6s6.2 1.9 7.2 5.6"/>',
        'curriculum'=> '<path d="M6 3h8l4 4v14H6z"/><path d="M14 3v4h4"/><path d="M9 12h6M9 16h6"/>',
        'web'       => '<circle cx="12" cy="12" r="8.5"/><path d="M3.5 12h17M12 3.5c2.4 2.6 2.4 14.4 0 17M12 3.5c-2.4 2.6-2.4 14.4 0 17"/>',
        'ver'       => '<path d="M14 4h6v6"/><path d="M20 4 11 13"/><path d="M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5"/>',
        'inicio'    => '<path d="M4 6h16M4 12h10M4 18h7"/>',
        'galeria'   => '<rect x="3.5" y="5" width="17" height="14" rx="2"/><circle cx="9" cy="10" r="1.6"/><path d="m4.5 17 4.7-4.3 3.4 3 2.6-2.2 4.3 3.9"/>',
        'video'     => '<rect x="3" y="6" width="12.5" height="12" rx="2"/><path d="m16.5 13.2 4.5 3V7.8l-4.5 3z"/>',
        'agenda'    => '<rect x="3.5" y="5" width="17" height="15" rx="2"/><path d="M3.5 10h17M8 3v4M16 3v4"/>',
        'actualidad'=> '<path d="M5 5h11v14H5z"/><path d="M16 9h3v8a2 2 0 0 1-2 2"/><path d="M8 9h5M8 13h5M8 16h3"/>',
        'contacto'  => '<rect x="3" y="5.5" width="18" height="13" rx="2"/><path d="m3.6 7 8.4 6 8.4-6"/>',
        'tarjeta'   => '<rect x="3" y="5.5" width="18" height="13" rx="2"/><path d="M3 10h18M7 14.5h4"/>',
        'banners'   => '<path d="M4 6h16v7H4z"/><path d="M8 13v5l4-2.4 4 2.4v-5"/>',
        'servicios' => '<path d="m12 3.6 2.4 5 5.5.8-4 3.9.9 5.4-4.8-2.5-4.8 2.5.9-5.4-4-3.9 5.5-.8z"/>',
        'seguridad' => '<rect x="5" y="10.5" width="14" height="9.5" rx="2"/><path d="M8.4 10.5V8a3.6 3.6 0 0 1 7.2 0v2.5"/>',
        'academia'  => '<path d="m3.5 9 8.5-4.5L20.5 9 12 13.5z"/><path d="M7 11v4.5c0 1.4 2.2 2.5 5 2.5s5-1.1 5-2.5V11"/>',
        'puntos'    => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5v9M9.6 9.8h3.6a1.9 1.9 0 0 1 0 3.8h-3.2a1.9 1.9 0 0 0 0 3.8h3.8"/>',
        'redes'     => '<circle cx="6.5" cy="12" r="2.6"/><circle cx="17.5" cy="6.5" r="2.6"/><circle cx="17.5" cy="17.5" r="2.6"/><path d="m8.9 10.8 6.2-3M8.9 13.2l6.2 3"/>',
    ];

    return '<svg class="member-tile-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
        . ($paths[$name] ?? $paths['perfil']) . '</svg>';
}

/**
 * Portada del panel en tres bloques. Las herramientas principales primero, los
 * contenidos de la web despues y por ultimo los servicios y la cuenta.
 */
$panelPrimaryCards = [
    [
        'target' => 'perfil',
        'icon' => 'perfil',
        'title' => 'Mi perfil',
        'note' => 'Gestiona tus datos personales, identidad artistica, fotografia, ubicacion y contacto.',
        'metric' => 'Perfil completo ' . $profileCompletion . '%',
    ],
];

// Fase 1: agenda, cartera y redes. Van delante del resto porque son el nuevo
// centro del panel. Las tarjetas anteriores siguen justo detras, intactas.
if ($fase1Activa) {
    $panelPrimaryCards[] = [
        'target' => 'mis-eventos',
        'icon' => 'agenda',
        'title' => 'Mis eventos',
        'note' => 'Publica tus actuaciones en la agenda de Con Sabor Flamenco. Crear un evento es gratis.',
        'metric' => $proximosCount === 1 ? '1 próximo evento' : $proximosCount . ' próximos eventos',
    ];
    $panelPrimaryCards[] = [
        'target' => 'mis-puntos',
        'icon' => 'puntos',
        'title' => 'Mis puntos',
        'note' => 'Tu cartera. Los puntos dan visibilidad extra a tus eventos y a tus enlaces.',
        'metric' => csf_puntos_formato($puntosSaldo) . ' disponibles',
    ];
    $panelPrimaryCards[] = [
        'target' => 'mis-redes',
        'icon' => 'redes',
        'title' => 'Redes sociales',
        'note' => 'Muestra todas tus redes. El primer enlace clicable es gratuito.',
        'metric' => $redesActivas === 1 ? '1 enlace activo' : $redesActivas . ' enlaces activos',
    ];
}

$panelPrimaryCards[] = [
    'target' => 'curriculum',
    'icon' => 'curriculum',
    'title' => 'Mi curriculum',
    'note' => 'Configura tu curriculum artistico, formacion, experiencia, premios y trayectoria profesional.',
    'metric' => $totalCvEntries === 1 ? '1 entrada' : $totalCvEntries . ' entradas',
    'metric_id' => 'curriculum-total',
];
if ($hasWebPage) {
    $panelPrimaryCards[] = [
        'target' => 'pagina-web',
        'icon' => 'web',
        'title' => 'Mi pagina web',
        'note' => 'Gestiona todo el contenido publico de tu espacio en Con Sabor Flamenco.',
        'metric' => $webBlocksWithContent === 1 ? '1 bloque con contenido' : $webBlocksWithContent . ' bloques con contenido',
    ];
    $panelPrimaryCards[] = [
        'href' => $publicProfileUrl,
        'external' => true,
        'icon' => 'ver',
        'title' => 'Ver mi web',
        'note' => 'Accede directamente a tu microweb publica, tal y como la ve el visitante.',
        'metric' => $memberTypePrefix . '/' . $publicSlug,
    ];
}

// Atajos a cada bloque de la web. No son pantallas nuevas: llevan al bloque
// correspondiente dentro de "Mi pagina web", que se guarda de una sola vez.
$panelWebCards = [];
if ($hasWebPage) {
    $webUnit = static fn (int $total, string $one, string $many): string => $total . ' ' . ($total === 1 ? $one : $many);

    // Un unico juego de tarjetas: lo usa el hub de "Mi pagina web" y tambien el
    // bloque "Contenido de tu web" de la portada, para no describir lo mismo
    // en dos sitios distintos.
    $webSectionCards = [
        ['target' => 'inicio-articulos', 'icon' => 'inicio', 'title' => 'Inicio', 'note' => 'La presentacion que abre tu web, debajo de la cabecera.', 'metric' => $webUnit($introMetrics['total'], 'articulo', 'articulos'), 'metric_section' => $introSectionKey],
        ['target' => 'web-slider', 'icon' => 'galeria', 'title' => 'Slider de cabecera', 'note' => 'Las imagenes principales de la cabecera de tu microweb.', 'metric' => $webUnit($webSlidesFilled, 'cabecera', 'cabeceras')],
        ['target' => 'web-galeria', 'icon' => 'galeria', 'title' => 'Galeria', 'note' => 'Las fotografias que quieres mostrar publicamente.', 'metric' => $webUnit(count($webGallery), 'fotografia', 'fotografias')],
        ['target' => 'web-videos', 'icon' => 'video', 'title' => 'Videos', 'note' => 'Tus videos publicos de YouTube, Vimeo u otra plataforma.', 'metric' => $webUnit(count($webVideos), 'video', 'videos')],
        ['target' => 'web-eventos', 'icon' => 'agenda', 'title' => 'Agenda', 'note' => 'Actuaciones, cursos, festivales y proximos eventos.', 'metric' => $webUnit(count($webEvents), 'evento', 'eventos'), 'legacy' => 'mis-eventos'],
        ['target' => 'web-actualidad', 'icon' => 'actualidad', 'title' => 'Actualidad', 'note' => 'Noticias, comunicados y novedades de tu actividad.', 'metric' => $webUnit(count($webNews), 'publicacion', 'publicaciones')],
        ['target' => 'web-redes', 'icon' => 'contacto', 'title' => 'Redes sociales', 'note' => 'Los enlaces sociales de la cabecera de tu microweb.', 'metric' => $webUnit(count(array_filter($webSocialLinks)), 'red configurada', 'redes configuradas'), 'legacy' => 'mis-redes'],
        ['target' => 'web-contacto', 'icon' => 'contacto', 'title' => 'Contacto', 'note' => 'Que datos de contacto se muestran en tu web.', 'metric' => $webUnit(count($webContactFields), 'dato visible', 'datos visibles')],
    ];

    /* Tarjetas sustituidas por la fase 1.
       "Agenda" (web-eventos) la sustituye "Mis eventos"; "Redes sociales"
       (web-redes) la sustituye la pantalla de redes con puntos.

       No se borran: la clave 'legacy' solo las retira de la PORTADA cuando la
       fase 1 esta activa. Sus pantallas, sus formularios y sus datos siguen
       intactos y accesibles por su ancla (#web-eventos, #web-redes), asi que
       nadie pierde lo que tenia. Para volver a mostrarlas basta con poner
       $ocultarTarjetasSustituidas a false. */
    $ocultarTarjetasSustituidas = $fase1Activa;

    $panelWebCards = $ocultarTarjetasSustituidas
        ? array_values(array_filter($webSectionCards, static fn (array $card): bool => !isset($card['legacy'])))
        : $webSectionCards;
}

$panelAccountCards = [
    ['target' => 'tarjeta-miembro', 'icon' => 'tarjeta', 'title' => 'Tarjeta de miembro', 'note' => 'Tu carnet digital y el QR para compartirlo.', 'metric' => $memberStatus],
    ['target' => 'banners', 'icon' => 'banners', 'title' => 'Banners', 'note' => 'Espacios publicitarios contratables en tu provincia.'],
    ['href' => 'servicios.php', 'external' => true, 'icon' => 'servicios', 'title' => 'Servicios', 'note' => 'Servicios digitales de Con Sabor Flamenco.'],
    ['target' => 'seguridad', 'icon' => 'seguridad', 'title' => 'Seguridad', 'note' => 'Contrasena de acceso y ajustes de la cuenta.'],
];
if ($academiaPanelLink) {
    $panelAccountCards[] = ['href' => 'panel-academia.php', 'icon' => 'academia', 'title' => 'Mi academia', 'note' => 'Alumnos, profesores, cursos, grupos y matriculas.'];
}
if ($alumnoPanelLink) {
    $panelAccountCards[] = ['href' => 'panel-alumno.php', 'icon' => 'academia', 'title' => 'Mis clases', 'note' => 'Los cursos en los que estas matriculado.'];
}

/**
 * Tarjeta de la portada. `href` la convierte en enlace externo al panel;
 * `focus` desplaza hasta un bloque concreto de la pantalla de destino.
 */
function panel_tile_markup(array $card, string $size = 'lg'): string
{
    $isExternal = !empty($card['external']) || isset($card['href']);
    $href = $isExternal ? (string) $card['href'] : '#' . $card['target'];
    ob_start();
    ?>
    <a class="member-tile member-tile-<?= e($size) ?>"
       href="<?= e($href) ?>"
       <?= $isExternal ? '' : 'data-panel-link="' . e((string) $card['target']) . '"' ?>
       <?= isset($card['focus']) ? 'data-panel-focus="' . e((string) $card['focus']) . '"' : '' ?>
       <?= !empty($card['external']) ? 'target="_blank" rel="noopener"' : '' ?>>
        <span class="member-tile-badge"><?= panel_icon((string) ($card['icon'] ?? 'perfil')) ?></span>
        <span class="member-tile-body">
            <strong><?= e((string) $card['title']) ?></strong>
            <?php if (!empty($card['legacy'])): ?>
                <?php /* Bloque sustituido por una pantalla nueva. Se sigue
                         pudiendo editar desde aqui, pero ya no es el camino
                         principal, asi que se avisa. */ ?>
                <span class="member-tile-legacy">Versión anterior</span>
            <?php endif; ?>
            <?php if (!empty($card['note'])): ?>
                <span class="member-tile-note"><?= e((string) $card['note']) ?></span>
            <?php endif; ?>
        </span>
        <?php if (isset($card['metric']) && $card['metric'] !== ''): ?>
            <span class="member-tile-metric"<?= isset($card['metric_section']) ? ' data-nav-metric="' . e((string) $card['metric_section']) . '"' : '' ?><?= isset($card['metric_id']) ? ' data-panel-metric="' . e((string) $card['metric_id']) . '"' : '' ?>><?= e((string) $card['metric']) ?></span>
        <?php endif; ?>
        <span class="member-tile-go" aria-hidden="true"><?= $isExternal ? 'Abrir' : 'Gestionar' ?> &rarr;</span>
    </a>
    <?php

    return (string) ob_get_clean();
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
                    <?php /* Datos que antes vivian en la barra lateral. */ ?>
                    <ul class="member-dashboard-meta">
                        <li><?= e($memberStatus) ?></li>
                        <li>Nº <?= e($memberNumber) ?></li>
                        <li class="member-dashboard-progress">
                            <span>Perfil <?= e((string) $profileCompletion) ?>%</span>
                            <span class="member-dashboard-bar" aria-hidden="true"><i style="width: <?= e((string) $profileCompletion) ?>%"></i></span>
                        </li>
                    </ul>
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
            <div class="member-panel-content">
                <?php /* Sin barra lateral: la orientacion la dan la portada de
                        tarjetas y estas migas, que nombran la pantalla actual. */ ?>
                <nav class="member-panel-back" data-panel-back aria-label="Migas de pan" hidden>
                    <a href="#inicio" data-panel-link="inicio">&larr; Volver al panel</a>
                    <span class="member-panel-crumb" data-panel-crumb-parent hidden></span>
                    <span class="member-panel-crumb" data-panel-crumb></span>
                </nav>
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
                    <?php if ($fase1Activa): ?>
                        <?php /* Resumen de un vistazo: quien eres, con cuanto
                                 cuentas y que puedes hacer ahora mismo. */ ?>
                        <div class="csf-panel-summary">
                            <div>
                                <p class="csf-panel-greeting">Hola, <?= e($displayName) ?></p>
                                <p class="csf-panel-greeting-note"><?= e($memberTypeLabel) ?><?= $memberProfile['city'] !== '' ? ' · ' . e($memberProfile['city']) : '' ?></p>
                            </div>

                            <ul class="csf-panel-stats">
                                <li class="csf-panel-stat">
                                    <strong><?= e((string) $puntosSaldo) ?></strong>
                                    <span><?= e($puntosSaldo === 1 ? 'punto disponible' : 'puntos disponibles') ?></span>
                                </li>
                                <li class="csf-panel-stat">
                                    <strong><?= e((string) $proximosCount) ?></strong>
                                    <span><?= e($proximosCount === 1 ? 'próximo evento' : 'próximos eventos') ?></span>
                                </li>
                                <li class="csf-panel-stat">
                                    <strong><?= e((string) $profileCompletion) ?>%</strong>
                                    <span>perfil completado</span>
                                    <span class="csf-panel-progress" aria-hidden="true"><i style="width: <?= e((string) $profileCompletion) ?>%"></i></span>
                                </li>
                            </ul>

                            <div class="csf-panel-cta">
                                <a class="button button-primary" href="#evento-form" data-panel-link="evento-form">Crear evento</a>
                                <a class="button button-secondary" href="#perfil" data-panel-link="perfil">Editar perfil</a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <header class="member-home-intro">
                        <p class="section-kicker">Tu panel</p>
                        <h2>¿Que quieres hacer hoy?</h2>
                        <p>Gestiona tu perfil, curriculum, pagina web y servicios desde un unico lugar.</p>
                    </header>

                    <div class="member-tile-grid member-tile-grid-lg">
                        <?php foreach ($panelPrimaryCards as $card): ?><?= panel_tile_markup($card) ?><?php endforeach; ?>
                    </div>

                    <?php if ($panelWebCards): ?>
                        <div class="member-home-group">
                            <h3 class="member-home-group-title">Contenido de tu web</h3>
                            <div class="member-tile-grid member-tile-grid-sm">
                                <?php foreach ($panelWebCards as $card): ?><?= panel_tile_markup($card, 'sm') ?><?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="member-home-group">
                        <h3 class="member-home-group-title">Servicios y cuenta</h3>
                        <div class="member-tile-grid member-tile-grid-md">
                            <?php foreach ($panelAccountCards as $card): ?><?= panel_tile_markup($card, 'md') ?><?php endforeach; ?>
                        </div>
                    </div>
                </section>

                <div class="member-profile-editor">
                    <form class="member-profile-form member-profile-cards cv-editor" id="member-profile-form" action="panel-usuario.php#perfil" method="post" enctype="multipart/form-data" data-profile-form data-panel-form hidden>
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
                            <?php /* Cada bloque muestra un resumen y despliega bajo demanda el
                                     formulario que ya existia, sin cambiar ningun campo. */ ?>
                            <article class="member-detail" data-detail>
                                <header class="member-detail-head">
                                    <div class="member-detail-summary">
                                        <p class="member-detail-kicker">Identidad artistica</p>
                                        <dl>
                                            <div><dt>Nombre publico</dt><dd><?= e($displayName) ?></dd></div>
                                            <div><dt>Cuenta</dt><dd><?= e((string) ($user['name'] ?? '—')) ?></dd></div>
                                            <div><dt>Especialidad</dt><dd><?= e($memberProfile['artistic_headline'] !== '' ? $memberProfile['artistic_headline'] : '—') ?></dd></div>
                                            <div><dt>Tipo de espacio</dt><dd><?= e($memberTypeLabel) ?></dd></div>
                                            <div><dt>URL publica</dt><dd><?= e($memberTypePrefix) ?>/<?= e($publicSlug) ?></dd></div>
                                        </dl>
                                    </div>
                                    <button type="button" class="button button-secondary" data-detail-toggle aria-expanded="false">Editar</button>
                                </header>
                                <div class="member-detail-form" data-detail-form hidden>
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
                                </div>
                            </article>

                            <article class="member-detail" data-detail>
                                <header class="member-detail-head">
                                    <div class="member-detail-summary">
                                        <p class="member-detail-kicker">Ubicacion y contacto</p>
                                        <dl>
                                            <div><dt>Localidad</dt><dd><?= e($memberProfile['city'] !== '' ? $memberProfile['city'] : '—') ?></dd></div>
                                            <div><dt>Provincia</dt><dd><?= e($memberProfile['province'] !== '' ? $memberProfile['province'] : '—') ?></dd></div>
                                            <div><dt>Email</dt><dd><?= e((string) ($user['email'] ?? '—')) ?></dd></div>
                                            <div><dt>Telefono</dt><dd><?= e($memberProfile['phone'] !== '' ? $memberProfile['phone'] : '—') ?></dd></div>
                                        </dl>
                                    </div>
                                    <button type="button" class="button button-secondary" data-detail-toggle aria-expanded="false">Editar</button>
                                </header>
                                <div class="member-detail-form" data-detail-form hidden>
                                    <fieldset class="cv-fieldset profile-data-fieldset">
                                        <legend>
                                            <span>Datos visibles</span>
                                            <strong>Ubicacion y contacto</strong>
                                            <em>Donde estas y como pueden localizarte desde tu ficha publica.</em>
                                        </legend>
                                        <div class="form-grid-three">
                                            <label for="city">Municipio
                                                <?php /* Se llama city por compatibilidad: es el nombre que ya
                                                         guardaba el perfil y que lee todo el codigo anterior.
                                                         La lista sugiere municipios ya registrados en la
                                                         provincia, pero admite escribir uno nuevo. */ ?>
                                                <input id="city" name="city" type="text" value="<?= e($memberProfile['city']) ?>" required list="csf-municipios-perfil">
                                                <?php if ($fase1Activa): ?>
                                                    <datalist id="csf-municipios-perfil">
                                                        <?php foreach ($municipiosPerfil as $municipioPerfil): ?>
                                                            <option value="<?= e($municipioPerfil['nombre']) ?>"></option>
                                                        <?php endforeach; ?>
                                                    </datalist>
                                                <?php endif; ?>
                                            </label>
                                            <label for="province">Provincia
                                                <?php if ($fase1Activa && $provinciasLista): ?>
                                                    <select id="province" name="province" required>
                                                        <?php
                                                        $provinciaActualPerfil = clean_text((string) $memberProfile['province']);
                                                        $provinciaReconocida = false;
                                                        foreach ($provinciasLista as $provinciaOpcion) {
                                                            if ($provinciaOpcion['nombre'] === $provinciaActualPerfil) {
                                                                $provinciaReconocida = true;
                                                                break;
                                                            }
                                                        }
                                                        ?>
                                                        <option value="">Elige tu provincia</option>
                                                        <?php if (!$provinciaReconocida && $provinciaActualPerfil !== ''): ?>
                                                            <?php /* Lo que ya tuviera escrito no se pierde por
                                                                     pasar a lista cerrada: se conserva como opcion. */ ?>
                                                            <option value="<?= e($provinciaActualPerfil) ?>" selected><?= e($provinciaActualPerfil) ?> (sin normalizar)</option>
                                                        <?php endif; ?>
                                                        <?php foreach ($provinciasLista as $provinciaOpcion): ?>
                                                            <option value="<?= e($provinciaOpcion['nombre']) ?>"<?= $provinciaActualPerfil === $provinciaOpcion['nombre'] ? ' selected' : '' ?>><?= e($provinciaOpcion['nombre']) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                <?php else: ?>
                                                    <input id="province" name="province" type="text" value="<?= e($memberProfile['province']) ?>" required>
                                                <?php endif; ?>
                                            </label>
                                            <label for="birth_place">Lugar de origen
                                                <input id="birth_place" name="birth_place" type="text" value="<?= e($memberProfile['birth_place']) ?>">
                                            </label>
                                        </div>

                                        <?php if ($fase1Activa && $disciplinasCatalogo): ?>
                                            <?php /* Tipo de artista. Relacion N:M: se pueden marcar varias y
                                                     anadir especialidades nuevas es insertar una fila en
                                                     `disciplinas`, sin tocar codigo ni esquema. */ ?>
                                            <div class="member-field-block">
                                                <p class="member-field-title">Tipo de artista</p>
                                                <p class="csf-field-hint">Marca todas las que practiques. Se usan para que te encuentren en el directorio y en la agenda.</p>
                                                <div class="csf-discipline-picker">
                                                    <?php foreach ($disciplinasCatalogo as $disciplinaOpcion): ?>
                                                        <label class="csf-discipline-option">
                                                            <input type="checkbox" name="disciplinas[]" value="<?= e($disciplinaOpcion['slug']) ?>"<?= in_array($disciplinaOpcion['slug'], $disciplinasMiembro, true) ? ' checked' : '' ?>>
                                                            <span><?= e($disciplinaOpcion['nombre']) ?></span>
                                                        </label>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        <div class="form-grid-three">
                                            <label for="phone">Telefono / WhatsApp
                                                <input id="phone" name="phone" type="text" value="<?= e($memberProfile['phone']) ?>">
                                            </label>
                                            <label for="website_url">Web
                                                <input id="website_url" name="website_url" type="url" value="<?= e($memberProfile['website_url']) ?>" placeholder="https://...">
                                            </label>
                                            <label for="instagram_url">Instagram
                                                <input id="instagram_url" name="instagram_url" type="url" value="<?= e($memberProfile['instagram_url']) ?>" placeholder="https://instagram.com/...">
                                            </label>
                                        </div>
                                    </fieldset>
                                </div>
                            </article>

                            <article class="member-detail" data-detail>
                                <header class="member-detail-head">
                                    <div class="member-detail-summary">
                                        <p class="member-detail-kicker">Trayectoria y disponibilidad</p>
                                        <dl>
                                            <div><dt>Trayectoria</dt><dd><?= e($memberProfile['years_active'] !== '' ? $memberProfile['years_active'] : '—') ?></dd></div>
                                            <div><dt>Disponibilidad</dt><dd><?= e($memberProfile['availability'] !== '' ? $memberProfile['availability'] : '—') ?></dd></div>
                                        </dl>
                                    </div>
                                    <button type="button" class="button button-secondary" data-detail-toggle aria-expanded="false">Editar</button>
                                </header>
                                <div class="member-detail-form" data-detail-form hidden>
                                    <fieldset class="cv-fieldset">
                                        <legend>
                                            <span>Datos visibles</span>
                                            <strong>Trayectoria y disponibilidad</strong>
                                            <em>Se muestran en tu ficha publica y, si lo activas, en el curriculum PDF.</em>
                                        </legend>
                                        <div class="form-grid-two">
                                            <label for="years_active">Anos de trayectoria
                                                <input id="years_active" name="years_active" type="text" value="<?= e($memberProfile['years_active']) ?>" placeholder="Ej. Desde 2012">
                                            </label>
                                            <label for="availability">Disponibilidad
                                                <input id="availability" name="availability" type="text" value="<?= e($memberProfile['availability']) ?>" placeholder="Clases, tablaos, festivales, eventos...">
                                            </label>
                                        </div>
                                    </fieldset>
                                </div>
                            </article>

                            <article class="member-detail member-detail-photo">
                                <header class="member-detail-head">
                                    <div class="member-detail-summary">
                                        <p class="member-detail-kicker">Imagen de perfil</p>
                                        <div class="member-detail-photo-row">
                                            <span class="member-detail-thumb">
                                                <?php if ($mainPhotoVisiblePath !== ''): ?>
                                                    <img src="<?= e($mainPhotoVisiblePath) ?>" alt="Fotografia principal de <?= e($displayName) ?>" loading="lazy" data-main-photo-preview>
                                                <?php else: ?>
                                                    <img alt="Fotografia principal de <?= e($displayName) ?>" loading="lazy" data-main-photo-preview hidden>
                                                    <span data-main-photo-placeholder>Sin foto</span>
                                                <?php endif; ?>
                                            </span>
                                            <p class="field-help">Cada espacio debe tener al menos una fotografia principal. JPG, PNG o WebP, maximo 5 MB. Se guarda automaticamente al seleccionarla.</p>
                                        </div>
                                    </div>
                                    <button type="button" class="button button-secondary" data-main-photo-trigger>Cambiar imagen</button>
                                </header>
                                <input id="main_photo" name="main_photo" type="file" accept="image/jpeg,image/png,image/webp" data-main-photo-input hidden>
                            </article>
                        </section>

                        <?php /* Hub del curriculum: no duplica ninguna pantalla, solo agrupa
                                 las secciones, los ajustes del PDF y la impresion. */ ?>
                        <section id="curriculum" class="content-section member-panel-section">
                            <div class="member-panel-heading">
                                <div class="member-panel-heading-main">
                                    <div>
                                        <p class="section-kicker">Mi curriculum</p>
                                        <h2>Curriculum artistico</h2>
                                        <p>Formacion, experiencia, premios y trayectoria. Se imprime en PDF con el diseno que configures aqui.</p>
                                    </div>
                                    <button class="button button-primary" type="button" onclick="window.print()">Descargar PDF</button>
                                </div>
                                <div class="member-kpi-grid">
                                    <article class="member-kpi">
                                        <span>Entradas</span>
                                        <strong data-panel-metric="curriculum-total"><?= e((string) $totalCvEntries) ?></strong>
                                    </article>
                                    <article class="member-kpi">
                                        <span>Secciones</span>
                                        <strong><?= e((string) count($cvSectionConfig)) ?></strong>
                                    </article>
                                    <article class="member-kpi">
                                        <span>Fondo de cabecera</span>
                                        <strong><?= $cvHeaderBackground !== '' ? 'Personalizado' : 'Por defecto' ?></strong>
                                    </article>
                                </div>
                            </div>

                            <div class="member-tile-grid member-tile-grid-md">
                                <?php foreach ($cvSectionConfig as $sectionKey => $sectionConfig): ?>
                                    <?= panel_tile_markup([
                                        'target' => $cvSectionAnchors[$sectionKey],
                                        'icon' => 'curriculum',
                                        'title' => (string) $sectionConfig['title'],
                                        'note' => $cvSectionNotes[$sectionKey],
                                        'metric' => $cvSectionMetrics[$sectionKey]['total'] === 1 ? '1 entrada' : $cvSectionMetrics[$sectionKey]['total'] . ' entradas',
                                        'metric_section' => $sectionKey,
                                    ], 'md') ?>
                                <?php endforeach; ?>
                            </div>

                            <article class="member-detail" data-detail>
                                <header class="member-detail-head">
                                    <div class="member-detail-summary">
                                        <p class="member-detail-kicker">Diseno del PDF</p>
                                        <dl>
                                            <div><dt>Fondo de cabecera</dt><dd><?= $cvHeaderBackground !== '' ? 'Personalizado' : 'Sin fondo personalizado' ?></dd></div>
                                            <div><dt>Datos profesionales</dt><dd><?= !empty($memberProfile['print_professional_data']) ? 'Se imprimen' : 'No se imprimen' ?></dd></div>
                                        </dl>
                                    </div>
                                    <button type="button" class="button button-secondary" data-detail-toggle aria-expanded="false">Editar</button>
                                </header>
                                <div class="member-detail-form" data-detail-form hidden>
                                    <fieldset class="cv-fieldset">
                                        <legend>
                                            <span>Curriculum</span>
                                            <strong>Diseno del PDF</strong>
                                            <em>Portada y datos que se imprimen al descargar el curriculum.</em>
                                        </legend>
                                        <label class="cv-header-background-field" for="cv_header_image">Fondo de cabecera del curriculum PDF
                                            <span class="cv-header-background-preview" <?= $cvHeaderVisibleBackground !== '' ? 'style="background-image: linear-gradient(135deg, rgba(17, 17, 20, 0.72), rgba(32, 56, 71, 0.68)), url(' . e($cvHeaderVisibleBackground) . ');"' : '' ?>>
                                                <strong><?= $cvHeaderBackground !== '' ? 'Fondo actual' : 'Sin fondo personalizado' ?></strong>
                                                <em>Cambiar fondo</em>
                                            </span>
                                            <input id="cv_header_image" name="cv_header_image" type="file" accept="image/jpeg,image/png,image/webp" hidden>
                                        </label>
                                        <p class="field-help">El fondo de cabecera se guarda automaticamente al seleccionarlo.</p>
                                        <label class="visibility-toggle compact-toggle">
                                            <input type="hidden" name="print_professional_data" value="0">
                                            <input type="checkbox" name="print_professional_data" value="1" <?= !empty($memberProfile['print_professional_data']) ? 'checked' : '' ?>>
                                            <span>Imprimir trayectoria, disponibilidad, web e Instagram en el PDF</span>
                                        </label>
                                    </fieldset>
                                </div>
                            </article>

                            <p class="field-help">La vista previa es el propio PDF: al pulsar <strong>Descargar PDF</strong> se abre el dialogo de impresion del navegador con el curriculum ya maquetado.</p>
                        </section>

                        <?php if ($hasWebPage): ?>
                        <section id="inicio-articulos" class="content-section member-panel-section member-entry-section" data-entry-card="<?= e($introSectionKey) ?>" data-entry-noun="articulo">
                            <div class="member-panel-heading">
                                <div class="member-panel-heading-main">
                                    <div>
                                        <p class="section-kicker">Web publica</p>
                                        <h2>Inicio</h2>
                                        <p>La presentacion que abre tu web, justo debajo de la cabecera: trayectoria, hitos, formacion o proyectos, un articulo por cada momento.</p>
                                    </div>
                                    <button type="button" class="button button-primary member-card-cta" data-entry-create="<?= e($introSectionKey) ?>">+ Crear articulo</button>
                                </div>
                                <div class="member-kpi-grid">
                                    <article class="member-kpi">
                                        <span>Articulos</span>
                                        <strong data-kpi="total"><?= e((string) $introMetrics['total']) ?></strong>
                                    </article>
                                    <article class="member-kpi">
                                        <span>Visibles en la web</span>
                                        <strong data-kpi="active"><?= e((string) $introMetrics['active']) ?></strong>
                                    </article>
                                    <article class="member-kpi">
                                        <span>Con imagen</span>
                                        <strong data-kpi="images"><?= e((string) $introMetrics['images']) ?></strong>
                                    </article>
                                    <article class="member-kpi">
                                        <span>Periodo</span>
                                        <strong data-kpi="period"><?= e($introMetrics['period']) ?></strong>
                                    </article>
                                </div>
                            </div>

                            <div class="member-entry-list" data-entry-list="<?= e($introSectionKey) ?>" data-entry-next="<?= e((string) count($introEntries)) ?>">
                                <?php foreach ($introEntries as $rowIndex => $entry): ?>
                                    <?= cv_entry_item_markup($introSectionKey, $introSectionConfig, (string) $rowIndex, is_array($entry) ? $entry : [], $introSectionTitle) ?>
                                <?php endforeach; ?>
                            </div>

                            <div class="member-entry-empty" data-entry-empty <?= $introEntries ? 'hidden' : '' ?>>
                                <p>Todavia no has creado ningun articulo de <strong>Inicio</strong>. Mientras no haya ninguno visible, tu web publica pasa directamente de la cabecera a la galeria.</p>
                                <button type="button" class="button button-secondary" data-entry-create="<?= e($introSectionKey) ?>">+ Crear articulo</button>
                            </div>

                            <p class="field-help">Los articulos se muestran en la web ordenados por el campo <strong>Orden</strong>, de menor a mayor. Los ocultos siguen aqui pero no aparecen en la web.</p>

                            <template data-entry-template="<?= e($introSectionKey) ?>"><?= cv_entry_item_markup($introSectionKey, $introSectionConfig, '__INDEX__', [], $introSectionTitle) ?></template>
                        </section>
                        <?php endif; ?>

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
                <?php /* Hub de la web publica: una tarjeta por bloque, con el mismo
                         lenguaje que "Mi curriculum". Cada tarjeta abre su pantalla. */ ?>
                <section id="pagina-web" class="content-section member-panel-section">
                    <div class="member-panel-heading">
                        <div class="member-panel-heading-main">
                            <div>
                                <p class="section-kicker">Mi pagina web</p>
                                <h2>Mi pagina web</h2>
                                <p>Gestiona el contenido que aparece en tu espacio publico de Con Sabor Flamenco. Cada bloque solo sale en la web cuando tiene contenido guardado.</p>
                            </div>
                            <a class="button button-primary" href="<?= e($publicProfileUrl) ?>" target="_blank" rel="noopener">Ver mi web</a>
                        </div>
                        <div class="member-kpi-grid">
                            <article class="member-kpi">
                                <span>Bloques con contenido</span>
                                <strong><?= e((string) $webBlocksWithContent) ?></strong>
                            </article>
                            <article class="member-kpi">
                                <span>Fotografias</span>
                                <strong><?= e((string) count($webGallery)) ?></strong>
                            </article>
                            <article class="member-kpi">
                                <span>Eventos</span>
                                <strong><?= e((string) count($webEvents)) ?></strong>
                            </article>
                            <article class="member-kpi">
                                <span>Redes</span>
                                <strong><?= e((string) count(array_filter($webSocialLinks))) ?></strong>
                            </article>
                        </div>
                    </div>

                    <a class="public-url-cta member-web-public-url" href="<?= e($publicProfileUrl) ?>" target="_blank" rel="noopener" data-public-url-cta>
                        <span>URL publica de tu pagina web</span>
                        <strong data-public-url-text><?= e($publicProfileUrl) ?></strong>
                    </a>

                    <div class="member-tile-grid member-tile-grid-md">
                        <?php foreach ($webSectionCards as $card): ?><?= panel_tile_markup($card, 'md') ?><?php endforeach; ?>
                    </div>
                </section>

                <form class="member-profile-form member-web-form" action="panel-usuario.php#pagina-web" method="post" enctype="multipart/form-data" data-panel-form hidden>
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="profile_action" value="update_web_page">

                        <section id="web-slider" class="content-section member-panel-section">
                            <?= web_section_heading('Slider de cabecera', 'Las imagenes principales de la cabecera de tu microweb. El titulo, la descripcion y el boton solo apareceran cuando tengan contenido.', $webSlidesFilled, 'cabecera', 'cabeceras') ?>
                            <article class="member-config-card">
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

                            </section>

                        <section id="web-galeria" class="content-section member-panel-section">
                            <?= web_section_heading('Galeria', 'Anade y organiza las fotografias que quieres mostrar publicamente.', count($webGallery), 'fotografia', 'fotografias') ?>
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

                            </section>

                        <section id="web-videos" class="content-section member-panel-section">
                            <?= web_section_heading('Videos', 'Enlaces de YouTube, Vimeo u otra plataforma para tu seccion de videos.', count($webVideos), 'video', 'videos') ?>
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

                            </section>

                        <section id="web-eventos" class="content-section member-panel-section">
                            <?= web_section_heading('Agenda', 'Actuaciones, cursos, festivales y proximos eventos.', count($webEvents), 'evento', 'eventos') ?>
                            <article class="member-config-card">
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

                            </section>

                        <section id="web-actualidad" class="content-section member-panel-section">
                            <?= web_section_heading('Actualidad', 'Noticias, comunicados y novedades de tu actividad.', count($webNews), 'publicacion', 'publicaciones') ?>
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

                            </section>

                        <section id="web-redes" class="content-section member-panel-section">
                            <?= web_section_heading('Redes sociales', 'Los enlaces sociales que apareceran en la cabecera de tu microweb.', count(array_filter($webSocialLinks)), 'red configurada', 'redes configuradas') ?>
                            <article class="member-config-card social-networks-card">
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

                            </section>

                        <section id="web-contacto" class="content-section member-panel-section">
                            <?= web_section_heading('Contacto', 'Que datos de contacto se muestran en tu web publica.', count($webContactFields), 'dato visible', 'datos visibles') ?>
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
                        </section>

                        <div class="member-form-savebar member-web-savebar">
                            <div>
                                <strong>Guardar la pagina web</strong>
                                <span>Los cambios de todos los bloques se guardan juntos.</span>
                            </div>
                            <button class="button button-primary member-save-button" type="submit">Guardar pagina web</button>
                        </div>
                </form>
                <?php endif; ?>

                <?php if ($fase1Activa): ?>
                <?php
                /* ==============================================================
                   FASE 1 — Mis eventos / Crear evento / Mis puntos / Mis redes

                   Cuatro pantallas del panel, fuera de los formularios grandes
                   (form[data-panel-form]) para que la navegacion por secciones
                   que ya existia las trate como cualquier otra.

                   Todas las acciones que gastan puntos pasan por POST con CSRF y
                   confirmacion previa. El coste lo calcula el servidor: aqui solo
                   se muestra.
                   ============================================================== */
                $csrfFase1 = csrf_token();
                $accionFase1 = 'panel-usuario.php';

                /** Botonera de una tarjeta de evento dentro del panel. */
                $herramientasEvento = static function (array $evento) use ($csrfFase1, $accionFase1): string {
                    $eventoId = (int) $evento['id'];
                    $destacado = csf_evento_promocion_vigente($evento);
                    $pasado = csf_evento_es_pasado($evento);

                    ob_start();
                    ?>
                    <span class="csf-event-tools">
                        <a class="csf-event-tool" href="panel-usuario.php?evento=<?= e((string) $eventoId) ?>#evento-form" data-panel-link="evento-form">Editar</a>

                        <?php if ($destacado): ?>
                            <span class="csf-event-tool" aria-disabled="true">Promocionado</span>
                        <?php elseif (!$pasado && (string) $evento['estado'] === 'PUBLICADO'): ?>
                            <button class="csf-event-tool is-promote" type="button"
                                    data-abrir-confirmar="promocionar-<?= e((string) $eventoId) ?>">
                                Promocionar · <?= e((string) csf_puntos_coste('promocion_evento')) ?> pts
                            </button>
                        <?php endif; ?>

                        <button class="csf-event-tool is-danger" type="button"
                                data-abrir-confirmar="eliminar-<?= e((string) $eventoId) ?>">Eliminar</button>
                    </span>
                    <?php

                    return (string) ob_get_clean();
                };
                ?>

                <section id="mis-eventos" class="content-section member-panel-section">
                    <div class="member-panel-heading">
                        <div class="member-panel-heading-main">
                            <div>
                                <p class="section-kicker">Agenda</p>
                                <h2>Mis eventos</h2>
                                <p>Publicar es gratis. Los puntos solo sirven para dar visibilidad extra.</p>
                            </div>
                            <span class="member-heading-count"><?= e($proximosCount === 1 ? '1 próximo' : $proximosCount . ' próximos') ?></span>
                        </div>
                    </div>

                    <div class="csf-panel-cta" style="margin-bottom: 22px;">
                        <a class="button button-primary" href="panel-usuario.php?evento=nuevo#evento-form" data-panel-link="evento-form">Crear evento</a>
                        <a class="button button-secondary" href="<?= e(app_url('agenda')) ?>" target="_blank" rel="noopener">Ver la agenda pública</a>
                    </div>

                    <h3 class="csf-agenda-month-title">Próximos<span><?= e((string) count($eventosProximos)) ?></span></h3>
                    <?php if ($eventosProximos): ?>
                        <div class="csf-event-grid">
                            <?php foreach ($eventosProximos as $evento): ?>
                                <?= csf_evento_card($evento, ['artista' => false, 'acciones' => $herramientasEvento($evento)]) ?>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="csf-empty">Todavía no tienes eventos programados. Crea el primero: es gratis y aparecerá en la agenda pública.</p>
                    <?php endif; ?>

                    <?php if ($eventosPasados): ?>
                        <h3 class="csf-agenda-month-title" style="margin-top: 34px;">Histórico<span><?= e((string) count($eventosPasados)) ?></span></h3>
                        <div class="csf-event-grid">
                            <?php foreach ($eventosPasados as $evento): ?>
                                <?= csf_evento_card($evento, ['artista' => false, 'acciones' => $herramientasEvento($evento)]) ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>

                <section id="evento-form" class="content-section member-panel-section">
                    <div class="member-panel-heading">
                        <div class="member-panel-heading-main">
                            <div>
                                <p class="section-kicker">Agenda</p>
                                <h2><?= $eventoEnEdicion !== null ? 'Editar evento' : 'Crear evento' ?></h2>
                                <p>Crear y editar eventos es gratis, sin límite y sin gastar puntos.</p>
                            </div>
                        </div>
                    </div>

                    <form method="post" action="<?= e($accionFase1) ?>" enctype="multipart/form-data" class="member-config-card">
                        <input type="hidden" name="csrf_token" value="<?= e($csrfFase1) ?>">
                        <input type="hidden" name="panel_action" value="evento_guardar">
                        <input type="hidden" name="evento_id" value="<?= e((string) ($eventoEnEdicion['id'] ?? 0)) ?>">
                        <input type="hidden" name="imagen_actual" value="<?= e((string) ($eventoEnEdicion['imagen_path'] ?? '')) ?>">

                        <div class="csf-form-grid">
                            <div class="csf-field csf-field-full">
                                <label for="evento-titulo">Título del evento *</label>
                                <input type="text" id="evento-titulo" name="titulo" required maxlength="200"
                                       value="<?= e((string) ($eventoEnEdicion['titulo'] ?? '')) ?>"
                                       placeholder="Noche de bulerías en el Teatro Garnelo">
                            </div>

                            <div class="csf-field">
                                <label for="evento-fecha">Fecha *</label>
                                <input type="date" id="evento-fecha" name="fecha" required
                                       value="<?= e((string) ($eventoEnEdicion['fecha'] ?? '')) ?>">
                            </div>

                            <div class="csf-field">
                                <label for="evento-hora">Hora</label>
                                <input type="time" id="evento-hora" name="hora"
                                       value="<?= e(csf_evento_hora_corta((string) ($eventoEnEdicion['hora'] ?? ''))) ?>">
                            </div>

                            <div class="csf-field">
                                <label for="evento-provincia">Provincia</label>
                                <select id="evento-provincia" name="provincia">
                                    <option value="">Sin especificar</option>
                                    <?php foreach ($provinciasLista as $provincia): ?>
                                        <option value="<?= e($provincia['nombre']) ?>"<?= (string) ($eventoEnEdicion['provincia_texto'] ?? '') === $provincia['nombre'] ? ' selected' : '' ?>><?= e($provincia['nombre']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="csf-field-hint">Clasifica el evento en la agenda por territorio.</p>
                            </div>

                            <div class="csf-field">
                                <label for="evento-municipio">Municipio</label>
                                <input type="text" id="evento-municipio" name="municipio" maxlength="160" list="csf-municipios"
                                       value="<?= e((string) ($eventoEnEdicion['municipio_texto'] ?? '')) ?>"
                                       placeholder="Montilla">
                                <datalist id="csf-municipios">
                                    <?php foreach ($municipiosLista as $municipio): ?>
                                        <option value="<?= e($municipio['nombre']) ?>"></option>
                                    <?php endforeach; ?>
                                </datalist>
                            </div>

                            <div class="csf-field">
                                <label for="evento-lugar">Lugar</label>
                                <input type="text" id="evento-lugar" name="lugar" maxlength="190"
                                       value="<?= e((string) ($eventoEnEdicion['lugar'] ?? '')) ?>"
                                       placeholder="Teatro Garnelo">
                            </div>

                            <div class="csf-field">
                                <label for="evento-direccion">Dirección</label>
                                <input type="text" id="evento-direccion" name="direccion" maxlength="255"
                                       value="<?= e((string) ($eventoEnEdicion['direccion'] ?? '')) ?>"
                                       placeholder="Calle Ancha, 12">
                            </div>

                            <div class="csf-field csf-field-full">
                                <label for="evento-descripcion">Descripción</label>
                                <textarea id="evento-descripcion" name="descripcion" maxlength="4000"
                                          placeholder="Cuenta de qué va el espectáculo, quién actúa y qué se va a ver."><?= e((string) ($eventoEnEdicion['descripcion'] ?? '')) ?></textarea>
                            </div>

                            <div class="csf-field">
                                <label for="evento-enlace">Enlace externo</label>
                                <input type="url" id="evento-enlace" name="enlace_url" maxlength="255"
                                       value="<?= e((string) ($eventoEnEdicion['enlace_url'] ?? '')) ?>"
                                       placeholder="https://entradas.example.com">
                            </div>

                            <div class="csf-field">
                                <label for="evento-video">Vídeo (opcional)</label>
                                <input type="url" id="evento-video" name="video_url" maxlength="255"
                                       value="<?= e((string) ($eventoEnEdicion['video_url'] ?? '')) ?>"
                                       placeholder="https://youtube.com/watch?v=...">
                            </div>

                            <div class="csf-field csf-field-full">
                                <label for="evento-imagen">Cartel del evento</label>
                                <div class="csf-image-field">
                                    <?php $carteActual = csf_evento_imagen_url($eventoEnEdicion ?? []); ?>
                                    <img class="csf-image-preview" data-evento-preview
                                         src="<?= e($carteActual) ?>"
                                         alt="Cartel actual"<?= $carteActual === '' ? ' hidden' : '' ?>>
                                    <input type="file" id="evento-imagen" name="imagen" accept="image/jpeg,image/png,image/webp" data-evento-imagen>
                                </div>
                                <p class="csf-field-hint">JPG, PNG o WebP, hasta 5 MB. Si no subes cartel mostraremos la fecha destacada.</p>
                            </div>

                            <div class="csf-field">
                                <label for="evento-estado">Estado</label>
                                <select id="evento-estado" name="estado">
                                    <?php foreach (csf_evento_estados() as $valor => $etiqueta): ?>
                                        <option value="<?= e($valor) ?>"<?= (string) ($eventoEnEdicion['estado'] ?? 'PUBLICADO') === $valor ? ' selected' : '' ?>><?= e($etiqueta) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="csf-field-hint">Solo los publicados aparecen en la agenda pública.</p>
                            </div>
                        </div>

                        <div class="member-form-savebar">
                            <div>
                                <strong><?= $eventoEnEdicion !== null ? 'Guardar los cambios' : 'Publicar el evento' ?></strong>
                                <span>Publicar en la agenda no cuesta puntos.</span>
                            </div>
                            <button class="button button-primary member-save-button" type="submit">
                                <?= $eventoEnEdicion !== null ? 'Guardar evento' : 'Crear evento' ?>
                            </button>
                        </div>
                    </form>
                </section>

                <section id="mis-puntos" class="content-section member-panel-section">
                    <div class="member-panel-heading">
                        <div class="member-panel-heading-main">
                            <div>
                                <p class="section-kicker">Cartera</p>
                                <h2>Mis puntos</h2>
                                <p>Publicar siempre es gratis. Los puntos compran visibilidad.</p>
                            </div>
                        </div>
                    </div>

                    <?= csf_puntos_widget_saldo($puntosSaldo) ?>

                    <div class="csf-panel-stats" style="margin: 20px 0;">
                        <div class="csf-panel-stat">
                            <strong><?= e((string) $puntosResumen['total_ingresado']) ?></strong>
                            <span>puntos recibidos</span>
                        </div>
                        <div class="csf-panel-stat">
                            <strong><?= e((string) $puntosResumen['total_gastado']) ?></strong>
                            <span>puntos usados</span>
                        </div>
                        <div class="csf-panel-stat">
                            <strong><?= e((string) csf_puntos_coste('promocion_evento')) ?></strong>
                            <span>puntos por promocionar un evento</span>
                        </div>
                        <div class="csf-panel-stat">
                            <strong><?= e((string) csf_puntos_coste('enlace_social')) ?></strong>
                            <span>puntos por enlace social extra</span>
                        </div>
                    </div>

                    <h3 class="csf-agenda-month-title">Movimientos<span><?= e((string) count($puntosMovimientos)) ?></span></h3>
                    <?= csf_puntos_historial($puntosMovimientos) ?>
                </section>

                <section id="mis-redes" class="content-section member-panel-section">
                    <div class="member-panel-heading">
                        <div class="member-panel-heading-main">
                            <div>
                                <p class="section-kicker">Perfil público</p>
                                <h2>Redes sociales</h2>
                                <p>Muestra todas tus redes gratis. El primer enlace clicable también es gratuito.</p>
                            </div>
                            <span class="member-heading-count"><?= e($redesActivas === 1 ? '1 enlace activo' : $redesActivas . ' enlaces activos') ?></span>
                        </div>
                    </div>

                    <p class="csf-social-note">
                        <?php if ($redesActivas === 0): ?>
                            Tienes <strong>un enlace gratuito</strong> por estrenar. Añade tus redes, guarda y activa la que más te interese.
                        <?php else: ?>
                            Activar un enlace más cuesta <strong><?= e(csf_puntos_formato(csf_puntos_coste('enlace_social'))) ?></strong>.
                            Una vez activado permanece enlazado para siempre. Tu saldo: <strong><?= e(csf_puntos_formato($puntosSaldo)) ?></strong>.
                        <?php endif; ?>
                    </p>

                    <form method="post" action="<?= e($accionFase1) ?>">
                        <input type="hidden" name="csrf_token" value="<?= e($csrfFase1) ?>">
                        <input type="hidden" name="panel_action" value="redes_guardar">

                        <div class="csf-social-list">
                            <?php foreach ($redesMiembro as $clave => $red): ?>
                                <div class="csf-social-row<?= $red['enlace_activo'] ? ' is-active' : '' ?>">
                                    <div class="csf-social-head">
                                        <span class="csf-social-name">
                                            <strong><?= e($red['nombre']) ?></strong>
                                            <?php if ($red['handle'] !== ''): ?><small><?= e($red['handle']) ?></small><?php endif; ?>
                                        </span>
                                        <span class="csf-social-status <?= $red['enlace_activo'] ? 'is-on' : 'is-off' ?>">
                                            <?= $red['enlace_activo'] ? 'Enlace activo' : 'Sin enlace' ?>
                                        </span>
                                    </div>

                                    <input type="url" name="redes[<?= e($clave) ?>][url]" maxlength="255"
                                           value="<?= e($red['url']) ?>" placeholder="<?= e($red['placeholder']) ?>">

                                    <div class="csf-social-controls">
                                        <label class="csf-social-visible">
                                            <input type="checkbox" name="redes[<?= e($clave) ?>][visible]" value="1"<?= $red['visible'] ? ' checked' : '' ?>>
                                            Mostrar en mi perfil público
                                        </label>

                                        <?php if ($red['enlace_activo']): ?>
                                            <span class="csf-event-tool" aria-disabled="true">
                                                <?= $red['coste_puntos'] > 0
                                                    ? 'Activado por ' . e(csf_puntos_formato($red['coste_puntos']))
                                                    : 'Activado gratis' ?>
                                            </span>
                                        <?php elseif ($red['configurada']): ?>
                                            <button class="csf-social-activate" type="button"
                                                    data-abrir-confirmar="red-<?= e($clave) ?>">
                                                <?= $redesCosteSiguiente === 0
                                                    ? 'Activar enlace gratis'
                                                    : 'Activar enlace — ' . e(csf_puntos_formato($redesCosteSiguiente)) ?>
                                            </button>
                                        <?php else: ?>
                                            <button class="csf-social-activate" type="button" disabled>Añade la dirección primero</button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="member-form-savebar">
                            <div>
                                <strong>Guardar tus redes</strong>
                                <span>Guardar direcciones y visibilidad no cuesta puntos.</span>
                            </div>
                            <button class="button button-primary member-save-button" type="submit">Guardar redes</button>
                        </div>
                    </form>
                </section>

                <?php /* Modales: paquetes de puntos y confirmaciones de gasto. */ ?>
                <?= csf_puntos_modal_paquetes($accionFase1, $csrfFase1) ?>

                <?php foreach ($eventosProximos as $evento): ?>
                    <?php if (!csf_evento_promocion_vigente($evento) && (string) $evento['estado'] === 'PUBLICADO' && !csf_evento_es_pasado($evento)): ?>
                        <?= csf_puntos_dialogo_confirmar([
                            'id' => 'promocionar-' . (int) $evento['id'],
                            'titulo' => 'Promocionar «' . (string) $evento['titulo'] . '»',
                            'texto' => 'El evento aparecerá destacado en la agenda, con la etiqueta DESTACADO y por delante del resto de eventos de su mismo día.',
                            'coste' => csf_puntos_coste('promocion_evento'),
                            'saldo' => $puntosSaldo,
                            'accion' => $accionFase1,
                            'csrf' => $csrfFase1,
                            'panel_action' => 'evento_promocionar',
                            'campos' => ['evento_id' => (string) (int) $evento['id']],
                            'confirmar' => 'Promocionar por ' . csf_puntos_formato(csf_puntos_coste('promocion_evento')),
                        ]) ?>
                    <?php endif; ?>
                <?php endforeach; ?>

                <?php foreach (array_merge($eventosProximos, $eventosPasados) as $evento): ?>
                    <div class="csf-modal" data-confirmar-modal="eliminar-<?= e((string) (int) $evento['id']) ?>" hidden>
                        <div class="csf-modal-backdrop" data-cerrar-confirmar></div>
                        <section class="csf-modal-dialog csf-modal-narrow" role="dialog" aria-modal="true">
                            <header class="csf-modal-header">
                                <div>
                                    <p class="section-kicker">Confirmación</p>
                                    <h2>Eliminar evento</h2>
                                </div>
                                <button class="modal-close" type="button" data-cerrar-confirmar aria-label="Cerrar">×</button>
                            </header>
                            <p class="csf-confirm-text">
                                «<?= e((string) $evento['titulo']) ?>» dejará de aparecer en la agenda y en tu perfil público.
                                Los puntos que hayas gastado en promocionarlo no se devuelven.
                            </p>
                            <div class="csf-confirm-actions">
                                <button class="button button-secondary" type="button" data-cerrar-confirmar>Cancelar</button>
                                <form method="post" action="<?= e($accionFase1) ?>">
                                    <input type="hidden" name="csrf_token" value="<?= e($csrfFase1) ?>">
                                    <input type="hidden" name="panel_action" value="evento_eliminar">
                                    <input type="hidden" name="evento_id" value="<?= e((string) (int) $evento['id']) ?>">
                                    <button class="button button-primary" type="submit">Eliminar evento</button>
                                </form>
                            </div>
                        </section>
                    </div>
                <?php endforeach; ?>

                <?php foreach ($redesMiembro as $clave => $red): ?>
                    <?php if (!$red['enlace_activo'] && $red['configurada']): ?>
                        <?= csf_puntos_dialogo_confirmar([
                            'id' => 'red-' . $clave,
                            'titulo' => 'Activar el enlace de ' . $red['nombre'],
                            'texto' => $redesCosteSiguiente === 0
                                ? 'Este es tu enlace gratuito. Después de activarlo permanecerá enlazado en tu perfil.'
                                : 'Activar este enlace cuesta ' . csf_puntos_formato(csf_puntos_coste('enlace_social'))
                                    . '. Después de activarlo permanecerá enlazado en tu perfil.',
                            'coste' => $redesCosteSiguiente,
                            'saldo' => $puntosSaldo,
                            'accion' => $accionFase1,
                            'csrf' => $csrfFase1,
                            'panel_action' => 'red_activar',
                            'campos' => ['red' => $clave],
                            'confirmar' => $redesCosteSiguiente === 0
                                ? 'Activar gratis'
                                : 'Activar por ' . csf_puntos_formato($redesCosteSiguiente),
                        ]) ?>
                    <?php endif; ?>
                <?php endforeach; ?>
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
            if (!(event.target instanceof Element)) {
                return;
            }
            const section = event.target.closest('.member-panel-section');
            if (section instanceof HTMLElement && !section.classList.contains('active')) {
                activateMemberPanel(section.id);
            }
            // El campo tambien puede estar en un bloque de perfil sin desplegar.
            const detailForm = event.target.closest('[data-detail-form]');
            if (detailForm instanceof HTMLElement && detailForm.hidden) {
                detailForm.hidden = false;
                const toggle = detailForm.closest('[data-detail]')?.querySelector('[data-detail-toggle]');
                if (toggle instanceof HTMLElement) {
                    toggle.setAttribute('aria-expanded', 'true');
                    toggle.textContent = 'Cerrar';
                }
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

        // Espejo de cv_entry_meta_label(): por defecto fechas y lugar, salvo que
        // la fila declare con que campos se resume (el bloque Inicio usa el ano).
        const entryMetaLabel = (fields, item) => {
            const declared = (item?.dataset.entryMetaFields || '').split(',').filter(Boolean);
            if (declared.length > 0) {
                return declared.map((field) => entryFieldValue(fields, field)).filter(Boolean).join(' · ');
            }

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
            const toggleButton = item.querySelector('[data-entry-toggle]');
            const imagePath = entryFieldValue(fields, 'image_path');
            const isVisible = entryFieldValue(fields, 'is_active') === '1';

            if (title instanceof HTMLElement) {
                title.textContent = entryFieldValue(fields, item.dataset.entryTitleField || 'category') || 'Entrada sin titulo';
            }
            if (meta instanceof HTMLElement) {
                meta.textContent = entryMetaLabel(fields, item);
            }
            if (flag instanceof HTMLElement) {
                flag.hidden = isVisible;
            }
            if (toggleButton instanceof HTMLElement) {
                toggleButton.textContent = isVisible ? 'Ocultar' : 'Mostrar';
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
                ['date_start', 'date_end', 'year'].forEach((field) => {
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
                const noun = card.dataset.entryNoun || 'entrada';
                navMetric.textContent = `${metrics.total} ${metrics.total === 1 ? noun : noun + 's'}`;
            }
            if (empty instanceof HTMLElement) {
                empty.hidden = metrics.total > 0;
            }
            if (list instanceof HTMLElement) {
                list.hidden = metrics.total === 0;
            }
        };

        const entryPreviewMarkup = (fields, item) => {
            const imagePath = entryFieldValue(fields, 'image_path');
            const meta = entryMetaLabel(fields, item);
            const description = entryFieldValue(fields, 'description');
            const blocks = [];

            if (imagePath) {
                blocks.push(`<img class="member-entry-preview-image" src="${escapeEntryText(imagePath)}" alt="">`);
            }
            blocks.push(`<h5>${escapeEntryText(entryFieldValue(fields, item?.dataset.entryTitleField || 'category') || 'Entrada sin titulo')}</h5>`);
            if (meta) {
                blocks.push(`<p class="member-entry-preview-meta">${escapeEntryText(meta)}</p>`);
            }
            blocks.push(description
                ? `<div class="member-entry-preview-text">${description}</div>`
                : '<p class="member-entry-preview-empty">Esta entrada todavia no tiene descripcion.</p>');
            if (entryFieldValue(fields, 'is_active') !== '1') {
                const hiddenNote = item?.dataset.entryHiddenLabel === 'Oculto en la web'
                    ? 'Este articulo esta oculto: no aparece en tu web publica.'
                    : 'Esta entrada no se imprime en el curriculum PDF.';
                blocks.push(`<p class="member-entry-preview-flag">${escapeEntryText(hiddenNote)}</p>`);
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
                    preview.innerHTML = entryPreviewMarkup(fields, fields.closest('[data-entry-item]'));
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

            // Ocultar / mostrar sin abrir el panel: cambia la misma casilla que
            // el formulario, asi que se confirma con Guardar cambios como todo
            // lo demas y no hace falta un envio aparte.
            const toggleButton = target.closest('[data-entry-toggle]');
            if (toggleButton) {
                const item = toggleButton.closest('[data-entry-item]');
                const visibleInput = item?.querySelector('[data-entry-field="is_active"]');
                if (item instanceof HTMLElement && visibleInput instanceof HTMLInputElement) {
                    visibleInput.checked = !visibleInput.checked;
                    refreshEntryItem(item);
                    refreshEntryCard(item.closest('[data-entry-card]'));
                    markProfilePendingSave(visibleInput.checked
                        ? 'Articulo marcado como visible. Pulsa Guardar cambios para confirmarlo.'
                        : 'Articulo marcado como oculto. Pulsa Guardar cambios para confirmarlo.');
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

        // Sin barra lateral, las migas son la unica referencia de donde estas:
        // se rellenan con el titulo de la pantalla y, cuando cuelga de otra, con
        // el de su pantalla madre.
        const panelSectionParents = {
            formacion: ['curriculum', 'Mi curriculum'],
            experiencia: ['curriculum', 'Mi curriculum'],
            premios: ['curriculum', 'Mi curriculum'],
            'seccion-personalizada': ['curriculum', 'Mi curriculum'],
            'inicio-articulos': ['pagina-web', 'Mi pagina web'],
            'web-slider': ['pagina-web', 'Mi pagina web'],
            'web-galeria': ['pagina-web', 'Mi pagina web'],
            'web-videos': ['pagina-web', 'Mi pagina web'],
            'web-eventos': ['pagina-web', 'Mi pagina web'],
            'web-actualidad': ['pagina-web', 'Mi pagina web'],
            'web-redes': ['pagina-web', 'Mi pagina web'],
            'web-contacto': ['pagina-web', 'Mi pagina web'],
        };

        function syncPanelBreadcrumb(target) {
            const backLink = document.querySelector('[data-panel-back]');
            const crumb = document.querySelector('[data-panel-crumb]');
            const parentCrumb = document.querySelector('[data-panel-crumb-parent]');
            if (!(backLink instanceof HTMLElement)) {
                return;
            }

            backLink.hidden = target === 'inicio';
            if (crumb instanceof HTMLElement) {
                const heading = document.querySelector(`#${CSS.escape(target)} h2`);
                crumb.textContent = heading instanceof HTMLElement ? heading.textContent.trim() : '';
            }
            if (parentCrumb instanceof HTMLElement) {
                const parent = panelSectionParents[target];
                parentCrumb.hidden = !parent;
                parentCrumb.textContent = parent ? parent[1] : '';
                parentCrumb.dataset.panelParent = parent ? parent[0] : '';
            }
        }

        function activateMemberPanel(target) {
            if (!target || !document.getElementById(target)) {
                return;
            }
            document.querySelectorAll('[data-panel-link]').forEach((link) => link.classList.toggle('active', link.dataset.panelLink === target));
            document.querySelectorAll('.member-panel-section').forEach((section) => {
                section.classList.toggle('active', section.id === target);
            });

            // Cada formulario envuelve varias pantallas (el de perfil, Mi perfil y
            // las del curriculum; el de la web, sus bloques). Solo se muestra, con
            // su barra de guardado, el que contiene la pantalla activa.
            const owningForm = document.getElementById(target)?.closest('form[data-panel-form]') ?? null;
            document.querySelectorAll('form[data-panel-form]').forEach((panelForm) => {
                panelForm.hidden = panelForm !== owningForm;
            });
            if (owningForm instanceof HTMLFormElement) {
                owningForm.action = 'panel-usuario.php#' + target;
            }

            syncPanelBreadcrumb(target);
        }

        document.querySelectorAll('[data-panel-link]').forEach((link) => {
            link.addEventListener('click', () => {
                activateMemberPanel(link.dataset.panelLink);

                // Atajos de "Contenido de tu web": la pantalla es la misma, asi
                // que ademas hay que llevar la vista hasta el bloque pedido.
                const focusId = link.dataset.panelFocus;
                if (focusId) {
                    const focusTarget = document.getElementById(focusId);
                    if (focusTarget instanceof HTMLElement) {
                        window.requestAnimationFrame(() => {
                            focusTarget.scrollIntoView({ behavior: 'smooth', block: 'start' });
                            focusTarget.classList.add('is-focused');
                            window.setTimeout(() => focusTarget.classList.remove('is-focused'), 1600);
                        });
                    }
                }
            });
        });

        // Migas de pan: el segundo nivel devuelve a su pantalla madre.
        document.querySelector('[data-panel-crumb-parent]')?.addEventListener('click', (event) => {
            const parent = event.currentTarget.dataset.panelParent;
            if (parent) {
                activateMemberPanel(parent);
            }
        });

        // Bloques de "Mi perfil" y "Diseno del PDF": el resumen esta siempre a la
        // vista y el formulario, que es el de siempre, se despliega al editar.
        document.querySelectorAll('[data-detail-toggle]').forEach((button) => {
            button.addEventListener('click', () => {
                const body = button.closest('[data-detail]')?.querySelector('[data-detail-form]');
                if (!(body instanceof HTMLElement)) {
                    return;
                }
                const willOpen = body.hidden;
                body.hidden = !willOpen;
                button.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
                button.textContent = willOpen ? 'Cerrar' : 'Editar';
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
