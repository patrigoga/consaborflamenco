# CLAUDE.md

Guía técnica para trabajar en este repositorio. Complementa a [AGENTS.md](AGENTS.md), que define el **producto** (áreas, fases, reglas de negocio). Este documento describe el **código**.

## Qué es esto

Plataforma web "Con Sabor Flamenco": revista + comunidad + área de miembros + panel de administración + sistema comercial de appointment setters.

**Stack real:** PHP 8.1+ plano (sin framework, sin Composer, sin autoloader) + MySQL/MariaDB vía PDO + CSS y JS vanilla. Se sirve desde Apache con `mod_rewrite`. Producción: Hostinger. Local: XAMPP.

Nota: `docs/10_DECISIONES_TECNICAS.md` todavía lista "lenguaje / framework / motor de BD" como *pendiente de definir*. Eso está desactualizado: el stack de facto es el de arriba.

Hay un subproyecto aparte, [artist-microsite/](artist-microsite/): Next.js 13 + Prisma + Tailwind + TypeScript, con su propio `package.json`, `Dockerfile` y base de datos. **No comparte código con el PHP** — solo se sincroniza mediante llamadas HTTP desde `db_sync_member_to_artist_microsite()` en [app/auth.php](app/auth.php).

## Arquitectura

### Convención central: "una página = un `.php` en la raíz"

No hay router ni front controller. Cada URL pública es un fichero en la raíz. El patrón de cabecera de cada página es siempre:

```php
<?php
declare(strict_types=1);

require_once __DIR__ . '/app/auth.php';                     // arrastra config.php + database.php
require_once __DIR__ . '/app/site_content_repository.php';  // si necesita contenido administrable
require_once __DIR__ . '/app/layout.php';                   // page_header() / page_footer()
```

`app/` no contiene clases: son **ficheros de funciones globales** cargados con `require_once`. Prefijos por módulo:

| Fichero | Prefijo | Responsabilidad |
|---|---|---|
| [app/env.php](app/env.php) | `csf_env*` | Carga `.env` y `.env.local`, detecta entorno |
| [app/config.php](app/config.php) | `csf_*`, `app_url`, `e` | Constantes, sesión, rutas de uploads, helpers de media |
| [app/database.php](app/database.php) | `db*` | Singleton PDO + bootstrap idempotente del esquema |
| [app/auth.php](app/auth.php) | `db_*`, verbos sueltos | Usuarios, sesión, CSRF, subidas, email, migración JSON→SQL |
| [app/layout.php](app/layout.php) | `page_*`, `section_page` | Header, nav, footer, modales, plantilla de sección |
| [app/admin_repository.php](app/admin_repository.php) | `admin_*` | Consultas del panel admin |
| [app/admin_ui.php](app/admin_ui.php) | `admin_*` | Secciones, badges, navegación del admin |
| [app/site_content_repository.php](app/site_content_repository.php) | `site_*` | Servicios, contacto, mensajes del formulario |
| [app/legal_repository.php](app/legal_repository.php) | `legal_*` | Documentos legales versionados |
| [app/directory_helpers.php](app/directory_helpers.php) | `csf_*` | Directorios de miembros y filtros por disciplina |
| [app/qr.php](app/qr.php) | `csf_qr_*` | Generador QR en SVG puro, sin dependencias |

**Al añadir funciones nuevas, respeta el prefijo del módulo.** Son funciones globales: una colisión de nombres es un fatal error.

### Los dos paneles son monolitos

[panel-usuario.php](panel-usuario.php) (~146 KB) y [panel-admin.php](panel-admin.php) (~73 KB) concentran casi toda la lógica de área privada. Navegan por query string:

```
panel-admin.php?section=miembros
panel-usuario.php?section=perfil
```

Las secciones del admin se declaran en `admin_sections()` en [app/admin_ui.php](app/admin_ui.php). Añadir una sección = registrarla ahí + añadir su bloque de render en `panel-admin.php`.

No trocees estos ficheros salvo que se pida explícitamente: `AGENTS.md` prohíbe reestructuraciones no justificadas.

### Estado de datos: híbrido JSON + MySQL

El proyecto está **a mitad de migración**. Convive:

- Almacenamiento legacy en ficheros JSON bajo `storage/` (`users.json`, `password_resets.json`, `email_verifications.json`)
- Tablas MySQL, cuyo esquema se crea de forma **idempotente en código** en `db_bootstrap()` ([app/database.php](app/database.php)), no solo desde `database/schema.sql`

Consecuencias prácticas:

- `db()` devuelve `?PDO` — **puede ser `null`**. Todo el código de lectura debe degradar con elegancia (mira `admin_safe_count()` / `admin_safe_fetch_all()` como patrón).
- Si añades una columna o tabla, hay que tocar **tres sitios**: `db_bootstrap()`, `database/schema.sql`, y un fichero nuevo en `database/` o `database/migrations/`.
- Usa los helpers de evolución de esquema ya existentes: `db_column_exists()`, `db_add_column_if_missing()`, `db_index_exists()`, `db_add_unique_index_if_missing()`.
- En local `db()` hace `CREATE DATABASE IF NOT EXISTS`; en producción **no** — solo conecta.

Tablas y columnas están **en español** (`usuarios`, `miembros`, `tipos_miembro`, `provincias`, `rol`, `estado`, `activo`). Las tablas más nuevas están en inglés (`services`, `contact_settings`, `contact_messages`, `legal_documents`). Sigue la convención de la tabla que estés tocando, no impongas una global.

### Uploads: fuera del árbol de código

