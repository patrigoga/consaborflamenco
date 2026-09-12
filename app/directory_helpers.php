<?php
declare(strict_types=1);

function csf_discipline_options(): array
{
    return [
        'todos' => 'Todos',
        'baile' => 'Baile',
        'cante' => 'Cante',
        'toque' => 'Toque',
        'percusion' => 'Percusión',
    ];
}

function csf_active_discipline(array $query): string
{
    $discipline = strtolower(clean_text((string) ($query['disciplina'] ?? 'todos')));
    return array_key_exists($discipline, csf_discipline_options()) ? $discipline : 'todos';
}

function csf_discipline_query(string $basePath, string $discipline): string
{
    return $discipline === 'todos' ? $basePath : $basePath . '?disciplina=' . rawurlencode($discipline);
}

function csf_render_discipline_filters(string $basePath, string $activeDiscipline, string $label): void
{
    ?>
    <nav class="directory-filters" aria-label="<?= e($label) ?>">
        <?php foreach (csf_discipline_options() as $discipline => $text): ?>
            <a href="<?= e(csf_discipline_query($basePath, $discipline)) ?>"<?= $activeDiscipline === $discipline ? ' class="is-active" aria-current="page"' : '' ?>><?= e($text) ?></a>
        <?php endforeach; ?>
    </nav>
    <?php
}

function csf_discipline_terms(string $discipline): array
{
    return match ($discipline) {
        'baile' => ['baile', 'bailaor', 'bailaora', 'bailarin', 'bailarina', 'danza'],
        'cante' => ['cante', 'cantaor', 'cantaora', 'cantante', 'voz'],
        'toque' => ['toque', 'guitarra', 'guitarrista'],
        'percusion' => ['percusion', 'percusión', 'percusionista', 'cajon', 'cajón', 'palmas', 'compas', 'compás'],
        default => [],
    };
}

function csf_decode_profile(?string $json): array
{
    if (!$json) {
        return [];
    }

    $decoded = json_decode($json, true);
    return is_array($decoded) ? $decoded : [];
}

function csf_text_from_profile_value(mixed $value): string
{
    if (is_scalar($value)) {
        return clean_text((string) $value);
    }

    if (!is_array($value)) {
        return '';
    }

    $parts = [];
    foreach ($value as $child) {
        $text = csf_text_from_profile_value($child);
        if ($text !== '') {
            $parts[] = $text;
        }
    }

    return implode(' ', $parts);
}

function csf_directory_haystack(array $row, array $profile): string
{
    return mb_strtolower(implode(' ', array_filter([
        clean_text((string) ($row['nombre_publico'] ?? '')),
        clean_text((string) ($row['biografia'] ?? '')),
        clean_text((string) ($row['nombre'] ?? '')),
        csf_text_from_profile_value($profile),
    ])), 'UTF-8');
}

function csf_discipline_labels_from_text(string $haystack): array
{
    $labels = [];
    foreach (csf_discipline_options() as $discipline => $label) {
        if ($discipline === 'todos') {
            continue;
        }
        foreach (csf_discipline_terms($discipline) as $term) {
            if (str_contains($haystack, mb_strtolower($term, 'UTF-8'))) {
                $labels[] = $label;
                break;
            }
        }
    }

    return $labels;
}

/**
 * Directorio filtrable por territorio para tipos de miembro sin disciplinas
 * (tablao, pena, tienda...). Misma filosofia que el directorio de artistas.php
 * pero sin el filtro de disciplina, que no aplica a estos tipos. Se pinta
 * como una seccion completa lista para inyectarse via section_page()
 * (config 'after_ranking') o incluirse directamente en una pagina.
 *
 * @param array<string, mixed> $query Normalmente $_GET.
 */
