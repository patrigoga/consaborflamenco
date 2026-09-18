# Fase 1 de lanzamiento: la agenda y la economia de puntos

Documento de decisiones para el lanzamiento publico de consaborflamenco.com. Recoge que puede
hacer cada tipo de miembro, como funcionan los puntos y como se presenta todo en la web.
Complementa a `17_RED_SOCIAL_FASE1.md` (eventos y puntos) y `18_MEGA_AGENDA.md` (directorios,
reservas y tienda), y **cambia** algunas reglas de esos dos: donde haya discrepancia, manda
este.

## 1. Alcance: que puede publicar cada quien

Fase 1 se limita a publicar contenido en la agenda. Nada de venta de entradas, ni pagos online,
ni mensajeria interna.

| Tipo de miembro | Publica | No publica todavia |
|---|---|---|
| Artista | Eventos de agenda | — |
| Peña | Eventos de agenda | — |
| Tablao | Eventos de agenda (con reservas opcionales, ya implementadas) | — |
| Academia | Eventos de agenda **y** cursos | — |
| Tienda | Productos de escaparate | Eventos |
| Festival | Eventos de agenda | — |

Todos conservan lo que ya tienen: ficha publica, directorio por provincia, perfil y, en el caso
de los tablaos, la bandeja de reservas.

### Campos de un evento

Imagen, titulo, descripcion, **fecha o fechas**, hora, lugar, precio y un campo libre
**"Mas detalles"** donde el miembro escribe lo que quiera (horario de puertas, condiciones,
aforo, lo que sea). Ese campo es el que rellena la ficha ampliada del modal.

### Campos de un curso

Imagen, titulo, descripcion, fecha, **palo** (obligatorio), profesor y nivel. El palo **no es un
desplegable**: es un campo de texto libre con sugerencias mientras se escribe (alegrias,
bulerias, solea, seguiriya, tangos, fandangos...). Se guardan dos valores: lo que escribio la
academia (`palo`) y una version normalizada sin tildes ni mayusculas (`palo_normalizado`), que
es la que agrupa y filtra. Asi el buscador junta "Bulerias", "bulerías" y "Bulerias de Jerez"
bajo el mismo palo sin obligar a nadie a elegir de una lista cerrada.

### Campos de un producto de tienda

Los que ya existen en `tienda_productos`: imagen, titulo, descripcion, precio orientativo y
enlace o contacto externo. Sin carrito ni pago en la web.

## 2. Economia de puntos

### Asignacion mensual

**Reinicio mensual**: cada mes el saldo vuelve a ser la asignacion del nivel. Lo que no se gasta
se pierde. Es la regla mas facil de explicar ("cada mes tienes tus puntos otra vez") y la mas
barata de programar.

| Tipo de miembro | Gratuito | VIP |
|---|---|---|
| Artista, peña, tablao, festival | 30 de bienvenida, luego **20/mes** | 100 de bienvenida, luego **80/mes** |
| Academia | **60/mes**, bolsa unica para agenda y cursos | **120/mes** |
| Tienda | **60/mes** (+ tope de 5 fichas activas) | **120/mes** (+ tope de 20 fichas activas) |

La bolsa de la academia es **unica**: 60 puntos que reparte como quiera entre eventos y cursos.
La idea inicial de reservar 30 para cada cosa se descarto porque obliga a explicar en pantalla
por que hay puntos que no se pueden usar donde el usuario quiere.

La tienda mantiene **las dos cosas**: paga 10 puntos por publicar y ademas tiene un tope de
fichas visibles a la vez. Sin el tope, en un ano se acumularian setenta productos publicados;
sin los puntos, publicar no costaria nada. El tope ya esta implementado
(`csf_tienda_limite_productos()`).

### Tarifa

| Operacion | Coste |
|---|---|
| Publicar un evento (con todas sus fechas) | 10 puntos |
| Publicar un curso | 10 puntos |
| Publicar un producto de tienda | 10 puntos |
| Editar cualquiera de los tres | **gratis, siempre** |
| Destacar un evento en portada (30 dias) | 10 puntos adicionales |

