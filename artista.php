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
$legacyHeroImage = artist_public_media_url(clean_text((string) (($webPage['header_image_path'] ?? '') ?: ($profile['cv_header_image_path'] ?? '') ?: ($profile['main_photo_path'] ?? ''))));
$mainPhoto = artist_public_media_url(clean_text((string) ($profile['main_photo_path'] ?? '')));
$gallery = array_values(array_filter(array_map(
    static fn ($path): string => artist_public_media_url(clean_text((string) $path)),
    array_slice(is_array($webPage['gallery'] ?? null) ? $webPage['gallery'] : [], 0, 9)
), static fn (string $path): bool => $path !== ''));
$events = array_values(is_array($webPage['events'] ?? null) ? $webPage['events'] : []);
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
$menuImage = ($heroSlides[0]['image'] ?? '') !== '' ? $heroSlides[0]['image'] : ($legacyHeroImage !== '' ? $legacyHeroImage : $mainPhoto);
$pageDescription = clean_text((string) ($heroSlides[0]['description'] ?? '')) ?: $displayName;

$contactItems = [];
if (in_array('email', $contactFields, true) && !empty($member['email'])) {
    $contactItems[] = ['label' => 'Email', 'value' => (string) $member['email'], 'href' => 'mailto:' . (string) $member['email']];
}
if (in_array('phone', $contactFields, true) && !empty($profile['phone'])) {
    $contactItems[] = ['label' => 'Telefono', 'value' => (string) $profile['phone'], 'href' => 'tel:' . preg_replace('/\s+/', '', (string) $profile['phone'])];
}
if (in_array('website', $contactFields, true) && !empty($profile['website_url'])) {
    $contactItems[] = ['label' => 'Web', 'value' => (string) $profile['website_url'], 'href' => artist_public_link_url((string) $profile['website_url'])];
}
if (in_array('instagram', $contactFields, true) && !empty($profile['instagram_url'])) {
    $contactItems[] = ['label' => 'Instagram', 'value' => (string) $profile['instagram_url'], 'href' => artist_public_link_url((string) $profile['instagram_url'])];
}

$publicSections = [];
if ($gallery) {
    $publicSections['galeria'] = 'Galeria';
}
if ($events) {
    $publicSections['eventos'] = 'Eventos';
}
if ($contactItems) {
    $publicSections['contacto'] = 'Contacto';
}

