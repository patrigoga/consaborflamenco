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
    <script src="assets/js/section-rankings.js" defer></script>
</head>
<body>
    <?php page_header('INICIO'); ?>

<main>
        <section id="inicio" class="hero-section" data-ad-category="INICIO">
            <div class="hero-inner">
                <div class="hero-content">
                    <p class="section-kicker">Revista, comunidad y tecnología flamenca</p>
                    <h1>Con Sabor Flamenco</h1>
                    <p class="hero-lead">La plataforma que une revista, comunidad y tecnología para impulsar el arte flamenco.</p>
                </div>

                <aside class="hero-panel" aria-label="Contenidos más visitados de la comunidad">
                    <div class="hero-panel-header">
                        <span class="hero-panel-label">Comunidad</span>
                        <h2 class="hero-panel-title">Lo más visitado ahora</h2>
                        <p>Los cuatro contenidos que más interés están generando en la comunidad.</p>
                    </div>
                    <div class="community-cards" data-community-ranking>
                        <article class="community-card">
                            <a class="community-card-image" href="#artistas" aria-label="Ver Bailaora en directo">
                                <img src="assets/images/community/artista-bailaora.webp" alt="Bailaora actuando en un tablao" width="640" height="480">
                            </a>
                            <div class="community-card-content">
                                <span>Artista</span>
                                <h3>Bailaora en directo</h3>
                                <p>Fuerza y arte sobre el tablao.</p>
                                <a class="community-card-link" href="#artistas">Ver más</a>
                            </div>
                        </article>
                        <article class="community-card">
                            <a class="community-card-image" href="#academias" aria-label="Ver Academia de baile">
                                <img src="assets/images/community/academia-flamenca.webp" alt="Clase en una academia de baile flamenco" width="640" height="480">
                            </a>
                            <div class="community-card-content">
                                <span>Academia</span>
                                <h3>Academia de baile</h3>
                                <p>Formación con raíz flamenca.</p>
                                <a class="community-card-link" href="#academias">Ver más</a>
                            </div>
                        </article>
                        <article class="community-card">
                            <a class="community-card-image" href="#eventos" aria-label="Ver Festival flamenco">
                                <img src="assets/images/community/evento-flamenco.webp" alt="Festival flamenco al aire libre" width="640" height="480" loading="lazy">
                            </a>
                            <div class="community-card-content">
                                <span>Evento</span>
                                <h3>Festival flamenco</h3>
                                <p>Una cita para vivir el cante.</p>
                                <a class="community-card-link" href="#eventos">Ver más</a>
                            </div>
                        </article>
                        <article class="community-card">
                            <a class="community-card-image" href="#penas" aria-label="Ver Peña flamenca">
                                <img src="assets/images/community/pena-flamenca.webp" alt="Encuentro musical en una peña flamenca" width="640" height="480" loading="lazy">
                            </a>
                            <div class="community-card-content">
                                <span>Peña</span>
                                <h3>Peña flamenca</h3>
                                <p>Tradición, cante y encuentro.</p>
                                <a class="community-card-link" href="#penas">Ver más</a>
                            </div>
                        </article>
                    </div>
                </aside>

                <div class="hero-actions">
                    <a class="button button-primary" href="#artistas">Descubre la comunidad</a>
                    <a class="button button-secondary" href="#hazte-miembro">Hazte miembro</a>
                </div>
            </div>
        </section>

        <div class="page-shell">
            <div class="primary-content">
                <aside class="ad-mobile-strip" aria-label="Publicidad local">
                    <div class="ad-sidebar-heading">
                        <div>
                            <span class="ad-eyebrow">Selección patrocinada</span>
                            <h2><span data-ad-category-label>Inicio</span> · <span data-ad-province>tu provincia</span></h2>
                        </div>
                        <button type="button" class="text-button" data-open-province>Cambiar provincia</button>
                    </div>
                    <div class="ad-slots" data-ad-slots aria-live="polite"></div>
                </aside>

                <section id="flamenco-home" class="content-section soft-band" data-ad-category="FLAMENCO"><div class="section-heading"><div class="section-heading-content"><p class="section-kicker">Flamenco</p><h2>Cultura flamenca</h2><p>Historia, palos del flamenco y Llaves de Oro reunidos en una página propia.</p></div><a class="section-enter-link" href="flamenco.php">Entrar en esta sección</a></div></section>
                <section id="revista" class="content-section" data-ad-category="REVISTA"><div class="section-heading"><div class="section-heading-content"><p class="section-kicker">Revista</p><h2>Artículos destacados</h2><p>Miradas actuales sobre baile, cante, guitarra, compás y cultura flamenca.</p></div><a class="section-enter-link" href="revista.php">Entrar en esta sección</a></div><div class="editorial-grid section-ranking" data-ranking-section="REVISTA"></div></section>
                <section id="academias" class="content-section soft-band" data-ad-category="ACADEMIAS"><div class="section-heading"><div class="section-heading-content"><p class="section-kicker">Formación</p><h2>Academias destacadas</h2><p>Los centros con mayor apoyo o promoción dentro de la comunidad.</p></div><a class="section-enter-link" href="academias.php">Entrar en esta sección</a></div><div class="editorial-grid section-ranking" data-ranking-section="ACADEMIAS"></div></section>
                <section id="cursos" class="content-section" data-ad-category="CURSOS"><div class="section-heading"><div class="section-heading-content"><p class="section-kicker">Cursos</p><h2>Cursos de flamenco destacados</h2><p>Formación presencial, online e intensiva posicionada por votos o promoción.</p></div><a class="section-enter-link" href="cursos.php">Entrar en esta sección</a></div><div class="section-subcategories" aria-label="Modalidades de Cursos"><a id="cursos-presenciales" href="#cursos">Presenciales</a><a id="cursos-online" href="#cursos">Online</a><a id="cursos-intensivos" href="#cursos">Talleres intensivos</a></div><div class="editorial-grid section-ranking" data-ranking-section="CURSOS"></div></section>
                <section id="artistas" class="content-section" data-ad-category="ARTISTAS"><div class="section-heading"><div class="section-heading-content"><p class="section-kicker">Directorio</p><h2>Artistas destacados</h2><p>Los perfiles artísticos que ocupan las tres primeras posiciones.</p></div><a class="section-enter-link" href="artistas.php">Entrar en esta sección</a></div><div class="editorial-grid section-ranking" data-ranking-section="ARTISTAS"></div></section>
                <!-- <section id="concursos" class="content-section soft-band" data-ad-category="CONCURSOS"><div class="section-heading"><p class="section-kicker">Talento</p><h2>Concursos destacados</h2><p>Convocatorias posicionadas por votos o promoción contratada.</p></div><div class="editorial-grid section-ranking" data-ranking-section="CONCURSOS"></div></section> -->
                <section id="eventos" class="content-section" data-ad-category="EVENTOS"><div class="section-heading"><div class="section-heading-content"><p class="section-kicker">Agenda</p><h2>Eventos destacados</h2><p>Las tres citas con mayor apoyo o visibilidad promocionada.</p></div><a class="section-enter-link" href="eventos.php">Entrar en esta sección</a></div><div class="editorial-grid section-ranking" data-ranking-section="EVENTOS"></div></section>
                <section id="festivales" class="content-section soft-band" data-ad-category="FESTIVALES"><div class="section-heading"><div class="section-heading-content"><p class="section-kicker">Grandes citas</p><h2>Festivales destacados</h2><p>Carteles y programaciones situados en las primeras posiciones.</p></div><a class="section-enter-link" href="festivales.php">Entrar en esta sección</a></div><div class="editorial-grid section-ranking" data-ranking-section="FESTIVALES"></div></section>
                <section id="penas" class="content-section" data-ad-category="PENAS"><div class="section-heading"><div class="section-heading-content"><p class="section-kicker">Comunidad local</p><h2>Peñas destacadas</h2><p>Espacios de encuentro ordenados por respaldo de la comunidad o promoción.</p></div><a class="section-enter-link" href="penas.php">Entrar en esta sección</a></div><div class="editorial-grid section-ranking" data-ranking-section="PENAS"></div></section>
                <!-- Servicios se ha trasladado a servicios.php. -->
                <section id="tablaos" class="content-section" data-ad-category="TABLAOS"><div class="section-heading"><div class="section-heading-content"><p class="section-kicker">Escenarios</p><h2>Tablaos destacados</h2><p>Los espacios de directo con mayor apoyo o promoción activa.</p></div><a class="section-enter-link" href="tablaos.php">Entrar en esta sección</a></div><div class="editorial-grid section-ranking" data-ranking-section="TABLAOS"></div></section>
                <section id="moda" class="content-section soft-band" data-ad-category="MODA"><div class="section-heading"><div class="section-heading-content"><p class="section-kicker">Moda</p><h2>Moda flamenca destacada</h2><p>Ropa, calzado y complementos posicionados por votos o promoción.</p></div><a class="section-enter-link" href="moda.php">Entrar en esta sección</a></div><div class="section-subcategories" aria-label="Subcategorías de Moda"><a id="moda-ropa" href="#moda">Ropa</a><a id="moda-calzado" href="#moda">Calzado</a><a id="moda-complementos" href="#moda">Complementos</a><a id="moda-infantil" href="#moda">Moda infantil</a></div><div class="editorial-grid section-ranking" data-ranking-section="MODA"></div></section>
                <section id="fotografia" class="content-section soft-band" data-ad-category="FOTOGRAFIA"><div class="section-heading"><div class="section-heading-content"><p class="section-kicker">Fotografía</p><h2>El flamenco en imágenes</h2><p>La selección visual más votada o promocionada de la comunidad.</p></div><a class="section-enter-link" href="fotografia.php">Entrar en esta sección</a></div><div class="editorial-grid section-ranking" data-ranking-section="FOTOGRAFIA"></div></section>
                <!-- Flamenco se ha trasladado a flamenco.php. -->

            </div>

            <aside class="ad-sidebar" aria-label="Publicidad local">
                <div class="ad-sidebar-inner">
                    <div class="ad-sidebar-heading">
                        <div>
                            <span class="ad-eyebrow">Selección patrocinada</span>
                            <h2><span data-ad-category-label>Inicio</span> · <span data-ad-province>tu provincia</span></h2>
                        </div>
                        <button type="button" class="text-button" data-open-province>Cambiar</button>
                    </div>
                    <div class="ad-slots" data-ad-slots aria-live="polite"></div>
                    <p class="ad-disclosure">Espacios publicitarios seleccionados por sección y provincia.</p>
                </div>
            </aside>
        </div>

        <section id="hazte-miembro" class="cta-section" data-ad-category="GENERAL">
            <div>
                <p class="section-kicker">Forma parte</p>
                <h2>Una comunidad preparada para impulsar el flamenco</h2>
                <p>Conecta tu proyecto con una plataforma pensada para cultura, promoción, servicios y futuro digital.</p>
            </div>
            <a class="button button-primary" href="registro.php">Hazte miembro</a>
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