Editar tiene que ser gratis sin excepciones: si corregir una errata cuesta puntos, la gente deja
las erratas puestas y la agenda empeora. Borrar no devuelve puntos.

### Eventos con varias fechas

Un evento puede llevar **varias fechas** (o repetirse: todos los viernes) y cuesta **una sola
publicacion**. Es la pieza que hace viable la agenda: un tablao que programa cada semana no
puede gastar 10 puntos por funcion. Tope de fechas por evento para que nadie publique un ano
entero por 10 puntos: **12 fechas en gratuito, 30 en VIP**.

### Caducidad y contenido publicado

Que los puntos caduquen **no afecta a lo ya publicado**. Un evento publicado sigue publicado
aunque el saldo del mes siguiente se reinicie; los puntos pagan el acto de publicar, no el
alojamiento.

### Como se implementa el reinicio sin cron

No hace falta tarea programada. En `puntos_saldos` se anaden dos columnas: `periodo_actual`
(`YYYY-MM`) y `asignacion_mensual`. En la primera peticion del miembro en un mes nuevo,
`csf_puntos_saldo()` ve que `periodo_actual` no es el mes de hoy, reinicia el saldo a la
asignacion que le toca por nivel y deja el movimiento correspondiente en `puntos_movimientos`
(tipo `ASIGNACION_MENSUAL`). Es idempotente, no depende del cron de Hostinger y el libro mayor
sigue cuadrando. Un miembro que no entre en tres meses no acumula tres asignaciones: al volver
recibe la del mes en curso, que es justo lo que dice la regla.

## 3. El VIP y el precio del punto: hay que decidir una cosa

**VIP: 60 € al ano.** Pero hoy el codigo valora el punto a **0,50 €**
(`CSF_PUNTOS_VALOR_CENTIMOS`) y vende paquetes a ese precio (10 puntos = 5 €).

Las cuentas no cuadran: un VIP recibe 100 + 80 x 12 = **1.060 puntos al ano**, que a 0,50 € son
**530 €** de valor nominal, por los que paga 60 €. Nadie compraria nunca un paquete de puntos
pudiendo hacerse VIP: sale casi nueve veces mas barato. Hay que elegir una de estas tres:

1. **No vender puntos en Fase 1** (recomendado). El unico producto de pago es el VIP. Se ocultan
   los paquetes y el punto deja de tener precio publico: es solo la unidad de racionamiento de
   la agenda. Ventaja adicional: **no hace falta pasarela de pago para lanzar**, porque el nivel
   VIP ya lo asigna administracion a mano; el cobro de los 60 € se hace por transferencia o
   Bizum fuera de la web hasta que Stripe este conectado.
2. Bajar el precio del punto a la tarifa que implica el VIP (unos 0,06 €) y vender solo paquetes
   grandes (500 puntos = 30 €, por ejemplo).
3. Subir el precio del VIP, que es lo que no quieres.

La opcion 1 es ademas la unica que permite lanzar ya: Stripe no esta conectado.

## 4. La vista principal

Maqueta navegable de esta propuesta: se entrega aparte como artefacto, con datos de ejemplo.

La portada actual muestra una rejilla de tarjetas iguales. Funciona, pero no se lee como una
agenda: para saber que hay el viernes hay que ir mirando tarjeta por tarjeta. La propuesta
reordena la portada en cinco piezas:

1. **Portada con buscador.** Titular, y debajo tres campos: que buscas, donde (provincia) y
   cuando (hoy / fin de semana / este mes). El buscador es el producto, no la decoracion.
2. **Dos banners de cabecera**, uno al lado del otro, del mismo ancho que el contenido. En movil
   se apilan. Van marcados como publicidad.
3. **Destacados de la semana.** Tres tarjetas grandes con los eventos promocionados, que son los
   que han pagado los 10 puntos extra. Es donde se ve el valor de destacar.
4. **Agenda dia a dia.** Lo importante. Cada dia es un bloque con su fecha y el numero de
   eventos; dentro, una fila por evento con miniatura, hora, titulo, tipo, organizador, ciudad y
   precio. Se ven diez eventos de un vistazo en vez de tres. Encima, una fila de filtros
   (Todo, Hoy, Fin de semana, Tablaos, Peñas, Artistas, Academias, Festivales) que queda pegada
   arriba al bajar.
