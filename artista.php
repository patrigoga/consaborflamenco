<?php
declare(strict_types=1);

require_once __DIR__ . '/app/auth.php';
require_once __DIR__ . '/app/layout.php';

function artist_public_media_url(string $path): string
{
    $path = trim($path);
    if ($path === '' || preg_match('#^(?:https?:)?//#i', $path) || str_starts_with($path, 'data:')) {
        return $path;
    }

    return app_url($path);
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
$memberTypeLabel = member_type_options()[$profile['member_type'] ?? 'artista'] ?? 'Artista';
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
$location = trim(clean_text((string) ($profile['city'] ?? '')) . (($profile['city'] ?? '') && ($profile['province'] ?? '') ? ', ' : '') . clean_text((string) ($profile['province'] ?? '')));
$brandHomeUrl = app_url('index.php#inicio');
$artistsUrl = app_url('artistas.php');
$registerUrl = app_url('registro.php');
$brandSealUrl = app_url('assets/images/member-cards/pegatina-con-sabor-flamenco.png');

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
$heroStyle = $heroImage !== ''
    ? "background-image: linear-gradient(135deg, rgba(17, 17, 20, 0.88), rgba(32, 56, 71, 0.72)), url('" . $heroImage . "');"
    : '';
?>
<!DOCTYPE html>
<html lang="es">
<?php page_head($displayName . ' | Con Sabor Flamenco', $heroSubtitle, false); ?>
<body class="artist-public-body">
    <main class="artist-web-page">
        <section id="inicio" class="artist-web-hero" <?= $heroStyle !== '' ? 'style="' . e($heroStyle) . '"' : '' ?>>
            <div class="container artist-web-hero-inner">
                <div class="artist-web-hero-copy">
                    <a class="artist-web-brand" href="<?= e($brandHomeUrl) ?>" aria-label="Con Sabor Flamenco">
                        <span class="brand-mark">CSF</span>
                        <span>Con Sabor Flamenco</span>
                    </a>
                    <p class="section-kicker"><?= e($memberTypeLabel) ?></p>
                    <h1><?= e($heroTitle) ?></h1>
                    <?php if ($heroSubtitle !== ''): ?><p class="artist-web-headline"><?= e($heroSubtitle) ?></p><?php endif; ?>
                    <div class="artist-web-meta">
                        <?php if ($location !== ''): ?><span><?= e($location) ?></span><?php endif; ?>
                        <span>Perfil publico</span>
                    </div>
                    <div class="artist-web-actions">
                        <?php if ($contactItems): ?><a href="#contacto">Contacto</a><?php endif; ?>
                        <?php if ($gallery): ?><a href="#galeria">Ver galeria</a><?php endif; ?>
                    </div>
                </div>
                <aside class="artist-web-profile-card" aria-label="Resumen de <?= e($displayName) ?>">
                    <?php if ($mainPhoto !== ''): ?>
                        <img class="artist-web-photo" src="<?= e($mainPhoto) ?>" alt="Fotografia principal de <?= e($displayName) ?>" loading="eager">
                    <?php else: ?>
                        <div class="artist-web-photo-placeholder"><?= e(strtoupper(substr($displayName, 0, 1))) ?></div>
                    <?php endif; ?>
                    <div>
                        <span><?= e($memberTypeLabel) ?></span>
                        <strong><?= e($displayName) ?></strong>
                        <?php if ($headline !== ''): ?><small><?= e($headline) ?></small><?php endif; ?>
                    </div>
                </aside>
            </div>
        </section>

        <?php if (count($publicSections) > 1): ?>
            <nav class="artist-web-nav" aria-label="Menu de la pagina del artista">
                <div class="container">
                    <?php foreach ($publicSections as $sectionId => $sectionLabel): ?>
                        <a href="#<?= e($sectionId) ?>"><?= e($sectionLabel) ?></a>
                    <?php endforeach; ?>
                </div>
            </nav>
        <?php endif; ?>

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
                <img src="<?= e($brandSealUrl) ?>" alt="Con Sabor Flamenco" loading="lazy">
                <div>
                    <strong>Con Sabor Flamenco</strong>
                    <p>Perfil publico creado para dar presencia digital al flamenco.</p>
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
