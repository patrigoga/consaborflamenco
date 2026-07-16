<?php
declare(strict_types=1);

require_once __DIR__ . '/app/auth.php';
require_once __DIR__ . '/app/layout.php';

$user = require_login();
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

function clean_web_events(array $source, array $existingEvents, array $files, int $maxItems, array &$errors): array
{
    $rows = is_array($source['web_events'] ?? null) ? $source['web_events'] : [];
    $events = [];

    foreach ($rows as $rowIndex => $row) {
        if (!is_array($row)) {
            continue;
        }

        $event = [
            'title' => clean_text((string) ($row['title'] ?? '')),
            'date' => clean_text((string) ($row['date'] ?? '')),
            'time' => clean_text((string) ($row['time'] ?? '')),
            'description' => clean_html_text((string) ($row['description'] ?? '')),
            'image_path' => clean_text((string) ($existingEvents[$rowIndex]['image_path'] ?? '')),
        ];

        if (isset($row['remove_image']) && (string) $row['remove_image'] === '1') {
            $event['image_path'] = '';
        }

        $uploadedImagePath = save_member_cv_image_upload(cv_uploaded_file($files, 'web_events', (int) $rowIndex), $errors);
        if ($uploadedImagePath) {
            $event['image_path'] = $uploadedImagePath;
        }

        $hasContent = $event['title'] !== '' || $event['date'] !== '' || $event['time'] !== '' || $event['description'] !== '' || $event['image_path'] !== '';
        if (!$hasContent) {
            continue;
        }

        if ($event['title'] === '' || $event['date'] === '' || $event['time'] === '' || $event['description'] === '') {
            $errors[] = 'Cada evento debe incluir titulo, fecha, hora y descripcion.';
            continue;
        }

        $events[] = $event;
    }

    if (count($events) > $maxItems) {
        $errors[] = 'Has superado el maximo de eventos permitido para tu membresia.';
        $events = array_slice($events, 0, $maxItems);
    }

    usort($events, static fn (array $left, array $right): int => strcmp((string) ($left['date'] ?? ''), (string) ($right['date'] ?? '')));

    return $events;
}

function clean_web_articles(array $source, array $existingArticles, array $files, int $maxItems, array &$errors): array
{
    $rows = is_array($source['web_articles'] ?? null) ? $source['web_articles'] : [];
    $articles = [];

    foreach ($rows as $rowIndex => $row) {
        if (!is_array($row)) {
            continue;
        }

        $article = [
            'title' => clean_text((string) ($row['title'] ?? '')),
            'summary' => clean_html_text((string) ($row['summary'] ?? '')),
            'image_path' => clean_text((string) ($existingArticles[$rowIndex]['image_path'] ?? '')),
            'submit_to_magazine' => isset($row['submit_to_magazine']) && (string) $row['submit_to_magazine'] === '1',
        ];

        if (isset($row['remove_image']) && (string) $row['remove_image'] === '1') {
            $article['image_path'] = '';
        }

        $uploadedImagePath = save_member_cv_image_upload(cv_uploaded_file($files, 'web_articles', (int) $rowIndex), $errors);
        if ($uploadedImagePath) {
            $article['image_path'] = $uploadedImagePath;
        }

        $hasContent = $article['title'] !== '' || $article['summary'] !== '' || $article['image_path'] !== '';
        if (!$hasContent) {
            continue;
        }

        if ($article['title'] === '' || $article['summary'] === '') {
            $errors[] = 'Cada articulo de actualidad debe incluir titulo y contenido breve.';
            continue;
        }

        $articles[] = $article;
    }

    if (count($articles) > $maxItems) {
        $errors[] = 'Has superado el maximo de articulos permitido para tu membresia.';
        $articles = array_slice($articles, 0, $maxItems);
    }

    return $articles;
}

function member_slug_in_use(string $slug, int $excludeUserId = 0): bool
{
    $slug = slugify(clean_text($slug));
    if ($slug === '') {
        return false;
    }

    $pdo = db();
    if (!$pdo) {
        return false;
    }
    if (!db_column_exists($pdo, 'miembros', 'slug')) {
        return false;
    }

    $statement = $pdo->prepare('SELECT COUNT(*) FROM miembros WHERE slug = :slug AND usuario_id != :usuario_id');
    $statement->execute([
        'slug' => $slug,
        'usuario_id' => max(0, $excludeUserId),
    ]);

    return ((int) $statement->fetchColumn()) > 0;
}

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

