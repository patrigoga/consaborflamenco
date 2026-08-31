# Red social flamenca - Fase 1

## Descripcion

Primera fase de la evolucion de Con Sabor Flamenco hacia una red social especializada en flamenco. Cubre cuatro bloques: **perfil del artista**, **eventos**, **agenda publica** y **sistema de puntos**.

La fase es una evolucion, no una reconstruccion. No se ha eliminado ninguna tabla, columna, ruta, funcion ni pantalla anterior.

## Principio estrategico

La arquitectura sirve a un ciclo comercial concreto:

```
PUBLICAR = GRATIS          ->  la agenda se llena de contenido
VISIBILIDAD EXTRA = PUNTOS ->  el contenido genera visitas
                           ->  las visitas hacen deseable la promocion
                           ->  la promocion genera ingresos
```

Por eso **crear y editar eventos no cuesta puntos en ningun caso**. Los puntos solo intervienen en promocionar un evento y en activar enlaces sociales adicionales.

## Modelo de datos

### Tablas nuevas

| Tabla | Para que |
|---|---|
| `municipios` | Municipios normalizados dentro de su provincia. Alta progresiva (find-or-create) |
| `eventos` | Eventos de la agenda, con soft delete y estado de promocion |
| `puntos_saldos` | Saldo materializado por usuario. `INT UNSIGNED`: el motor impide el negativo |
| `puntos_movimientos` | Libro mayor. Solo se anade; nunca se edita ni se borra |
| `miembro_redes` | Redes del miembro, con visibilidad y enlace activo por separado |
| `registro_actividad` | Auditoria de altas, ediciones, bajas, promociones y consumo de puntos |

### Tablas reutilizadas

- `provincias` — existia vacia; ahora se siembra con las 52 provincias.
- `disciplinas` + `miembro_disciplinas` — ya existian (migracion `20260718_disciplinas.sql`) con Baile, Cante, Toque y Percusion en relacion N:M. **No se creo ninguna tabla nueva para el tipo de artista.**
- `pagos_stripe` — la compra de puntos se apoya en la tabla de pagos que ya existia.
- `miembros` — la biografia, la foto principal, el nombre publico (nombre artistico) y la imagen de portada (`perfil_json.web_page.header_image_path`) ya existian y se reutilizan tal cual.

### Columnas anadidas

- `miembros.provincia_id`, `miembros.municipio_id` (ambas `NULL`).

Ninguna fila existente queda invalida y ningun codigo anterior deja de funcionar: el texto libre de `ciudad` y `provincia_texto` se sigue guardando igual.

### Campos preparados sin interfaz

En `eventos`: `precio_centimos`, `entradas_url`, `categoria`, `latitud`, `longitud`, `fecha_fin`, `vistas`. Estan en el esquema para venta de entradas, categorias y mapas, pero no tienen pantalla todavia.

## Integridad del sistema de puntos

Cuatro garantias, en capas:

1. **Los costes viven solo en el servidor.** `csf_puntos_costes()` en `app/points_repository.php` es la unica fuente. Un `coste=0` enviado por POST no tiene ningun efecto: el importe nunca se lee de la peticion.
2. **Toda operacion es transaccional.** `csf_puntos_registrar()` exige `inTransaction()` y bloquea la cartera con `SELECT ... FOR UPDATE`. Promocionar un evento descuenta y marca el evento en la misma transaccion: o pasan las dos cosas, o ninguna.
3. **El saldo negativo es imposible.** Se comprueba en PHP, la columna es `UNSIGNED`, y el bloqueo de fila serializa los gastos concurrentes.
4. **Idempotencia.** `clave_idempotencia` (UNIQUE) evita duplicar cargos por doble envio de formulario o por un webhook repetido.

El saldo materializado y el libro mayor siempre cuadran: `SUM(puntos_movimientos.puntos) == puntos_saldos.saldo`.

## Reglas de negocio

| Operacion | Coste | Constante |
|---|---|---|
| Crear evento | **Gratis** | — |
| Editar evento | **Gratis** | — |
| Mostrar una red social | **Gratis** | — |
| Promocionar un evento | 10 puntos | `csf_puntos_coste('promocion_evento')` |
| Primer enlace social clicable | **Gratis** | calculado en `csf_redes_coste_activacion()` |
| Enlaces sociales siguientes | 2 puntos | `csf_puntos_coste('enlace_social')` |

Puntos iniciales: **30** miembro gratuito, **100** miembro VIP. Valor: **1 punto = 0,50 EUR**.

Paquetes de compra: 10 / 20 / 30 / 50 / 100 puntos (5, 10, 15, 25 y 50 EUR).

Un enlace social activado **no se desactiva ni se reembolsa nunca**. Aunque el artista borre la URL, la fila se conserva con `activado_at` y `coste_puntos`.

## Que significa promocionar

La agenda es cronologica y esa promesa no se rompe: **un evento nunca adelanta a otro de fecha anterior por mucho que se pague**. Lo que compra la promocion es:

1. la etiqueta DESTACADO y el filete rojo en la tarjeta;
2. el primer puesto **dentro de su propio dia**;
3. sitio en la franja de destacados de la cabecera de la agenda.

Duracion: 30 dias (`CSF_PUNTOS_PROMOCION_DIAS`) o hasta que pase el evento, lo que ocurra antes. Destacar algo que ya paso no aporta nada.

