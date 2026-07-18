<?php
declare(strict_types=1);

require_once __DIR__ . '/app/auth.php';
require_once __DIR__ . '/app/site_content_repository.php';
require_once __DIR__ . '/app/layout.php';

$contactResult = null;
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && (string) ($_POST['form_type'] ?? '') === 'public_contact') {
    $contactResult = site_public_contact_submit($_POST, $_SERVER);
}

$featuredServices = site_services_active(true, 3);
$contactSettings = site_contact_settings();

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

        <?php if ($featuredServices): ?>
            <section class="content-section home-services-section" id="servicios-destacados">
                <div class="section-heading">
                    <div class="section-heading-content">
                        <p class="section-kicker">Servicios</p>
                        <h2>Impulso digital para profesionales del flamenco</h2>
                        <p>Servicios activos seleccionados para mejorar presencia, comunicacion y captacion.</p>
                    </div>
                    <a class="section-enter-link" href="servicios.php">Ver servicios</a>
                </div>
                <div class="service-public-grid service-public-grid-featured">
                    <?php foreach ($featuredServices as $service): ?>
                        <article class="service-public-card service-public-card-compact">
                            <?php if (!empty($service['image_path'])): ?>
                                <img src="<?= e((string) $service['image_path']) ?>" alt="<?= e((string) $service['title']) ?>">
                            <?php endif; ?>
                            <div class="service-public-content">
                                <div class="service-public-topline"><span>Destacado</span></div>
                                <h3><?= e((string) $service['title']) ?></h3>
                                <p><?= e((string) $service['short_description']) ?></p>
                                <div class="service-public-footer">
                                    <?php if ($service['price'] !== null && $service['price'] !== ''): ?>
                                        <strong><?= e(number_format((float) $service['price'], 2, ',', '.')) ?> EUR<?= !empty($service['price_suffix']) ? ' ' . e((string) $service['price_suffix']) : '' ?></strong>
                                    <?php endif; ?>
                                    <a class="button button-secondary" href="<?= e((string) ($service['button_url'] ?: 'servicios.php')) ?>"><?= e((string) ($service['button_text'] ?: 'Ver servicio')) ?></a>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <?php if (!empty($contactSettings) && !empty($contactSettings['is_enabled'])): ?>
            <section class="content-section professional-contact-section" id="contacto-profesional">
                <div class="professional-contact-grid">
                    <div class="professional-contact-info">
                        <p class="section-kicker">Contacto profesional</p>
                        <h2><?= e((string) ($contactSettings['section_title'] ?? 'Hablemos de tu proyecto flamenco')) ?></h2>
                        <?php if (!empty($contactSettings['section_intro'])): ?><p><?= nl2br(e((string) $contactSettings['section_intro'])) ?></p><?php endif; ?>

                        <?php if (!empty($contactSettings['image_path'])): ?>
                            <img class="professional-contact-image" src="<?= e((string) $contactSettings['image_path']) ?>" alt="<?= e((string) ($contactSettings['image_alt'] ?: $contactSettings['section_title'])) ?>">
                        <?php endif; ?>

                        <dl class="professional-contact-list">
                            <?php if (!empty($contactSettings['business_name'])): ?><dt>Proyecto</dt><dd><?= e((string) $contactSettings['business_name']) ?></dd><?php endif; ?>
                            <?php if (!empty($contactSettings['contact_person'])): ?><dt>Contacto</dt><dd><?= e((string) $contactSettings['contact_person']) ?></dd><?php endif; ?>
                            <?php if (!empty($contactSettings['show_email']) && !empty($contactSettings['email'])): ?><dt>Email</dt><dd><a href="mailto:<?= e((string) $contactSettings['email']) ?>"><?= e((string) $contactSettings['email']) ?></a></dd><?php endif; ?>
                            <?php if (!empty($contactSettings['show_phone']) && !empty($contactSettings['phone'])): ?><dt>Telefono</dt><dd><?= e((string) $contactSettings['phone']) ?></dd><?php endif; ?>
                            <?php if (!empty($contactSettings['show_whatsapp']) && !empty($contactSettings['whatsapp'])): ?><dt>WhatsApp</dt><dd><?= e((string) $contactSettings['whatsapp']) ?></dd><?php endif; ?>
                            <?php if (!empty($contactSettings['show_address']) && (!empty($contactSettings['address']) || !empty($contactSettings['city']) || !empty($contactSettings['province']))): ?>
                                <dt>Direccion</dt>
                                <dd><?= e(trim(implode(' ', array_filter([(string) ($contactSettings['address'] ?? ''), (string) ($contactSettings['postal_code'] ?? ''), (string) ($contactSettings['city'] ?? ''), (string) ($contactSettings['province'] ?? '')])))) ?></dd>
                            <?php endif; ?>
                            <?php if (!empty($contactSettings['show_opening_hours']) && !empty($contactSettings['opening_hours'])): ?><dt>Horario</dt><dd><?= nl2br(e((string) $contactSettings['opening_hours'])) ?></dd><?php endif; ?>
                        </dl>

                        <div class="professional-contact-actions">
                            <?php if (!empty($contactSettings['show_phone']) && !empty($contactSettings['phone'])): ?>
                                <a class="button button-secondary" href="tel:<?= e(preg_replace('/[^0-9+]/', '', (string) $contactSettings['phone']) ?? '') ?>"><?= e((string) ($contactSettings['phone_button_text'] ?: 'Llamar')) ?></a>
                            <?php endif; ?>
                            <?php if (!empty($contactSettings['show_whatsapp']) && !empty($contactSettings['whatsapp_url'])): ?>
                                <a class="button button-primary" href="<?= e((string) $contactSettings['whatsapp_url']) ?>" target="_blank" rel="noopener"><?= e((string) ($contactSettings['whatsapp_button_text'] ?: 'WhatsApp')) ?></a>
                            <?php endif; ?>
                        </div>

                        <?php
                        $socialLinks = [
                            'Facebook' => $contactSettings['facebook_url'] ?? '',
                            'Instagram' => $contactSettings['instagram_url'] ?? '',
                            'YouTube' => $contactSettings['youtube_url'] ?? '',
                            'TikTok' => $contactSettings['tiktok_url'] ?? '',
                        ];
                        ?>
                        <?php if (array_filter($socialLinks)): ?>
                            <div class="professional-social-links">
                                <?php foreach ($socialLinks as $label => $url): ?>
                                    <?php if ($url): ?><a href="<?= e((string) $url) ?>" target="_blank" rel="noopener"><?= e($label) ?></a><?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <form method="post" action="index.php#contacto-profesional" class="public-contact-form" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="form_type" value="public_contact">
                        <label class="honeypot-field" for="website">Web</label>
                        <input class="honeypot-field" id="website" name="website" type="text" tabindex="-1" autocomplete="off">

                        <h3>Cuéntanos que necesitas</h3>
                        <?php if ($contactResult && !empty($contactResult['ok'])): ?>
                            <div class="form-alert form-alert-success" role="status"><p><?= e((string) $contactResult['message']) ?></p></div>
                        <?php elseif ($contactResult && !empty($contactResult['errors'])): ?>
                            <div class="form-alert form-alert-error" role="alert">
                                <?php foreach ($contactResult['errors'] as $error): ?><p><?= e((string) $error) ?></p><?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <div class="form-grid-two">
                            <label for="public-contact-name">Nombre
                                <input id="public-contact-name" name="name" type="text" value="<?= e((string) ($_POST['name'] ?? '')) ?>" required>
                            </label>
                            <label for="public-contact-email">Email
                                <input id="public-contact-email" name="email" type="email" value="<?= e((string) ($_POST['email'] ?? '')) ?>" required>
                            </label>
                        </div>
                        <div class="form-grid-two">
                            <label for="public-contact-phone">Telefono
                                <input id="public-contact-phone" name="phone" type="text" value="<?= e((string) ($_POST['phone'] ?? '')) ?>">
                            </label>
                            <label for="public-contact-type">Tipo de consulta
                                <select id="public-contact-type" name="inquiry_type" required>
                                    <?php foreach (site_inquiry_types() as $value => $label): ?>
                                        <option value="<?= e($value) ?>" <?= (string) ($_POST['inquiry_type'] ?? '') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                        </div>
                        <label for="public-contact-subject">Asunto</label>
                        <input id="public-contact-subject" name="subject" type="text" value="<?= e((string) ($_POST['subject'] ?? '')) ?>" required>
                        <label for="public-contact-message">Mensaje</label>
                        <textarea id="public-contact-message" name="message" rows="6" required><?= e((string) ($_POST['message'] ?? '')) ?></textarea>
                        <label class="privacy-check">
                            <input type="checkbox" name="privacy_accepted" value="1" <?= !empty($_POST['privacy_accepted']) ? 'checked' : '' ?> required>
                            <span>Acepto la <a href="privacidad.php" data-legal-document="privacy">politica de privacidad</a>.</span>
                        </label>
                        <button class="button button-primary" type="submit">Enviar mensaje</button>
                    </form>
                </div>
            </section>
        <?php endif; ?>
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
