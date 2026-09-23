# Panel del miembro: cabecera unica

## Por que

El panel abria con la misma informacion tres veces antes de dejar hacer nada:

- Cabecera oscura: tipo de miembro, nombre, ciudad, estado, numero y "Perfil 100%" con barra.
- Franja blanca, justo debajo: "Hola, <nombre>", tipo y ciudad otra vez, y "100% perfil
  completado" con otra barra.
- Primera tarjeta: "PERFIL COMPLETO 100%".

Nombre, tipo y ciudad dos veces; el porcentaje tres, dos de ellas con barra de progreso. Una
barra al 100% ademas no informa de nada. Los proximos eventos tambien salian dos veces (franja
y tarjeta "Mis eventos").

Dos problemas mas del mismo bloque:

- El sitio de honor, arriba a la derecha, lo ocupaba el QR de la tarjeta de miembro, cuando lo
  que casi siempre viene a hacer un artista es publicar un evento.
- El titular decia "Gestiona tu perfil, curriculum, pagina web y servicios desde un unico
  lugar", pero el curriculum y la pagina web son exclusivos del artista destacado: al
  simpatizante se le nombraba lo que no puede abrir. Ademas estaba sin tildes ("¿Que quieres
  hacer hoy?") en el texto mas grande de la pantalla.

## Como queda

**Una sola cabecera** con la identidad a la izquierda (foto, tipo, nombre, ciudad, estado y
numero de miembro) y las acciones a la derecha: **"Crear evento"** como boton principal y el QR
de la tarjeta reducido a acceso secundario.

**Una sola franja de cifras**, y solo con datos que cambian:

| Cifra | De donde sale |
|---|---|
| Puntos disponibles | `csf_puntos_resumen()` |
| Proximos eventos | `csf_evento_contar_proximos()` |
| **Visitas a tus eventos** | suma de `vistas` de los eventos del miembro |

Las visitas son el dato nuevo: estaban guardadas en `eventos.vistas` desde la Fase 1 pero el
miembro no las veia en ninguna pantalla. Es lo que le dice si publicar le sirve de algo, y por
tanto lo que hace que vuelva a publicar al mes siguiente. No hace falta consulta nueva:
`csf_evento_select_sql()` devuelve `e.*`, asi que las filas ya cargadas traen el contador y solo
hay que sumarlas en PHP.

**El porcentaje de perfil desaparece cuando esta al 100%.** Si falta algo, y solo entonces,
aparece en la franja como aviso con enlace ("Tu perfil esta al 60%. Completalo para que te
encuentren mejor en los directorios"), que es accionable, en vez de una barra que solo decora.

**El texto del titular** lleva ya sus tildes y cambia segun el nivel: al destacado le habla de
su curriculum y su pagina web; al resto, de sus eventos, su perfil y su cuenta.

## Comportamiento por ancho

La cabecera es de dos columnas solo por encima de 1480 px (regla que ya existia). Por debajo
—la mayoria de portatiles— cae a una sola columna, asi que las acciones se colocan **en fila y
pegadas a la izquierda**, justo debajo de los datos del miembro, siguiendo la lectura. En
movil ocupan el ancho completo.

## Ficheros

- `panel-usuario.php` — suma de visitas, cabecera con la accion principal, franja de cifras sin
  saludo ni botones, aviso de perfil condicional y textos corregidos.
- `assets/css/styles.css` — clases nuevas al final (`member-dashboard-cta`, `csf-panel-aviso`,
  `csf-panel-summary-cifras`) y ajuste de `member-dashboard-actions` y del tamano del QR.

`.csf-panel-greeting` y `.csf-panel-greeting-note` se quedan en la hoja de estilos sin usarse:
no se borran por si vuelve a hacer falta un saludo en el panel.

## Pendiente

- La tarjeta "Mis eventos" dice "Crear un evento es gratis". Cuando se active la economia de
  puntos de `20_FASE1_LANZAMIENTO.md`, ese texto tiene que cambiar.
- Siguen existiendo dos agendas para el artista destacado: la real (`#mis-eventos`) y la de la
  microweb (`#web-eventos`), que ya no aparece en la portada pero sigue accesible por su ancla.
  Hay que decidir si se jubila o se deja con un aviso.

## Historial de cambios

- 2026-09-23: Cabecera unica, accion principal en la cabecera, franja con visitas acumuladas y
  aviso de perfil solo cuando falta algo.