function csf_render_member_type_directory(
    PDO $pdo,
    string $memberType,
    string $basePath,
    array $query,
    string $emptyLabel
): string {
    $provincias = [];
    $municipios = [];
    $provinciaActual = null;
    $municipioId = 0;
    $provinciaSlug = slugify(clean_text((string) ($query['provincia'] ?? '')));

    try {
        $provincias = csf_geo_provincias($pdo);
        $provinciaActual = $provinciaSlug !== '' ? csf_geo_provincia_por_slug($pdo, $provinciaSlug) : null;

        if ($provinciaActual !== null) {
            $municipios = csf_geo_municipios($pdo, $provinciaActual['id']);
            $municipioId = (int) ($query['municipio'] ?? 0);
            if ($municipioId > 0 && !in_array($municipioId, array_column($municipios, 'id'), true)) {
                $municipioId = 0;
            }
        }
    } catch (Throwable $exception) {
        error_log('[directorio-' . $memberType . '] filtros geograficos no disponibles: ' . $exception->getMessage());
    }

    $geoFiltros = ['provincia_id' => $provinciaActual['id'] ?? 0, 'municipio_id' => $municipioId];
    $miembros = [];
    foreach (csf_fetch_member_directory($pdo, $memberType, 'todos', 48, $geoFiltros) as $row) {
        $profile = csf_decode_profile((string) ($row['perfil_json'] ?? ''));
        $slug = clean_text((string) ($row['slug'] ?? ''));
        if ($slug === '') {
            continue;
        }

        $city = clean_text((string) ($row['ciudad'] ?? $profile['city'] ?? ''));
        $province = clean_text((string) ($row['provincia_texto'] ?? $profile['province'] ?? ''));

        $miembros[] = [
            'slug' => $slug,
            'name' => clean_text((string) ($row['nombre_publico'] ?? $profile['public_name'] ?? $row['nombre'] ?? 'Con Sabor Flamenco')),
            'location' => trim($city . ($city !== '' && $province !== '' ? ', ' : '') . $province),
            'description' => clean_text((string) ($profile['cv_summary'] ?? $profile['short_description'] ?? $row['biografia'] ?? '')),
            'photo' => clean_text((string) ($row['foto_principal_path'] ?? $profile['main_photo_path'] ?? '')),
        ];
    }

    ob_start();
    ?>
    <section id="directorio-<?= e($memberType) ?>" class="content-section" data-ad-category="<?= e(mb_strtoupper($memberType, 'UTF-8')) ?>">
        <div class="section-heading">
            <div class="section-heading-content">
                <p class="section-kicker">Perfiles reales</p>
                <h2>Explorar</h2>
                <p>Listado público de miembros con ficha activa. Cada tarjeta abre su landing individual.</p>
            </div>
        </div>

        <form class="csf-directory-filters" method="get" action="<?= e($basePath) ?>">
            <div>
                <label for="filtro-provincia-<?= e($memberType) ?>">Provincia</label>
                <select id="filtro-provincia-<?= e($memberType) ?>" name="provincia" onchange="this.form.municipio.value=''; this.form.submit();">
                    <option value="">Toda España</option>
                    <?php foreach ($provincias as $provincia): ?>
                        <option value="<?= e($provincia['slug']) ?>"<?= ($provinciaActual['slug'] ?? '') === $provincia['slug'] ? ' selected' : '' ?>><?= e($provincia['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="filtro-municipio-<?= e($memberType) ?>">Municipio</label>
                <select id="filtro-municipio-<?= e($memberType) ?>" name="municipio"<?= $municipios === [] ? ' disabled' : '' ?>>
                    <option value="">Todos</option>
                    <?php foreach ($municipios as $municipio): ?>
                        <option value="<?= e((string) $municipio['id']) ?>"<?= $municipioId === $municipio['id'] ? ' selected' : '' ?>><?= e($municipio['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="csf-directory-filters-actions">
                <button class="button button-primary" type="submit">Filtrar</button>
                <?php if ($provinciaActual !== null): ?>
                    <a class="button button-secondary" href="<?= e($basePath) ?>">Quitar filtros</a>
                <?php endif; ?>
            </div>
        </form>

        <?php if ($miembros): ?>
            <div class="editorial-grid directory-grid">
                <?php foreach ($miembros as $miembro): ?>
                    <a class="editorial-story directory-card" href="<?= e(member_public_path($memberType, $miembro['slug'])) ?>">
                        <?php if ($miembro['photo'] !== ''): ?>
                            <img src="<?= e($miembro['photo']) ?>" alt="Foto de <?= e($miembro['name']) ?>" loading="lazy" width="640" height="480">
                        <?php else: ?>
                            <img src="assets/images/community/artista-bailaora.webp" alt="Imagen de perfil" loading="lazy" width="640" height="480">
                        <?php endif; ?>
                        <div class="editorial-story-content">
                            <span class="editorial-meta"><strong><?= e($miembro['name']) ?></strong></span>
                            <?php if ($miembro['location'] !== ''): ?><p><?= e($miembro['location']) ?></p><?php endif; ?>
                            <?php if ($miembro['description'] !== ''): ?><p><?= e($miembro['description']) ?></p><?php endif; ?>
                            <span class="editorial-read">Abrir ficha →</span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="empty-state"><?= e($emptyLabel) ?></p>
        <?php endif; ?>
    </section>
    <?php

    return (string) ob_get_clean();
}

function csf_db_table_exists(PDO $pdo, string $table): bool
{
    $statement = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table'
    );
    $statement->execute(['table' => $table]);

    return (int) $statement->fetchColumn() > 0;
}

/**
 * @param array{provincia_id?:int, municipio_id?:int} $geoFilters Filtros de
 *        territorio de la fase 1. Opcionales: sin ellos la consulta se comporta
 *        exactamente igual que antes.
 */
function csf_fetch_member_directory(
    PDO $pdo,
    string $memberType,
    string $discipline,
    int $limit = 48,
    array $geoFilters = []
): array {
    $conditions = [
        'm.slug IS NOT NULL',
        'm.slug <> ""',
        'u.estado = "ACTIVO"',
        '(tm.slug = :member_type OR LOWER(m.perfil_json) LIKE :member_type_json)',
    ];
    $params = [
        'member_type' => $memberType,
        'member_type_json' => '%"member_type":"' . $memberType . '"%',
    ];

    // Territorio. Solo se aplica si la columna existe: en un entorno sin migrar,
    // el directorio sigue funcionando igual que siempre.
    $provinciaId = (int) ($geoFilters['provincia_id'] ?? 0);
    if ($provinciaId > 0 && db_column_exists($pdo, 'miembros', 'provincia_id')) {
        $conditions[] = 'm.provincia_id = :provincia_id';
        $params['provincia_id'] = $provinciaId;

        $municipioId = (int) ($geoFilters['municipio_id'] ?? 0);
        if ($municipioId > 0 && db_column_exists($pdo, 'miembros', 'municipio_id')) {
            $conditions[] = 'm.municipio_id = :municipio_id';
            $params['municipio_id'] = $municipioId;
        }
    }

    $disciplineConditions = [];
    $relationTable = $memberType === 'academia' ? 'academia_disciplinas' : 'miembro_disciplinas';
    if ($discipline !== 'todos' && csf_db_table_exists($pdo, 'disciplinas') && csf_db_table_exists($pdo, $relationTable)) {
        $disciplineConditions[] = 'EXISTS (
            SELECT 1
            FROM ' . $relationTable . ' rd
            INNER JOIN disciplinas d ON d.id = rd.disciplina_id
            WHERE rd.' . ($memberType === 'academia' ? 'academia_id' : 'miembro_id') . ' = m.id
                AND d.slug = :discipline_slug
                AND d.estado = "ACTIVA"
        )';
        $params['discipline_slug'] = $discipline;
    }

    foreach (csf_discipline_terms($discipline) as $index => $term) {
        $param = 'discipline_' . $index;
        $disciplineConditions[] = 'LOWER(CONCAT_WS(" ", m.nombre_publico, m.biografia, m.perfil_json, u.nombre)) LIKE :' . $param;
        $params[$param] = '%' . mb_strtolower($term, 'UTF-8') . '%';
    }

    if ($disciplineConditions) {
        $conditions[] = '(' . implode(' OR ', $disciplineConditions) . ')';
    }

    $sql = 'SELECT
            m.slug,
            m.nombre_publico,
            m.biografia,
            m.ciudad,
            m.provincia_texto,
            m.foto_principal_path,
            m.perfil_json,
            u.nombre,
            tm.slug AS tipo_miembro_slug
        FROM miembros m
        INNER JOIN usuarios u ON u.id = m.usuario_id
        LEFT JOIN tipos_miembro tm ON tm.id = m.tipo_miembro_id
        WHERE ' . implode(' AND ', $conditions) . '
        ORDER BY COALESCE(m.perfil_completo_at, m.updated_at) DESC
        LIMIT ' . max(1, min(96, $limit));

    $statement = $pdo->prepare($sql);
    $statement->execute($params);

    return $statement->fetchAll(PDO::FETCH_ASSOC);
}
