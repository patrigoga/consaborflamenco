<?php
declare(strict_types=1);

require_once __DIR__ . '/app/auth.php';
require_once __DIR__ . '/app/layout.php';

$assetVersion = static function (string $path): string {
    return (string) (@filemtime(__DIR__ . '/' . ltrim($path, '/')) ?: time());
};
$stylesVersion = $assetVersion('assets/css/styles.css');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Con Sabor Flamenco</title>
    <meta name="description" content="Revista, comunidad y tecnología para impulsar el arte flamenco.">
    <link rel="icon" type="image/svg+xml" href="assets/images/favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css?v=<?= e($stylesVersion) ?>">
    <script src="assets/js/advertising.js?v=<?= e($assetVersion('assets/js/advertising.js')) ?>" defer></script>
    <script src="assets/js/navigation.js?v=<?= e($assetVersion('assets/js/navigation.js')) ?>" defer></script>
    <script src="assets/js/legal-modal.js?v=<?= e($assetVersion('assets/js/legal-modal.js')) ?>" defer></script>
    <script src="assets/js/cookie-consent.js?v=<?= e($assetVersion('assets/js/cookie-consent.js')) ?>" defer></script>
    <script src="assets/js/landing-home.js?v=<?= e($assetVersion('assets/js/landing-home.js')) ?>" defer></script>
</head>
<body>
    <?php page_header('INICIO'); ?>

    <main>
        <section id="inicio" class="hero-section home-hero" data-ad-category="INICIO">
            <div class="hero-inner landing-hero-inner">
                <div class="hero-content landing-hero-copy">
                    <p class="section-kicker">Comunidad, presencia digital y compromiso real</p>
                    <h1 class="landing-title" data-landing-title>
                        <span class="landing-title-red">Con Sabor</span>
                        <span class="landing-title-white">Flamenco</span>
                    </h1>
                </div>

                <div class="story-slider-band" aria-label="Historia visual de Con Sabor Flamenco">
                    <div class="story-slider" data-story-slider>
                        <div class="story-slider-track" data-story-track>
                            <?php require __DIR__ . '/slider/slider01.php'; ?>
                            <?php require __DIR__ . '/slider/slider02.php'; ?>
                            <?php require __DIR__ . '/slider/slider03.php'; ?>
                        </div>

                        <div class="story-slider-controls" aria-label="Controles del slider">
                            <button class="story-slider-arrow" type="button" data-story-prev aria-label="Slide anterior">‹</button>
                            <div class="story-slider-dots" data-story-dots>
                                <button class="is-active" type="button" data-story-dot="0" aria-label="Ir al slide 1"></button>
                                <button type="button" data-story-dot="1" aria-label="Ir al slide 2"></button>
                                <button type="button" data-story-dot="2" aria-label="Ir al slide 3"></button>
                            </div>
                            <button class="story-slider-arrow" type="button" data-story-next aria-label="Slide siguiente">›</button>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php page_footer(); ?>

    <div class="province-modal" data-province-modal hidden>
        <div class="province-modal-backdrop" data-close-province></div>
        <section class="province-dialog" role="dialog" aria-modal="true" aria-labelledby="province-title" aria-describedby="province-description">
            <button class="modal-close" type="button" data-close-province aria-label="Cerrar selector de provincia">×</button>
            <p class="section-kicker">Publicidad más útil</p>
            <h2 id="province-title">¿Desde qué provincia nos visitas?</h2>
            <p id="province-description">Así te mostraremos primero eventos, espacios y anunciantes cercanos. Guardaremos únicamente la provincia en este dispositivo y podrás cambiarla cuando quieras.</p>
            <form data-province-form>
                <label for="province-select">Selecciona tu provincia</label>
                <select id="province-select" name="province" required>
                    <option value="">Elige una provincia</option>
                </select>
                <button class="button button-primary" type="submit">Ver contenido de mi provincia</button>
            </form>
            <button class="text-button modal-skip" type="button" data-skip-province>Ahora no, ver publicidad nacional</button>
        </section>
    </div>
</body>
</html>
