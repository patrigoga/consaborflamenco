<?php
declare(strict_types=1);

require_once __DIR__ . '/app/auth.php';
require_once __DIR__ . '/app/layout.php';

function artist_public_media_url(string $path): string
{
    $path = trim(str_replace('\\', '/', $path));
    if ($path === '' || str_starts_with($path, 'data:')) {
        return $path;
    }

    $baseUrl = artist_public_base_url();
    $mediaFile = csf_media_file_from_path($path);
    if ($mediaFile !== null) {
        return $baseUrl . '/' . csf_media_url($mediaFile);
    }

    if (preg_match('#^(?:https?:)?//#i', $path)) {
        $parts = parse_url($path);
        $urlPath = str_replace('\\', '/', (string) ($parts['path'] ?? ''));
        $assetPosition = strpos($urlPath, '/assets/');
        if ($assetPosition === false) {
            return $path;
        }

        $path = substr($urlPath, $assetPosition + 1);
    }

    $assetPosition = strpos($path, 'assets/');
    if ($assetPosition !== false) {
        $path = substr($path, $assetPosition);
    }

    return $baseUrl . '/' . ltrim($path, '/');
}

function artist_public_video_embed_url(string $url): string
{
    $url = trim($url);
    if ($url === '') {
        return '';
    }

    $parts = parse_url($url);
    $host = strtolower((string) ($parts['host'] ?? ''));
    $path = (string) ($parts['path'] ?? '');
    parse_str((string) ($parts['query'] ?? ''), $query);

    if (str_contains($host, 'youtube.com') && !empty($query['v'])) {
        return 'https://www.youtube.com/embed/' . rawurlencode((string) $query['v']);
    }
    if (str_contains($host, 'youtu.be')) {
        $videoId = trim($path, '/');
        return $videoId !== '' ? 'https://www.youtube.com/embed/' . rawurlencode($videoId) : '';
    }
    if (str_contains($host, 'vimeo.com')) {
        $videoId = trim($path, '/');
        return $videoId !== '' ? 'https://player.vimeo.com/video/' . rawurlencode($videoId) : '';
    }

    return '';
}

function artist_public_base_url(): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $requestPath = (string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    $scriptPath = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');

    if (preg_match('#^(.*?)/artista(?:/[^/]+)?/?$#', $requestPath, $matches)) {
        $basePath = rtrim($matches[1], '/');
    } elseif (strpos($scriptPath, '/artista.php') !== false) {
        $basePath = rtrim(dirname($scriptPath), '/');
    } else {
        $basePath = rtrim(str_replace('\\', '/', dirname($scriptPath)), '/');
    }

    $basePath = $basePath === '/' ? '' : $basePath;

    return $scheme . '://' . $host . $basePath;
}

function artist_public_link_url(string $url): string
{
    $url = trim($url);
    if ($url === '') {
        return $url;
    }

    if (preg_match('#^[a-z][a-z0-9+.-]*:#i', $url)) {
        return preg_match('#^(?:https?:|mailto:|tel:)#i', $url) ? $url : '#';
    }

    return 'https://' . ltrim($url, '/');
}

/**
 * Dominio limpio de una URL, para no imprimir enlaces crudos en pantalla.
 */
function artist_public_domain_label(string $url, string $fallback = ''): string
{
    $url = trim($url);
    if ($url === '') {
        return $fallback;
    }

    $normalized = preg_match('#^[a-z][a-z0-9+.-]*://#i', $url) === 1 ? $url : 'https://' . ltrim($url, '/');
    $host = strtolower((string) (parse_url($normalized, PHP_URL_HOST) ?? ''));
    $host = (string) preg_replace('/^www\./', '', $host);

    return $host !== '' ? $host : $fallback;
}

/**
 * Convierte un perfil social en `@usuario`. Acepta URL completa o solo el alias.
 */
function artist_public_social_handle(string $url, string $fallback = ''): string
{
    $url = trim($url);
    if ($url === '') {
        return $fallback;
    }

    if (!str_contains($url, '/') && !str_contains($url, '.')) {
        return '@' . ltrim($url, '@');
    }

    $normalized = preg_match('#^[a-z][a-z0-9+.-]*://#i', $url) === 1 ? $url : 'https://' . ltrim($url, '/');
    $path = trim((string) (parse_url($normalized, PHP_URL_PATH) ?? ''), '/');
    $handle = $path !== '' ? explode('/', $path)[0] : '';

    return $handle !== '' ? '@' . ltrim($handle, '@') : artist_public_domain_label($url, $fallback);
}

function artist_public_month_label(int $month): string
{
    $months = [1 => 'ENE', 'FEB', 'MAR', 'ABR', 'MAY', 'JUN', 'JUL', 'AGO', 'SEP', 'OCT', 'NOV', 'DIC'];

    return $months[$month] ?? '';
}

$uri = $_SERVER['REQUEST_URI'] ?? '';
$slug = null;
if (preg_match('#/artista/([a-z0-9\-_%]+)#i', $uri, $matches)) {
    $slug = urldecode($matches[1]);
} elseif (!empty($_GET['slug'])) {
    $slug = $_GET['slug'];
}

$slug = slugify((string) $slug);
if ($slug === '') {
    header('HTTP/1.1 404 Not Found');
    echo 'Not found';
    exit;
}

$member = find_user_by_member_slug($slug);
if (!$member) {
    header('HTTP/1.1 404 Not Found');
    echo 'Artista no encontrado';
    exit;
}

