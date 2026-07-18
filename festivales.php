<?php
declare(strict_types=1);

require_once __DIR__ . '/app/auth.php';
require_once __DIR__ . '/app/layout.php';

section_page([
    'title' => 'Festivales | Con Sabor Flamenco',
    'description' => 'Festivales flamencos destacados, carteles y programaciones.',
    'active' => 'FESTIVALES',
    'category' => 'FESTIVALES',
    'kicker' => 'Grandes citas',
    'heading' => 'Festivales flamencos',
    'lead' => 'Carteles, programaciones y encuentros con alcance cultural y comunitario.',
    'section_id' => 'festivales',
    'section_class' => 'content-section soft-band',
    'section_title' => 'Festivales destacados',
    'section_text' => 'Carteles y programaciones situados en las primeras posiciones.',
    'ranking' => 'FESTIVALES',
    'back_href' => 'index.php#festivales',
    'modal_description' => 'Así te mostraremos primero festivales y anunciantes cercanos. Guardaremos únicamente la provincia en este dispositivo.',
]);
