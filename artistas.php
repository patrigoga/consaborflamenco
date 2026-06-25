<?php
declare(strict_types=1);

require_once __DIR__ . '/app/layout.php';

section_page([
    'title' => 'Artistas | Con Sabor Flamenco',
    'description' => 'Directorio de artistas flamencos destacados.',
    'active' => 'ARTISTAS',
    'category' => 'ARTISTAS',
    'kicker' => 'Directorio',
    'heading' => 'Artistas flamencos',
    'lead' => 'Perfiles artísticos preparados para descubrir talento, trayectoria y contratación.',
    'section_id' => 'artistas',
    'section_class' => 'content-section',
    'section_title' => 'Artistas destacados',
    'section_text' => 'Los perfiles artísticos que ocupan las tres primeras posiciones.',
    'ranking' => 'ARTISTAS',
    'back_href' => 'index.php#artistas',
    'modal_description' => 'Así te mostraremos primero artistas y anunciantes cercanos. Guardaremos únicamente la provincia en este dispositivo.',
]);
