<?php
declare(strict_types=1);

require_once __DIR__ . '/app/auth.php';
require_once __DIR__ . '/app/layout.php';

$artists = [];
$pdo = db();
if ($pdo) {
    $statement = $pdo->query(
        'SELECT
            m.slug,
            m.nombre_publico,
            m.ciudad,
            m.provincia_texto,
            m.foto_principal_path,
            m.perfil_json,
            u.nombre
        FROM miembros m
        INNER JOIN usuarios u ON u.id = m.usuario_id
        WHERE m.slug IS NOT NULL AND m.slug <> "" AND u.estado = "ACTIVO"
        ORDER BY COALESCE(m.perfil_completo_at, m.updated_at) DESC
        LIMIT 24'
    );

    foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $profile = [];
        if (!empty($row['perfil_json'])) {
            $decoded = json_decode((string) $row['perfil_json'], true);
            $profile = is_array($decoded) ? $decoded : [];
        }

        $slug = clean_text((string) ($row['slug'] ?? ''));
        if ($slug === '') {
            continue;
        }

        $publicName = clean_text((string) ($row['nombre_publico'] ?? $profile['public_name'] ?? $row['nombre'] ?? 'Artista'));
        $city = clean_text((string) ($row['ciudad'] ?? $profile['city'] ?? ''));
        $province = clean_text((string) ($row['provincia_texto'] ?? $profile['province'] ?? ''));
        $headline = clean_text((string) ($profile['artistic_headline'] ?? ''));
        $mainPhoto = clean_text((string) ($row['foto_principal_path'] ?? $profile['main_photo_path'] ?? ''));

        $artists[] = [
            'slug' => $slug,
            'name' => $publicName,
            'location' => trim($city . ($city !== '' && $province !== '' ? ', ' : '') . $province),
            'headline' => $headline,
            'photo' => $mainPhoto,
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<?php page_head('Artistas | Con Sabor Flamenco', 'Directorio de artistas flamencos destacados.'); ?>
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
                            <p>Listado público de miembros con ficha activa. Cada tarjeta abre su landing individual.</p>
                        </div>
                    </div>

                    <?php if ($artists): ?>
                        <div class="editorial-grid">
                            <?php foreach ($artists as $artist): ?>
                                <a class="editorial-story" href="artista/<?= e(rawurlencode($artist['slug'])) ?>">
                                    <?php if ($artist['photo'] !== ''): ?>
                                        <img src="<?= e($artist['photo']) ?>" alt="Foto de <?= e($artist['name']) ?>" loading="lazy" width="640" height="480">
                                    <?php else: ?>
                                        <img src="assets/images/community/artista-bailaora.webp" alt="Imagen de perfil de artista" loading="lazy" width="640" height="480">
                                    <?php endif; ?>
                                    <div class="editorial-story-content">
                                        <span class="editorial-meta">
                                            <strong><?= e($artist['name']) ?></strong>
                                            <span><?= e($artist['location'] !== '' ? $artist['location'] : 'Con Sabor Flamenco') ?></span>
                                        </span>
                                        <h3><?= e($artist['headline'] !== '' ? $artist['headline'] : 'Ver perfil artístico') ?></h3>
                                        <span class="editorial-read">Abrir landing →</span>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p>No hay perfiles públicos disponibles todavía. Vuelve pronto para ver nuevos artistas.</p>
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
