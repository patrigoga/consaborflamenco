# Panel de administracion: rediseno de la Vista general

## Por que

La pantalla `panel-admin.php?section=general` pintaba 10 tarjetas de acceso, 4 listas de
novedades y 41 KPIs repartidos en 7 grupos: mas de cincuenta cifras a la vez, todas del mismo
tamano y del mismo peso visual, asi que ninguna destacaba. Tres problemas concretos:

1. **Sin jerarquia.** Nada distinguia "3 mensajes sin leer" (hay que hacer algo) de "Tokens de
   recuperacion usados" (dato de mantenimiento).
2. **Desfasada respecto al producto.** Los 7 grupos eran de la etapa revista + setters +
   banners. Despues de la Fase 1 (`docs/17_RED_SOCIAL_FASE1.md`) y de la mega agenda
   (`docs/18_MEGA_AGENDA.md`), la portada del admin no mencionaba ni un evento, ni una reserva,
   ni un punto, ni un articulo de tienda.
3. **Sin tendencia.** Todo eran acumulados historicos. Un total sin comparacion no permite
   decidir nada.

## Como queda

Cuatro franjas, de menos a mas detalle. Nada se ha borrado: todas las metricas anteriores
siguen accesibles, y las secciones siguen a un clic en la barra lateral.

### 1. Requiere tu atencion

Solo lo accionable, cada linea enlazando a su seccion ya filtrada por estado: mensajes nuevos,
reservas de tablao sin responder, miembros y academias pendientes, articulos en revision,
documentacion de setters, comisiones pendientes y banners sin pagar. **Las lineas a cero no se
pintan**, y si no queda ninguna la franja lo dice en una frase en vez de dejar un hueco.

### 2. Pulso de la agenda

El producto actual, con comparativa de los ultimos 30 dias frente a los 30 anteriores
(`admin_metric_trend()`, en `panel-admin.php`): eventos proximos y promocionados, reservas de
tablao, altas de miembros, VIP y destacados, articulos de tienda activos y puntos en
circulacion. La comparativa solo se pinta donde "mas" significa "mejor"; en las cifras donde
subir seria mala noticia (reservas pendientes) no hay tendencia.

### 3. Actividad reciente

Las 4 listas de "ultimos" de siempre, sin cambios, agrupadas ahora bajo su propio titulo.

### 4. Detalle completo

Los 41 KPIs anteriores, intactos, dentro de un `<details>` por grupo y **plegados por
defecto**. Se anade un grupo nuevo, "Agenda y mega agenda", y el grupo de banners lleva una nota
explicando que sus cifras no se mueven mientras Stripe no este conectado.

## Que se ha movido

- **"Ventas, leads y cobros"** (8 KPIs de `pagos_stripe`) ya no esta en la vista general: se
  pinta ahora dentro de **Finanzas > Comisiones**, con una nota que aclara que se queda a cero
  mientras la pasarela no este conectada.
- **Las 10 tarjetas de acceso** de la cabecera desaparecen como bloque propio: lo que era
  pendiente vive en la franja 1, lo que era volumen en la franja 2 y en el detalle, y la
  navegacion ya la da la barra lateral. Ninguna cifra se pierde.

## Metricas nuevas

`admin_dashboard_stats()` y `admin_dashboard_default_stats()` (`app/admin_repository.php`)
ganan 19 claves, todas via `admin_safe_count()`, asi que en una base sin migrar valen 0 en vez
de romper el panel:

```
events, events_upcoming, events_upcoming_7d, events_promoted, events_new_30d, events_prev_30d
members_destacado, members_new_30d, members_prev_30d
tablao_reservations, tablao_reservations_pending, tablao_reservations_new_30d, tablao_reservations_prev_30d
shop_products, shop_products_active, shops_with_catalog
points_in_circulation, points_spent, points_wallets
```

Las claves `*_prev_30d` cuentan la ventana de hace 60 a 30 dias, que es contra lo que compara
`admin_metric_trend()`.

## Ficheros

- `panel-admin.php` — helper `admin_metric_trend()`, arrays `$attentionItems`, `$pulseCards` y
  `$kpiVentasGroup`, render nuevo de la seccion `general` y grupo de ingresos en `comisiones`.