Las imágenes de runtime **no** se guardan en `assets/uploads/` (eso es legacy que sigue versionado). Van a `RUNTIME_UPLOADS_DIR`, por defecto `../../csf-uploads` respecto a `app/`, configurable con `CSF_UPLOADS_DIR`.

Se sirven **siempre** a través de `media.php`, nunca por URL directa:

```php
csf_media_url('member-photos/foo.png')  // → media.php?file=member-photos/foo.png
```

`csf_normalize_media_file()` es el guardián: solo acepta `member-photos/` o `curriculum-images/` con extensión `jpg|jpeg|png|webp` y bloquea `..`. **No lo puentees.**

### URLs limpias

Solo hay una regla de rewrite, en [.htaccess](.htaccess): `/artista/{slug}` → `artista.php?slug={slug}`, con 301 desde la forma antigua. Las páginas de artista se renderizan **sin header ni footer globales** (funcionan como microsite). El resto de páginas son `.php` directos.

## Convenciones obligatorias

- `declare(strict_types=1);` en la primera línea de todo PHP nuevo. Tipos declarados en firmas y retornos.
- **Escapa toda salida** con `e()`: `<?= e($valor) ?>`. Es `htmlspecialchars` con `ENT_QUOTES` y UTF-8.
- **CSRF en todo POST**: `csrf_token()` para emitir, `verify_csrf($_POST['csrf_token'] ?? null)` para validar. Sin excepciones.
- **Consultas preparadas siempre.** Nada de interpolar en SQL. Cuando hay que interpolar un identificador (nombre de tabla), se escapa con `str_replace('`', '``', $x)` — mira `db()`.
- HTML de usuario: `sanitize_html()` ([app/auth.php](app/auth.php)) o los `*_sanitize_html()` por módulo. Nunca imprimas HTML crudo de entrada.
- Contraseñas: `password_hash` / `password_verify`. La recuperación devuelve **siempre** respuesta genérica, no revela si el email existe.
- Cache-busting de assets: `?v=<?= filemtime(...) ?>`, como en `page_head()`.
- Idioma: código, comentarios y textos de UI **en español**. Los docs de `docs/` van sin tildes por convención existente; el código y la UI sí las llevan.

## Comandos

No hay build, ni tests, ni linter en el proyecto PHP.

```powershell
# Servidor local rápido (o usar XAMPP apuntando al repo)
php -S localhost:8000

# Comprobar sintaxis de un fichero
php -l panel-admin.php

# Ejecutar una migración SQL
php tools/run_migration.php database/migrations/001_add_slug_to_miembros.sql

# Rellenar slugs de miembros existentes
php tools/populate_slugs.php
```

Microsite de artistas:

```powershell
cd artist-microsite
npm install
npm run dev
npm run prisma:generate
npm run seed
```

## Entorno

Copia [.env.example](.env.example) a `.env` (ignorado por Git). `.env.local` se carga después y **sobrescribe** a `.env`.

- Local (`CSF_APP_ENV=local`): MySQL de XAMPP en `127.0.0.1`, base `consaborflamenco`, usuario `root`.
- Producción (`CSF_APP_ENV=production`): base Hostinger `u311361615_csf`, usuario `u311361615_admin`, contraseña en `.env`.
- Si no se define `CSF_APP_ENV`, `csf_detect_environment()` lo deduce del `HTTP_HOST` (localhost → `local`, resto → `production`).

`setup-prod-db.php` es un instalador temporal, bloqueado tras la existencia de `storage/ALLOW_PROD_SETUP`. **Nunca lo dejes accesible sin ese candado.**

## Reglas de trabajo heredadas de AGENTS.md

Vinculantes en este repo:

1. Revisar la estructura y la documentación **antes** de modificar.
2. No eliminar ficheros existentes salvo instrucción expresa.
3. No renombrar carpetas, rutas ni ficheros sin justificación.
4. No romper funcionalidad existente. Cambios pequeños y revisables.
5. Al terminar una fase o cambio relevante: actualizar el documento correspondiente en [docs/](docs/) **y** añadir la entrada en [docs/12_HISTORIAL_CAMBIOS.md](docs/12_HISTORIAL_CAMBIOS.md).
6. Mantener separadas las capas pública / administración / miembros / setters.

## Estilo visual

Paleta: rojo suave, azul suave, negro, blanco cálido. Tipografías: Playfair Display (títulos) e Inter (texto), desde Google Fonts.

Todo el CSS vive en un único [assets/css/styles.css](assets/css/styles.css) (~196 KB). No hay preprocesador ni framework CSS en la parte PHP. Antes de crear una clase nueva, busca si ya existe: hay convenciones consolidadas (`section-kicker`, `content-section`, `button button-primary`, `admin-thumb`, `service-public-card`…).

Menú horizontal en escritorio, hamburguesa accesible en tablet/móvil. Las vistas públicas comparten estructura de contenido principal + sidebar publicitario derecho (rail de 560 px que pasa a horizontal cuando no cabe).

## Zonas delicadas

- **`app/auth.php` (~62 KB)** es el fichero más cargado del proyecto: autenticación, perfiles, subidas, SMTP, sincronización con el microsite y migración JSON→SQL. Lee el contexto completo de la función antes de tocarla.
- **Doble fuente de verdad JSON/SQL** en usuarios. Al cambiar la forma de un usuario hay que revisar `default_member_profile()`, `db_user_from_row()`, `db_insert_legacy_user()` y `db_upsert_member_for_user()` a la vez.
- **`db_bootstrap()` corre en cada conexión.** Todo lo que añadas ahí debe ser idempotente y barato.
- Sesiones PHP se guardan en `storage/sessions`, protegido por `.htaccess`. `storage/` está fuera de Git salvo esos guardas.
