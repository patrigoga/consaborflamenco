<?php
declare(strict_types=1);

require_once __DIR__ . '/app/layout.php';

section_page([
    'title' => 'Servicios | Con Sabor Flamenco',
    'description' => 'Servicios digitales para artistas, academias y profesionales del flamenco.',
    'active' => 'SERVICIOS',
    'category' => 'GENERAL',
    'kicker' => 'Servicios para miembros',
    'heading' => 'Herramientas digitales para crecer',
    'lead' => 'Soluciones profesionales para mejorar visibilidad, comunicación y oportunidades dentro del flamenco.',
    'section_id' => 'servicios',
    'section_class' => 'content-section',
    'section_kicker' => 'Servicios',
    'section_title' => 'Servicios destacados',
    'section_text' => 'Las soluciones digitales situadas en las primeras posiciones.',
    'ranking' => 'SERVICIOS',
    'back_href' => 'index.php#hazte-miembro',
    'modal_description' => 'Así te mostraremos primero servicios y anunciantes cercanos. Guardaremos únicamente la provincia en este dispositivo.',
]);
