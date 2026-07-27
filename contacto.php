<?php
declare(strict_types=1);

require_once __DIR__ . '/app/site_content_repository.php';
require_once __DIR__ . '/app/layout.php';

$contactResult = null;
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && (string) ($_POST['form_type'] ?? '') === 'public_contact') {
    $contactResult = site_public_contact_submit($_POST, $_SERVER);
}

$contactSettings = site_contact_settings();
$contactEnabled = !empty($contactSettings) && !empty($contactSettings['is_enabled']);
?>
<!DOCTYPE html>
<html lang="es">
<?php page_head('Contacto | Con Sabor Flamenco', 'Formulario de contacto profesional de Con Sabor Flamenco.', false); ?>
<body class="contact-page">
    <?php page_header('CONTACTO'); ?>

    <main>
        <section class="page-intro contact-page-intro" data-ad-category="GENERAL">
            <p class="section-kicker">Contacto</p>
            <h1>Hablemos de tu proyecto flamenco</h1>
            <p>Escribenos para servicios digitales, colaboraciones, publicidad, revista o soporte de la comunidad.</p>
        </section>

        <div class="page-shell">
            <div class="primary-content">
                <aside class="ad-mobile-strip" aria-label="Publicidad local">
                    <div class="ad-sidebar-heading">
                        <div>
                            <span class="ad-eyebrow">Seleccion patrocinada</span>
                            <h2><span data-ad-category-label>Contacto</span> &middot; <span data-ad-province>tu provincia</span></h2>
                        </div>
                        <button type="button" class="text-button" data-open-province>Cambiar provincia</button>
                    </div>
                    <div class="ad-slots" data-ad-slots></div>
                </aside>

                <?php if ($contactEnabled): ?>
                    <section class="content-section professional-contact-section" id="contacto-profesional" data-ad-category="GENERAL">
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

                            <form method="post" action="contacto.php#contacto-profesional" class="public-contact-form" novalidate>
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="form_type" value="public_contact">
                                <label class="honeypot-field" for="website">Web</label>
                                <input class="honeypot-field" id="website" name="website" type="text" tabindex="-1" autocomplete="off">

                                <h3>Cuentanos que necesitas</h3>
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
                <?php else: ?>
                    <section class="content-section" id="contacto-profesional" data-ad-category="GENERAL">
                        <div class="empty-state">
                            <h3>Contacto no disponible temporalmente</h3>
                            <p>Estamos revisando este canal. Puedes escribirnos a hola@consaborflamenco.com.</p>
                            <a class="button button-primary" href="mailto:hola@consaborflamenco.com">Enviar email</a>
                        </div>
                    </section>
                <?php endif; ?>
            </div>

            <aside class="ad-sidebar" aria-label="Publicidad local">
                <div class="ad-sidebar-inner">
                    <div class="ad-sidebar-heading">
                        <div>
                            <span class="ad-eyebrow">Seleccion patrocinada</span>
                            <h2><span data-ad-category-label>Contacto</span> &middot; <span data-ad-province>tu provincia</span></h2>
                        </div>
                        <button type="button" class="text-button" data-open-province>Cambiar</button>
                    </div>
                    <div class="ad-slots" data-ad-slots></div>
                    <p class="ad-disclosure">Espacios publicitarios seleccionados por provincia.</p>
                </div>
            </aside>
        </div>
    </main>

    <?php page_footer(); ?>
    <?php province_modal('Asi te mostraremos primero informacion y anunciantes cercanos. Guardaremos unicamente la provincia en este dispositivo.'); ?>
</body>
</html>