$profile = default_member_profile($member);
$webPage = default_member_web_page(is_array($profile['web_page'] ?? null) ? $profile['web_page'] : []);
$displayName = clean_text((string) ($profile['public_name'] ?: ($member['name'] ?? 'Artista')));
$memberTypeLabel = member_type_options()[$profile['member_type'] ?? 'artista'] ?? 'Artista';
$artistHeadline = clean_text((string) ($profile['artistic_headline'] ?? ''));
$artistLocation = trim(clean_text((string) ($profile['city'] ?? '')) . (((string) ($profile['city'] ?? '') !== '' && (string) ($profile['province'] ?? '') !== '') ? ', ' : '') . clean_text((string) ($profile['province'] ?? '')));
$artistIntro = clean_text((string) (($profile['short_description'] ?? '') ?: ($profile['cv_summary'] ?? '') ?: ($profile['availability'] ?? '')));
$legacyHeroImage = artist_public_media_url(clean_text((string) (($webPage['header_image_path'] ?? '') ?: ($profile['cv_header_image_path'] ?? '') ?: ($profile['main_photo_path'] ?? ''))));
$mainPhoto = artist_public_media_url(clean_text((string) ($profile['main_photo_path'] ?? '')));
$gallery = array_values(array_filter(array_map(
    static fn ($path): string => artist_public_media_url(clean_text((string) $path)),
    array_slice(is_array($webPage['gallery'] ?? null) ? $webPage['gallery'] : [], 0, 9)
), static fn (string $path): bool => $path !== ''));
$videos = array_values(is_array($webPage['videos'] ?? null) ? $webPage['videos'] : []);

$events = array_values(is_array($webPage['events'] ?? null) ? $webPage['events'] : []);
usort($events, static function (array $a, array $b): int {
    $timeA = !empty($a['date']) ? strtotime((string) $a['date']) : false;
    $timeB = !empty($b['date']) ? strtotime((string) $b['date']) : false;

    // Sin fecha, al final: no sabemos cuando es, no se puede ordenar.
    return ($timeA !== false ? $timeA : PHP_INT_MAX) <=> ($timeB !== false ? $timeB : PHP_INT_MAX);
});

$news = array_values(is_array($webPage['news'] ?? null) ? $webPage['news'] : []);
usort($news, static function (array $a, array $b): int {
    $timeA = !empty($a['date']) ? strtotime((string) $a['date']) : false;
    $timeB = !empty($b['date']) ? strtotime((string) $b['date']) : false;

    // Actualidad: la mas reciente primero; sin fecha, al final.
    return ($timeB !== false ? $timeB : -1) <=> ($timeA !== false ? $timeA : -1);
});
$socialLinks = is_array($webPage['social_links'] ?? null) ? $webPage['social_links'] : [];
$contactFields = is_array($webPage['contact_fields'] ?? null) ? $webPage['contact_fields'] : [];
$siteBaseUrl = artist_public_base_url();
$artistPageUrl = $siteBaseUrl . '/artista/' . rawurlencode($slug);
$artistsUrl = $siteBaseUrl . '/artistas.php';
$registerUrl = $siteBaseUrl . '/registro.php';
$defaultHeroImage = artist_public_media_url('assets/images/flamenco-header-art.png');
$homeUrl = $siteBaseUrl . '/index.php#inicio';
$heroSlides = [];
foreach (array_slice(is_array($webPage['hero_slides'] ?? null) ? $webPage['hero_slides'] : [], 0, 3) as $slide) {
    if (!is_array($slide)) {
        continue;
    }

    $slideImage = artist_public_media_url(clean_text((string) ($slide['image_path'] ?? '')));
    $slideTitle = clean_text((string) ($slide['title'] ?? ''));
    $slideDescription = clean_text((string) ($slide['description'] ?? ''));
    $slideCtaUrl = trim((string) ($slide['cta_url'] ?? ''));
    $slideCtaLabel = clean_text((string) ($slide['cta_label'] ?? ''));
    if ($slideImage === '' && $slideTitle === '' && $slideDescription === '' && $slideCtaUrl === '') {
        continue;
    }

    $heroSlides[] = [
        'image' => $slideImage !== '' ? $slideImage : $defaultHeroImage,
        'title' => $slideTitle,
        'description' => $slideDescription,
        'cta_url' => $slideCtaUrl,
        'cta_label' => $slideCtaLabel !== '' ? $slideCtaLabel : 'Ver mas',
    ];
}
if (!$heroSlides) {
    $heroSlides[] = [
        'image' => $legacyHeroImage !== '' ? $legacyHeroImage : $defaultHeroImage,
        'title' => '',
        'description' => '',
        'cta_url' => '',
        'cta_label' => '',
    ];
}
$menuImage = $mainPhoto !== '' ? $mainPhoto : (($heroSlides[0]['image'] ?? '') !== '' ? $heroSlides[0]['image'] : $legacyHeroImage);
$menuImageIsProfile = $mainPhoto !== '';
$pageDescription = clean_text((string) ($heroSlides[0]['description'] ?? '')) ?: $displayName;

// El valor mostrado nunca es la URL cruda: se resume a dominio o `@usuario`.
$contactItems = [];
if (in_array('email', $contactFields, true) && !empty($member['email'])) {
    $contactItems[] = [
        'label' => 'Email',
        'value' => (string) $member['email'],
        'href' => 'mailto:' . (string) $member['email'],
        'icon' => 'mail',
    ];
}
if (in_array('phone', $contactFields, true) && !empty($profile['phone'])) {
    $contactItems[] = [
        'label' => 'Teléfono',
        'value' => (string) $profile['phone'],
        'href' => 'tel:' . preg_replace('/\s+/', '', (string) $profile['phone']),
        'icon' => 'phone',
    ];
}
if (in_array('website', $contactFields, true) && !empty($profile['website_url'])) {
    $contactItems[] = [
        'label' => 'Web',
        'value' => artist_public_domain_label((string) $profile['website_url'], 'Ver web'),
        'href' => artist_public_link_url((string) $profile['website_url']),
        'icon' => 'globe',
    ];
}
if (in_array('instagram', $contactFields, true) && !empty($profile['instagram_url'])) {
    $contactItems[] = [
        'label' => 'Instagram',
        'value' => artist_public_social_handle((string) $profile['instagram_url'], 'Ver perfil'),
        'href' => artist_public_link_url((string) $profile['instagram_url']),
        'icon' => 'instagram',
    ];
}

$publicSections = [];
if ($gallery) {
    $publicSections['galeria'] = 'Galería';
}
if ($videos) {
    $publicSections['videos'] = 'Vídeos';
}
if ($events) {
    $publicSections['eventos'] = 'Agenda';
}
if ($news) {
    $publicSections['actualidad'] = 'Actualidad';
}
if ($contactItems) {
    $publicSections['contacto'] = 'Contacto';
}

