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
    if ($url === '' || preg_match('#^[a-z][a-z0-9+.-]*:#i', $url)) {
        return $url;
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
$headline = clean_text((string) ($profile['artistic_headline'] ?? ''));
$heroTitle = clean_text((string) ($webPage['header_title'] ?? '')) ?: $displayName;
$heroSubtitle = clean_text((string) ($webPage['header_subtitle'] ?? '')) ?: $headline;
$heroImage = artist_public_media_url(clean_text((string) (($webPage['header_image_path'] ?? '') ?: ($profile['cv_header_image_path'] ?? '') ?: ($profile['main_photo_path'] ?? ''))));
$mainPhoto = artist_public_media_url(clean_text((string) ($profile['main_photo_path'] ?? '')));
$gallery = array_values(array_filter(array_map(
    static fn ($path): string => artist_public_media_url(clean_text((string) $path)),
    array_slice(is_array($webPage['gallery'] ?? null) ? $webPage['gallery'] : [], 0, 9)
), static fn (string $path): bool => $path !== ''));
$contactFields = is_array($webPage['contact_fields'] ?? null) ? $webPage['contact_fields'] : [];
$siteBaseUrl = artist_public_base_url();
$artistsUrl = $siteBaseUrl . '/artistas.php';
$registerUrl = $siteBaseUrl . '/registro.php';
$defaultHeroImage = artist_public_media_url('assets/images/flamenco-header-art.png');

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

$publicSections = ['inicio' => 'Cabecera'];
if ($gallery) {
    $publicSections['galeria'] = 'Galeria';
}
if ($contactItems) {
    $publicSections['contacto'] = 'Contacto';
}
$heroBackgrounds = array_values(array_unique(array_filter([$heroImage, $defaultHeroImage])));
$heroStyle = "background-image: linear-gradient(135deg, rgba(17, 17, 20, 0.88), rgba(32, 56, 71, 0.72))";
foreach ($heroBackgrounds as $backgroundImage) {
    $heroStyle .= ", url('" . str_replace("'", '%27', $backgroundImage) . "')";
}
$heroStyle .= ';';
?>
<!DOCTYPE html>
<html lang="es">
<?php page_head($displayName . ' | Con Sabor Flamenco', $heroSubtitle, false); ?>
<body class="artist-public-body">
    <header class="artist-web-topbar">
        <div class="container artist-web-topbar-inner">
            <a class="artist-web-logo" href="#inicio" aria-label="Ir a la cabecera de <?= e($displayName) ?>">
                <?php if ($mainPhoto !== ''): ?>
                    <img src="<?= e($mainPhoto) ?>" alt="Fotografia de <?= e($displayName) ?>" loading="eager" onerror="this.hidden=true;this.nextElementSibling.hidden=false;">
                    <span hidden><?= e(strtoupper(substr($displayName, 0, 1))) ?></span>
                <?php else: ?>
                    <span><?= e(strtoupper(substr($displayName, 0, 1))) ?></span>
                <?php endif; ?>
                <strong><?= e($displayName) ?></strong>
            </a>
            <nav class="artist-web-menu" aria-label="Menu de la pagina del artista">
                <?php foreach ($publicSections as $sectionId => $sectionLabel): ?>
                    <a href="#<?= e($sectionId) ?>"><?= e($sectionLabel) ?></a>
                <?php endforeach; ?>
            </nav>
        </div>
    </header>
    <main class="artist-web-page">
        <section id="inicio" class="artist-web-hero" style="<?= e($heroStyle) ?>">
            <div class="container artist-web-hero-inner">
                <div class="artist-web-hero-copy">
                    <h1><?= e($heroTitle) ?></h1>
                </div>
            </div>
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
                <a href="<?= e($registerUrl) ?>">Crear perfil</a>
            </nav>
        </div>
    </footer>
</body>
</html>
