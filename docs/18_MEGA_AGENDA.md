# Mega agenda flamenca

## Descripcion

Evolucion de la Fase 1 "red social" (`docs/17_RED_SOCIAL_FASE1.md`) hacia una agenda que
cubre a todos los tipos de miembro, no solo a los artistas: tablaos con reservas simples y
tiendas con catalogo, ademas de dar visibilidad real en la web publica a tablaos y peñas, que
hasta ahora solo tenian una pagina de ranking sin directorio.

Como en Fase 1, es una evolucion, no una reconstruccion: no se ha eliminado ninguna tabla,
columna, ruta, funcion ni pantalla anterior. El principio estrategico de esa fase se mantiene
sin cambios: publicar sigue siendo gratis; los puntos y el nivel VIP solo dan visibilidad y
capacidad extra.

## Bloques implementados

### Bloque 1 - Agenda visible en portada

`index.php` muestra ahora los proximos 6 eventos de la agenda (de cualquier tipo de miembro),
reutilizando `csf_evento_agenda()` y `csf_evento_grid()` (`app/events_ui.php`,
`app/events_repository.php`) sin ningun cambio de esquema. Sin filtro de provincia: la home es
nacional, igual que el resto de secciones destacadas.

### Bloque 2 - Directorios reales de tablaos y peñas

`tablaos.php` y `penas.php` eran paginas estaticas de solo ranking editorial. Ahora incluyen
ademas un directorio real y filtrable (provincia y municipio) de los miembros registrados de
ese tipo, con el mismo patron que `artistas.php`. Nada se ha borrado: la franja de ranking
sigue exactamente igual, el directorio se añade debajo.

Ficheros nuevos: `csf_render_member_type_directory()` en `app/directory_helpers.php`.
Modificados: `tablaos.php`, `penas.php`, y `section_page()` en `app/layout.php`, que ahora
admite una clave `after_ranking` opcional (HTML libre pintado despues de la franja de ranking).
Por defecto esta vacia, asi que las otras diez paginas que usan `section_page()` (moda,
festivales, cursos, flamenco, fotografia, historia, llaves de oro, palos, revista, eventos) no
cambian de comportamiento.

### Bloque 3 - Calendario de funciones y reservas simples de tablao

Una "funcion" de tablao no es una tabla nueva: es un `evento` normal cuyo `miembro_id` es un
tablao. El tablao decide evento a evento si admite reservas con la columna nueva
`eventos.acepta_reservas` (`BOOLEAN DEFAULT FALSE`, no afecta a ningun evento existente).

Solicitudes de reserva en la tabla nueva `tablao_reservas`: nombre, email, telefono, numero de
personas, mensaje y estado (`PENDIENTE`, `CONFIRMADA`, `RECHAZADA`, `CANCELADA`). Sin pago
online ni control de aforo en esta fase: el tablao confirma o rechaza a mano desde su panel.

- `app/tablao_repository.php` (prefijo `csf_tablao_*`): validacion, envio publico (mismo
  patron anti-spam que `site_public_contact_submit()`: honeypot, CSRF, limite de 3
  intentos/minuto por sesion) y bandeja de gestion con comprobacion de propiedad.
- Publico: formulario de reserva en `evento.php` cuando el evento es de un tablao, esta
  publicado, admite reservas y no ha pasado.
- Privado: casilla "Admitir reservas" en el formulario de evento de `panel-usuario.php`
  (solo visible si el miembro es de tipo `tablao`) y pantalla nueva "Reservas"
  (`#mis-reservas`) con bandeja de confirmar/rechazar.

### Bloque 4 - Catalogo de tienda

Escaparate sin carrito: cada producto es una ficha con foto, precio orientativo y un enlace o
contacto externo para comprar, no una compra dentro de la web. Tabla nueva `tienda_productos`
(baja logica con `deleted_at`, como el resto de la plataforma).

Limite de fichas **activas**: **5 en el plan gratuito, 20 en VIP**. Reutiliza el helper que ya
distinguia VIP/destacado para los limites de la microweb
(`member_tier_has_high_limits()`, `app/auth.php`) — no existe un sistema de niveles paralelo
para la tienda. Bajar de nivel nunca borra ni pausa productos existentes por encima del nuevo
limite; el limite solo se comprueba al crear uno nuevo o reactivar uno pausado.