5. **Cursos y escaparate.** Una franja de cursos (con palo, profesor y nivel visibles, que es lo
   que se compara) y otra de productos de tienda.

### La ficha del evento en modal

Al pulsar cualquier evento se abre un modal, sin salir de la agenda: cartel grande, tipo,
titulo, los cinco datos (fecha, hora, lugar, ciudad, precio) en rejilla, la descripcion, el
bloque **"Mas detalles"** con el texto libre del organizador, y al pie quien organiza con enlace
a su ficha publica. El boton principal cambia segun el tipo: en un tablao que admite reservas
dice "Solicitar reserva"; en el resto, "Como llegar".

Se implementa con `<dialog>` nativo: trae gratis el cierre con Escape, el foco atrapado dentro y
el fondo oscurecido.

### Los tres tamanos

- **Escritorio**: destacados a tres columnas; filas de agenda con miniatura, hora, datos y precio
  en una sola linea; modal a dos columnas (cartel a la izquierda, datos a la derecha).
- **Tablet**: destacados a dos columnas, el resto igual.
- **Movil**: destacados a una columna; la fila de agenda se reordena en dos lineas con la hora
  junto al organizador; los filtros se desplazan en horizontal; **el modal sube desde abajo como
  una hoja**, con su propio desplazamiento interno.

## 5. Cambios de base de datos que pide esta fase

Todos aditivos. Como siempre, en los tres sitios: `db_bootstrap()`, `database/schema.sql` y una
migracion nueva en `database/`.

| Tabla | Cambio |
|---|---|
| `eventos` | `mas_detalles TEXT NULL` y `precio_texto VARCHAR(60) NULL` ("25 €", "Gratis", "Desde 15 €") |
| `evento_fechas` (nueva) | `evento_id`, `fecha`, `hora`, para los eventos de varias fechas |
| `academia_cursos` | `palo VARCHAR(80) NOT NULL`, `palo_normalizado VARCHAR(80)`, `profesor_nombre VARCHAR(160) NULL` |
| `puntos_saldos` | `periodo_actual CHAR(7)` y `asignacion_mensual SMALLINT UNSIGNED` |

`academia_cursos` ya existe desde la fase de academias con nombre, descripcion, fecha de inicio,
precio, imagen, nivel y estado: solo le faltan el palo y el profesor. No hay que crear una tabla
de cursos nueva.

La tabla `puntos_movimientos` gana un tipo de movimiento, `ASIGNACION_MENSUAL`, y los conceptos
`publicacion_evento`, `publicacion_curso` y `publicacion_producto` en `csf_puntos_costes()`.

## 6. Orden de construccion propuesto

1. **Puntos**: reinicio mensual, asignacion por tipo y nivel, y cobro de 10 puntos al publicar.
   Es la pieza de la que dependen las demas.
2. **Eventos**: campo "Mas detalles", precio y fechas multiples.
3. **Cursos**: palo con sugerencias, profesor y nivel en el panel de la academia.
4. **Portada**: buscador, banners, destacados, agenda por dias y modal.
5. **Tienda**: cobro por publicacion sobre el limite que ya existe.
6. **Panel del miembro**: que cada uno vea su saldo, en que lo ha gastado y cuando se renueva.

## 7. Pendiente de decidir

- **Precio del punto** (apartado 3). Es la unica decision que bloquea la parte economica.
- **Que pasa al bajar de VIP a gratuito** a mitad de mes: lo publicado se queda, pero el saldo
  del mes siguiente ya es el de gratuito. Confirmar que es lo que se quiere.
- **Aviso de renovacion**: si se manda un correo el dia 1 ("tienes tus puntos del mes") o no se
  manda nada. Empuja a publicar, pero hay que tener el envio a punto.

## Historial de cambios

- 2026-09-18: Documento inicial de la Fase 1 de lanzamiento: alcance por tipo de miembro,
  economia de puntos con reinicio mensual, eventos de varias fechas, cursos con palo libre
  sugerido, cambios de esquema y propuesta de portada con agenda por dias y ficha en modal.
