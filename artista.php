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
$news = array_values(is_array($webPage['news'] ?? null) ? $webPage['news'] : []);
$socialLinks = is_array($webPage['social_links'] ?? null) ? $webPage['social_links'] : [];
$contactFields = is_array($webPage['contact_fields'] ?? null) ? $webPage['contact_fields'] : [];
$siteBaseUrl = artist_public_base_url();
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
if ($artistIntro !== '') {
    $publicSections['perfil'] = 'Perfil';
}
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

$introParagraphs = array_values(array_filter(array_map('trim', preg_split('/\R{2,}/', $artistIntro) ?: [])));
if (!$introParagraphs && $artistIntro !== '') {
    $introParagraphs = [$artistIntro];
}
// La capitular solo luce con un texto de cierta extension.
$introHasDropcap = mb_strlen($artistIntro) >= 240;
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

                <?php if ($memberTypeLabel !== '' || $artistLocation !== ''): ?>
                    <div class="ms-hero-meta">
                        <?php if ($memberTypeLabel !== ''): ?><span><?= e($memberTypeLabel) ?></span><?php endif; ?>
                        <?php if ($artistLocation !== ''): ?><span><?= e($artistLocation) ?></span><?php endif; ?>
                    </div>
                <?php endif; ?>

                <div class="ms-hero-captions">
                    <?php foreach ($heroSlides as $slideIndex => $slide): ?>
                        <div class="ms-hero-caption<?= $slideIndex === 0 ? ' is-active' : '' ?>" data-hero-caption>
                            <?php $slideText = trim((string) ($slide['description'] ?: $slide['title'])); ?>
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

    <?php if ($introParagraphs): ?>
        <section id="perfil" class="ms-section ms-section-alt">
            <div class="ms-shell">
                <div class="ms-heading" data-reveal>
                    <div class="ms-heading-main">
                        <p class="ms-kicker">Perfil</p>
                        <h2>Sobre <?= e($displayName) ?></h2>
                    </div>
                </div>

                <div class="ms-bio">
                    <?php if ($mainPhoto !== ''): ?>
                        <div class="ms-bio-portrait" data-reveal>
                            <img src="<?= e($mainPhoto) ?>" alt="Retrato de <?= e($displayName) ?>" loading="lazy">
                        </div>
                    <?php endif; ?>
                    <div class="ms-bio-text<?= $introHasDropcap ? ' has-dropcap' : '' ?>" data-reveal>
                        <?php foreach ($introParagraphs as $paragraph): ?>
                            <p><?= nl2br(e($paragraph)) ?></p>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($gallery): ?>
        <section id="galeria" class="ms-section">
            <div class="ms-shell">
                <div class="ms-heading" data-reveal>
                    <div class="ms-heading-main">
                        <p class="ms-kicker">Galería</p>
                        <h2>En escena</h2>
                    </div>
                    <p class="ms-heading-lead">Una selección de imágenes del trabajo de <?= e($displayName) ?>. Pulsa sobre cualquiera para verla a pantalla completa.</p>
                </div>

                <div class="ms-gallery-grid" data-gallery>
                    <?php foreach ($gallery as $galleryIndex => $galleryImage): ?>
                        <button type="button" class="ms-gallery-item" data-gallery-item data-full="<?= e($galleryImage) ?>" data-reveal aria-label="Ampliar imagen <?= e((string) ($galleryIndex + 1)) ?> de <?= e($displayName) ?>">
                            <img src="<?= e($galleryImage) ?>" alt="Fotografía de <?= e($displayName) ?>" loading="lazy">
                        </button>
                    <?php endforeach; ?>
                </div>

                <div class="ms-rule"></div>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($videos): ?>
        <section id="videos" class="ms-section ms-section-alt">
            <div class="ms-shell">
                <div class="ms-heading" data-reveal>
                    <div class="ms-heading-main">
                        <p class="ms-kicker">Vídeos</p>
                        <h2>En movimiento</h2>
                    </div>
                    <p class="ms-heading-lead">Una selección audiovisual del trabajo artístico de <?= e($displayName) ?>.</p>
                </div>

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
        <section id="eventos" class="ms-section">
            <div class="ms-shell">
                <div class="ms-heading" data-reveal>
                    <div class="ms-heading-main">
                        <p class="ms-kicker">Agenda</p>
                        <h2>Próximas citas</h2>
                    </div>
                </div>

                <div class="ms-agenda">
                    <?php foreach ($events as $ev): ?>
                        <?php $eventTimestamp = !empty($ev['date']) ? strtotime((string) $ev['date']) : false; ?>
                        <article class="ms-agenda-row" data-reveal>
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
            </div>
        </section>
    <?php endif; ?>

    <?php if ($news): ?>
        <section id="actualidad" class="ms-section ms-section-alt">
            <div class="ms-shell">
                <div class="ms-heading" data-reveal>
                    <div class="ms-heading-main">
                        <p class="ms-kicker">Actualidad</p>
                        <h2>Novedades</h2>
                    </div>
                    <p class="ms-heading-lead">Últimas noticias, convocatorias y contenidos destacados de <?= e($displayName) ?>.</p>
                </div>

                <div class="ms-cards">
                    <?php foreach ($news as $item): ?>
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
            </div>
        </section>
    <?php endif; ?>

    <?php if ($contactItems): ?>
        <section id="contacto" class="ms-section">
            <div class="ms-shell">
                <div class="ms-contact">
                    <div data-reveal>
                        <p class="ms-kicker">Contacto</p>
                        <p class="ms-contact-claim">Hablemos de tu próximo espectáculo</p>
                        <p class="ms-contact-note">Disponible para actuaciones, colaboraciones y clases. Escribe directamente por el canal que prefieras.</p>
                    </div>

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
            </div>
        </section>
    <?php endif; ?>
</main>

<footer class="ms-footer">
    <div class="ms-shell ms-footer-inner">
        <div>
            <p class="ms-footer-name"><?= e($displayName) ?></p>
            <p class="ms-footer-note">Perfil público en consaborflamenco.com</p>
        </div>
        <a class="ms-footer-back" href="<?= e($homeUrl) ?>">Con Sabor Flamenco &nbsp;→</a>
    </div>
</footer>

<?php if ($gallery): ?>
    <div class="ms-lightbox" data-lightbox hidden role="dialog" aria-modal="true" aria-label="Imagen ampliada">
        <button type="button" class="ms-lightbox-btn ms-lightbox-close" data-lightbox-close aria-label="Cerrar">&times;</button>
        <button type="button" class="ms-lightbox-btn ms-lightbox-prev" data-lightbox-prev aria-label="Imagen anterior">&lsaquo;</button>
        <img data-lightbox-image src="" alt="">
        <button type="button" class="ms-lightbox-btn ms-lightbox-next" data-lightbox-next aria-label="Imagen siguiente">&rsaquo;</button>
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

    /* --- Galeria a pantalla completa -------------------------------------- */
    const lightbox = document.querySelector('[data-lightbox]');
    const items = Array.from(document.querySelectorAll('[data-gallery-item]'));
    if (lightbox && items.length) {
        const image = lightbox.querySelector('[data-lightbox-image]');
        const prev = lightbox.querySelector('[data-lightbox-prev]');
        const next = lightbox.querySelector('[data-lightbox-next]');
        const sources = items.map((item) => item.dataset.full || '');
        let index = 0;
        let opener = null;

        const render = () => {
            image.src = sources[index];
            image.alt = items[index].querySelector('img')?.alt || '';
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
    }
})();
</script>
</body>
</html>
