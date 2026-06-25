<?php
declare(strict_types=1);

require_once __DIR__ . '/app/auth.php';
require_once __DIR__ . '/app/layout.php';
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
    <link rel="stylesheet" href="assets/css/styles.css">
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
                    <p class="hero-lead">Una plataforma viva para artistas, academias, peñas, tablaos y festivales que quieren visibilidad, contactos y crecimiento digital.</p>
                    <div class="hero-actions">
                        <a class="button button-primary" href="#como-funciona">Empezar ahora</a>
                        <a class="button button-secondary" href="registro.php">Quiero unirme</a>
                    </div>
                </div>

                <aside class="hero-panel landing-focus-panel" aria-label="Resumen de valor de la comunidad">
                    <div class="hero-panel-header">
                        <span class="hero-panel-label">Empieza por aquí</span>
                        <h2 class="hero-panel-title">Qué te ofrece la comunidad</h2>
                        <p class="landing-rotator" data-landing-rotator>Más visibilidad para tu proyecto flamenco.</p>
                    </div>

                    <div class="landing-tags" aria-label="Perfiles de la comunidad">
                        <span>Artistas</span>
                        <span>Academias</span>
                        <span>Peñas</span>
                        <span>Tablaos</span>
                        <span>Festivales</span>
                    </div>

                    <article class="landing-focus-block">
                        <h3>Un punto de entrada claro</h3>
                        <p>Sin menús interminables ni pasos pesados: explicamos rápido qué es la plataforma y te llevamos a la acción.</p>
                        <ul>
                            <li>Mensaje directo en portada.</li>
                            <li>Recorrido visual de 3 pasos.</li>
                            <li>Registro en primer plano.</li>
                        </ul>
                    </article>

                    <div class="landing-focus-actions">
                        <a class="button button-primary" href="registro.php">Crear mi perfil</a>
                        <a class="text-button" href="revista.php">Ver revista</a>
                    </div>
                </aside>
            </div>
        </section>

        <section id="como-funciona" class="content-section soft-band landing-value" data-ad-category="GENERAL">
            <div class="section-heading">
                <div class="section-heading-content">
                    <p class="section-kicker">Comunidad con dirección</p>
                    <h2>Cómo funciona en solo 3 pasos</h2>
                    <p>Queremos que el usuario entienda rápido la propuesta y avance sin aburrirse ni perderse.</p>
                </div>
                <a class="section-enter-link" href="revista.php">Ver revista</a>
            </div>

            <div class="landing-points" aria-label="Recorrido principal de la plataforma">
                <article class="landing-point-card" data-landing-reveal>
                    <span class="landing-point-number">01</span>
                    <h3>Entiende la propuesta</h3>
                    <p>Desde la cabecera se explica qué es Con Sabor Flamenco y para quién está pensada.</p>
                </article>
                <article class="landing-point-card" data-landing-reveal>
                    <span class="landing-point-number">02</span>
                    <h3>Visualiza el valor</h3>
                    <p>Ves de forma rápida cómo mejorar visibilidad, posicionamiento y contacto con la comunidad.</p>
                </article>
                <article class="landing-point-card" data-landing-reveal>
                    <span class="landing-point-number">03</span>
                    <h3>Actúa en un clic</h3>
                    <p>Registro visible y acceso inmediato para empezar hoy con tu perfil profesional.</p>
                </article>
            </div>
        </section>

        <section id="perfiles" class="content-section landing-profiles" data-ad-category="GENERAL">
            <div class="section-heading">
                <div class="section-heading-content">
                    <p class="section-kicker">Tu lugar en la comunidad</p>
                    <h2>Elige tu camino dentro de Con Sabor Flamenco</h2>
                    <p>Un mismo ecosistema para distintos perfiles del mundo flamenco.</p>
                </div>
                <a class="section-enter-link" href="registro.php">Empezar registro</a>
            </div>

            <div class="landing-profiles-grid" aria-label="Tipos de perfiles de la comunidad">
                <article class="landing-profile-card" data-landing-reveal>
                    <h3>Artistas</h3>
                    <p>Muestra tu trayectoria y aumenta tu visibilidad profesional.</p>
                    <a href="artistas.php">Explorar artistas</a>
                </article>
                <article class="landing-profile-card" data-landing-reveal>
                    <h3>Academias</h3>
                    <p>Capta alumnado y presenta tu oferta formativa de forma atractiva.</p>
                    <a href="academias.php">Explorar academias</a>
                </article>
                <article class="landing-profile-card" data-landing-reveal>
                    <h3>Peñas y tablaos</h3>
                    <p>Difunde programación, conecta con público y gana presencia digital.</p>
                    <a href="penas.php">Explorar peñas</a>
                </article>
                <article class="landing-profile-card" data-landing-reveal>
                    <h3>Festivales y eventos</h3>
                    <p>Impulsa cartel, agenda y difusión de tus citas más importantes.</p>
                    <a href="festivales.php">Explorar festivales</a>
                </article>
            </div>
        </section>

        <section id="hazte-miembro" class="cta-section" data-ad-category="GENERAL">
            <div>
                <p class="section-kicker">Da el siguiente paso</p>
                <h2>Únete a la comunidad Con Sabor Flamenco</h2>
                <p>Empieza hoy con un perfil profesional y entra en un entorno digital creado para impulsar talento, espacios y proyectos flamencos.</p>
            </div>
            <div class="landing-cta-actions">
                <a class="button button-primary" href="registro.php">Crear mi cuenta</a>
                <a class="button button-secondary" href="acceso.php">Ya tengo cuenta</a>
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
