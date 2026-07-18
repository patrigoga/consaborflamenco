<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

function page_head(string $title, string $description, bool $includeRankings = true): void
{
    $stylesVersion = (string) (@filemtime(__DIR__ . '/../assets/css/styles.css') ?: time());
    $adminSidebarVersion = (string) (@filemtime(__DIR__ . '/../assets/js/admin-sidebar.js') ?: time());
    $isAdmin = strpos($_SERVER['REQUEST_URI'] ?? '', 'panel-admin.php') !== false;
    ?>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= e($title) ?></title>
        <meta name="description" content="<?= e($description) ?>">
        <link rel="icon" type="image/svg+xml" href="<?= e(app_url('assets/images/favicon.svg')) ?>">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:wght@700;800&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="<?= e(app_url('assets/css/styles.css')) ?>?v=<?= e($stylesVersion) ?>">
        <script src="<?= e(app_url('assets/js/advertising.js')) ?>" defer></script>
        <script src="<?= e(app_url('assets/js/navigation.js')) ?>" defer></script>
        <script src="<?= e(app_url('assets/js/password-visibility.js')) ?>" defer></script>
        <?php if ($isAdmin): ?><script src="<?= e(app_url('assets/js/admin-sidebar.js')) ?>?v=<?= e($adminSidebarVersion) ?>" defer></script><?php endif; ?>
        <?php if ($includeRankings): ?><script src="<?= e(app_url('assets/js/section-rankings.js')) ?>" defer></script><?php endif; ?>
    </head>
    <?php
}

function nav_class(string $active, string $section): string
{
    return $active === $section ? ' class="is-active"' : '';
}

