<?php
declare(strict_types=1);

require_once __DIR__ . '/app/auth.php';
require_once __DIR__ . '/app/layout.php';

section_page([
    'title' => 'Moda flamenca | Con Sabor Flamenco',
    'description' => 'Moda flamenca destacada: ropa, calzado, complementos y moda infantil.',
    'active' => 'MODA',
    'category' => 'MODA',
    'kicker' => 'Moda',
    'heading' => 'Moda flamenca',
    'lead' => 'Ropa, calzado y complementos con identidad escénica, tradición y diseño actual.',
    'section_id' => 'moda',
    'section_class' => 'content-section soft-band',
    'section_title' => 'Moda flamenca destacada',
    'section_text' => 'Ropa, calzado y complementos posicionados por votos o promoción.',
    'ranking' => 'MODA',
    'back_href' => 'index.php#moda',
    'subcategories_label' => 'Subcategorías de Moda',
    'modal_description' => 'Así te mostraremos primero moda y anunciantes cercanos. Guardaremos únicamente la provincia en este dispositivo.',
    'subcategories' => [
        ['id' => 'moda-ropa', 'label' => 'Ropa', 'href' => '#moda'],
        ['id' => 'moda-calzado', 'label' => 'Calzado', 'href' => '#moda'],
        ['id' => 'moda-complementos', 'label' => 'Complementos', 'href' => '#moda'],
        ['id' => 'moda-infantil', 'label' => 'Moda infantil', 'href' => '#moda'],
    ],
]);