- `app/admin_repository.php` — las 19 metricas nuevas.
- `assets/css/styles.css` — clases nuevas al final del fichero (`admin-band-heading`,
  `admin-attention-*`, `admin-pulse-*`, `admin-trend-*`, `admin-kpi-details-*`). No se ha
  modificado ninguna clase existente.

## Cabecera duplicada (2026-09-15)

Cada pantalla del panel mostraba **dos cabeceras**: una franja `page-intro` con el nombre de la
seccion activa en `<h1>`, un subtitulo generico ("Panel operativo para gestionar comunidad,
contenido, publicidad, finanzas y contacto") y un boton fijo "Crear contenido" que llevaba
siempre a Articulos; y justo debajo, el `section-heading` propio de la seccion, que ya dice de
que va la pantalla. El `<h1>` de la franja lo reescribia `assets/js/admin-sidebar.js` al cambiar
de seccion, asi que su unica funcion era repetir el nombre de la seccion que ya titulaba la
seccion de abajo y que ya estaba marcada en la barra lateral.

Se ha quitado la franja entera. Consecuencias:

- El titulo de cada vista lo pone ahora solo su `section-heading`.
- El `<h1>` de la pagina pasa a ser "Panel Admin", el de la barra lateral (antes `<h2>`), para
  que la pagina siga teniendo exactamente un `<h1>`. El CSS acepta los dos elementos
  (`.admin-sidebar-header h1, .admin-sidebar-header h2`).
- El nombre de la seccion activa se aprovecha ahora en el titulo de la pestana del navegador
  ("Miembros | Panel de administracion | Con Sabor Flamenco").
- `assets/js/admin-sidebar.js` pierde el manejo de esa cabecera (`pageIntro`, `pageTitle`) y el
  parametro `title` de `showSection()`, que ya no tenia a quien escribir.
- El atributo `data-ad-category="GENERAL"` desaparece con la franja. No afecta a nada: el panel
  de administracion no tiene rail de publicidad.

## Barra lateral con grupos desplegables (2026-09-15)

Con las secciones nuevas de la mega agenda, la barra lateral pasaba de los 18 enlaces y no
cabia en pantalla: habia que hacer scroll dentro del menu para llegar a Finanzas o Contacto.
Cada grupo (`admin_sections()`) es ahora un `<details>` desplegable, con **solo uno abierto a la
vez**, y de salida se abre el que contiene la seccion activa.

- El acordeon lo resuelve el atributo `name` de `<details>`, nativo en los navegadores
  actuales. `assets/js/admin-sidebar.js` lleva un respaldo que cierra los hermanos a mano solo
  si el navegador no soporta ese atributo (`'name' in document.createElement('details')`).
- El `<span class="admin-sidebar-group-label">` pasa a `<summary>` con el mismo aspecto, mas
  zona de clic, chevron que gira al abrir, hover y `:focus-visible`. Los enlaces van dentro de
  un `div.admin-sidebar-group-items`.
- No cambia nada de la navegacion: los enlaces siguen siendo URLs reales
  (`panel-admin.php?section=...`) y el JS sigue cambiando de seccion sin recargar.

## Pendiente de decidir

- **Tildes en el panel de administracion.** `CLAUDE.md` pide tildes en la UI, pero todo el
  panel admin esta escrito sin ellas ("Articulos", "Ultimos miembros", "Al dia"). El texto
  nuevo sigue la convencion de la pantalla para no mezclar; una pasada de tildes a todo el
  panel seria un cambio aparte.
- **Barra lateral duplicada.** `panel-admin.php` conserva una segunda `<nav class="admin-sidebar-nav">`
  con enlaces fijos y emojis, oculta por CSS
  (`.admin-sidebar-nav-modern + .admin-sidebar-nav { display: none; }`) y desactualizada (no
  incluye Eventos, Puntos, Reservas ni Tienda). No se ha tocado; es candidata a limpieza.

## Historial de cambios

- 2026-09-15: Rediseno completo de la vista general en cuatro franjas, 19 metricas nuevas de
  agenda/reservas/tienda/puntos y grupo de ingresos movido a Finanzas.
- 2026-09-15: Eliminada la cabecera `page-intro` duplicada de todas las secciones del panel.
- 2026-09-15: Barra lateral con grupos desplegables, uno abierto a la vez.
