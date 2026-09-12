<?php
declare(strict_types=1);

require_once __DIR__ . '/app/auth.php';
require_once __DIR__ . '/app/layout.php';
require_once __DIR__ . '/app/directory_helpers.php';
require_once __DIR__ . '/app/geo_repository.php';

$pdo = db();
$directorio = $pdo
    ? csf_render_member_type_directory(
        $pdo,
        'pena',
        'penas.php',
        $_GET,
        'Todavía no hay peñas con ficha activa. Si llevas una peña flamenca, hazte miembro y publica su perfil.'
    )
    : '';

section_page([
    'title' => 'Peñas flamencas | Con Sabor Flamenco',
    'description' => 'Peñas flamencas destacadas y espacios de comunidad local.',
    'active' => 'PENAS',
    'category' => 'PENAS',
    'kicker' => 'Comunidad local',
    'heading' => 'Peñas flamencas',
    'lead' => 'Espacios de encuentro donde el cante, la guitarra y la convivencia mantienen viva la cultura.',
    'section_id' => 'penas',
    'section_class' => 'content-section',
    'section_title' => 'Peñas destacadas',
    'section_text' => 'Espacios de encuentro ordenados por respaldo de la comunidad o promoción.',
    'ranking' => 'PENAS',
    'back_href' => 'index.php#penas',
    'modal_description' => 'Así te mostraremos primero peñas y anunciantes cercanos. Guardaremos únicamente la provincia en este dispositivo.',
    'after_ranking' => $directorio,
]);
