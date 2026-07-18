<?php
declare(strict_types=1);

require_once __DIR__ . '/app/auth.php';
require_once __DIR__ . '/app/layout.php';
require_once __DIR__ . '/app/directory_helpers.php';

$activeDiscipline = csf_active_discipline($_GET);
$activeDisciplineLabel = csf_discipline_options()[$activeDiscipline] ?? 'Todos';
$academies = [];
$pdo = db();

if ($pdo) {
    foreach (csf_fetch_member_directory($pdo, 'academia', $activeDiscipline, 48) as $row) {
        $profile = csf_decode_profile((string) ($row['perfil_json'] ?? ''));
        $slug = clean_text((string) ($row['slug'] ?? ''));
        if ($slug === '') {
            continue;
        }

        $publicName = clean_text((string) ($row['nombre_publico'] ?? $profile['public_name'] ?? $row['nombre'] ?? 'Academia'));
        $city = clean_text((string) ($row['ciudad'] ?? $profile['city'] ?? ''));
        $province = clean_text((string) ($row['provincia_texto'] ?? $profile['province'] ?? ''));
        $headline = clean_text((string) ($profile['artistic_headline'] ?? ''));
        $description = clean_text((string) ($profile['cv_summary'] ?? $profile['short_description'] ?? $row['biografia'] ?? ''));
        $mainPhoto = clean_text((string) ($row['foto_principal_path'] ?? $profile['main_photo_path'] ?? ''));
        $haystack = csf_directory_haystack($row, $profile);
        $disciplines = csf_discipline_labels_from_text($haystack);

        $academies[] = [
            'slug' => $slug,
            'name' => $publicName,
            'location' => trim($city . ($city !== '' && $province !== '' ? ', ' : '') . $province),
            'headline' => $headline,
            'description' => $description,
            'photo' => $mainPhoto,
            'disciplines' => $disciplines ?: ['Formación flamenca'],
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<?php page_head('Academias | Con Sabor Flamenco', 'Directorio de academias y centros de formación flamenca con filtros por disciplinas impartidas.'); ?>
<body>
    <?php page_header('ACADEMIAS'); ?>
    <main>
        <section class="page-intro" data-ad-category="ACADEMIAS">
            <p class="section-kicker">Formación</p>
            <h1>Academias flamencas</h1>
            <p>Centros, escuelas y espacios formativos con presencia destacada en la comunidad.</p>
        </section>

        <div class="page-shell">
            <div class="primary-content">
                <aside class="ad-mobile-strip" aria-label="Publicidad local">
                    <div class="ad-sidebar-heading">
                        <div>
                            <span class="ad-eyebrow">Selección patrocinada</span>
                            <h2><span data-ad-category-label>Formación</span> · <span data-ad-province>tu provincia</span></h2>
                        </div>
                        <button type="button" class="text-button" data-open-province>Cambiar provincia</button>
                    </div>
                    <div class="ad-slots" data-ad-slots></div>
                </aside>

                <section id="academias" class="content-section soft-band" data-ad-category="ACADEMIAS">
                    <div class="section-heading">
                        <div class="section-heading-content">
                            <p class="section-kicker">Academias destacadas</p>
                            <h2>Centros recomendados</h2>
                            <p>Los centros con mayor apoyo o promoción dentro de la comunidad.</p>
                        </div>
                        <a class="section-enter-link" href="index.php#academias">Ver en portada</a>
                    </div>
                    <div class="editorial-grid section-ranking" data-ranking-section="ACADEMIAS"></div>
                </section>

                <section id="directorio-academias" class="content-section" data-ad-category="ACADEMIAS">
                    <div class="section-heading">
                        <div class="section-heading-content">
                            <p class="section-kicker">Directorio</p>
                            <h2>Explorar academias</h2>
                            <p><?= e($activeDiscipline === 'todos' ? 'Listado público de academias activas y centros de formación flamenca.' : 'Academias filtradas por disciplinas de ' . mb_strtolower($activeDisciplineLabel, 'UTF-8') . '.') ?></p>
                        </div>
                    </div>

                    <?php csf_render_discipline_filters('academias.php', $activeDiscipline, 'Filtrar academias por disciplina'); ?>

                    <?php if ($academies): ?>
                        <div class="editorial-grid directory-grid">
                            <?php foreach ($academies as $academy): ?>
                                <a class="editorial-story directory-card" href="artista/<?= e(rawurlencode($academy['slug'])) ?>">
                                    <?php if ($academy['photo'] !== ''): ?>
                                        <img src="<?= e($academy['photo']) ?>" alt="Imagen de <?= e($academy['name']) ?>" loading="lazy" width="640" height="480">
                                    <?php else: ?>
                                        <img src="assets/images/community/academia-flamenca.webp" alt="Imagen de academia flamenca" loading="lazy" width="640" height="480">
                                    <?php endif; ?>
                                    <div class="editorial-story-content">
                                        <span class="editorial-meta">
                                            <strong><?= e($academy['name']) ?></strong>
                                            <span><?= e(implode(' · ', $academy['disciplines'])) ?></span>
                                        </span>
                                        <h3><?= e($academy['headline'] !== '' ? $academy['headline'] : 'Ver ficha de la academia') ?></h3>
                                        <?php if ($academy['location'] !== ''): ?><p><?= e($academy['location']) ?></p><?php endif; ?>
                                        <?php if ($academy['description'] !== ''): ?><p><?= e($academy['description']) ?></p><?php endif; ?>
                                        <span class="editorial-read">Ver ficha →</span>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="empty-state">No hay academias disponibles para <?= e(mb_strtolower($activeDisciplineLabel, 'UTF-8')) ?> todavía. Prueba con otra disciplina o vuelve pronto.</p>
                    <?php endif; ?>
                </section>
            </div>

            <aside class="ad-sidebar" aria-label="Publicidad local">
                <div class="ad-sidebar-inner">
                    <div class="ad-sidebar-heading">
                        <div>
                            <span class="ad-eyebrow">Selección patrocinada</span>
                            <h2><span data-ad-category-label>Formación</span> · <span data-ad-province>tu provincia</span></h2>
                        </div>
                        <button type="button" class="text-button" data-open-province>Cambiar</button>
                    </div>
                    <div class="ad-slots" data-ad-slots></div>
                    <p class="ad-disclosure">Espacios publicitarios seleccionados por sección y provincia.</p>
                </div>
            </aside>
        </div>
    </main>
    <?php page_footer(); ?>
    <?php province_modal('Así te mostraremos primero academias y anunciantes cercanos. Guardaremos únicamente la provincia en este dispositivo.'); ?>
</body>
</html>
