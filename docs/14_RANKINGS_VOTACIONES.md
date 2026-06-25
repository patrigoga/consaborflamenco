# Rankings, Votaciones y Promociones

## Descripcion

Este documento define el sistema de posicionamiento de contenidos dentro de las categorias publicas de Con Sabor Flamenco.

## Estructura visual

Cada categoria mostrara exactamente tres posiciones destacadas:

1. Primer puesto en una tarjeta principal de mayor tamano.
2. Segundo puesto en una tarjeta secundaria.
3. Tercer puesto en una tarjeta secundaria.

Todas las tarjetas incluiran imagen principal, tipo, titulo, origen de la posicion y enlace al contenido concreto.

## Origen de las posiciones

Una posicion podra proceder de:

- Votacion de la comunidad.
- Promocion pagada durante un periodo contratado.

Las posiciones pagadas mostraran siempre la etiqueta Promocionado. Los resultados organicos mostraran la etiqueta Mas votado.

## Reglas comerciales iniciales

- Cada promocion pertenecera a una categoria y posicion concretas.
- Toda promocion tendra fecha de inicio y fin.
- No se mostrara una promocion vencida o inactiva.
- El panel administrativo evitara vender dos promociones incompatibles para la misma posicion y periodo.
- El sistema conservara metricas de impresiones, clics y rendimiento.

## Orden de las secciones finales

- Fotografia sera la penultima seccion de contenido.
- Flamenco sera la ultima seccion de contenido.
- Moda aparecera antes de Fotografia y tendra ranking propio.
- Cursos tendra ranking propio aunque su acceso se agrupe dentro de Academias.

## Implementacion actual

`assets/js/section-rankings.js` contiene datos demostrativos y renderiza la plantilla comun. En la version productiva estos datos se obtendran desde administracion o API.

## Pendiente de definir

- Formula exacta de votos y desempates.
- Precios por categoria, posicion y periodo.
- Limites de voto por usuario.
- Moderacion y deteccion de fraude.
- Renovacion y sustitucion de promociones.

## Historial de cambios

- 2026-06-21: Definida la estructura inicial de rankings, votos y promociones pagadas.
