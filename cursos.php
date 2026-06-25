<?php
declare(strict_types=1);

require_once __DIR__ . '/app/layout.php';

section_page([
    'title' => 'Cursos de flamenco | Con Sabor Flamenco',
    'description' => 'Cursos presenciales, online e intensivos de flamenco.',
    'active' => 'CURSOS',
    'category' => 'CURSOS',
    'kicker' => 'Cursos',
    'heading' => 'Formación flamenca',
    'lead' => 'Propuestas presenciales, online e intensivas para aprender y perfeccionar el arte flamenco.',
    'section_id' => 'cursos',
    'section_class' => 'content-section',
    'section_title' => 'Cursos de flamenco destacados',
    'section_text' => 'Formación presencial, online e intensiva posicionada por votos o promoción.',
    'ranking' => 'CURSOS',
    'back_href' => 'index.php#cursos',
    'subcategories_label' => 'Modalidades de Cursos',
    'modal_description' => 'Así te mostraremos primero cursos y anunciantes cercanos. Guardaremos únicamente la provincia en este dispositivo.',
    'subcategories' => [
        ['id' => 'cursos-presenciales', 'label' => 'Presenciales', 'href' => '#cursos'],
        ['id' => 'cursos-online', 'label' => 'Online', 'href' => '#cursos'],
        ['id' => 'cursos-intensivos', 'label' => 'Talleres intensivos', 'href' => '#cursos'],
    ],
]);