## Ficheros

### Nuevos

```
app/geo_repository.php           csf_geo_*     Provincias y municipios
app/points_repository.php        csf_puntos_*  Cartera, costes y movimientos
app/events_repository.php        csf_evento_*  Eventos, agenda y promocion
app/social_links_repository.php  csf_redes_*   Redes y activacion de enlaces
app/activity_log.php             csf_log_*     Auditoria
app/events_ui.php                csf_evento_*  Tarjeta y rejillas de eventos
app/points_ui.php                csf_puntos_*  Saldo, paquetes y confirmaciones
agenda.php                                     Agenda publica  (/agenda)
evento.php                                     Ficha del evento (/evento/{slug})
assets/js/panel-eventos.js                     Modales y vista previa
tools/backfill_geo_miembros.php                Clasifica miembros existentes
database/20260831_fase1_red_social.sql         Migracion incremental
```

### Modificados

- `app/database.php` — bootstrap idempotente de las tablas nuevas y seed de provincias y disciplinas.
- `app/layout.php` — enlace «Agenda» en el menu y en el pie; carga de `panel-eventos.js` solo donde hace falta.
- `app/directory_helpers.php` — `csf_fetch_member_directory()` acepta filtros de territorio (parametro opcional).
- `app/admin_ui.php` + `panel-admin.php` — secciones Eventos y Puntos, solo lectura.
- `panel-usuario.php` — tarjetas y pantallas nuevas, cabecera resumen, provincia normalizada y disciplinas.
- `artista.php` — proximos eventos, eventos anteriores y redes sociales desde base de datos.
- `artistas.php` — filtros por provincia y municipio.
- `.htaccess` — rutas `/agenda` y `/evento/{slug}`.
- `assets/css/styles.css` — bloque `.csf-*`, mobile first.

## Que se ha ocultado y que no

Solo **dos tarjetas** de la portada del panel, y ninguna se ha borrado:

| Tarjeta | La sustituye | Estado |
|---|---|---|
| «Agenda» (`web-eventos`) | «Mis eventos» | Fuera de la portada; sigue en el hub de «Mi pagina web» marcada como *Version anterior* |
| «Redes sociales» (`web-redes`) | «Redes sociales» con puntos | Igual |

Sus pantallas, sus formularios y sus datos siguen intactos y accesibles por su ancla (`#web-eventos`, `#web-redes`). Para revertirlo basta con poner `$ocultarTarjetasSustituidas` a `false` en `panel-usuario.php`.

En el perfil publico (`artista.php`), la agenda antigua guardada en `perfil_json` **se sigue mostrando** para los miembros que aun no han publicado ningun evento en la tabla nueva. Solo se oculta cuando hay agenda nueva, para no ensenar dos agendas a la vez.

Nada mas se ha tocado: curriculum, microweb, tarjeta de miembro, banners, academias, alumnos, setters, legal, contacto, servicios, rankings y publicidad siguen exactamente igual.

## Puesta en marcha

```bash
# 1. Esquema (idempotente: se puede repetir sin riesgo)
php tools/run_migration.php database/20260831_fase1_red_social.sql

# 2. Clasificar los miembros que ya existen (simulacion primero)
php tools/backfill_geo_miembros.php
php tools/backfill_geo_miembros.php --aplicar
```

En local no hace falta el paso 1: `db_bootstrap()` crea las tablas al abrir cualquier pagina.

Los puntos de bienvenida se abonan solos la primera vez que cada miembro entra en su panel, mediante `csf_puntos_asegurar_alta()`, que es idempotente.

## Preparado para las fases siguientes

- **Stripe** — `csf_puntos_crear_intento_compra()` ya deja la fila en `pagos_stripe` en estado `PENDIENTE`, y `csf_puntos_acreditar_pago()` esta escrita y probada esperando al webhook. Conectar el pago no exigira tocar la logica de la cartera. Hoy **no se acredita ningun punto** al pulsar «Comprar».
- **Seguidores, me gusta, publicaciones, comentarios, mensajes, notificaciones, favoritos** — `registro_actividad` y el patron de repositorio con prefijo estan listos para colgar de ahi.
- **Venta de entradas y mapas** — columnas ya presentes en `eventos`.
- **Especialidades nuevas** — insertar una fila en `disciplinas`; la relacion es N:M y no hay limite por artista.
- **Otros tipos de miembro** (academias, penas, tablaos, festivales, fotografos, moda) — la infraestructura de eventos y puntos es por miembro, no por tipo de artista, asi que ya les sirve.

## Pendiente de decidir

- **Duracion de la promocion.** Fijada en 30 dias como valor de partida (`CSF_PUNTOS_PROMOCION_DIAS`), pendiente de validar con datos reales.
- **Costes en tabla.** Hoy son constantes en `csf_puntos_costes()`. Cuando el administrador necesite editarlos sin desplegar, se moveran a configuracion.
- **Subida a VIP.** Un miembro que pase de gratuito a VIP no recibe automaticamente los 100 puntos: seria una decision comercial con su propio movimiento de tipo `PROMOCIONAL`.

## Historial de cambios

- 2026-08-31: Fase 1 completa. Perfil de artista con provincia, municipio y disciplinas; eventos con agenda publica y pagina propia; sistema de puntos con libro mayor transaccional; redes sociales con enlace gratuito y activacion por puntos.
