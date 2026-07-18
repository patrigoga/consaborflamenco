<?php
declare(strict_types=1);

require_once __DIR__ . '/app/auth.php';
require_once __DIR__ . '/app/layout.php';

section_page([
    'title' => 'Llaves de Oro del Cante | Con Sabor Flamenco',
    'description' => 'Historia, contexto y galardonados de la Llave de Oro del Cante flamenco.',
    'active' => 'LLAVES_ORO',
    'category' => 'LLAVES_ORO',
    'kicker' => 'Reconocimientos',
    'heading' => 'Llaves de Oro del Cante',
    'lead' => 'Contexto, historia y galardonados de uno de los reconocimientos más simbólicos del cante flamenco.',
    'section_id' => 'llaves-de-oro',
    'section_class' => 'content-section',
    'section_kicker' => 'Legado del cante',
    'section_title' => 'Galardonados, memoria y contexto',
    'section_text' => 'Un espacio para situar la importancia de la Llave de Oro, sus protagonistas y su valor dentro de la historia flamenca.',
    'ranking' => 'LLAVES_ORO',
    'back_href' => 'index.php#inicio',
    'modal_description' => 'Así te mostraremos primero contenidos sobre la Llave de Oro del Cante y anunciantes cercanos. Guardaremos únicamente la provincia en este dispositivo.',
]);