/**
 * Cabecera de seccion: banda a todo el ancho con el nombre de la seccion
 * centrado y a gran tamano. Sin numeracion ni antetitulo: el rotulo manda.
 */
function artist_render_section_band(string $title, string $meta = ''): void
{
    ?>
    <div class="ms-section-band">
        <div class="ms-shell ms-section-band-inner" data-reveal>
            <h2><?= e($title) ?></h2>
            <span class="ms-section-ornament" aria-hidden="true"></span>
            <?php if ($meta !== ''): ?>
                <p class="ms-section-meta"><?= e($meta) ?></p>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

/**
 * Destinos de compartir para una URL concreta (una foto de la galeria).
 */
function artist_share_targets(string $url, string $text): array
{
    $encodedUrl = rawurlencode($url);
    $encodedText = rawurlencode($text);

    return [
        ['key' => 'whatsapp', 'label' => 'WhatsApp', 'href' => 'https://wa.me/?text=' . rawurlencode($text . ' ' . $url)],
        ['key' => 'facebook', 'label' => 'Facebook', 'href' => 'https://www.facebook.com/sharer/sharer.php?u=' . $encodedUrl],
        ['key' => 'twitter', 'label' => 'X', 'href' => 'https://twitter.com/intent/tweet?url=' . $encodedUrl . '&text=' . $encodedText],
        ['key' => 'telegram', 'label' => 'Telegram', 'href' => 'https://t.me/share/url?url=' . $encodedUrl . '&text=' . $encodedText],
    ];
}

/**
 * Barra de compartir: redes, compartir nativo del movil y copiar enlace.
 */
function artist_render_share_bar(string $url, string $text, string $groupLabel, array $icons, string $extraClass = ''): void
{
    ?>
    <div class="ms-share<?= $extraClass !== '' ? ' ' . e($extraClass) : '' ?>"
         role="group"
         aria-label="<?= e($groupLabel) ?>"
         data-share
         data-share-url="<?= e($url) ?>"
         data-share-text="<?= e($text) ?>">
        <button type="button" class="ms-share-btn" data-share-native hidden aria-label="Compartir"><?= $icons['share'] ?? '' ?></button>
        <?php foreach (artist_share_targets($url, $text) as $target): ?>
            <a class="ms-share-btn"
               href="<?= e($target['href']) ?>"
               target="_blank"
               rel="noopener"
               data-share-key="<?= e($target['key']) ?>"
               aria-label="Compartir en <?= e($target['label']) ?>"><?= $icons[$target['key']] ?? '' ?></a>
        <?php endforeach; ?>
        <button type="button" class="ms-share-btn" data-share-copy aria-label="Copiar enlace">
            <?= $icons['link'] ?? '' ?>
            <span class="ms-share-toast" aria-hidden="true">Copiado</span>
        </button>
    </div>
    <?php
}

$contactIcons = [
    'mail' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2.5" y="4.5" width="19" height="15" rx="2.5"/><path d="m3.5 6.5 8.5 6 8.5-6"/></svg>',
    'phone' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6.5 3h3l1.5 4-2 1.5a12 12 0 0 0 6.5 6.5l1.5-2 4 1.5v3a2 2 0 0 1-2.2 2A17.5 17.5 0 0 1 4.5 5.2 2 2 0 0 1 6.5 3z"/></svg>',
    'globe' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a15 15 0 0 1 0 18 15 15 0 0 1 0-18z"/></svg>',
    'instagram' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.2" cy="6.8" r="1" fill="currentColor" stroke="none"/></svg>',
];

$socialIcons = [
    'instagram' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><circle cx="12" cy="12" r="4.5"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/></svg>',
    'facebook'  => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>',
    'youtube'   => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M22.54 6.42A2.78 2.78 0 0 0 20.6 4.46C18.88 4 12 4 12 4s-6.88 0-8.6.46A2.78 2.78 0 0 0 1.46 6.42 29 29 0 0 0 1 12a29 29 0 0 0 .46 5.58A2.78 2.78 0 0 0 3.4 19.54C5.12 20 12 20 12 20s6.88 0 8.6-.46a2.78 2.78 0 0 0 1.94-1.96A29 29 0 0 0 23 12a29 29 0 0 0-.46-5.58z"/><polygon fill="#0b0d10" points="9.75 15.02 15.5 12 9.75 8.98 9.75 15.02"/></svg>',
    'tiktok'    => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-2.88 2.5 2.89 2.89 0 0 1-2.89-2.89 2.89 2.89 0 0 1 2.89-2.89c.28 0 .54.04.79.1V9.01a6.33 6.33 0 0 0-.79-.05 6.34 6.34 0 0 0-6.34 6.34 6.34 6.34 0 0 0 6.34 6.34 6.34 6.34 0 0 0 6.33-6.34V8.69a8.15 8.15 0 0 0 4.77 1.52V6.73a4.86 4.86 0 0 1-1-.04z"/></svg>',
    'spotify'   => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path fill="#0b0d10" d="M16.5 16.5a.75.75 0 0 1-.41-.12 8.27 8.27 0 0 0-8.18 0 .75.75 0 0 1-.82-1.26 9.77 9.77 0 0 1 9.82 0 .75.75 0 0 1-.41 1.38zm1.25-2.75a.75.75 0 0 1-.41-.12 10.52 10.52 0 0 0-10.68 0 .75.75 0 0 1-.82-1.26 12 12 0 0 1 12.32 0 .75.75 0 0 1-.41 1.38zm1.25-2.75a.75.75 0 0 1-.41-.12 12.77 12.77 0 0 0-13.18 0 .75.75 0 1 1-.82-1.26 14.27 14.27 0 0 1 14.82 0 .75.75 0 0 1-.41 1.38z"/></svg>',
    'twitter'   => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.744l7.736-8.849L2.25 2.25h6.883l4.254 5.621zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>',
];

// Iconos de la barra de compartir: reutiliza los sociales y anade los canales
// que solo se usan para compartir (mensajeria, enlace, compartir nativo).
$shareIcons = [
    'facebook' => $socialIcons['facebook'],
    'twitter'  => $socialIcons['twitter'],
    'whatsapp' => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12.04 2.01a9.9 9.9 0 0 0-8.5 14.96L2 22.5l5.66-1.48a9.9 9.9 0 1 0 4.38-19.01zm0 1.68a8.22 8.22 0 0 1 6.99 12.55l-.2.31.83 3.03-3.12-.82-.3.18a8.2 8.2 0 0 1-4.2 1.16 8.22 8.22 0 0 1-4.19-15.28 8.2 8.2 0 0 1 4.19-1.13zm4.52 10.3c-.25-.13-1.47-.72-1.69-.81-.23-.09-.4-.13-.56.12-.17.25-.64.8-.79.97-.14.16-.29.18-.54.06-.25-.13-1.06-.4-2.02-1.25-.75-.68-1.25-1.5-1.4-1.75-.14-.25-.01-.38.11-.5.11-.11.25-.29.37-.43.12-.15.17-.25.25-.42.08-.16.04-.31-.02-.43-.06-.13-.56-1.35-.76-1.84-.2-.49-.4-.42-.56-.43h-.48c-.16 0-.42.06-.64.31-.22.25-.83.81-.83 1.98s.85 2.3.97 2.46c.12.16 1.67 2.55 4.04 3.57.56.25 1 .39 1.35.5.56.18 1.07.16 1.47.09.45-.07 1.39-.57 1.58-1.11.2-.55.2-1.02.14-1.12-.06-.1-.22-.16-.47-.28z"/></svg>',
    'telegram' => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M22.05 3.06 2.6 10.55c-1.05.4-1.04 1.9.02 2.29l4.3 1.55 1.62 5.2c.3.96 1.5 1.2 2.13.42l2.28-2.83 4.2 3.1c.75.55 1.82.14 2.01-.78l3.2-15.2c.2-.98-.77-1.79-1.71-1.44zM9.4 14.36l8.02-5.63-6.36 6.83-.16 3.03-1.5-4.23z"/></svg>',
    'link'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.6 13.4a4 4 0 0 1 0-5.66l2.83-2.83a4 4 0 0 1 5.66 5.66l-1.42 1.41"/><path d="M13.4 10.6a4 4 0 0 1 0 5.66l-2.83 2.83a4 4 0 0 1-5.66-5.66l1.42-1.41"/></svg>',
    'share'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="18" cy="5.2" r="2.6"/><circle cx="6" cy="12" r="2.6"/><circle cx="18" cy="18.8" r="2.6"/><path d="m8.3 10.8 7.4-4.3m-7.4 6.7 7.4 4.3"/></svg>',
];

$shareText = $displayName . ' — Galería';

?>
<!DOCTYPE html>
<html lang="es">
<?php page_head($displayName . ' | Con Sabor Flamenco', $pageDescription, false, ['assets/css/artist-microsite.css']); ?>
<body class="artist-public-body">
<script>document.body.classList.add('ms-js');</script>

<a class="ms-skip" href="#contenido">Saltar al contenido</a>

<header class="ms-topbar" data-topbar>
    <div class="ms-shell ms-topbar-inner">
        <a class="ms-brand" href="#inicio">
            <?php if ($menuImage !== ''): ?>
                <img src="<?= e($menuImage) ?>" alt="<?= e($menuImageIsProfile ? 'Foto de perfil de ' . $displayName : 'Imagen de cabecera de ' . $displayName) ?>" loading="eager">
            <?php endif; ?>
            <span><?= e($displayName) ?></span>
        </a>

        <nav class="ms-nav" aria-label="Secciones de <?= e($displayName) ?>">
            <?php foreach ($publicSections as $sectionId => $sectionLabel): ?>
                <a href="#<?= e($sectionId) ?>" data-nav-link="<?= e($sectionId) ?>"><?= e($sectionLabel) ?></a>
            <?php endforeach; ?>
        </nav>

        <?php if ($socialLinks): ?>
            <div class="ms-social">
                <?php foreach ($socialLinks as $network => $url): ?>
                    <?php if (!empty($socialIcons[$network])): ?>
                        <a href="<?= e(artist_public_link_url((string) $url)) ?>" target="_blank" rel="noopener" aria-label="<?= e(ucfirst((string) $network)) ?> de <?= e($displayName) ?>"><?= $socialIcons[$network] ?></a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</header>

<main id="contenido">
    <section id="inicio" class="ms-hero" data-hero>
        <?php foreach ($heroSlides as $slideIndex => $slide): ?>
            <?php $slideImage = (string) ($slide['image'] ?? $defaultHeroImage); ?>
            <div class="ms-hero-slide<?= $slideIndex === 0 ? ' is-active' : '' ?>"
                 style="background-image: url('<?= e(str_replace("'", '%27', $slideImage)) ?>');"
                 data-hero-slide
                 role="img"
                 aria-label="<?= e($displayName) ?>"></div>
        <?php endforeach; ?>

        <div class="ms-shell ms-hero-inner">
            <div class="ms-hero-content">
                <p class="ms-kicker"><?= e($artistHeadline !== '' ? $artistHeadline : $memberTypeLabel) ?></p>
                <h1><?= e($displayName) ?></h1>

                <div class="ms-hero-captions">
                    <?php foreach ($heroSlides as $slideIndex => $slide): ?>
                        <div class="ms-hero-caption<?= $slideIndex === 0 ? ' is-active' : '' ?>" data-hero-caption>
                            <?php
                            $slideText = trim((string) ($slide['description'] ?: $slide['title']));
                            if ($slideText === '' && $slideIndex === 0) {
                                $slideText = $artistIntro;
                            }
                            ?>
                            <?php if ($slideText !== ''): ?>
                                <p class="ms-hero-summary"><?= e($slideText) ?></p>
                            <?php endif; ?>
                            <?php if (($slide['cta_url'] ?? '') !== ''): ?>
                                <a class="ms-hero-cta" href="<?= e(artist_public_link_url((string) $slide['cta_url'])) ?>" target="_blank" rel="noopener"><?= e((string) ($slide['cta_label'] ?: 'Ver mas')) ?></a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <?php if (count($heroSlides) > 1): ?>
            <div class="ms-hero-dots" aria-label="Selector de cabecera">
                <?php foreach ($heroSlides as $slideIndex => $slide): ?>
                    <button type="button" class="<?= $slideIndex === 0 ? 'is-active' : '' ?>" data-hero-dot aria-label="Ver imagen <?= e((string) ($slideIndex + 1)) ?>"></button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <?php if ($gallery): ?>
        <?php
        $galleryIsSlider = count($gallery) > 3;
        $galleryCount = count($gallery);
        $galleryCountLabel = $galleryCount . ' ' . ($galleryCount === 1 ? 'fotografía' : 'fotografías');
        $galleryZoomIcon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="10.5" cy="10.5" r="6.5"/><path d="M15.2 15.2 20 20M10.5 7.5v6M7.5 10.5h6"/></svg>';
        ?>
        <section id="galeria" class="ms-section">
            <?php artist_render_section_band('Galería', $galleryCountLabel); ?>
            <div class="ms-shell">
                <div class="ms-gallery<?= $galleryIsSlider ? ' is-slider' : '' ?>" data-gallery>
                    <div class="ms-gallery-viewport" data-gallery-viewport<?= $galleryIsSlider ? ' tabindex="0" role="group" aria-label="Galería de ' . e($displayName) . ', desliza para ver más fotos"' : '' ?>>
                        <div class="ms-gallery-track">
                            <?php foreach ($gallery as $galleryIndex => $galleryImage): ?>
                                <?php $photoShareUrl = $artistPageUrl . '?foto=' . ($galleryIndex + 1); ?>
                                <figure class="ms-gallery-item"<?= $galleryIsSlider ? '' : ' data-reveal' ?>>
                                    <button type="button" class="ms-gallery-open" data-gallery-item data-full="<?= e($galleryImage) ?>" data-share-url="<?= e($photoShareUrl) ?>" aria-label="Ampliar imagen <?= e((string) ($galleryIndex + 1)) ?> de <?= e($displayName) ?>">
                                        <img src="<?= e($galleryImage) ?>" alt="Fotografía de <?= e($displayName) ?>" loading="lazy">
                                        <span class="ms-gallery-index" aria-hidden="true"><?= e(sprintf('%02d', $galleryIndex + 1)) ?></span>
                                        <span class="ms-gallery-zoom" aria-hidden="true"><?= $galleryZoomIcon ?></span>
                                    </button>
                                    <?php artist_render_share_bar($photoShareUrl, $shareText, 'Compartir imagen ' . ($galleryIndex + 1), $shareIcons, 'ms-gallery-share'); ?>
                                </figure>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <?php if ($galleryIsSlider): ?>
                        <button type="button" class="ms-gallery-nav ms-gallery-prev" data-gallery-prev aria-label="Ver fotos anteriores">&lsaquo;</button>
                        <button type="button" class="ms-gallery-nav ms-gallery-next" data-gallery-next aria-label="Ver fotos siguientes">&rsaquo;</button>
                    <?php endif; ?>
                </div>

                <?php if ($galleryIsSlider): ?>
                    <!-- Fuera de .ms-gallery: asi las flechas quedan centradas sobre las fotos -->
                    <div class="ms-gallery-dots" data-gallery-dots role="group" aria-label="Grupos de fotos"></div>
                <?php endif; ?>

                <div class="ms-rule"></div>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($videos): ?>
        <section id="videos" class="ms-section">
            <?php artist_render_section_band('Vídeos'); ?>
            <div class="ms-shell">
                <div class="ms-video-grid">
                    <?php foreach ($videos as $video): ?>
                        <?php
                        $videoUrl = trim((string) ($video['url'] ?? ''));
                        $embedUrl = artist_public_video_embed_url($videoUrl);
                        ?>
                        <article class="ms-video-card" data-reveal>
                            <figure>
                                <?php if ($embedUrl !== ''): ?>
                                    <iframe src="<?= e($embedUrl) ?>" title="<?= e((string) (($video['title'] ?? '') ?: 'Vídeo de ' . $displayName)) ?>" loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
                                <?php else: ?>
                                    <a class="ms-video-link" href="<?= e(artist_public_link_url($videoUrl)) ?>" target="_blank" rel="noopener">Ver vídeo</a>
                                <?php endif; ?>
                            </figure>
                            <?php if (!empty($video['title'])): ?><h3><?= e((string) $video['title']) ?></h3><?php endif; ?>
                            <?php if (!empty($video['description'])): ?><p><?= nl2br(e((string) $video['description'])) ?></p><?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($events): ?>
        <?php $eventsHaveList = count($events) > 1; ?>
        <section id="eventos" class="ms-section">
            <?php artist_render_section_band('Agenda'); ?>
            <div class="ms-shell">
                <div class="ms-agenda-layout<?= $eventsHaveList ? ' has-list' : '' ?>">
                    <div class="ms-agenda">
                        <?php foreach ($events as $eventIndex => $ev): ?>
                            <?php $eventTimestamp = !empty($ev['date']) ? strtotime((string) $ev['date']) : false; ?>
                            <article class="ms-agenda-row" id="ms-evento-<?= e((string) $eventIndex) ?>" data-agenda-row="<?= e((string) $eventIndex) ?>" data-reveal>
                                <?php if (!empty($ev['date'])): ?>
                                    <div class="ms-agenda-date">
                                        <?php if ($eventTimestamp): ?>
                                            <strong><?= e(date('d', $eventTimestamp)) ?></strong>
                                            <span><?= e(artist_public_month_label((int) date('n', $eventTimestamp))) ?></span>
                                        <?php else: ?>
                                            <span><?= e((string) $ev['date']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <div class="ms-agenda-body">
                                    <?php if ($eventTimestamp || !empty($ev['time'])): ?>
                                        <p class="ms-agenda-time">
                                            <?php if ($eventTimestamp): ?><?= e(date('Y', $eventTimestamp)) ?><?php endif; ?>
                                            <?php if ($eventTimestamp && !empty($ev['time'])): ?> · <?php endif; ?>
                                            <?php if (!empty($ev['time'])): ?><?= e((string) $ev['time']) ?> h<?php endif; ?>
                                        </p>
                                    <?php endif; ?>
                                    <?php if (!empty($ev['title'])): ?><h3><?= e((string) $ev['title']) ?></h3><?php endif; ?>
                                    <?php if (!empty($ev['description'])): ?><p><?= nl2br(e((string) $ev['description'])) ?></p><?php endif; ?>
                                    <?php if (!empty($ev['url'])): ?>
                                        <a class="ms-agenda-link" href="<?= e(artist_public_link_url((string) $ev['url'])) ?>" target="_blank" rel="noopener">Ver evento</a>
                                    <?php endif; ?>
                                </div>

                                <?php if (!empty($ev['image_path'])): ?>
                                    <div class="ms-agenda-thumb">
                                        <img src="<?= e(artist_public_media_url((string) $ev['image_path'])) ?>" alt="<?= e((string) ($ev['title'] ?? 'Evento')) ?>" loading="lazy">
                                    </div>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($eventsHaveList): ?>
                        <aside class="ms-agenda-aside" data-reveal aria-label="Todas las fechas de <?= e($displayName) ?>">
                            <p class="ms-agenda-aside-title">Todas las fechas</p>
                            <nav class="ms-agenda-list" data-agenda-nav>
                                <?php foreach ($events as $eventIndex => $ev): ?>
                                    <?php $listTimestamp = !empty($ev['date']) ? strtotime((string) $ev['date']) : false; ?>
                                    <a class="ms-agenda-list-item" href="#ms-evento-<?= e((string) $eventIndex) ?>" data-agenda-link="<?= e((string) $eventIndex) ?>">
                                        <span class="ms-agenda-list-date"><?= $listTimestamp ? e(date('d', $listTimestamp) . ' ' . artist_public_month_label((int) date('n', $listTimestamp))) : '—' ?></span>
                                        <span class="ms-agenda-list-title"><?= e((string) ($ev['title'] ?? 'Evento')) ?></span>
                                    </a>
                                <?php endforeach; ?>
                            </nav>
                        </aside>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($news): ?>
        <?php
        $featuredNews = $news[0];
        $restNews = array_slice($news, 1);
        ?>
        <section id="actualidad" class="ms-section">
            <?php artist_render_section_band('Actualidad'); ?>
            <div class="ms-shell">
                <article class="ms-news-featured" data-reveal>
                    <?php if (!empty($featuredNews['image_path'])): ?>
                        <div class="ms-news-featured-media">
                            <img src="<?= e(artist_public_media_url((string) $featuredNews['image_path'])) ?>" alt="<?= e((string) ($featuredNews['title'] ?? 'Actualidad')) ?>" loading="lazy">
                        </div>
                    <?php endif; ?>
                    <div class="ms-news-featured-body">
                        <p class="ms-news-featured-kicker">Última actualidad</p>
                        <?php if (!empty($featuredNews['date'])): ?>
                            <p class="ms-card-meta"><?php $ts = strtotime((string) $featuredNews['date']); echo $ts ? e(date('d/m/Y', $ts)) : e((string) $featuredNews['date']); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($featuredNews['title'])): ?><h3><?= e((string) $featuredNews['title']) ?></h3><?php endif; ?>
                        <?php if (!empty($featuredNews['summary'])): ?><p><?= nl2br(e((string) $featuredNews['summary'])) ?></p><?php endif; ?>
                        <?php if (!empty($featuredNews['url'])): ?>
                            <a class="ms-agenda-link" href="<?= e(artist_public_link_url((string) $featuredNews['url'])) ?>" target="_blank" rel="noopener">Leer más</a>
                        <?php endif; ?>
                    </div>
                </article>

                <?php if ($restNews): ?>
                    <div class="ms-cards">
                        <?php foreach ($restNews as $item): ?>
                            <article class="ms-card" data-reveal>
                                <?php if (!empty($item['image_path'])): ?>
                                    <img src="<?= e(artist_public_media_url((string) $item['image_path'])) ?>" alt="<?= e((string) ($item['title'] ?? 'Actualidad')) ?>" loading="lazy">
                                <?php endif; ?>
                                <div class="ms-card-body">
                                    <?php if (!empty($item['date'])): ?>
                                        <p class="ms-card-meta"><?php $ts = strtotime((string) $item['date']); echo $ts ? e(date('d/m/Y', $ts)) : e((string) $item['date']); ?></p>
                                    <?php endif; ?>
                                    <?php if (!empty($item['title'])): ?><h3><?= e((string) $item['title']) ?></h3><?php endif; ?>
                                    <?php if (!empty($item['summary'])): ?><p><?= nl2br(e((string) $item['summary'])) ?></p><?php endif; ?>
                                    <?php if (!empty($item['url'])): ?>
                                        <a class="ms-agenda-link" href="<?= e(artist_public_link_url((string) $item['url'])) ?>" target="_blank" rel="noopener">Leer más</a>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($contactItems): ?>
        <section id="contacto" class="ms-section">
            <?php artist_render_section_band('Contacto'); ?>
            <div class="ms-shell">
                <p class="ms-contact-note" data-reveal>Disponible para actuaciones, colaboraciones y clases. Escribe directamente por el canal que prefieras.</p>

                <div class="ms-contact-list" data-reveal>
                    <?php foreach ($contactItems as $contactItem): ?>
                        <a href="<?= e($contactItem['href']) ?>" <?= str_starts_with((string) $contactItem['href'], 'http') ? 'target="_blank" rel="noopener"' : '' ?>>
                            <span class="ms-contact-icon"><?= $contactIcons[$contactItem['icon']] ?? '' ?></span>
                            <span class="ms-contact-text">
                                <span><?= e($contactItem['label']) ?></span>
                                <strong><?= e($contactItem['value']) ?></strong>
                            </span>
                            <span class="ms-contact-arrow" aria-hidden="true">→</span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>