function page_header(string $active = ''): void
{
    static $headerRendered = false;
    if ($headerRendered) {
        return;
    }
    $headerRendered = true;

    $flamencoOpen = $active === 'FLAMENCO';
    $revistaOpen = in_array($active, ['REVISTA', 'ARTISTAS', 'ACADEMIAS', 'FOTOGRAFIA', 'MODA', 'PENAS', 'TABLAOS', 'FESTIVALES'], true);
    $user = function_exists('current_user') ? current_user() : null;
    if ($user && ($user['role'] ?? 'user') !== 'admin' && function_exists('user_email_is_verified') && !user_email_is_verified($user)) {
        $user = null;
    }
    $panelHref = ($user['role'] ?? '') === 'admin' ? 'panel-admin.php' : 'panel-usuario.php';
    $userName = $user['name'] ?? 'Miembro';
    $initials = strtoupper(substr($userName, 0, 1));
    $userProfilePhoto = clean_text((string) ($user['artistic_profile']['main_photo_path'] ?? ''));
    ?>
    <header class="site-header">
        <a class="brand" href="index.php#inicio" aria-label="Con Sabor Flamenco - Inicio">
            <span class="brand-mark">CSF</span>
            <span>Con Sabor Flamenco</span>
        </a>

        <button class="menu-toggle" type="button" aria-controls="main-nav" aria-expanded="false">
            <span class="menu-toggle-lines" aria-hidden="true"><span></span><span></span><span></span></span>
            <span>Menú</span>
        </button>

        <nav id="main-nav" class="main-nav" aria-label="Menú principal">
            <a href="index.php#inicio" data-ad-nav="INICIO"<?= nav_class($active, 'INICIO') ?>>Inicio</a>
            <div class="nav-accordion<?= $flamencoOpen ? ' is-open' : '' ?>">
                <button class="nav-accordion-toggle<?= $flamencoOpen ? ' is-active' : '' ?>" type="button" data-ad-nav="FLAMENCO" aria-controls="flamenco-submenu" aria-expanded="<?= $flamencoOpen ? 'true' : 'false' ?>">
                    <span>Flamenco</span><span class="nav-chevron" aria-hidden="true">⌄</span>
                </button>
                <div id="flamenco-submenu" class="nav-submenu">
                    <a href="flamenco.php#historia-flamenco">Historia</a>
                    <a href="flamenco.php#palos-flamenco">Palos del flamenco</a>
                    <a href="flamenco.php#llaves-oro">Llaves de Oro</a>
                </div>
            </div>
            <div class="nav-accordion<?= $revistaOpen ? ' is-open' : '' ?>">
                <button class="nav-accordion-toggle<?= $revistaOpen ? ' is-active' : '' ?>" type="button" data-ad-nav="REVISTA" aria-controls="revista-submenu" aria-expanded="<?= $revistaOpen ? 'true' : 'false' ?>">
                    <span>Revista</span><span class="nav-chevron" aria-hidden="true">⌄</span>
                </button>
                <div id="revista-submenu" class="nav-submenu nav-submenu-wide">
                    <a href="revista.php" data-ad-nav="REVISTA"<?= nav_class($active, 'REVISTA') ?>>Portada</a>
                    <a href="artistas.php" data-ad-nav="ARTISTAS"<?= nav_class($active, 'ARTISTAS') ?>>Artistas</a>
                    <a href="academias.php" data-ad-nav="ACADEMIAS"<?= nav_class($active, 'ACADEMIAS') ?>>Academias</a>
                    <a href="fotografia.php" data-ad-nav="FOTOGRAFIA"<?= nav_class($active, 'FOTOGRAFIA') ?>>Fotografía</a>
                    <a href="moda.php" data-ad-nav="MODA"<?= nav_class($active, 'MODA') ?>>Moda</a>
                    <a href="penas.php" data-ad-nav="PENAS"<?= nav_class($active, 'PENAS') ?>>Peñas</a>
                    <a href="tablaos.php" data-ad-nav="TABLAOS"<?= nav_class($active, 'TABLAOS') ?>>Tablaos</a>
                    <a href="festivales.php" data-ad-nav="FESTIVALES"<?= nav_class($active, 'FESTIVALES') ?>>Festivales</a>
                </div>
            </div>
            <a href="servicios.php"<?= nav_class($active, 'SERVICIOS') ?>>Servicios</a>
            <a href="#contacto">Contacto</a>
            <div class="mobile-nav-footer">
                <button class="location-trigger mobile-location-trigger" type="button" data-open-province aria-label="Cambiar provincia">
                    <span aria-hidden="true">●</span>
                    <span data-current-province>Tu provincia</span>
                </button>
                <?php if ($user): ?>
                    <a href="<?= e($panelHref) ?>#perfil">Editar perfil</a>
                    <a href="<?= e($panelHref) ?>#seguridad">Cambiar contraseña</a>
                    <a href="cerrar-sesion.php">Cerrar sesión</a>
                <?php else: ?>
                    <a class="link-access" href="acceso.php">Acceder</a>
                    <a class="button button-primary" href="registro.php">Hazte miembro</a>
                <?php endif; ?>
            </div>
        </nav>

        <div class="header-actions">
            <button class="location-trigger" type="button" data-open-province aria-label="Cambiar provincia">
                <span aria-hidden="true">●</span>
                <span data-current-province>Tu provincia</span>
            </button>
            <?php if ($user): ?>
                <div class="profile-menu">
                    <a class="profile-trigger" href="<?= e($panelHref) ?>" aria-label="Abrir panel de <?= e($userName) ?>">
                        <span class="profile-avatar"><?php if ($userProfilePhoto !== ''): ?><img src="<?= e($userProfilePhoto) ?>" alt="Foto de <?= e($userName) ?>" loading="lazy"><?php else: ?><?= e($initials) ?><?php endif; ?></span>
                        <span class="profile-name"><?= e($userName) ?></span>
                    </a>
                    <div class="profile-dropdown" aria-label="Menú de usuario">
                        <a href="<?= e($panelHref) ?>#perfil">Editar perfil</a>
                        <a href="<?= e($panelHref) ?>#seguridad">Cambiar contraseña</a>
                        <a href="cerrar-sesion.php">Cerrar sesión</a>
                    </div>
                </div>
            <?php else: ?>
                <a class="link-access" href="acceso.php">Acceder</a>
                <a class="button button-primary" href="registro.php">Hazte miembro</a>
            <?php endif; ?>
        </div>
    </header>
    <?php
}

function page_footer(): void
{
    ?>
    <footer id="contacto" class="site-footer">
        <div>
            <h2>Con Sabor Flamenco</h2>
            <p>Revista, comunidad y servicios digitales para impulsar el arte flamenco.</p>
        </div>
        <div class="footer-links">
            <div><h3>Principal</h3><a href="index.php#inicio">Inicio</a><a href="revista.php">Revista</a><a href="artistas.php">Artistas</a><a href="servicios.php">Servicios</a></div>
            <div><h3>Legal</h3><a href="terminos-condiciones.php">Términos y condiciones</a><a href="#legal">Aviso legal</a><a href="#privacidad">Privacidad</a><a href="#cookies">Cookies</a></div>
            <div><h3>Contacto</h3><a href="mailto:hola@consaborflamenco.com">hola@consaborflamenco.com</a><span>Redes sociales</span><span>Instagram · Facebook · YouTube</span></div>
        </div>
    </footer>
    <?php
}

