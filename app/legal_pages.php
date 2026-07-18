<?php
declare(strict_types=1);

require_once __DIR__ . '/legal_repository.php';
require_once __DIR__ . '/layout.php';

function render_legal_page(string $documentKey): void
{
    $definitions = legal_document_definitions();
    $definition = $definitions[$documentKey] ?? null;
    $document = legal_document_by_key($documentKey, true);
    $title = (string) ($document['title'] ?? $definition['title'] ?? 'Documento legal');
    $description = (string) ($definition['description'] ?? 'Documento legal de Con Sabor Flamenco.');
    $updatedAt = legal_format_date((string) ($document['visible_updated_at'] ?? $document['updated_at'] ?? ''));
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <?php page_head($title . ' | Con Sabor Flamenco', $description, false); ?>
    <body>
        <?php page_header(); ?>
        <main>
            <section class="page-intro" data-ad-category="GENERAL">
                <p class="section-kicker">Legal</p>
                <h1><?= e($title) ?></h1>
                <p>Última actualización: <?= e($updatedAt) ?></p>
            </section>
            <section class="legal-section content-section" data-ad-category="GENERAL">
                <?php if ($document): ?>
                    <div class="legal-document-body">
                        <?= (string) $document['content'] ?>
                    </div>
                    <div class="legal-page-actions">
                        <a class="button button-secondary" href="index.php#inicio">Volver al inicio</a>
                        <button class="button button-primary" type="button" onclick="window.print()">Imprimir</button>
                    </div>
                <?php else: ?>
                    <p class="empty-state">Este documento legal no está disponible actualmente. Vuelve pronto o contacta con hola@consaborflamenco.com.</p>
                    <a class="button button-secondary" href="index.php#inicio">Volver al inicio</a>
                <?php endif; ?>
            </section>
        </main>
        <?php page_footer(); ?>
        <?php province_modal('Así podremos mostrarte contenido y anunciantes relevantes también en páginas legales.'); ?>
    </body>
    </html>
    <?php
}
