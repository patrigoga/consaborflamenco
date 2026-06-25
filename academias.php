<?php
declare(strict_types=1);

require_once __DIR__ . '/app/layout.php';

section_page([
    'title' => 'Academias | Con Sabor Flamenco',
    'description' => 'Academias de flamenco destacadas dentro de la comunidad.',
    'active' => 'ACADEMIAS',
    'category' => 'ACADEMIAS',
    'kicker' => 'Formación',
    'heading' => 'Academias flamencas',
    'lead' => 'Centros, escuelas y espacios formativos con presencia destacada en la comunidad.',
    'section_id' => 'academias',
    'section_class' => 'content-section soft-band',
    'section_title' => 'Academias destacadas',
    'section_text' => 'Los centros con mayor apoyo o promoción dentro de la comunidad.',
    'ranking' => 'ACADEMIAS',
    'back_href' => 'index.php#academias',
    'modal_description' => 'Así te mostraremos primero academias y anunciantes cercanos. Guardaremos únicamente la provincia en este dispositivo.',
]);
