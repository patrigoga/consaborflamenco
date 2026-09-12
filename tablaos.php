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
        'tablao',
        'tablaos.php',
        $_GET,
        'Todavía no hay tablaos con ficha activa. Si tienes un tablao, hazte miembro y publica tu perfil.'
    )
    : '';

section_page([
    'title' => 'Tablaos | Con Sabor Flamenco',
    'description' => 'Tablaos flamencos destacados y espacios de directo.',
    'active' => 'TABLAOS',
    'category' => 'TABLAOS',
    'kicker' => 'Escenarios',
    'heading' => 'Tablaos flamencos',
    'lead' => 'Espacios de directo donde el baile, el cante y la guitarra se viven de cerca.',
    'section_id' => 'tablaos',
    'section_class' => 'content-section',
    'section_title' => 'Tablaos destacados',
    'section_text' => 'Los espacios de directo con mayor apoyo o promoción activa.',
    'ranking' => 'TABLAOS',
    'back_href' => 'index.php#tablaos',
    'modal_description' => 'Así te mostraremos primero tablaos y anunciantes cercanos. Guardaremos únicamente la provincia en este dispositivo.',
    'after_ranking' => $directorio,
]);
