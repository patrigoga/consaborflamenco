<?php
declare(strict_types=1);

require_once __DIR__ . '/app/auth.php';
require_once __DIR__ . '/app/layout.php';

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
$heroImage = clean_text((string) (($webPage['header_image_path'] ?? '') ?: ($profile['cv_header_image_path'] ?? '') ?: ($profile['main_photo_path'] ?? '')));
$gallery = array_slice(is_array($webPage['gallery'] ?? null) ? $webPage['gallery'] : [], 0, 9);
$contactFields = is_array($webPage['contact_fields'] ?? null) ? $webPage['contact_fields'] : [];
$location = trim(clean_text((string) ($profile['city'] ?? '')) . (($profile['city'] ?? '') && ($profile['province'] ?? '') ? ', ' : '') . clean_text((string) ($profile['province'] ?? '')));

$contactItems = [];
if (in_array('email', $contactFields, true) && !empty($member['email'])) {
    $contactItems[] = ['label' => 'Email', 'value' => (string) $member['email'], 'href' => 'mailto:' . (string) $member['email']];
}
if (in_array('phone', $contactFields, true) && !empty($profile['phone'])) {
    $contactItems[] = ['label' => 'Telefono', 'value' => (string) $profile['phone'], 'href' => 'tel:' . preg_replace('/\s+/', '', (string) $profile['phone'])];
}
if (in_array('website', $contactFields, true) && !empty($profile['website_url'])) {
    $contactItems[] = ['label' => 'Web', 'value' => (string) $profile['website_url'], 'href' => (string) $profile['website_url']];
}
if (in_array('instagram', $contactFields, true) && !empty($profile['instagram_url'])) {
    $contactItems[] = ['label' => 'Instagram', 'value' => (string) $profile['instagram_url'], 'href' => (string) $profile['instagram_url']];
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
<body>
    <?php page_header('ARTISTAS'); ?>
    <main class="artist-web-page">
        <section id="inicio" class="artist-web-hero" <?= $heroStyle !== '' ? 'style="' . e($heroStyle) . '"' : '' ?>>
            <div class="container artist-web-hero-inner">
                <div class="artist-web-hero-copy">
                    <p class="section-kicker"><?= e($memberTypeLabel) ?></p>
                    <h1><?= e($heroTitle) ?></h1>
                    <?php if ($heroSubtitle !== ''): ?><p><?= e($heroSubtitle) ?></p><?php endif; ?>
                    <?php if ($location !== ''): ?><span><?= e($location) ?></span><?php endif; ?>
                </div>
                <?php if (!empty($profile['main_photo_path'])): ?>
                    <img class="artist-web-photo" src="<?= e((string) $profile['main_photo_path']) ?>" alt="Fotografia principal de <?= e($displayName) ?>" loading="eager">
                <?php endif; ?>
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
                    </div>
                    <div class="artist-web-gallery-grid">
                        <?php foreach ($gallery as $galleryImage): ?>
                            <img src="<?= e((string) $galleryImage) ?>" alt="Galeria de <?= e($displayName) ?>" loading="lazy">
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
    <?php page_footer(); ?>
    <?php province_modal(); ?>
</body>
</html>