function province_modal(string $description = 'Así te mostraremos primero eventos, espacios y anunciantes cercanos. Guardaremos únicamente la provincia en este dispositivo y podrás cambiarla cuando quieras.'): void
{
    ?>
    <div class="province-modal" data-province-modal hidden>
        <div class="province-modal-backdrop" data-close-province></div>
        <section class="province-dialog" role="dialog" aria-modal="true" aria-labelledby="province-title" aria-describedby="province-description">
            <button class="modal-close" type="button" data-close-province aria-label="Cerrar selector de provincia">×</button>
            <p class="section-kicker">Publicidad más útil</p>
            <h2 id="province-title">¿Desde qué provincia nos visitas?</h2>
            <p id="province-description"><?= e($description) ?></p>
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
    <?php
}

function section_page(array $config): void
{
    $title = $config['title'] ?? APP_NAME;
    $description = $config['description'] ?? '';
    $active = $config['active'] ?? '';
    $category = $config['category'] ?? 'GENERAL';
    $kicker = $config['kicker'] ?? '';
    $heading = $config['heading'] ?? '';
    $lead = $config['lead'] ?? '';
    $sectionId = $config['section_id'] ?? strtolower($category);
    $sectionClass = $config['section_class'] ?? 'content-section';
    $sectionKicker = $config['section_kicker'] ?? $kicker;
    $sectionTitle = $config['section_title'] ?? $heading;
    $sectionText = $config['section_text'] ?? $lead;
    $ranking = $config['ranking'] ?? $category;
    $backHref = $config['back_href'] ?? 'index.php#' . $sectionId;
    $modalDescription = $config['modal_description'] ?? 'Así te mostraremos primero contenido y anunciantes cercanos. Guardaremos únicamente la provincia en este dispositivo.';
    $subcategories = $config['subcategories'] ?? [];
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <?php page_head($title, $description); ?>
    <body>
        <?php page_header($active); ?>
        <main>
            <section class="page-intro" data-ad-category="<?= e($category) ?>">
                <p class="section-kicker"><?= e($kicker) ?></p>
                <h1><?= e($heading) ?></h1>
                <p><?= e($lead) ?></p>
            </section>
            <div class="page-shell">
                <div class="primary-content">
                    <aside class="ad-mobile-strip" aria-label="Publicidad local">
                        <div class="ad-sidebar-heading">
                            <div>
                                <span class="ad-eyebrow">Selección patrocinada</span>
                                <h2><span data-ad-category-label><?= e($kicker) ?></span> · <span data-ad-province>tu provincia</span></h2>
                            </div>
                            <button type="button" class="text-button" data-open-province>Cambiar provincia</button>
                        </div>
                        <div class="ad-slots" data-ad-slots></div>
                    </aside>
                    <section id="<?= e($sectionId) ?>" class="<?= e($sectionClass) ?>" data-ad-category="<?= e($category) ?>">
                        <div class="section-heading">
                            <div class="section-heading-content">
                                <p class="section-kicker"><?= e($sectionKicker) ?></p>
                                <h2><?= e($sectionTitle) ?></h2>
                                <p><?= e($sectionText) ?></p>
                            </div>
                            <a class="section-enter-link" href="<?= e($backHref) ?>">Ver en portada</a>
                        </div>
                        <?php if ($subcategories): ?>
                            <div class="section-subcategories" aria-label="<?= e($config['subcategories_label'] ?? 'Subcategorías') ?>">
                                <?php foreach ($subcategories as $subcategory): ?><a id="<?= e($subcategory['id']) ?>" href="<?= e($subcategory['href'] ?? '#' . $sectionId) ?>"><?= e($subcategory['label']) ?></a><?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <div class="editorial-grid section-ranking" data-ranking-section="<?= e($ranking) ?>"></div>
                    </section>
                </div>
                <aside class="ad-sidebar" aria-label="Publicidad local">
                    <div class="ad-sidebar-inner">
                        <div class="ad-sidebar-heading">
                            <div>
                                <span class="ad-eyebrow">Selección patrocinada</span>
                                <h2><span data-ad-category-label><?= e($kicker) ?></span> · <span data-ad-province>tu provincia</span></h2>
                            </div>
                            <button type="button" class="text-button" data-open-province>Cambiar</button>
                        </div>
                        <div class="ad-slots" data-ad-slots></div>
                        <p class="ad-disclosure">Espacios publicitarios seleccionados por sección y provincia.</p>
                    </div>
                </aside>
            </div>
        </main>
        <?php page_footer(); ?>
        <?php province_modal($modalDescription); ?>
    </body>
    </html>
    <?php
}
