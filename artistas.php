<?php
declare(strict_types=1);

require_once __DIR__ . '/app/auth.php';
require_once __DIR__ . '/app/layout.php';
require_once __DIR__ . '/app/directory_helpers.php';
require_once __DIR__ . '/app/geo_repository.php';

$activeDiscipline = csf_active_discipline($_GET);
$activeDisciplineLabel = csf_discipline_options()[$activeDiscipline] ?? 'Todos';
$artists = [];
$pdo = db();

// Fase 1: filtros por territorio. Artistas > Cordoba > Montilla > Baile.
$provincias = [];
$municipios = [];
$provinciaActual = null;
$municipioId = 0;
$provinciaSlug = slugify(clean_text((string) ($_GET['provincia'] ?? '')));

if ($pdo) {
    try {
        $provincias = csf_geo_provincias($pdo);
        $provinciaActual = $provinciaSlug !== '' ? csf_geo_provincia_por_slug($pdo, $provinciaSlug) : null;

        if ($provinciaActual !== null) {
            $municipios = csf_geo_municipios($pdo, $provinciaActual['id']);
            $municipioId = (int) ($_GET['municipio'] ?? 0);
            if ($municipioId > 0 && !in_array($municipioId, array_column($municipios, 'id'), true)) {
                $municipioId = 0;
            }
        }
    } catch (Throwable $exception) {
        error_log('[artistas] filtros geograficos no disponibles: ' . $exception->getMessage());
    }
}

