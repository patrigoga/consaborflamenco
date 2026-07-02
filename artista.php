<?php
declare(strict_types=1);
require_once __DIR__ . '/app/auth.php';
require_once __DIR__ . '/app/layout.php';

$uri = $_SERVER['REQUEST_URI'] ?? '';
$slug = null;
if (preg_match('#/artista/([a-z0-9\-_%]+)#i', $uri, $m)) {
    $slug = urldecode($m[1]);
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

$profile = $member['artistic_profile'] ?? [];
$title = $profile['public_name'] ?? $member['name'] ?? 'Artista';

page_head($title . ' | Con Sabor Flamenco', $profile['short_description'] ?? '', false);
page_header();
?>
<main class="artist-landing">
    <section class="artist-hero">
        <div class="container">
            <h1><?= e($title) ?></h1>
            <?php if (!empty($profile['main_photo_path'])): ?>
                <img src="<?= e($profile['main_photo_path']) ?>" alt="Foto de <?= e($title) ?>" loading="lazy">
            <?php endif; ?>
            <p><?= e($profile['short_description'] ?? $profile['artistic_headline'] ?? '') ?></p>
        </div>
    </section>
    <section class="artist-bio">
        <div class="container">
            <h2>Biografía</h2>
            <div><?= sanitize_html($profile['biography'] ?? $member['biografia'] ?? '') ?></div>
        </div>
    </section>
    <section class="artist-contact">
        <div class="container">
            <h2>Contacto</h2>
            <p>Correo: <?= e($member['email']) ?></p>
            <?php if (!empty($profile['phone'])): ?><p>Tel: <?= e($profile['phone']) ?></p><?php endif; ?>
            <?php if (!empty($profile['website_url'])): ?><p>Web: <a href="<?= e($profile['website_url']) ?>" rel="noopener" target="_blank"><?= e($profile['website_url']) ?></a></p><?php endif; ?>
        </div>
    </section>
</main>

<?php page_footer();