</main>

<footer class="ms-footer">
    <div class="ms-shell">
        <nav class="ms-footer-nav" aria-label="Secciones de <?= e($displayName) ?>">
            <a href="#inicio">Inicio</a>
            <?php foreach ($publicSections as $sectionId => $sectionLabel): ?>
                <a href="#<?= e($sectionId) ?>"><?= e($sectionLabel) ?></a>
            <?php endforeach; ?>
        </nav>

        <div class="ms-footer-inner">
            <div>
                <p class="ms-footer-name"><?= e($displayName) ?></p>
                <p class="ms-footer-note">Perfil público en consaborflamenco.com</p>
            </div>
            <a class="ms-footer-back" href="<?= e($homeUrl) ?>">Con Sabor Flamenco &nbsp;→</a>
        </div>
    </div>
</footer>

<?php if ($gallery): ?>
    <div class="ms-lightbox" data-lightbox hidden role="dialog" aria-modal="true" aria-label="Imagen ampliada">
        <button type="button" class="ms-lightbox-btn ms-lightbox-close" data-lightbox-close aria-label="Cerrar">&times;</button>
        <div class="ms-lightbox-stage">
            <button type="button" class="ms-lightbox-btn ms-lightbox-prev" data-lightbox-prev aria-label="Imagen anterior">&lsaquo;</button>
            <img data-lightbox-image src="" alt="">
            <button type="button" class="ms-lightbox-btn ms-lightbox-next" data-lightbox-next aria-label="Imagen siguiente">&rsaquo;</button>
        </div>
        <?php artist_render_share_bar($artistPageUrl, $shareText, 'Compartir esta imagen', $shareIcons, 'ms-lightbox-share'); ?>
    </div>
