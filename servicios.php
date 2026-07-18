<?php
declare(strict_types=1);

require_once __DIR__ . '/app/site_content_repository.php';
require_once __DIR__ . '/app/layout.php';

$services = site_services_active();
?>
<!DOCTYPE html>
<html lang="es">
<?php page_head('Servicios | Con Sabor Flamenco', 'Servicios digitales para artistas, academias y profesionales del flamenco.', false); ?>
<body>
    <?php page_header('SERVICIOS'); ?>

    <main>
        <section class="page-intro" data-ad-category="GENERAL">
            <p class="section-kicker">Servicios para miembros</p>
            <h1>Herramientas digitales para crecer</h1>
            <p>Soluciones profesionales para mejorar visibilidad, comunicacion y oportunidades dentro del flamenco.</p>
        </section>

        <section class="content-section public-services-section" id="servicios">
            <div class="section-heading">
                <div class="section-heading-content">
                    <p class="section-kicker">Servicios</p>
                    <h2>Servicios destacados</h2>
                    <p>Propuestas activas ordenadas por prioridad editorial y fecha de actualizacion.</p>
                </div>
                <button class="section-enter-link" type="button" data-open-province>Provincia: <span data-province-label>Cordoba</span></button>
            </div>

            <?php if (!$services): ?>
                <div class="empty-state">
                    <h3>Estamos preparando nuevos servicios</h3>
                    <p>Vuelve pronto o contacta con el equipo para contar tu necesidad profesional.</p>
                    <a class="button button-primary" href="index.php#contacto-profesional">Contactar</a>
                </div>
            <?php else: ?>
                <div class="service-public-grid">
                    <?php foreach ($services as $service): ?>
                        <article class="service-public-card">
                            <?php if (!empty($service['image_path'])): ?>
                                <img src="<?= e((string) $service['image_path']) ?>" alt="<?= e((string) $service['title']) ?>">
                            <?php endif; ?>
                            <div class="service-public-content">
                                <div class="service-public-topline">
                                    <?php if (!empty($service['icon'])): ?><span><?= e((string) $service['icon']) ?></span><?php endif; ?>
                                    <?php if (!empty($service['is_featured'])): ?><span>Destacado</span><?php endif; ?>
                                </div>
                                <h3><?= e((string) $service['title']) ?></h3>
                                <p><?= e((string) $service['short_description']) ?></p>
                                <?php if (!empty($service['full_description'])): ?>
                                    <div class="service-public-description"><?= $service['full_description'] ?></div>
                                <?php endif; ?>
                                <div class="service-public-footer">
                                    <?php if ($service['price'] !== null && $service['price'] !== ''): ?>
                                        <strong><?= e(number_format((float) $service['price'], 2, ',', '.')) ?> EUR<?= !empty($service['price_suffix']) ? ' ' . e((string) $service['price_suffix']) : '' ?></strong>
                                    <?php endif; ?>
                                    <?php if (!empty($service['button_text']) && !empty($service['button_url'])): ?>
                                        <a class="button button-secondary" href="<?= e((string) $service['button_url']) ?>"><?= e((string) $service['button_text']) ?></a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <?php page_footer(); ?>
    <?php province_modal('Asi te mostraremos primero servicios y anunciantes cercanos. Guardaremos unicamente la provincia en este dispositivo.'); ?>
</body>
</html>