$socialIcons = [
    'instagram' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><circle cx="12" cy="12" r="4.5"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/></svg>',
    'facebook'  => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>',
    'youtube'   => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M22.54 6.42A2.78 2.78 0 0 0 20.6 4.46C18.88 4 12 4 12 4s-6.88 0-8.6.46A2.78 2.78 0 0 0 1.46 6.42 29 29 0 0 0 1 12a29 29 0 0 0 .46 5.58A2.78 2.78 0 0 0 3.4 19.54C5.12 20 12 20 12 20s6.88 0 8.6-.46a2.78 2.78 0 0 0 1.94-1.96A29 29 0 0 0 23 12a29 29 0 0 0-.46-5.58z"/><polygon fill="#fff" points="9.75 15.02 15.5 12 9.75 8.98 9.75 15.02"/></svg>',
    'tiktok'    => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-2.88 2.5 2.89 2.89 0 0 1-2.89-2.89 2.89 2.89 0 0 1 2.89-2.89c.28 0 .54.04.79.1V9.01a6.33 6.33 0 0 0-.79-.05 6.34 6.34 0 0 0-6.34 6.34 6.34 6.34 0 0 0 6.34 6.34 6.34 6.34 0 0 0 6.33-6.34V8.69a8.15 8.15 0 0 0 4.77 1.52V6.73a4.86 4.86 0 0 1-1-.04z"/></svg>',
    'spotify'   => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path fill="#fff" d="M16.5 16.5a.75.75 0 0 1-.41-.12 8.27 8.27 0 0 0-8.18 0 .75.75 0 0 1-.82-1.26 9.77 9.77 0 0 1 9.82 0 .75.75 0 0 1-.41 1.38zm1.25-2.75a.75.75 0 0 1-.41-.12 10.52 10.52 0 0 0-10.68 0 .75.75 0 0 1-.82-1.26 12 12 0 0 1 12.32 0 .75.75 0 0 1-.41 1.38zm1.25-2.75a.75.75 0 0 1-.41-.12 12.77 12.77 0 0 0-13.18 0 .75.75 0 1 1-.82-1.26 14.27 14.27 0 0 1 14.82 0 .75.75 0 0 1-.41 1.38z"/></svg>',
    'twitter'   => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.744l7.736-8.849L2.25 2.25h6.883l4.254 5.621zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>',
];
?>
<!DOCTYPE html>
<html lang="es">
<?php page_head($displayName . ' | Con Sabor Flamenco', $pageDescription, false); ?>
<body class="artist-public-body">
    <header class="artist-web-topbar">
        <div class="container artist-web-topbar-inner">
            <?php if ($menuImage !== ''): ?>
            <a class="artist-web-logo" href="#inicio" aria-label="Ir a la cabecera de <?= e($displayName) ?>">
                <img src="<?= e($menuImage) ?>" alt="Imagen de cabecera de <?= e($displayName) ?>" loading="eager">
            </a>
            <?php endif; ?>
            <nav class="artist-web-menu" aria-label="Menu de la pagina del artista">
                <a href="#inicio">Inicio</a>
                <?php foreach ($publicSections as $sectionId => $sectionLabel): ?>
                    <a href="#<?= e($sectionId) ?>"><?= e($sectionLabel) ?></a>
                <?php endforeach; ?>
            </nav>
            <?php if ($socialLinks): ?>
                <div class="artist-web-social-links">
                    <?php foreach ($socialLinks as $network => $url): ?>
                        <?php if (!empty($socialIcons[$network])): ?>
                            <a href="<?= e(artist_public_link_url((string) $url)) ?>" target="_blank" rel="noopener" class="artist-web-social-icon" aria-label="<?= e($network) ?>"><?= $socialIcons[$network] ?></a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </header>
    <main class="artist-web-page">
        <section class="artist-web-name-band" aria-label="Nombre artistico">
            <div class="container">
                <h1><?= e($displayName) ?></h1>
            </div>
        </section>
        <section id="inicio" class="artist-web-hero-slider" data-artist-slider>
            <?php foreach ($heroSlides as $slideIndex => $slide): ?>
                <?php
                $slideImage = (string) ($slide['image'] ?? $defaultHeroImage);
                $slideStyle = "background-image: linear-gradient(90deg, rgba(17, 17, 20, 0.86), rgba(17, 17, 20, 0.34) 56%, rgba(32, 56, 71, 0.58)), url('" . str_replace("'", '%27', $slideImage) . "');";
                $slideHasContent = ($slide['title'] ?? '') !== '' || ($slide['description'] ?? '') !== '' || ($slide['cta_url'] ?? '') !== '';
                ?>
                <article class="artist-web-hero-slide <?= $slideIndex === 0 ? 'active' : '' ?>" style="<?= e($slideStyle) ?>" data-artist-slide>
                    <div class="container artist-web-hero-inner">
                        <?php if ($slideHasContent): ?>
                            <div class="artist-web-hero-content">
                                <?php if (($slide['title'] ?? '') !== ''): ?><h2><?= e((string) $slide['title']) ?></h2><?php endif; ?>
                                <?php if (($slide['description'] ?? '') !== ''): ?><p><?= e((string) $slide['description']) ?></p><?php endif; ?>
                                <?php if (($slide['cta_url'] ?? '') !== ''): ?>
                                    <a href="<?= e(artist_public_link_url((string) $slide['cta_url'])) ?>" target="_blank" rel="noopener"><?= e((string) ($slide['cta_label'] ?: 'Ver mas')) ?></a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
            <?php if (count($heroSlides) > 1): ?>
                <div class="artist-web-slider-dots" aria-label="Selector de cabecera">
                    <?php foreach ($heroSlides as $slideIndex => $slide): ?>
                        <button type="button" class="<?= $slideIndex === 0 ? 'active' : '' ?>" data-artist-slide-dot="<?= e((string) $slideIndex) ?>" aria-label="Ver slide <?= e((string) ($slideIndex + 1)) ?>"></button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <?php if ($gallery): ?>
            <section id="galeria" class="artist-web-section artist-web-gallery">
                <div class="container">
                    <div class="section-heading align-left">
                        <p class="section-kicker">Galeria</p>
                        <h2>Momentos destacados</h2>
                        <p>Una seleccion visual del trabajo y presencia artistica de <?= e($displayName) ?>.</p>
                    </div>
                    <div class="artist-web-gallery-grid">
                        <?php foreach ($gallery as $galleryImage): ?>
                            <img src="<?= e($galleryImage) ?>" alt="Galeria de <?= e($displayName) ?>" loading="lazy">
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($events): ?>
            <section id="eventos" class="artist-web-section artist-web-events">
                <div class="container">
                    <div class="section-heading align-left">
                        <p class="section-kicker">Eventos</p>
                        <h2>Agenda flamenca</h2>
                    </div>
                    <div class="artist-web-events-grid">
                        <?php foreach ($events as $ev): ?>
                            <article class="artist-web-event-card">
                                <?php if (!empty($ev['image_path'])): ?>
                                    <img src="<?= e(artist_public_media_url((string) $ev['image_path'])) ?>" alt="<?= e((string) ($ev['title'] ?? 'Evento')) ?>" loading="lazy">
                                <?php endif; ?>
                                <div class="artist-web-event-info">
                                    <?php if (!empty($ev['date'])): ?>
                                        <p class="artist-web-event-meta">
                                            <?php
                                            $ts = strtotime((string) $ev['date']);
                                            echo $ts ? e(date('d/m/Y', $ts)) : e((string) $ev['date']);
                                            if (!empty($ev['time'])): ?> · <?= e((string) $ev['time']) ?><?php endif; ?>
                                        </p>
                                    <?php endif; ?>
                                    <?php if (!empty($ev['title'])): ?><h3><?= e((string) $ev['title']) ?></h3><?php endif; ?>
                                    <?php if (!empty($ev['description'])): ?><p><?= nl2br(e((string) $ev['description'])) ?></p><?php endif; ?>
                                    <?php if (!empty($ev['url'])): ?>
                                        <a href="<?= e(artist_public_link_url((string) $ev['url'])) ?>" target="_blank" rel="noopener" class="artist-web-event-link">Ver evento</a>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($contactItems): ?>
            <section id="contacto" class="artist-web-section artist-web-contact">
                <div class="container">
                    <div class="section-heading align-left">
                        <p class="section-kicker">Contacto</p>
                        <h2>Conecta con <?= e($displayName) ?></h2>
                        <p>Canales disponibles para propuestas profesionales, contratacion o colaboraciones.</p>
                    </div>
                    <div class="artist-web-contact-grid">
                        <?php foreach ($contactItems as $contactItem): ?>
                            <a href="<?= e($contactItem['href']) ?>" <?= str_starts_with((string) $contactItem['href'], 'http') ? 'target="_blank" rel="noopener"' : '' ?>>
                                <span><?= e($contactItem['label']) ?></span>
                                <strong><?= e($contactItem['value']) ?></strong>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>
    </main>
    <footer class="artist-web-footer">
        <div class="container artist-web-footer-inner">
            <div class="artist-web-footer-brand">
                <div>
                    <strong><?= e($displayName) ?></strong>
                    <p>Perfil publico en consaborflamenco.com</p>
                </div>
            </div>
            <nav aria-label="Enlaces del perfil publico">
                <a href="#inicio">Cabecera</a>
                <?php if ($gallery): ?><a href="#galeria">Galeria</a><?php endif; ?>
                <?php if ($contactItems): ?><a href="#contacto">Contacto</a><?php endif; ?>
                <a href="<?= e($artistsUrl) ?>">Directorio</a>
                <a href="<?= e($homeUrl) ?>">Inicio</a>
            </nav>
        </div>
    </footer>
    <script>
        (() => {
            const slider = document.querySelector('[data-artist-slider]');
            if (!(slider instanceof HTMLElement)) {
                return;
            }
            const slides = Array.from(slider.querySelectorAll('[data-artist-slide]'));
            const dots = Array.from(slider.querySelectorAll('[data-artist-slide-dot]'));
            if (slides.length <= 1) {
                return;
            }
            let activeIndex = 0;
            const activateSlide = (nextIndex) => {
                activeIndex = (nextIndex + slides.length) % slides.length;
                slides.forEach((slide, index) => slide.classList.toggle('active', index === activeIndex));
                dots.forEach((dot, index) => dot.classList.toggle('active', index === activeIndex));
            };
            dots.forEach((dot, index) => {
                dot.addEventListener('click', () => activateSlide(index));
            });
            window.setInterval(() => activateSlide(activeIndex + 1), 6200);
        })();
    </script>
</body>
</html>