$profileAction = (string) ($_POST['profile_action'] ?? '');
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($profileAction, ['update_profile', 'update_profile_images'], true)) {
    $isSlugSave = $profileAction === 'update_profile' && (string) ($_POST['slug_action'] ?? '') === 'save_public_slug';

    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $profileErrors[] = 'La sesion ha caducado. Vuelve a intentarlo.';
    }

    $photoPath = null;
    $cvHeaderImagePath = null;
    if (!$profileErrors) {
        $photoPath = save_member_photo_upload($_FILES['main_photo'] ?? null, $profileErrors, empty($memberProfile['main_photo_path']));
        if ($photoPath) {
            $memberProfile['main_photo_path'] = $photoPath;
        }

        $cvHeaderImagePath = save_member_cv_image_upload($_FILES['cv_header_image'] ?? null, $profileErrors);
        if ($cvHeaderImagePath) {
            $memberProfile['cv_header_image_path'] = $cvHeaderImagePath;
        }
    }

    if (!$profileErrors && $isSlugSave) {
        $requestedSlug = slugify(clean_text((string) ($_POST['slug'] ?? '')));
        if ($requestedSlug === '') {
            $profileErrors[] = 'La URL publica no es valida. Usa solo letras, numeros y guiones.';
        } elseif (member_slug_in_use($requestedSlug, (int) ($user['db_id'] ?? 0))) {
            $profileErrors[] = 'La URL publica ya esta en uso. Elige otro slug.';
        } else {
            $memberProfile['slug'] = $requestedSlug;
            $memberProfile['slug_locked_at'] = null;
            $user['artistic_profile'] = $memberProfile;
            update_user($user);
            $profileMessages[] = 'URL publica guardada. Ya puedes abrir tu ficha con este enlace.';
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

        $accountName = clean_text((string) ($_POST['user_name'] ?? $user['name'] ?? ''));
        if (strlen($accountName) < 2 || strlen($accountName) > 160) {
            $profileErrors[] = 'El nombre de usuario debe tener entre 2 y 160 caracteres.';
        } elseif (user_name_in_use($accountName, (string) ($user['id'] ?? ''))) {
            $profileErrors[] = 'Ya existe otro usuario con ese nombre. Usa una variacion.';
        } else {
            $user['name'] = $accountName;
        }
    }

    if (!$profileErrors && !$isSlugSave) {
        $user['artistic_profile'] = $memberProfile;
        update_user($user);
        $profileMessages[] = $profileAction === 'update_profile_images'
            ? 'Imagenes actualizadas y guardadas correctamente.'
            : (profile_is_complete($memberProfile)
                ? 'Perfil artistico actualizado.'
                : 'Perfil guardado. Sigue pendiente completar nombre artistico, ciudad, provincia, fotografia principal y al menos una formacion, experiencia profesional o actuacion.');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['profile_action'] ?? '') === 'update_web_page') {
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
        $maxEvents = $isVipMember ? 15 : 3;
        $maxArticles = $isVipMember ? 5 : 1;
        $webPage['events'] = clean_web_events(
            $_POST,
            is_array($webPage['events'] ?? null) ? $webPage['events'] : [],
            $_FILES,
            $maxEvents,
            $profileErrors
        );
        $webPage['articles'] = clean_web_articles(
            $_POST,
            is_array($webPage['articles'] ?? null) ? $webPage['articles'] : [],
            $_FILES,
            $maxArticles,
            $profileErrors
        );
        $webPage['contact_fields'] = array_values(array_intersect(
            ['email', 'phone', 'website', 'instagram'],
            array_map('strval', is_array($_POST['web_contact_fields'] ?? null) ? $_POST['web_contact_fields'] : [])
        ));

        if (!$profileErrors) {
            $memberProfile['web_page'] = default_member_web_page($webPage);
            $user['artistic_profile'] = $memberProfile;
            update_user($user);
            $profileMessages[] = 'Pagina web actualizada.';
        }
    }
}

