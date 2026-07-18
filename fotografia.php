<?php
declare(strict_types=1);

require_once __DIR__ . '/app/auth.php';
require_once __DIR__ . '/app/layout.php';

section_page([
    'title' => 'Fotografía flamenca | Con Sabor Flamenco',
    'description' => 'Fotografía flamenca destacada y selección visual de la comunidad.',
    'active' => 'FOTOGRAFIA',
    'category' => 'FOTOGRAFIA',
    'kicker' => 'Fotografía',
    'heading' => 'El flamenco en imágenes',
    'lead' => 'Una selección visual para mirar el baile, el cante y la escena desde cerca.',
    'section_id' => 'fotografia',
    'section_class' => 'content-section soft-band',
    'section_title' => 'El flamenco en imágenes',
    'section_text' => 'La selección visual más votada o promocionada de la comunidad.',
    'ranking' => 'FOTOGRAFIA',
    'back_href' => 'index.php#fotografia',
    'modal_description' => 'Así te mostraremos primero fotografía y anunciantes cercanos. Guardaremos únicamente la provincia en este dispositivo.',
]);
