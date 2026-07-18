<?php
declare(strict_types=1);

require_once __DIR__ . '/app/auth.php';
require_once __DIR__ . '/app/layout.php';

section_page([
    'title' => 'Palos del flamenco | Con Sabor Flamenco',
    'description' => 'Guía de palos del flamenco: compás, familias, estilos y claves para reconocer cada forma.',
    'active' => 'PALOS_FLAMENCO',
    'category' => 'PALOS_FLAMENCO',
    'kicker' => 'Palos del flamenco',
    'heading' => 'Directorio de palos flamencos',
    'lead' => 'Una guía para consultar estilos, familias, compases y rasgos de los diferentes palos del flamenco.',
    'section_id' => 'palos-flamenco',
    'section_class' => 'content-section soft-band',
    'section_kicker' => 'Guía de consulta',
    'section_title' => 'Familias, compás y expresión',
    'section_text' => 'Soleá, seguiriyas, tangos, bulerías, alegrías y otros estilos como punto de partida para escuchar con más contexto.',
    'ranking' => 'PALOS_FLAMENCO',
    'back_href' => 'index.php#inicio',
    'subcategories_label' => 'Consultar palos del flamenco',
    'subcategories' => [
        ['id' => 'solea', 'label' => 'Soleá', 'href' => '#palos-flamenco'],
        ['id' => 'seguiriyas', 'label' => 'Seguiriyas', 'href' => '#palos-flamenco'],
        ['id' => 'tangos', 'label' => 'Tangos', 'href' => '#palos-flamenco'],
        ['id' => 'bulerias', 'label' => 'Bulerías', 'href' => '#palos-flamenco'],
        ['id' => 'alegrias', 'label' => 'Alegrías', 'href' => '#palos-flamenco'],
    ],
    'modal_description' => 'Así te mostraremos primero contenidos sobre palos flamencos y anunciantes cercanos. Guardaremos únicamente la provincia en este dispositivo.',
]);
