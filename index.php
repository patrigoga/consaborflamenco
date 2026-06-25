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
                        <a class="button button-primary" href="#landing-wizard">Empezar ahora</a>
                        <a class="button button-secondary" href="registro.php">Quiero unirme</a>
                    </div>
                </div>

                <aside id="landing-wizard" class="hero-panel landing-wizard-panel" data-landing-wizard aria-label="Guía rápida para conocer la comunidad">
                    <div class="hero-panel-header">
                        <span class="hero-panel-label">Empieza por aquí</span>
                        <h2 class="hero-panel-title">Descubre la web en 3 pasos</h2>
                        <p>Menos fricción, más claridad. En menos de un minuto sabrás cómo aprovechar la comunidad.</p>
                    </div>

                    <div class="landing-progress" aria-label="Progreso del recorrido">
                        <button type="button" class="landing-step-chip is-active" data-step-target="0">1. Qué es</button>
                        <button type="button" class="landing-step-chip" data-step-target="1">2. Qué ganas</button>
                        <button type="button" class="landing-step-chip" data-step-target="2">3. Primer paso</button>
                    </div>

                    <article class="landing-step is-active" data-landing-step="0">
                        <h3>Una comunidad flamenca con enfoque práctico</h3>
                        <p>Con Sabor Flamenco conecta cultura, revista y promoción profesional en un mismo lugar para que te encuentren y te recomienden.</p>
                        <ul>
                            <li>Revista y contenidos de valor.</li>
                            <li>Perfiles públicos de miembros.</li>
                            <li>Ecosistema preparado para crecer.</li>
                        </ul>
                    </article>

                    <article class="landing-step" data-landing-step="1" aria-hidden="true">
                        <h3>Qué puedes conseguir dentro</h3>
                        <p>Más visibilidad, mejor posicionamiento de tu proyecto y una presencia profesional para captar oportunidades reales.</p>
                        <ul>
                            <li>Presencia digital con identidad flamenca.</li>
                            <li>Conexión con público y colaboradores.</li>
                            <li>Servicios y herramientas para miembros.</li>
                        </ul>
                    </article>

                    <article class="landing-step" data-landing-step="2" aria-hidden="true">
                        <h3>Empieza sin complicarte</h3>
                        <p>Crea tu cuenta, completa tu perfil y entra en una comunidad diseñada para impulsar proyectos flamencos de forma moderna.</p>
                        <ul>
                            <li>Registro simple.</li>
                            <li>Panel privado para organizar tu perfil.</li>
                            <li>Base preparada para futuras opciones VIP.</li>
                        </ul>
                    </article>

                    <div class="landing-step-actions">
                        <button type="button" class="text-button" data-step-prev disabled>Anterior</button>
                        <button type="button" class="button button-primary" data-step-next>Siguiente</button>
                    </div>
                </aside>
            </div>
        </section>

        <section id="como-funciona" class="content-section soft-band landing-value" data-ad-category="GENERAL">
            <div class="section-heading">
                <div class="section-heading-content">
                    <p class="section-kicker">Comunidad con dirección</p>
                    <h2>Una landing pensada para que el visitante actúe</h2>
                    <p>Hemos reducido ruido para centrar la portada en explicar, convencer y llevar al siguiente paso sin saturar al usuario.</p>
                </div>
                <a class="section-enter-link" href="revista.php">Ver revista</a>
            </div>

            <div class="landing-points" aria-label="Beneficios principales de la plataforma">
                <article class="landing-point-card">
                    <span class="landing-point-number">01</span>
                    <h3>Mensaje claro desde el primer segundo</h3>
                    <p>La propuesta de valor queda visible en cabecera y guía al usuario sin distracciones.</p>
                </article>
                <article class="landing-point-card">
                    <span class="landing-point-number">02</span>
                    <h3>Recorrido guiado tipo wizard</h3>
                    <p>Tres pasos breves para explicar qué es la comunidad y por qué merece quedarse.</p>
                </article>
                <article class="landing-point-card">
                    <span class="landing-point-number">03</span>
                    <h3>Llamadas a la acción visibles</h3>
                    <p>Botones directos para comenzar el recorrido o registrarse, sin forzar procesos largos.</p>
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