- `app/tienda_repository.php` (prefijo `csf_tienda_*`).
- Privado: pantalla "Mi tienda" (`#mis-productos` + `#producto-form`) en `panel-usuario.php`,
  solo visible si el miembro es de tipo `tienda`, con alta/edicion/baja logica y aviso al
  llegar al limite.
- Publico: nueva pagina `tiendas.php` (directorio, mismo patron que tablaos/peñas), nuevo
  enlace "Tiendas" en el menu principal, y seccion "Catalogo" en la microweb (`artista.php`)
  para los miembros de tipo tienda.

## Tabla de capacidades por nivel de membresia

Amplia la tabla de `docs/17_RED_SOCIAL_FASE1.md` con las areas nuevas. El nivel es el mismo
para todo el miembro (`miembros.estado`, gestionado solo por administracion): no hay un nivel
distinto por tipo de miembro.

| Capacidad | Simpatizante | VIP | Destacado |
|---|---|---|---|
| Publicar eventos | Gratis, sin limite | Gratis, sin limite | Gratis, sin limite |
| Promocionar un evento (destacado en su dia) | 10 puntos | 10 puntos | 10 puntos |
| Reservas de tablao (crear, recibir, gestionar) | Si, sin limite | Si, sin limite | Si, sin limite |
| Artículos activos en la tienda | **5** | **20** | 20 |
| Limites de microweb (fotos/videos/curriculum) | 3 / 3 / 5 | 20 / 12 / 20 | 20 / 12 / 20 |
| Curriculum y microweb publica | No | No | Si |
| Descuentos con la tarjeta de miembro | No | Si | No |

Publicar eventos y gestionar reservas de tablao son gratis e ilimitados para cualquier nivel,
igual que en Fase 1: son lo que llena la agenda de contenido. El unico limite nuevo de esta
fase es el de articulos activos de tienda, y usa el mismo interruptor de nivel que ya existia
para la microweb en vez de crear uno nuevo.

## Pendiente de decidir

- **Limite de eventos simultaneos en el plan gratuito.** Hoy, como en Fase 1, crear eventos
  sigue siendo gratis y sin limite para todos los niveles. No se ha añadido ningun limite
  nuevo aqui: es una decision de producto pendiente de confirmar, no una limitacion tecnica.
- **Calendario visual de funciones de tablao.** Esta fase muestra las proximas funciones en
  orden cronologico (agenda y microweb); un widget de calendario mes a mes queda para una
  fase posterior si hace falta.
- **Aforo y pago online de reservas.** Fuera de alcance de este bloque a proposito (decision
  tomada con el usuario): sin control de plazas ni cobro, solo solicitud y confirmacion manual.

## Ficheros

### Nuevos

```
app/tablao_repository.php          csf_tablao_*  Reservas simples de tablao
app/tienda_repository.php          csf_tienda_*  Catalogo de tienda
tiendas.php                                      Directorio publico de tiendas (/tiendas)
database/20260912_tablao_reservas.sql            Migracion incremental (columna + tabla)
database/20260912_tienda_productos.sql           Migracion incremental (tabla)
```

### Modificados

- `index.php` — franja de proximos eventos en portada.
- `tablaos.php`, `penas.php` — directorio real ademas del ranking.
- `evento.php` — formulario publico de reserva cuando el evento lo admite.
- `panel-usuario.php` — casilla "Admitir reservas", pantalla "Reservas" y pantalla "Mi tienda".
- `artista.php` — seccion "Catalogo" para miembros de tipo tienda.
- `app/layout.php` — `section_page()` admite `after_ranking`; enlace "Tiendas" en el menu.
- `app/directory_helpers.php` — `csf_render_member_type_directory()`.
- `app/events_repository.php` — columna `acepta_reservas` en `csf_evento_guardar()`.
- `app/database.php`, `database/schema.sql` — columna y tablas nuevas de los bloques 3 y 4.
- `assets/js/advertising.js`, `assets/js/section-rankings.js` — categoria `TIENDAS`.

## Historial de cambios

- 2026-09-12: Bloques 1 a 4 completos. Agenda en portada, directorios de tablaos y peñas,
  reservas simples de tablao y catalogo de tienda con limite por nivel de membresia.
