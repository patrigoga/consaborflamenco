<?php
declare(strict_types=1);

require_once __DIR__ . '/admin_ui.php';

function academia_panel_sections(string $rol): array
{
    $sections = [
        'resumen' => 'Resumen',
        'academia' => 'Mi academia',
    ];

    if ($rol === 'RESPONSABLE') {
        $sections['profesores'] = 'Profesores';
    }

    $sections['alumnos'] = 'Alumnos';
    $sections['cursos'] = 'Cursos';
    $sections['grupos'] = 'Grupos';

    if ($rol === 'RESPONSABLE') {
        $sections['matriculas'] = 'Matrículas';
    }

    return $sections;
}

function academia_active_section_key(array $allowedSections): string
{
    $section = clean_text((string) ($_GET['section'] ?? 'resumen'));

    return array_key_exists($section, $allowedSections) ? $section : array_key_first($allowedSections);
}

function academia_section_url(string $section, array $params = []): string
{
    $query = array_merge(['section' => $section], $params);

    return 'panel-academia.php?' . http_build_query(array_filter($query, static fn ($value): bool => $value !== null && $value !== ''));
}

function academia_dia_semana_label(int $dia): string
{
    $labels = academia_dias_semana();

    return $labels[$dia] ?? '-';
}

/**
 * Pinta las <option> de un catalogo de ENUM del modulo.
 *
 * Los catalogos viven en app/academia_repository.php, no aqui: los necesita
 * tambien la capa de datos para validar, y ese fichero si lo carga siempre
 * app/auth.php.
 */
function academia_enum_options(array $options, string $selected): string
{
    $html = '';
    foreach ($options as $value => $label) {
        $html .= '<option value="' . e((string) $value) . '"'
            . ((string) $value === $selected ? ' selected' : '') . '>'
            . e((string) $label) . '</option>';
    }

    return $html;
}