<?php endif; ?>

<script>
(() => {
    'use strict';
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* --- Cabecera: se opaca al bajar ------------------------------------- */
    const topbar = document.querySelector('[data-topbar]');
    if (topbar) {
        const syncTopbar = () => topbar.classList.toggle('is-scrolled', window.scrollY > 40);
        syncTopbar();
        window.addEventListener('scroll', syncTopbar, { passive: true });
    }

    /* --- Hero: imagen y texto cambian a la vez --------------------------- */
    const slides = Array.from(document.querySelectorAll('[data-hero-slide]'));
    const captions = Array.from(document.querySelectorAll('[data-hero-caption]'));
    const dots = Array.from(document.querySelectorAll('[data-hero-dot]'));
    if (slides.length > 1) {
        let current = 0;
        let timer = null;
        const show = (next) => {
            current = (next + slides.length) % slides.length;
            slides.forEach((el, i) => el.classList.toggle('is-active', i === current));
            captions.forEach((el, i) => el.classList.toggle('is-active', i === current));
            dots.forEach((el, i) => el.classList.toggle('is-active', i === current));
        };
        const restart = () => {
            if (timer) { window.clearInterval(timer); }
            timer = window.setInterval(() => show(current + 1), 7000);
        };
        dots.forEach((dot, i) => dot.addEventListener('click', () => { show(i); restart(); }));
        restart();
    }

    /* --- Aparicion progresiva -------------------------------------------- */
    const revealables = Array.from(document.querySelectorAll('[data-reveal]'));
    if (revealables.length) {
        if (reduceMotion || !('IntersectionObserver' in window)) {
            revealables.forEach((el) => el.classList.add('is-visible'));
        } else {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (!entry.isIntersecting) { return; }
                    const siblings = Array.from(entry.target.parentElement ? entry.target.parentElement.children : []);
                    const position = siblings.indexOf(entry.target);
                    entry.target.style.transitionDelay = Math.min(Math.max(position, 0), 5) * 90 + 'ms';
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                });
            }, { rootMargin: '0px 0px -12% 0px', threshold: 0.08 });
            revealables.forEach((el) => observer.observe(el));
        }
    }

    /* --- Menu: marca la seccion visible ---------------------------------- */
    const navLinks = Array.from(document.querySelectorAll('[data-nav-link]'));
    const sections = navLinks
        .map((link) => document.getElementById(link.dataset.navLink))
        .filter((section) => section instanceof HTMLElement);
    if (sections.length && 'IntersectionObserver' in window) {
        const spy = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) { return; }
                navLinks.forEach((link) => link.classList.toggle('is-current', link.dataset.navLink === entry.target.id));
            });
        }, { rootMargin: '-45% 0px -50% 0px' });
        sections.forEach((section) => spy.observe(section));
    }

    /* --- Agenda: el listado lateral marca la fila visible ------------------ */
    const agendaLinks = Array.from(document.querySelectorAll('[data-agenda-link]'));
    const agendaRows = Array.from(document.querySelectorAll('[data-agenda-row]'));
    if (agendaLinks.length && agendaRows.length) {
        const setCurrent = (index) => {
            agendaLinks.forEach((link) => link.classList.toggle('is-current', link.dataset.agendaLink === index));
        };
        agendaLinks.forEach((link) => link.addEventListener('click', () => setCurrent(link.dataset.agendaLink || '')));
        if ('IntersectionObserver' in window) {
            const agendaSpy = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (!entry.isIntersecting) { return; }
                    setCurrent(entry.target.dataset.agendaRow || '');
                });
            }, { rootMargin: '-45% 0px -50% 0px' });
            agendaRows.forEach((row) => agendaSpy.observe(row));
        }
    }

    /* --- Compartir: nativo del movil, redes y copiar enlace --------------- */
    const copyToClipboard = async (text) => {
        try {
            if (navigator.clipboard && window.isSecureContext) {
                await navigator.clipboard.writeText(text);
                return true;
            }
        } catch (error) { /* se prueba el metodo antiguo */ }

        const helper = document.createElement('textarea');
        helper.value = text;
        helper.setAttribute('readonly', '');
        helper.style.position = 'fixed';
        helper.style.top = '-1000px';
        helper.style.opacity = '0';
        document.body.appendChild(helper);
        helper.select();
        let copied = false;
        try { copied = document.execCommand('copy'); } catch (error) { copied = false; }
        helper.remove();

        return copied;
    };

    Array.from(document.querySelectorAll('[data-share]')).forEach((group) => {
        const nativeButton = group.querySelector('[data-share-native]');
        if (nativeButton && typeof navigator.share === 'function') {
            nativeButton.hidden = false;
            nativeButton.addEventListener('click', () => {
                navigator.share({
                    title: group.dataset.shareText || document.title,
                    text: group.dataset.shareText || '',
                    url: group.dataset.shareUrl || window.location.href,
                }).catch(() => {});
            });
        }

        const copyButton = group.querySelector('[data-share-copy]');
        if (copyButton) {
            copyButton.addEventListener('click', async () => {
                await copyToClipboard(group.dataset.shareUrl || window.location.href);
                copyButton.classList.add('is-copied');
                window.setTimeout(() => copyButton.classList.remove('is-copied'), 1800);
            });
        }
    });

    /* --- Galeria: con mas de tres fotos, carrusel por paginas ------------- */
    const galleryBox = document.querySelector('[data-gallery].is-slider');
    if (galleryBox) {
        const viewport = galleryBox.querySelector('[data-gallery-viewport]');
        const track = galleryBox.querySelector('.ms-gallery-track');
        const figures = Array.from(galleryBox.querySelectorAll('.ms-gallery-item'));
        const prevButton = galleryBox.querySelector('[data-gallery-prev]');
        const nextButton = galleryBox.querySelector('[data-gallery-next]');
        const dotsBox = galleryBox.parentElement
            ? galleryBox.parentElement.querySelector('[data-gallery-dots]')
            : null;
        const behavior = reduceMotion ? 'auto' : 'smooth';
        let dots = [];

        const metrics = () => {
            const itemWidth = figures[0].getBoundingClientRect().width;
            const gap = parseFloat(window.getComputedStyle(track).columnGap) || 0;
            const perView = itemWidth > 0
                ? Math.max(1, Math.round((viewport.clientWidth + gap) / (itemWidth + gap)))
                : 1;

            return {
                pageWidth: (itemWidth + gap) * perView,
                pages: Math.max(1, Math.ceil(figures.length / perView)),
            };
        };

        const buildDots = (pages) => {
            if (!dotsBox || dots.length === pages) { return; }
            dotsBox.textContent = '';
            dots = Array.from({ length: pages }, (unused, position) => {
                const dot = document.createElement('button');
                dot.type = 'button';
                dot.setAttribute('aria-label', 'Ver grupo de fotos ' + (position + 1));
                dot.addEventListener('click', () => viewport.scrollTo({ left: position * metrics().pageWidth, behavior }));
                dotsBox.appendChild(dot);
                return dot;
            });
            dotsBox.hidden = pages < 2;
        };

        const sync = () => {
            const { pageWidth, pages } = metrics();
            buildDots(pages);
            const atEnd = viewport.scrollLeft >= viewport.scrollWidth - viewport.clientWidth - 2;
            prevButton.disabled = viewport.scrollLeft <= 2;
            nextButton.disabled = atEnd;
            // En la ultima pagina el scroll queda recortado por el maximo, asi que
            // se fuerza el punto final en lugar de calcularlo por division.
            const current = atEnd
                ? pages - 1
                : Math.min(pages - 1, pageWidth > 0 ? Math.round(viewport.scrollLeft / pageWidth) : 0);
            dots.forEach((dot, position) => dot.classList.toggle('is-active', position === current));
        };

        const step = (delta) => viewport.scrollBy({ left: delta * metrics().pageWidth, behavior });
        prevButton.addEventListener('click', () => step(-1));
        nextButton.addEventListener('click', () => step(1));
        viewport.addEventListener('scroll', sync, { passive: true });
        window.addEventListener('resize', sync);
        sync();
    }

    /* --- Galeria a pantalla completa -------------------------------------- */
    const lightbox = document.querySelector('[data-lightbox]');
    const items = Array.from(document.querySelectorAll('[data-gallery-item]'));
    if (lightbox && items.length) {
        const image = lightbox.querySelector('[data-lightbox-image]');
        const prev = lightbox.querySelector('[data-lightbox-prev]');
        const next = lightbox.querySelector('[data-lightbox-next]');
        const shareBar = lightbox.querySelector('[data-share]');
        const shareLinks = shareBar ? Array.from(shareBar.querySelectorAll('[data-share-key]')) : [];
        const sources = items.map((item) => item.dataset.full || '');
        let index = 0;
        let opener = null;

        // Los enlaces de compartir del visor se copian de la propia foto: asi no
        // se duplican las plantillas de URL de cada red en el JavaScript.
        const syncShare = () => {
            if (!shareBar) { return; }
            const itemShare = items[index].parentElement
                ? items[index].parentElement.querySelector('[data-share]')
                : null;
            if (items[index].dataset.shareUrl) {
                shareBar.dataset.shareUrl = items[index].dataset.shareUrl;
            }
            shareLinks.forEach((link) => {
                const origin = itemShare
                    ? itemShare.querySelector('[data-share-key="' + link.dataset.shareKey + '"]')
                    : null;
                if (origin) { link.href = origin.href; }
            });
        };

        const render = () => {
            image.src = sources[index];
            image.alt = items[index].querySelector('img')?.alt || '';
            syncShare();
        };
        const open = (at) => {
            index = at;
            opener = items[at];
            render();
            lightbox.hidden = false;
            document.body.classList.add('modal-open');
            lightbox.querySelector('[data-lightbox-close]').focus();
        };
        const close = () => {
            lightbox.hidden = true;
            image.src = '';
            document.body.classList.remove('modal-open');
            if (opener) { opener.focus(); }
        };
        const step = (delta) => {
            index = (index + delta + sources.length) % sources.length;
            render();
        };

        items.forEach((item, at) => item.addEventListener('click', () => open(at)));
        lightbox.querySelector('[data-lightbox-close]').addEventListener('click', close);
        prev.addEventListener('click', () => step(-1));
        next.addEventListener('click', () => step(1));
        lightbox.addEventListener('click', (event) => {
            if (event.target === lightbox) { close(); }
        });
        document.addEventListener('keydown', (event) => {
            if (lightbox.hidden) { return; }
            if (event.key === 'Escape') { close(); }
            if (event.key === 'ArrowLeft') { step(-1); }
            if (event.key === 'ArrowRight') { step(1); }
        });

        if (sources.length < 2) {
            prev.hidden = true;
            next.hidden = true;
        }

        // Enlace compartido del tipo ?foto=3: abre directamente esa imagen.
        const requestedPhoto = parseInt(new URLSearchParams(window.location.search).get('foto') || '', 10);
        if (!Number.isNaN(requestedPhoto) && requestedPhoto >= 1 && requestedPhoto <= items.length) {
            window.requestAnimationFrame(() => open(requestedPhoto - 1));
        }
    }
})();
</script>
</body>
</html>
