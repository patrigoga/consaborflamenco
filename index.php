<?php
declare(strict_types=1);

require_once __DIR__ . '/app/auth.php';
require_once __DIR__ . '/app/layout.php';

$stylesVersion = (string) (@filemtime(__DIR__ . '/assets/css/styles.css') ?: time());
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Con Sabor Flamenco</title>
    <meta name="description" content="Revista, comunidad y tecnología para impulsar el arte flamenco.">
    <link rel="icon" type="image/svg+xml" href="assets/images/favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css?v=<?= e($stylesVersion) ?>">
    <script src="assets/js/advertising.js" defer></script>
    <script src="assets/js/navigation.js" defer></script>
    <script src="assets/js/landing-home.js" defer></script>
</head>
<body>
    <?php page_header('INICIO'); ?>

    <main>
        <section id="inicio" class="hero-section" data-ad-category="INICIO">
            <div class="hero-inner landing-hero-inner">
                <div class="hero-content landing-hero-copy">
                    <p class="section-kicker">Comunidad, revista y oportunidades reales</p>
                    <h1 class="landing-title" data-landing-title>Con Sabor Flamenco</h1>
                </div>
            </div>
        </section>
    </main>

    <footer id="contacto" class="site-footer">
        <div>
            <h2>Con Sabor Flamenco</h2>
            <p>Revista, comunidad y servicios digitales para impulsar el arte flamenco.</p>
        </div>
        <div class="footer-links">
            <div><h3>Principal</h3><a href="#inicio">Inicio</a><a href="revista.php">Revista</a><a href="artistas.php">Artistas</a><a href="servicios.php">Servicios</a></div>
            <div><h3>Legal</h3><a href="terminos-condiciones.php">Términos y condiciones</a><a href="#legal">Aviso legal</a><a id="privacidad" href="#privacidad">Privacidad</a><a href="#cookies">Cookies</a></div>
            <div><h3>Contacto</h3><a href="mailto:hola@consaborflamenco.com">hola@consaborflamenco.com</a><span>Redes sociales</span><span>Instagram · Facebook · YouTube</span></div>
        </div>
    </footer>

    <div class="province-modal" data-province-modal hidden>
        <div class="province-modal-backdrop" data-close-province></div>
        <section class="province-dialog" role="dialog" aria-modal="true" aria-labelledby="province-title" aria-describedby="province-description">
            <button class="modal-close" type="button" data-close-province aria-label="Cerrar selector de provincia">×</button>
            <p class="section-kicker">Publicidad más útil</p>
            <h2 id="province-title">¿Desde qué provincia nos visitas?</h2>
            <p id="province-description">Así te mostraremos primero eventos, espacios y anunciantes cercanos. Guardaremos únicamente la provincia en este dispositivo y podrás cambiarla cuando quieras.</p>
            <form data-province-form>
                <label for="province-select">Selecciona tu provincia</label>
                <select id="province-select" name="province" required>
                    <option value="">Elige una provincia</option>
                </select>
                <button class="button button-primary" type="submit">Ver contenido de mi provincia</button>
            </form>
            <button class="text-button modal-skip" type="button" data-skip-province>Ahora no, ver publicidad nacional</button>
        </section>
    </div>
</body>
</html>
