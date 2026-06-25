<?php
declare(strict_types=1);

require_once __DIR__ . '/app/layout.php';

section_page([
    'title' => 'Revista | Con Sabor Flamenco',
    'description' => 'Artículos, entrevistas y miradas actuales sobre la cultura flamenca.',
    'active' => 'REVISTA',
    'category' => 'REVISTA',
    'kicker' => 'Revista',
    'heading' => 'Actualidad y cultura flamenca',
    'lead' => 'Artículos, entrevistas y miradas editoriales para seguir el pulso del flamenco.',
    'section_id' => 'revista',
    'section_class' => 'content-section',
    'section_title' => 'Artículos destacados',
    'section_text' => 'Miradas actuales sobre baile, cante, guitarra, compás y cultura flamenca.',
    'ranking' => 'REVISTA',
    'back_href' => 'index.php#revista',
    'modal_description' => 'Así te mostraremos primero contenido y anunciantes cercanos. Guardaremos únicamente la provincia en este dispositivo.',
]);
