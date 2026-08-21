<?php
// El banner ya trae su propio titular, texto e iconos, asi que este slide va a
// sangre: sin columna de copy al lado, que duplicaria el mensaje. Mientras la
// imagen no este subida se mantiene el slide anterior para no dejar la portada
// con una imagen rota.
$sliderBannerImage = 'assets/images/slider/tu-web-tu-arte-tu-legado.webp';
?>
<?php if (is_file(__DIR__ . '/../' . $sliderBannerImage)): ?>
<article class="story-slide story-slide-full is-active" data-story-slide aria-hidden="false">
    <img src="<?= e($sliderBannerImage) ?>" alt="Tu web, tu arte, tu legado. Paginas web profesionales para artistas, academias y penas flamencas." width="2000" height="790" loading="eager" fetchpriority="high">
</article>
<?php else: ?>
<article class="story-slide is-active" data-story-slide aria-hidden="false">
    <div class="story-slide-media">
        <img src="assets/images/slider/slider01.png" alt="Esquema de comunidad flamenca en Con Sabor Flamenco" width="960" height="720" loading="lazy">
    </div>
    <div class="story-slide-copy">
        <p class="story-slide-kicker">Comunidad flamenca</p>
        <h2>Un espacio para conectar el flamenco</h2>
        <p>Artistas, academias, tablaos, penas, eventos y servicios unidos en consaborflamenco.com.</p>
        <div class="story-slide-buttons">
            <a class="story-chip chip-red" href="artistas.php"><svg class="story-chip-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5a4 4 0 1 1 0 8 4 4 0 0 1 0-8ZM5 21c1.4-4 12.6-4 14 0"/></svg>Artistas</a>
            <a class="story-chip chip-blue" href="academias.php"><svg class="story-chip-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 10l8-5 8 5M6 10v8h12v-8M9 18v-5h6v5"/></svg>Academias</a>
            <a class="story-chip chip-gold" href="eventos.php"><svg class="story-chip-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M7 4v3M17 4v3M5 8h14M6 6h12a1 1 0 0 1 1 1v12H5V7a1 1 0 0 1 1-1ZM9 13h2M13 13h2M9 16h2"/></svg>Eventos</a>
            <a class="story-chip chip-dark" href="servicios.php"><svg class="story-chip-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3l2.4 5 5.6.8-4 3.9.9 5.5L12 15.6 7.1 18.2l.9-5.5-4-3.9 5.6-.8L12 3Z"/></svg>Servicios</a>
        </div>
    </div>
</article>
<?php endif; ?>
