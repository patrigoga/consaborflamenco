<?php
declare(strict_types=1);

require_once __DIR__ . '/app/auth.php';
require_once __DIR__ . '/app/layout.php';

section_page([
    'title' => 'Historia del flamenco | Con Sabor Flamenco',
    'description' => 'Historia y evolución del flamenco: etapas, raíces, contextos y figuras relevantes.',
    'active' => 'HISTORIA',
    'category' => 'HISTORIA',
    'kicker' => 'Historia',
    'heading' => 'Historia y evolución del flamenco',
    'lead' => 'Un recorrido por las raíces, las etapas históricas y las figuras que han dado forma al arte flamenco.',
    'section_id' => 'historia-flamenco',
    'section_class' => 'content-section',
    'section_kicker' => 'Memoria flamenca',
    'section_title' => 'Etapas, raíces y nombres propios',
    'section_text' => 'Contenidos para comprender el desarrollo del flamenco desde sus contextos populares hasta la escena contemporánea.',
    'ranking' => 'HISTORIA',
    'back_href' => 'index.php#inicio',
    'modal_description' => 'Así te mostraremos primero contenidos de historia y anunciantes cercanos. Guardaremos únicamente la provincia en este dispositivo.',
]);
