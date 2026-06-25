<?php
declare(strict_types=1);

require_once __DIR__ . '/app/layout.php';

section_page([
    'title' => 'Eventos | Con Sabor Flamenco',
    'description' => 'Agenda de eventos flamencos destacados.',
    'active' => 'EVENTOS',
    'category' => 'EVENTOS',
    'kicker' => 'Agenda',
    'heading' => 'Eventos flamencos',
    'lead' => 'Citas, clases magistrales y encuentros para vivir el flamenco cerca de la comunidad.',
    'section_id' => 'eventos',
    'section_class' => 'content-section',
    'section_title' => 'Eventos destacados',
    'section_text' => 'Las tres citas con mayor apoyo o visibilidad promocionada.',
    'ranking' => 'EVENTOS',
    'back_href' => 'index.php#eventos',
    'modal_description' => 'Así te mostraremos primero eventos y anunciantes cercanos. Guardaremos únicamente la provincia en este dispositivo.',
]);
