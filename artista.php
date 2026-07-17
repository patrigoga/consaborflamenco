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
$events = array_values(is_array($webPage['events'] ?? null) ? $webPage['events'] : []);
$articles = array_values(is_array($webPage['articles'] ?? null) ? $webPage['articles'] : []);
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
$menuSections = [
    'inicio' => 'Inicio',
    'galeria' => 'Galeria',
    'eventos' => 'Eventos',
    'actualidad' => 'Actualidad',
    'contacto' => 'Contacto',
];
$heroStyle = $heroImage !== ''
    ? "background-image: linear-gradient(135deg, rgba(17, 17, 20, 0.88), rgba(32, 56, 71, 0.72)), url('" . $heroImage . "');"
    : '';

$formatPublicDate = static function (string $date): string {
    $raw = clean_text($date);
    if ($raw === '') {
        return '';
    }

    $timestamp = strtotime($raw);
    return $timestamp ? date('d/m/Y', $timestamp) : $raw;
};
?>
<!DOCTYPE html>
<html lang="es">
<?php page_head($displayName . ' | Con Sabor Flamenco', $heroSubtitle, false); ?>
<body class="artist-public-body">
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

        <nav class="artist-web-nav" aria-label="Menu de la pagina del artista">
            <div class="container">
                <?php foreach ($menuSections as $sectionId => $sectionLabel): ?>
                    <a href="#<?= e($sectionId) ?>"><?= e($sectionLabel) ?></a>
                <?php endforeach; ?>
            </div>
        </nav>

        <section id="galeria" class="artist-web-section artist-web-gallery">
            <div class="container">
                <div class="section-heading align-left">
                    <p class="section-kicker">Galeria</p>
                    <h2>Momentos destacados</h2>
                </div>
                <?php if ($gallery): ?>
                    <div class="artist-web-gallery-grid">
                        <?php foreach ($gallery as $galleryImage): ?>
                            <figure class="artist-web-gallery-card">
                                <img src="<?= e((string) $galleryImage) ?>" alt="Galeria de <?= e($displayName) ?>" loading="lazy">
                            </figure>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="artist-web-empty">Todavia no hay imagenes publicadas en la galeria.</p>
                <?php endif; ?>
            </div>
        </section>

        <section id="eventos" class="artist-web-section artist-web-events">
            <div class="container">
                <div class="section-heading align-left">
                    <p class="section-kicker">Eventos</p>
                    <h2>Agenda flamenca</h2>
                </div>
                <?php if ($events): ?>
                    <div class="artist-web-events-grid">
                        <?php foreach ($events as $event): ?>
                            <article class="artist-web-event-card">
                                <?php if (!empty($event['image_path'])): ?>
                                    <img src="<?= e((string) $event['image_path']) ?>" alt="Imagen del evento de <?= e($displayName) ?>" loading="lazy">
                                <?php endif; ?>
                                <div>
                                    <p class="artist-web-event-meta"><?= e($formatPublicDate((string) ($event['date'] ?? ''))) ?><?php if (!empty($event['time'])): ?> · <?= e((string) $event['time']) ?><?php endif; ?></p>
                                    <h3><?= e((string) ($event['title'] ?? 'Evento')) ?></h3>
                                    <?php if (!empty($event['description'])): ?><p><?= nl2br(e((string) $event['description'])) ?></p><?php endif; ?>
                                    <?php if (!empty($event['url'])): ?><a href="<?= e((string) $event['url']) ?>" target="_blank" rel="noopener" class="artist-web-event-link">Ver evento</a><?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="artist-web-empty">Todavia no hay eventos publicados.</p>
                <?php endif; ?>
            </div>
        </section>

        <section id="actualidad" class="artist-web-section artist-web-articles">
            <div class="container">
                <div class="section-heading align-left">
                    <p class="section-kicker">Actualidad</p>
                    <h2>Novedades y articulos</h2>
                </div>
                <?php if ($articles): ?>
                    <div class="artist-web-articles-grid">
                        <?php foreach ($articles as $article): ?>
                            <article class="artist-web-article-card">
                                <?php if (!empty($article['image_path'])): ?>
                                    <img src="<?= e((string) $article['image_path']) ?>" alt="Imagen del articulo de <?= e($displayName) ?>" loading="lazy">
                                <?php endif; ?>
                                <div>
                                    <h3><?= e((string) ($article['title'] ?? 'Articulo')) ?></h3>
                                    <?php if (!empty($article['summary'])): ?><p><?= nl2br(e((string) $article['summary'])) ?></p><?php endif; ?>
                                    <?php if (!empty($article['submit_to_magazine'])): ?><span class="artist-web-badge">Propuesta para revista</span><?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="artist-web-empty">Todavia no hay articulos publicados en actualidad.</p>
                <?php endif; ?>
            </div>
        </section>

        <section id="contacto" class="artist-web-section artist-web-contact">
            <div class="container">
                <div class="section-heading align-left">
                    <p class="section-kicker">Contacto</p>
                    <h2>Conecta con <?= e($displayName) ?></h2>
                </div>
                <?php if ($contactItems): ?>
                    <div class="artist-web-contact-grid">
                        <?php foreach ($contactItems as $contactItem): ?>
                            <a href="<?= e($contactItem['href']) ?>" <?= str_starts_with((string) $contactItem['href'], 'http') ? 'target="_blank" rel="noopener"' : '' ?>>
                                <span><?= e($contactItem['label']) ?></span>
                                <strong><?= e($contactItem['value']) ?></strong>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="artist-web-empty">Aun no hay datos de contacto visibles.</p>
                <?php endif; ?>
            </div>
        </section>
    </main>
</body>
</html>
