<?php
declare(strict_types=1);

require_once __DIR__ . '/app/auth.php';
require_once __DIR__ . '/app/layout.php';
require_once __DIR__ . '/app/directory_helpers.php';
require_once __DIR__ . '/app/geo_repository.php';

$pdo = db();
$directorio = $pdo
    ? csf_render_member_type_directory(
        $pdo,
        'tienda',
        'tiendas.php',
        $_GET,
        'Todavía no hay tiendas con ficha activa. Si tienes una tienda flamenca, hazte miembro y publica tu perfil.'
    )
    : '';

section_page([
    'title' => 'Tiendas | Con Sabor Flamenco',
    'description' => 'Tiendas flamencas destacadas: moda, calzado, complementos e instrumentos.',
    'active' => 'TIENDAS',
    'category' => 'TIENDAS',
    'kicker' => 'Escaparate',
    'heading' => 'Tiendas flamencas',
    'lead' => 'Comercios y talleres artesanos donde encontrar moda, calzado, complementos e instrumentos flamencos.',
    'section_id' => 'tiendas',
    'section_class' => 'content-section',
    'section_title' => 'Tiendas destacadas',
    'section_text' => 'Los comercios con mayor apoyo o promoción activa.',
    'ranking' => 'TIENDAS',
    'back_href' => 'index.php#tiendas',
    'modal_description' => 'Así te mostraremos primero tiendas y anunciantes cercanos. Guardaremos únicamente la provincia en este dispositivo.',
    'after_ranking' => $directorio,
]);