$userName = $user['name'] ?? 'Miembro';

$memberTypeLabel = member_type_options()[$memberProfile['member_type']] ?? 'Artista';
$profileStatus = profile_is_complete($memberProfile) ? 'Perfil completo' : 'Perfil pendiente';
$profileStatusClass = profile_is_complete($memberProfile) ? 'status-pill-active' : 'status-pill-pending';
$displayName = $memberProfile['public_name'] !== '' ? $memberProfile['public_name'] : $userName;
$publicSlug = clean_text((string) ($memberProfile['slug'] ?? slugify($displayName)));
$publicSlug = $publicSlug !== '' ? $publicSlug : slugify($displayName);
$publicProfileUrl = app_url('artista/' . rawurlencode($publicSlug));
$webPage = default_member_web_page(is_array($memberProfile['web_page'] ?? null) ? $memberProfile['web_page'] : []);
$webGallery = array_slice($webPage['gallery'], 0, 9);
$webEvents = array_values(is_array($webPage['events'] ?? null) ? $webPage['events'] : []);
$webArticles = array_values(is_array($webPage['articles'] ?? null) ? $webPage['articles'] : []);
$maxWebEvents = $isVipMember ? 15 : 3;
$maxWebArticles = $isVipMember ? 5 : 1;
$webEvents = array_slice($webEvents, 0, $maxWebEvents);
$webArticles = array_slice($webArticles, 0, $maxWebArticles);
$webEvents = $webEvents ?: [['title' => '', 'date' => '', 'time' => '', 'description' => '', 'image_path' => '']];
$webArticles = $webArticles ?: [['title' => '', 'summary' => '', 'image_path' => '', 'submit_to_magazine' => false]];
$webContactFields = is_array($webPage['contact_fields'] ?? null) ? $webPage['contact_fields'] : [];
$webHeaderImage = clean_text((string) ($webPage['header_image_path'] ?: ($memberProfile['cv_header_image_path'] ?? '')));
$cardHeadline = clean_text((string) ($memberProfile['artistic_headline'] ?? ''));
$profileRequiredFields = [
    $memberProfile['public_name'] ?? '',
    $memberProfile['city'] ?? '',
    $memberProfile['province'] ?? '',
    $memberProfile['main_photo_path'] ?? '',
    (!empty($memberProfile['education']) || !empty($memberProfile['experience']) || !empty($memberProfile['performances'])) ? 'curriculum' : '',
];
$completedProfileFields = count(array_filter($profileRequiredFields, static fn ($value): bool => clean_text((string) $value) !== ''));
$profileCompletion = (int) round(($completedProfileFields / count($profileRequiredFields)) * 100);
$cvHeaderBackground = clean_text((string) ($memberProfile['cv_header_image_path'] ?? ''));
$cvHeaderStyle = $cvHeaderBackground !== ''
    ? "background-image: linear-gradient(135deg, rgba(17, 17, 20, 0.82), rgba(32, 56, 71, 0.74)), url('" . $cvHeaderBackground . "');"
    : '';
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
                <div class="member-sidebar-progress" aria-label="Estado del perfil">
                    <div>
                        <span>Perfil completo</span>
                        <strong><?= e((string) $profileCompletion) ?>%</strong>
                    </div>
                    <meter min="0" max="100" value="<?= e((string) $profileCompletion) ?>"><?= e((string) $profileCompletion) ?>%</meter>
                </div>
                <button class="member-sidebar-print" type="button" onclick="window.print()">Imprimir curriculum PDF</button>
                <nav class="member-sidebar-nav">
                    <a class="active" href="#perfil" data-panel-link="perfil">Perfil</a>
                    <a href="#pagina-web" data-panel-link="pagina-web">Pagina web</a>
                    <a href="#tarjeta-miembro" data-panel-link="tarjeta-miembro">Tarjeta de miembro</a>
                    <a href="#banners" data-panel-link="banners">Banners</a>
                    <a href="#seguridad" data-panel-link="seguridad">Seguridad</a>
                </nav>
            </aside>

            <div class="member-panel-content">
                <div class="member-panel-tabs" role="tablist" aria-label="Secciones del panel de miembro">
                    <button type="button" class="tab-button panel-tab-button active" data-tab-target="perfil">Perfil</button>
                    <button type="button" class="tab-button panel-tab-button" data-tab-target="pagina-web">Pagina web</button>
                    <button type="button" class="tab-button panel-tab-button" data-tab-target="tarjeta-miembro">Tarjeta de miembro</button>
                    <button type="button" class="tab-button panel-tab-button" data-tab-target="banners">Banners</button>
                    <button type="button" class="tab-button panel-tab-button" data-tab-target="seguridad">Seguridad</button>
                </div>
                <section class="member-dashboard-hero" aria-label="Resumen del espacio">
                    <div class="member-dashboard-identity">
                        <button type="button" class="member-dashboard-photo-edit" data-main-photo-trigger aria-label="Editar fotografia principal">
                            <?php if (!empty($memberProfile['main_photo_path'])): ?>
                                <img src="<?= e($memberProfile['main_photo_path']) ?>" alt="Fotografia principal de <?= e($displayName) ?>" loading="lazy" data-main-photo-preview>
                            <?php else: ?>
                                <img alt="Fotografia principal de <?= e($displayName) ?>" loading="lazy" data-main-photo-preview hidden>
                                <div class="member-dashboard-photo-placeholder" data-main-photo-placeholder><?= e(strtoupper(substr($displayName, 0, 1))) ?></div>
                            <?php endif; ?>
                            <span>Editar imagen</span>
                        </button>
                        <div>
                            <span><?= e($memberTypeLabel) ?></span>
                            <h2><?= e($displayName) ?></h2>
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

                <section id="perfil" class="content-section member-panel-section active">
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
                            <span>Tipo de membresia</span>
                            <strong><?= e($memberStatus) ?></strong>
                        </article>
                    </div>
                    <div class="member-profile-editor">
                        <form class="member-profile-form cv-editor" id="member-profile-form" action="panel-usuario.php#perfil" method="post" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="profile_action" value="update_profile">
                            <div class="cv-editor-actions">
                                <button class="button button-primary" type="submit">Guardar curriculum</button>
                                <button class="button button-secondary" type="button" onclick="window.print()">Imprimir / guardar PDF</button>
                            </div>

                            <fieldset class="cv-fieldset profile-tab-panel active" data-profile-tab="artistica">
                                <legend>Identidad artistica</legend>
                                <div class="form-grid-two">
                                    <label for="user_name">Nombre de usuario (cuenta)
                                        <input id="user_name" name="user_name" type="text" value="<?= e((string) ($user['name'] ?? '')) ?>" maxlength="160" required>
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
                                        <select id="member_type" name="member_type" required>
                                            <?php foreach (member_type_options() as $typeValue => $typeLabel): ?>
                                                <option value="<?= e($typeValue) ?>" <?= $memberProfile['member_type'] === $typeValue ? 'selected' : '' ?>><?= e($typeLabel) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </label>
                                    <label for="public_name">Nombre artistico
                                        <input id="public_name" name="public_name" type="text" value="<?= e($displayName) ?>" required>
                                    </label>
                                </div>
                                <div class="public-url-control">
                                    <label for="slug">URL pública (slug)
                                        <input id="slug" name="slug" type="text" value="<?= e($publicSlug) ?>" placeholder="nombre-artista" required>
                                    </label>
                                    <button class="button button-secondary public-url-save" type="submit" name="slug_action" value="save_public_slug" formnovalidate>Guardar URL</button>
                                    <p class="field-help public-url-preview">URL publica completa: <a href="<?= e($publicProfileUrl) ?>" target="_blank" rel="noopener"><?= e($publicProfileUrl) ?></a></p>
                                </div>
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
                                    <input id="main_photo" name="main_photo" type="file" accept="image/jpeg,image/png,image/webp" data-main-photo-input hidden>
                                    <p class="field-help">Haz clic sobre la imagen de la cabecera para cambiarla.</p>
                                    <p class="field-help">Al seleccionar una imagen, se guarda automaticamente.</p>
                                </div>
                                <p class="field-help">Cada espacio debe tener al menos una fotografia principal. JPG, PNG o WebP, maximo 5 MB.</p>
                                <label class="cv-header-background-field" for="cv_header_image">Fondo de cabecera del curriculum PDF
                                    <span class="cv-header-background-preview" <?= $cvHeaderBackground !== '' ? 'style="background-image: linear-gradient(135deg, rgba(17, 17, 20, 0.72), rgba(32, 56, 71, 0.68)), url(' . e($cvHeaderBackground) . ');"' : '' ?>>
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

                            <?php foreach ($cvSectionConfig as $sectionKey => $sectionConfig): ?>
                                <?php
                                $sectionSettings = is_array($memberProfile['section_settings'][$sectionKey] ?? null) ? $memberProfile['section_settings'][$sectionKey] : [];
                                $sectionActive = (bool) ($sectionSettings['active'] ?? true);
                                $sectionDisplayOrder = (int) ($sectionSettings['order'] ?? ($sectionConfig['default_order'] ?? 1));
                                $isCustomSection = $sectionKey === 'custom_section';
                                $sectionTitle = $sectionConfig['title'];
                                ?>
                                <fieldset class="cv-fieldset cv-repeat-section">
                                    <div class="cv-section-heading">
                                        <legend><span>Seccion</span><?php if ($isCustomSection): ?><input type="text" name="custom_section_title" value="<?= e($sectionTitle) ?>" placeholder="Nombre de la seccion" class="cv-section-title-input"><?php else: ?><?= e($sectionTitle) ?><?php endif; ?></legend>
                                        <div class="cv-section-tools">
                                            <input type="hidden" name="section_settings[<?= e($sectionKey) ?>][active]" value="0">
                                            <label class="cv-section-toggle">
                                                <input type="checkbox" name="section_settings[<?= e($sectionKey) ?>][active]" value="1" <?= $sectionActive ? 'checked' : '' ?>>
                                                Activa en PDF
                                            </label>
                                            <label>Orden seccion
                                                <input name="section_settings[<?= e($sectionKey) ?>][order]" type="number" min="1" step="1" value="<?= e((string) $sectionDisplayOrder) ?>">
                                            </label>
                                            <?php if (!empty($sectionConfig['sortable'])): ?>
                                                <label>Orden entradas
                                                    <select name="sort_orders[<?= e($sectionKey) ?>]">
                                                        <?php $sortOrder = normalize_cv_sort_order($memberProfile['sort_orders'][$sectionKey] ?? 'desc'); ?>
                                                        <option value="desc" <?= $sortOrder === 'desc' ? 'selected' : '' ?>>Mas reciente primero</option>
                                                        <option value="asc" <?= $sortOrder === 'asc' ? 'selected' : '' ?>>Mas antiguo primero</option>
                                                        <option value="manual" <?= $sortOrder === 'manual' ? 'selected' : '' ?>>Orden manual</option>
                                                    </select>
                                                </label>
                                            <?php endif; ?>
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
                                            <div class="cv-entry-controls">
                                                <label class="visibility-toggle">
                                                    <input type="checkbox" name="<?= e($sectionKey) ?>[<?= e((string) $rowIndex) ?>][is_active]" value="1" <?= ((bool) ($entry['is_active'] ?? true)) ? 'checked' : '' ?> data-default-checked="1">
                                                    <span>Articulo activo en PDF</span>
                                                </label>
                                                <label>Orden
                                                    <input name="<?= e($sectionKey) ?>[<?= e((string) $rowIndex) ?>][display_order]" type="number" min="1" step="1" value="<?= e((string) ($entry['display_order'] ?? ($rowIndex + 1))) ?>">
                                                </label>
                                            </div>
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
                                                <?php if ($fieldName === 'description'): ?>
                                                    <div class="cv-editor-field <?= e($fieldClass) ?>">
                                                        <span class="field-label"><?= e($fieldLabel) ?></span>
                                                        <div class="rich-text-toolbar" data-editor-toolbar></div>
                                                        <div class="rich-text-editor" contenteditable="true" data-rich-editor><?= $entry['description'] ?? '' ?></div>
                                                        <textarea name="<?= e($sectionKey) ?>[<?= e((string) $rowIndex) ?>][<?= e($fieldName) ?>]" rows="5" hidden><?= e((string) ($entry[$fieldName] ?? '')) ?></textarea>
                                                    </div>
                                                <?php else: ?>
                                                    <label class="<?= e($fieldClass) ?>">
                                                        <?= e($fieldLabel) ?>
                                                        <input name="<?= e($sectionKey) ?>[<?= e((string) $rowIndex) ?>][<?= e($fieldName) ?>]" type="<?= str_starts_with($fieldName, 'date_') ? 'date' : 'text' ?>" value="<?= e((string) ($entry[$fieldName] ?? '')) ?>">
                                                    </label>
                                                <?php endif; ?>
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
                            <header class="cv-print-header" <?= $cvHeaderStyle !== '' ? 'style="' . e($cvHeaderStyle) . '"' : '' ?>>
                                <?php if (!empty($memberProfile['main_photo_path'])): ?>
                                    <img src="<?= e($memberProfile['main_photo_path']) ?>" alt="Fotografia principal de <?= e($displayName) ?>">
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

                <section id="pagina-web" class="content-section member-panel-section">
                    <div class="section-heading">
                        <div class="section-heading-content">
                            <p class="section-kicker">Pagina web</p>
                            <h2>Web de una sola pagina</h2>
                            <p>Configura tu propia web con menu fijo: Inicio, Galeria, Eventos, Actualidad y Contacto.</p>
                        </div>
                        <a class="section-enter-link" href="<?= e($publicProfileUrl) ?>" target="_blank" rel="noopener">Ver pagina publica</a>
                    </div>

                    <form class="member-profile-form member-web-form" action="panel-usuario.php#pagina-web" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="profile_action" value="update_web_page">

                        <nav class="member-web-submenu" aria-label="Submenu de pagina web">
                            <a href="#web-inicio">Inicio</a>
                            <a href="#web-galeria">Galeria</a>
                            <a href="#web-eventos">Eventos</a>
                            <a href="#web-actualidad">Actualidad</a>
                            <a href="#web-contacto">Contacto</a>
                        </nav>

                        <div class="member-website-grid">
                            <article id="web-inicio" class="member-config-card">
                                <h3>Cabecera</h3>
                                <p>Esta seccion representa el Inicio de tu web personal, no el Inicio de Con Sabor Flamenco.</p>
                                <label for="web_header_title">Titulo visible
                                    <input id="web_header_title" name="web_header_title" type="text" value="<?= e((string) ($webPage['header_title'] ?? '')) ?>" placeholder="<?= e($displayName) ?>">
                                </label>
                                <label for="web_header_subtitle">Texto breve de cabecera
                                    <textarea id="web_header_subtitle" name="web_header_subtitle" rows="3" maxlength="280" placeholder="Una frase breve para presentar tu espacio."><?= e((string) ($webPage['header_subtitle'] ?? '')) ?></textarea>
                                </label>
                                <label class="cv-header-background-field" for="web_header_image">Fondo de cabecera web
                                    <span class="cv-header-background-preview" <?= $webHeaderImage !== '' ? 'style="' . e("background-image: linear-gradient(135deg, rgba(17, 17, 20, 0.62), rgba(32, 56, 71, 0.56)), url('" . $webHeaderImage . "');") . '"' : '' ?>>
                                        <span><?= $webHeaderImage !== '' ? 'Fondo actual' : 'Subir fondo' ?></span>
                                    </span>
                                    <input id="web_header_image" name="web_header_image" type="file" accept="image/jpeg,image/png,image/webp" hidden>
                                </label>
                            </article>

                            <article id="web-galeria" class="member-config-card">
                                <h3>Galeria</h3>
                                <p>Sube hasta 9 imagenes desde este submenu para mostrarlas en tu web.</p>
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

                            <article id="web-eventos" class="member-config-card">
                                <h3>Eventos</h3>
                                <p><?= $isVipMember ? 'Como miembro de pago puedes publicar mas de 3 eventos.' : 'Como miembro simpatizante puedes publicar hasta 3 eventos.' ?></p>
                                <div class="member-web-repeat-list" data-web-repeat-list="events" data-web-max="<?= e((string) $maxWebEvents) ?>">
                                    <?php foreach ($webEvents as $eventIndex => $event): ?>
                                        <div class="member-web-repeat-row" data-web-repeat-row>
                                            <div class="form-grid-three">
                                                <label>Titulo
                                                    <input name="web_events[<?= e((string) $eventIndex) ?>][title]" type="text" value="<?= e((string) ($event['title'] ?? '')) ?>" maxlength="140" placeholder="Nombre del evento flamenco">
                                                </label>
                                                <label>Fecha
                                                    <input name="web_events[<?= e((string) $eventIndex) ?>][date]" type="date" value="<?= e((string) ($event['date'] ?? '')) ?>">
                                                </label>
                                                <label>Hora
                                                    <input name="web_events[<?= e((string) $eventIndex) ?>][time]" type="time" value="<?= e((string) ($event['time'] ?? '')) ?>">
                                                </label>
                                            </div>
                                            <label>Descripcion
                                                <textarea name="web_events[<?= e((string) $eventIndex) ?>][description]" rows="3" maxlength="700" placeholder="Programa, artistas invitados, direccion y detalles clave."><?= e((string) ($event['description'] ?? '')) ?></textarea>
                                            </label>
                                            <div class="member-web-image-row">
                                                <?php if (!empty($event['image_path'])): ?>
                                                    <img src="<?= e((string) $event['image_path']) ?>" alt="Imagen del evento <?= e((string) ($eventIndex + 1)) ?>" loading="lazy">
                                                <?php endif; ?>
                                                <label>Imagen del evento
                                                    <input name="web_events[<?= e((string) $eventIndex) ?>][image]" type="file" accept="image/jpeg,image/png,image/webp">
                                                </label>
                                                <?php if (!empty($event['image_path'])): ?>
                                                    <label><input type="checkbox" name="web_events[<?= e((string) $eventIndex) ?>][remove_image]" value="1"> Quitar imagen actual</label>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="member-web-repeat-actions">
                                    <button type="button" class="button button-secondary" data-web-add="events">Anadir evento</button>
                                    <p class="field-help">Maximo actual: <?= e((string) $maxWebEvents) ?> eventos.</p>
                                </div>
                            </article>

                            <article id="web-actualidad" class="member-config-card">
                                <h3>Actualidad y revista</h3>
                                <p><?= $isVipMember ? 'Puedes publicar hasta 5 articulos y marcarlos para revista.' : 'Como simpatizante puedes publicar 1 articulo y marcarlo para revista.' ?></p>
                                <div class="member-web-repeat-list" data-web-repeat-list="articles" data-web-max="<?= e((string) $maxWebArticles) ?>">
                                    <?php foreach ($webArticles as $articleIndex => $article): ?>
                                        <div class="member-web-repeat-row" data-web-repeat-row>
                                            <label>Titulo del articulo
                                                <input name="web_articles[<?= e((string) $articleIndex) ?>][title]" type="text" value="<?= e((string) ($article['title'] ?? '')) ?>" maxlength="140" placeholder="Titular del articulo">
                                            </label>
                                            <label>Texto breve
                                                <textarea name="web_articles[<?= e((string) $articleIndex) ?>][summary]" rows="4" maxlength="1200" placeholder="Comparte una novedad, lanzamiento o noticia."><?= e((string) ($article['summary'] ?? '')) ?></textarea>
                                            </label>
                                            <div class="member-web-image-row">
                                                <?php if (!empty($article['image_path'])): ?>
                                                    <img src="<?= e((string) $article['image_path']) ?>" alt="Imagen del articulo <?= e((string) ($articleIndex + 1)) ?>" loading="lazy">
                                                <?php endif; ?>
                                                <label>Imagen del articulo
                                                    <input name="web_articles[<?= e((string) $articleIndex) ?>][image]" type="file" accept="image/jpeg,image/png,image/webp">
                                                </label>
                                                <?php if (!empty($article['image_path'])): ?>
                                                    <label><input type="checkbox" name="web_articles[<?= e((string) $articleIndex) ?>][remove_image]" value="1"> Quitar imagen actual</label>
                                                <?php endif; ?>
                                            </div>
                                            <label><input type="checkbox" name="web_articles[<?= e((string) $articleIndex) ?>][submit_to_magazine]" value="1" <?= !empty($article['submit_to_magazine']) ? 'checked' : '' ?>> Marcar para propuesta en revista</label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="member-web-repeat-actions">
                                    <button type="button" class="button button-secondary" data-web-add="articles">Anadir articulo</button>
                                    <p class="field-help">Limite de articulos para tu membresia: <?= e((string) $maxWebArticles) ?>.</p>
                                </div>
                            </article>

                            <article id="web-contacto" class="member-config-card">
                                <h3>Contacto</h3>
                                <p>Elige que datos se mostraran en la seccion Contacto de tu web.</p>
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

        const submitImageUpdate = () => {
            if (!(memberProfileForm instanceof HTMLFormElement)) {
                return;
            }
            if (profileActionInput instanceof HTMLInputElement) {
                profileActionInput.value = 'update_profile_images';
            }
            memberProfileForm.requestSubmit();
        };
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
                    if (input.name) {
                        input.name = input.name.replace(/\[\d+\]/, `[${nextIndex}]`);
                    }
                    if (input instanceof HTMLInputElement && input.type === 'checkbox') {
                        input.checked = input.dataset.defaultChecked !== '0';
                        return;
                    }
                    if (input instanceof HTMLInputElement && input.type === 'number' && input.name.includes('[display_order]')) {
                        input.value = String(nextIndex + 1);
                        return;
                    }
                    input.value = '';
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
                submitImageUpdate();
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
                submitImageUpdate();
            }
        });

        if (memberProfileForm instanceof HTMLFormElement) {
            memberProfileForm.addEventListener('submit', () => {
                if (profileActionInput instanceof HTMLInputElement && profileActionInput.value !== 'update_profile_images') {
                    profileActionInput.value = 'update_profile';
                }
            });
        }

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

        document.querySelectorAll('[data-web-add]').forEach((button) => {
            button.addEventListener('click', () => {
                const repeatKey = button.getAttribute('data-web-add');
                if (!repeatKey) {
                    return;
                }

                const list = document.querySelector(`[data-web-repeat-list="${repeatKey}"]`);
                if (!(list instanceof HTMLElement)) {
                    return;
                }

                const rows = list.querySelectorAll('[data-web-repeat-row]');
                const maxItems = Number(list.getAttribute('data-web-max') || '1');
                if (rows.length >= maxItems) {
                    return;
                }

                const firstRow = rows[0];
                if (!(firstRow instanceof HTMLElement)) {
                    return;
                }

                const clone = firstRow.cloneNode(true);
                const nextIndex = rows.length;
                clone.querySelectorAll('input, textarea').forEach((field) => {
                    if (!(field instanceof HTMLInputElement) && !(field instanceof HTMLTextAreaElement)) {
                        return;
                    }

                    if (field.name) {
                        field.name = field.name.replace(/\[\d+\]/, `[${nextIndex}]`);
                    }

                    if (field instanceof HTMLInputElement && field.type === 'checkbox') {
                        field.checked = false;
                        return;
                    }

                    if (field instanceof HTMLInputElement && field.type === 'file') {
                        field.value = '';
                        return;
                    }

                    field.value = '';
                });

                clone.querySelectorAll('img').forEach((image) => image.remove());
                list.appendChild(clone);
            });
        });

        function activateMemberPanel(target) {
            if (!target) {
                return;
            }
            document.querySelectorAll('.panel-tab-button').forEach((tab) => tab.classList.toggle('active', tab.dataset.tabTarget === target));
            document.querySelectorAll('[data-panel-link]').forEach((link) => link.classList.toggle('active', link.dataset.panelLink === target));
            document.querySelectorAll('.member-panel-section').forEach((section) => {
                section.style.display = section.id === target ? 'block' : 'none';
            });
        }

        document.querySelectorAll('.panel-tab-button').forEach((button) => {
            button.addEventListener('click', () => {
                const target = button.dataset.tabTarget;
                activateMemberPanel(target);
            });
        });

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

    </script>
</body>
</html>
