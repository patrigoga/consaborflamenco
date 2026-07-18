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

function csf_db_table_exists(PDO $pdo, string $table): bool
{
    $statement = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table'
    );
    $statement->execute(['table' => $table]);

    return (int) $statement->fetchColumn() > 0;
}

function csf_fetch_member_directory(PDO $pdo, string $memberType, string $discipline, int $limit = 48): array
{
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