if ($pdo) {
    $geoFiltros = ['provincia_id' => $provinciaActual['id'] ?? 0, 'municipio_id' => $municipioId];
    foreach (csf_fetch_member_directory($pdo, 'artista', $activeDiscipline, 48, $geoFiltros) as $row) {
        $profile = csf_decode_profile((string) ($row['perfil_json'] ?? ''));
        $slug = clean_text((string) ($row['slug'] ?? ''));
        if ($slug === '') {
            continue;
        }

        $publicName = clean_text((string) ($row['nombre_publico'] ?? $profile['public_name'] ?? $row['nombre'] ?? 'Artista'));
        $city = clean_text((string) ($row['ciudad'] ?? $profile['city'] ?? ''));
        $province = clean_text((string) ($row['provincia_texto'] ?? $profile['province'] ?? ''));
        $headline = clean_text((string) ($profile['artistic_headline'] ?? ''));
        $description = clean_text((string) ($profile['cv_summary'] ?? $profile['short_description'] ?? $row['biografia'] ?? ''));
        $mainPhoto = clean_text((string) ($row['foto_principal_path'] ?? $profile['main_photo_path'] ?? ''));
        $haystack = csf_directory_haystack($row, $profile);
        $disciplines = csf_discipline_labels_from_text($haystack);

        $artists[] = [
            'slug' => $slug,
            'name' => $publicName,
            'location' => trim($city . ($city !== '' && $province !== '' ? ', ' : '') . $province),
            'headline' => $headline,
            'description' => $description,
            'photo' => $mainPhoto,
            'disciplines' => $disciplines ?: ['Flamenco'],
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<?php page_head('Artistas | Con Sabor Flamenco', 'Directorio de artistas flamencos destacados con filtros por baile, cante, toque y percusión.'); ?>
<body>
    <?php page_header('ARTISTAS'); ?>
    <main>
        <section class="page-intro" data-ad-category="ARTISTAS">
            <p class="section-kicker">Directorio</p>
            <h1>Artistas flamencos</h1>
            <p>Perfiles artísticos preparados para descubrir talento, trayectoria y contratación.</p>
        </section>

        <div class="page-shell">
            <div class="primary-content">
                <aside class="ad-mobile-strip" aria-label="Publicidad local">
                    <div class="ad-sidebar-heading">
                        <div>
                            <span class="ad-eyebrow">Selección patrocinada</span>
                            <h2><span data-ad-category-label>Directorio</span> · <span data-ad-province>tu provincia</span></h2>
                        </div>
                        <button type="button" class="text-button" data-open-province>Cambiar provincia</button>
                    </div>
                    <div class="ad-slots" data-ad-slots></div>
                </aside>

                <section id="artistas" class="content-section" data-ad-category="ARTISTAS">
                    <div class="section-heading">
                        <div class="section-heading-content">
                            <p class="section-kicker">Directorio</p>
                            <h2>Artistas destacados</h2>
                            <p>Los perfiles artísticos que ocupan las tres primeras posiciones.</p>
                        </div>
                        <a class="section-enter-link" href="index.php#artistas">Ver en portada</a>
                    </div>
                    <div class="editorial-grid section-ranking" data-ranking-section="ARTISTAS"></div>
                </section>

                <section id="directorio-artistas" class="content-section" data-ad-category="ARTISTAS">
                    <div class="section-heading">
                        <div class="section-heading-content">
                            <p class="section-kicker">Perfiles reales</p>
                            <h2>Explorar artistas</h2>
                            <p><?= e($activeDiscipline === 'todos' ? 'Listado público de miembros con ficha activa. Cada tarjeta abre su landing individual.' : 'Artistas filtrados por ' . mb_strtolower($activeDisciplineLabel, 'UTF-8') . '.') ?></p>
                        </div>
                    </div>

                    <?php csf_render_discipline_filters('artistas.php', $activeDiscipline, 'Filtrar artistas por disciplina'); ?>

                    <?php /* Filtros por territorio. Los de disciplina de arriba
                             se conservan tal cual: siguen siendo enlaces. */ ?>
                    <form class="csf-directory-filters" method="get" action="artistas.php">
                        <input type="hidden" name="disciplina" value="<?= e($activeDiscipline) ?>">
                        <div>
                            <label for="filtro-provincia">Provincia</label>
                            <select id="filtro-provincia" name="provincia" onchange="this.form.municipio.value=''; this.form.submit();">
                                <option value="">Toda España</option>
                                <?php foreach ($provincias as $provincia): ?>
                                    <option value="<?= e($provincia['slug']) ?>"<?= ($provinciaActual['slug'] ?? '') === $provincia['slug'] ? ' selected' : '' ?>><?= e($provincia['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="filtro-municipio">Municipio</label>
                            <select id="filtro-municipio" name="municipio"<?= $municipios === [] ? ' disabled' : '' ?>>
                                <option value="">Todos</option>
                                <?php foreach ($municipios as $municipio): ?>
                                    <option value="<?= e((string) $municipio['id']) ?>"<?= $municipioId === $municipio['id'] ? ' selected' : '' ?>><?= e($municipio['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="csf-directory-filters-actions">
                            <button class="button button-primary" type="submit">Filtrar</button>
                            <?php if ($provinciaActual !== null || $activeDiscipline !== 'todos'): ?>
                                <a class="button button-secondary" href="artistas.php">Quitar filtros</a>
                            <?php endif; ?>
                        </div>
                    </form>

                    <?php if ($artists): ?>
                        <div class="editorial-grid directory-grid">
                            <?php foreach ($artists as $artist): ?>
                                <a class="editorial-story directory-card" href="artista/<?= e(rawurlencode($artist['slug'])) ?>">
                                    <?php if ($artist['photo'] !== ''): ?>
                                        <img src="<?= e($artist['photo']) ?>" alt="Foto de <?= e($artist['name']) ?>" loading="lazy" width="640" height="480">
                                    <?php else: ?>
                                        <img src="assets/images/community/artista-bailaora.webp" alt="Imagen de perfil de artista" loading="lazy" width="640" height="480">
                                    <?php endif; ?>
                                    <div class="editorial-story-content">
                                        <span class="editorial-meta">
                                            <strong><?= e($artist['name']) ?></strong>
                                            <span><?= e(implode(' · ', $artist['disciplines'])) ?></span>
                                        </span>
                                        <h3><?= e($artist['headline'] !== '' ? $artist['headline'] : 'Ver perfil artístico') ?></h3>
                                        <?php if ($artist['location'] !== ''): ?><p><?= e($artist['location']) ?></p><?php endif; ?>
                                        <?php if ($artist['description'] !== ''): ?><p><?= e($artist['description']) ?></p><?php endif; ?>
                                        <span class="editorial-read">Abrir landing →</span>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="empty-state">No hay artistas disponibles para <?= e(mb_strtolower($activeDisciplineLabel, 'UTF-8')) ?> todavía. Prueba con otra disciplina o vuelve pronto.</p>
                    <?php endif; ?>
                </section>
            </div>

            <aside class="ad-sidebar" aria-label="Publicidad local">
                <div class="ad-sidebar-inner">
                    <div class="ad-sidebar-heading">
                        <div>
                            <span class="ad-eyebrow">Selección patrocinada</span>
                            <h2><span data-ad-category-label>Directorio</span> · <span data-ad-province>tu provincia</span></h2>
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
    <?php province_modal('Así te mostraremos primero artistas y anunciantes cercanos. Guardaremos únicamente la provincia en este dispositivo.'); ?>
</body>
</html>
