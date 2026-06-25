# Publicidad y Banners

## Descripcion

Este documento define la base funcional y comercial del sistema de publicidad de Con Sabor Flamenco. Los banners seran una de las fuentes principales de ingresos y se adaptaran a la provincia del visitante y a la seccion publica consultada.

## Objetivo del documento

Establecer una regla comun para mostrar, administrar y medir publicidad relevante sin bloquear el crecimiento hacia base de datos, panel administrativo y API REST.

## Experiencia del visitante

- En la primera visita se solicita la provincia mediante un popup.
- La seleccion se guarda en el dispositivo y puede cambiarse desde la cabecera o el sidebar.
- Si el usuario inicia sesion, la provincia de su perfil sustituira la preferencia local.
- Si no se conoce la provincia, se muestra publicidad nacional.
- El sidebar permanece a la derecha en escritorio y se adapta a formatos apilados en pantallas pequenas.

No se utiliza geolocalizacion precisa ni deteccion automatica por IP en esta fase.

## Categorias publicitarias

Las categorias se corresponden con el navbar publico:

- Inicio.
- Revista.
- Fotografia.
- Moda.
- Artistas.
- Academias.
- Cursos.
- Eventos.
- Penas.
- Tablaos.
- Festivales.
- Flamenco.
- Concursos.

Servicios y Contacto no son categorias publicitarias. En esas vistas se podran mostrar campanas generales o nacionales.

## Prioridad de seleccion

El futuro servicio de anuncios resolvera el inventario en este orden:

1. Campana activa de la provincia y categoria exactas.
2. Campana activa de la provincia con categoria general.
3. Campana nacional de la categoria exacta.
4. Campana nacional general.
5. Autopromocion o espacio comercial disponible.

Antes de mostrar una campana se comprobaran estado, fechas, formato, ubicacion, prioridad, frecuencia y disponibilidad contratada.

## Formatos iniciales

- Premium: banner principal de mayor altura y prioridad.
- Estandar: banner secundario para rotacion local o tematica.
- Compacto: formato de apoyo, nacional o de autopromocion.

Todos los formatos deben mostrar la etiqueta Publicidad y disponer de texto alternativo cuando utilicen imagenes.

## Administracion futura

El panel permitira gestionar anunciantes, campanas, creatividades, provincias, categorias, fechas, enlaces, posiciones, prioridades y estados. Tambien mostrara impresiones, clics, tasa de clic y ocupacion del inventario.

## Banners contratados por miembros

Los miembros podran contratar banners desde `panel-usuario.php`.

Datos previstos por banner:

- Miembro propietario.
- Titulo del banner.
- URL de destino.
- Imagen del banner.
- Fecha de inicio y fin de publicacion.
- Fecha de inicio y fin de contratacion.
- Pago asociado de Stripe.
- Estado: borrador, pendiente de pago, pagado, activo, inactivo, caducado o rechazado.

Reglas de visibilidad:

- El banner no se muestra si no tiene pago confirmado.
- El banner no se muestra si esta fuera de las fechas contratadas.
- El banner no se muestra si esta fuera de las fechas de publicacion.
- El banner no se muestra si el estado no es activo.
- Al caducar el periodo contratado, el sistema debe pasarlo a inactivo o caducado.

## Implementacion actual

La version actual es un prototipo funcional en navegador:

- `index.php` contiene el layout publico, el sidebar y el popup.
- `assets/js/advertising.js` gestiona provincia, categoria activa e inventario de demostracion.
- `assets/css/styles.css` define los formatos y su comportamiento responsive.
- `window.CSFAdvertising.setProvince(province)` permite que el futuro login aplique la provincia del perfil.

El inventario de demostracion debera sustituirse por campanas obtenidas desde administracion o API antes de vender espacios reales.

## Privacidad y medicion

- Guardar solo la provincia necesaria para la personalizacion.
- Explicar al visitante el uso de la provincia y permitir cambiarla.
- Definir el consentimiento necesario antes de activar analitica publicitaria no esencial.
- Evitar almacenar direcciones exactas o datos personales en impresiones y clics.
- Aplicar controles contra trafico automatizado y duplicacion de metricas.

## Pendiente de definir

- Tarifas por formato, categoria, provincia y periodo.
- Numero maximo de anunciantes por ubicacion.
- Rotacion, limites de frecuencia y exclusividad comercial.
- Flujo de contratacion, facturacion y renovacion.
- Integracion definitiva con Stripe Checkout y webhooks para activar banners.
- Requisitos definitivos de consentimiento y politica de privacidad.
- Criterios de medicion y deteccion de fraude.

## Historial de cambios

- 2026-06-21: Creado el documento inicial del sistema de publicidad y banners.
- 2026-06-23: Anadida la regla de banners contratables por miembros, pago Stripe y visibilidad por fechas.
