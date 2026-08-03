# Academias - Analisis del estado actual y plan de ampliacion

Documento de trabajo previo a la Fase 2 del area de Academias. Complementa a
[15_AREA_ACADEMIAS.md](15_AREA_ACADEMIAS.md), que describe lo que se construyo en la Fase 1.
Aqui se recoge el analisis del codigo real, los fallos detectados y el plan por bloques.

Fecha del analisis: 2026-08-03.

## 0. Nota sobre la arquitectura real

El encargo original mencionaba controladores, servicios, DTO, guards, interceptores y rutas
tipo framework. **Este proyecto no tiene nada de eso** y no se va a introducir: es PHP plano
sin framework, sin Composer y sin autoloader, con la convencion "una pagina = un `.php` en la
raiz" y ficheros de funciones globales con prefijo por modulo en `app/`. Ver
[../CLAUDE.md](../CLAUDE.md).

La traduccion de conceptos que se aplica en todo el plan:

| Concepto del encargo | Equivalente real en este proyecto |
|---|---|
| Controlador | Bloque `if ($_SERVER['REQUEST_METHOD'] === 'POST')` de la pagina |
| Servicio / repositorio | Funciones `academia_*()` en `app/academia_repository.php` |
| Guard | `academia_require_role()` en `app/academia_security.php` |
| DTO | Arrays asociativos; no se introducen clases |
| Rutas `/academia/alumnos` | `panel-academia.php?section=alumnos` |
| Componentes de frontend | Parciales PHP y clases CSS ya existentes en `assets/css/styles.css` |
| "Compilar backend/frontend" | `php -l` sobre cada fichero + carga real por HTTP |

Las rutas propuestas en el encargo (`/academia/panel`, `/alumno/horario`...) **no se van a
crear**: chocarian con el prefijo publico `/academia/{slug}` que sirve la microweb de cada
academia. Se mantiene el sistema de secciones por query string.

## 1. Estado actual

### 1.1 Lo que existe y funciona

- **Modelo de datos completo de Fase 1**: 13 tablas `academia_*` mas `disciplinas`, creadas
  por `database/20260802_academias_fase1.sql` de forma idempotente.
- **La academia es una extension 1:1 de `miembros`** (`academias.miembro_id` es PK y FK).
  Decision correcta y coherente con `tarjetas_miembro` y `appointment_setters`.
- **Roles en entidad intermedia** `academia_miembros` (RESPONSABLE, PROFESOR, ALUMNO, TUTOR),
  lo que permite multiacademia y multirol.
- **Aislamiento por academia resuelto en el backend** con `academia_require_role()`, que
  resuelve la academia desde la sesion y no desde el formulario.
- **Alta automatica**: al registrarse como academia, `academia_sync_membership()` crea la
  fila en `academias` (estado PENDIENTE) y en `academia_miembros` (RESPONSABLE / ACTIVO).
- **Panel de academia** con siete secciones y alta de nivel, profesor, alumno, curso, grupo
  y matricula.
- **Microweb publica** `/academia/{slug}` con cursos, profesorado y formularios de solicitud.
- **Administracion general**: listado de academias, cambio de estado y contadores.
- **Panel de alumno** minimo (inicio, cursos, perfil).

### 1.2 Fallos y carencias detectados en el codigo

Ordenados por gravedad. Los tres primeros son los que justifican empezar por seguridad.

| # | Gravedad | Problema | Evidencia |
|---|---|---|---|
| 1 | **Critico** | Al crear una matricula, `grupo_id` llega crudo desde `$_POST` y **no se comprueba que el grupo sea de la academia**. Se validan alumno y curso, el grupo no. Permite enganchar una matricula a un grupo de otra academia. | `panel-academia.php:127`. `academia_verify_grupo_ownership()` existe en `app/academia_security.php:74` y **no se llama desde ningun sitio**. |
| 2 | **Critico** | El panel de alumno es **inalcanzable**. `academia_require_alumno()` filtra por `academia_alumnos.usuario_id`, pero esa columna **no se rellena en ningun punto del codigo**: el INSERT de alumnos ni la menciona. Ningun alumno puede entrar nunca. | `app/academia_repository.php:346` (INSERT sin `usuario_id`), `app/academia_security.php:45`. |
| 3 | **Alto** | Una academia `SUSPENDIDA` o de `BAJA` conserva el panel completo. `academia_require_role()` comprueba el estado de la *membresia*, nunca el de la *academia*. Suspender no sirve de nada salvo para ocultar la web publica. | `app/academia_security.php:15-35`. |
| 4 | Alto | **Editar un grupo destruye su horario.** `academia_upsert_grupo()` borra todos los `academia_horarios_grupo` del grupo y reinserta como maximo uno. El modelo admite varias clases por semana; el codigo, no. | `app/academia_repository.php:599-614`. |
| 5 | Alto | **No existe edicion de nada.** Solo hay formularios "Nuevo X". Prueba de ello: `academia_get_alumno()`, `academia_get_curso()`, `academia_get_grupo()` y `academia_curso_profesor_ids()` estan escritas y **no se llaman desde ninguna pagina**. Media capa de repositorio es codigo muerto esperando una UI. | Repositorio lineas 303, 419, 483, 560. |
| 6 | Alto | **Las solicitudes publicas caen en un agujero negro.** La web guarda en `academia_solicitudes_info` y `academia_solicitudes_matricula`, el dashboard las cuenta, y **no hay ninguna pantalla para leerlas**. Solo existen las funciones de INSERT. | Repositorio: solo `academia_create_solicitud_*`. |
| 7 | Medio | Los ENUM (`estado`, `modalidad`, `rol`) llegan crudos desde `$_POST`. Con `ERRMODE_EXCEPTION` un valor inventado revienta la consulta y el usuario ve un "No se pudo guardar" generico. No es fuga de datos, pero si robustez y mensajes. | `panel-academia.php:88,93,111,128,137`. |
| 8 | Medio | **No se comprueba el aforo** al matricular: nada mira `plazas_maximas` del grupo ni del curso. | `academia_create_matricula()`. |
| 9 | Medio | **Sin paginacion**: todos los listados llevan un `LIMIT 200` fijo, sin total ni navegacion. Busqueda solo en alumnos y solo por nombre/apellidos/email. Sin filtros por curso, grupo, nivel o estado de matricula. | Repositorio, lineas 295, 385, 531, 640, 871. |
| 10 | Medio | `academia_tutores` es una tabla **sin una sola linea de codigo** que la use. | Sin coincidencias en todo el repo. |
| 11 | Medio | Las tablas de academias **no estan en `db_bootstrap()` ni en `database/schema.sql`**, solo en la migracion suelta. Rompe la convencion de los tres sitios documentada en CLAUDE.md: un entorno nuevo se levanta sin el modulo. | `app/database.php` no menciona ninguna tabla `academia_*`. |
| 12 | Bajo | `academia_list_alumnos()` se vuelve a llamar **dentro del render** del formulario de matricula, aunque el listado ya venia cargado. | `panel-academia.php:595`. |
| 13 | Bajo | `academia_upsert_nivel()` se llama "upsert" pero solo inserta. No hay editar, ordenar ni desactivar niveles. | Repositorio linea 187. |

### 1.3 Subsistemas del proyecto que se pueden reutilizar

Antes de crear nada se ha comprobado que existe:

- **Autenticacion y sesion**: `require_login()`, `current_user()`, CSRF con `csrf_token()` /
  `verify_csrf()`. Se reutiliza tal cual.
- **Subida de imagenes**: `save_member_photo_upload()`, almacenamiento runtime fuera del
  repositorio (`RUNTIME_UPLOADS_DIR`) y servido por `media.php` con `csf_normalize_media_file()`
  como guardian. **Solo admite `member-photos/` y `curriculum-images/`**: para materiales
  educativos habra que ampliar esa lista blanca, no puentearla.
- **Badges de estado**: `admin_badge_class()` ya conoce casi todos los estados del modulo.
- **Estilos**: `member-summary-grid`, `member-config-card`, `admin-table`, `status-pill`,
  `content-section`, `form-grid-two/three`, `empty-state`, `directory-filters`. Hay base
  suficiente; falta una capa de tabla responsive y de paginacion.
- **Correo**: helpers de envio SMTP en `app/auth.php` (usados por verificacion y reset).

Y que **no existe** (hay que crearlo, minimo imprescindible):

- Ningun sistema de notificaciones, avisos ni inbox. El "Inbox" de AGENTS.md esta sin
  implementar. Se creara acotado al modulo de academias.
- Ninguna tabla de asistencia, cuotas de academia, materiales ni auditoria.
- `pagos_stripe` **existe**, pero es un pago puntual usuario-Stripe con `concepto` libre; su
  semantica no es la de una cuota recurrente de academia. **No se reutiliza como tabla de
  cuotas**, pero las nuevas tablas se diseñan para poder enlazar con ella cuando se quiera
  cobrar online.

## 2. Tablas nuevas estrictamente necesarias

Se crean solo las que cubren funcionalidad hoy inexistente. Todas siguen la convencion del
modulo: prefijo `academia_`, columnas en español, `estado` para borrado logico, marcas de
tiempo, `created_by` donde aplica, FK a `academias(miembro_id)` e indices en las FK y en los
campos de filtro.

| Tabla | Por que es necesaria | Relaciones | Indices |
|---|---|---|---|
| `academia_avisos` | No hay ningun sistema de comunicaciones en el proyecto. | FK academia, autor (`usuarios`) | (academia_id, estado, publicado_at) |
| `academia_aviso_destinatarios` | Un aviso puede ir a la academia, un curso, un grupo, un alumno o un rol. Relacion polimorfica acotada por `ambito`. | FK aviso | (aviso_id, ambito, referencia_id) |
| `academia_aviso_lecturas` | Indicador de leido/no leido por usuario. | FK aviso, usuario | UNIQUE (aviso_id, usuario_id) |
| `academia_asistencias` | No existe. Registro por alumno, grupo y fecha. | FK academia, grupo, alumno, registrado_por | UNIQUE (grupo_id, alumno_id, fecha) |
| `academia_progreso_alumno` | No existe. Evaluacion y observaciones, con visibilidad interna o compartida. | FK academia, alumno, autor | (alumno_id, fecha_revision) |
| `academia_cuotas` | Define el importe y la periodicidad; hoy solo existe `cursos.precio` como texto suelto. | FK academia, curso opcional | (academia_id, estado) |
| `academia_pagos` | Movimientos economicos por alumno y periodo, con estado. Los recibos se generan desde aqui, sin tabla aparte. | FK academia, alumno, matricula, cuota, `pagos_stripe` (nullable) | (academia_id, estado, periodo), (alumno_id) |
| `academia_materiales` | Material educativo asignable a curso, grupo o alumno. | FK academia, autor | (academia_id, ambito, referencia_id) |
| `academia_horario_excepciones` | Cambios puntuales, cancelaciones, recuperaciones y sustituciones. El modelo actual solo admite horario semanal fijo. | FK grupo, profesor sustituto | (grupo_id, fecha) |
| `academia_lista_espera` | Orden de solicitud cuando un grupo esta completo. | FK academia, curso, grupo, alumno | (grupo_id, posicion) |
| `academia_actividad` | Historial de acciones sensibles. No hay auditoria en el proyecto. | FK academia, usuario | (academia_id, created_at) |

**Tablas que NO se crean**, pese a estar en el encargo, porque el modelo actual ya lo cubre:

- *Recibos*: se derivan de `academia_pagos`; una tabla aparte duplicaria los mismos datos.
- *Documentos*: `academia_materiales` con un campo `tipo` cubre material y documentacion.
- *Alumno en varios grupos*: se resuelve con varias filas en `academia_matriculas`, que ya
  lleva `grupo_id`. Crear `academia_alumno_grupos` duplicaria la relacion.
- *Clases de prueba*: se modelan como `academia_lista_espera.tipo = 'PRUEBA'` en Fase 3, para
  no crear una tabla casi identica.

**Columnas nuevas sobre tablas existentes** (con `db_add_column_if_missing()`, sin renombrar
ni borrar nada):

- `academia_grupos`: `nivel_id`, `profesor_principal_id`, `fecha_inicio`, `fecha_fin`, `observaciones`.
- `academia_cursos`: `edad_minima`, `edad_maxima`.
- `academia_horarios_grupo`: `academia_miembro_id` (profesor), `vigente_desde`, `vigente_hasta`, `observaciones`.
- `academias`: `logo_path`, `portada_path`, `visibilidad_json`.
- `academia_niveles`: ya tiene `estado` y `orden`; solo falta la UI.

## 3. Plan por bloques

Cada bloque es verificable por separado y deja el modulo funcionando. Se ejecutan en orden.

### Bloque 1 - Seguridad y correcciones criticas — IMPLEMENTADO (2026-08-04)
Ficheros: `app/academia_security.php`, `app/academia_repository.php`, `app/academia_ui.php`,
`panel-academia.php`.

1. **Propiedad del grupo al matricular** (fallo #1): nueva
   `academia_verify_grupo_del_curso()`, que exige que el grupo sea de la academia **y** del
   curso elegido. Se añaden ademas `academia_verify_profesor_ownership()`,
   `academia_verify_matricula_ownership()` y `academia_verify_nivel_ownership()`, y se
   comprueba la propiedad del curso antes de reescribir su profesorado.
2. **Academia suspendida en solo lectura** (fallo #3): `academia_estado_operativo()` y
   `$canManage`. Todo POST se rechaza en servidor y el panel muestra un aviso. Solo `ACTIVA`
   y `PENDIENTE` admiten cambios.
3. **Vinculo alumno-cuenta** (fallo #2): `academia_sync_alumno_usuario()` rellena
   `academia_alumnos.usuario_id` a partir del email y crea el rol `ALUMNO` en
   `academia_miembros`. **Es la primera vez que el panel de alumno es accesible.** Una misma
   cuenta no puede quedar enlazada a dos fichas de la misma academia.
4. **ENUM validados** (fallo #7): catalogos en `app/academia_repository.php` como fuente
   unica, usados a la vez para pintar los `<select>` (`academia_enum_options()`) y para
   validar en servidor (`academia_enum()`). Un valor inventado cae al valor por defecto en
   lugar de reventar la consulta.
5. **Aforo del grupo** (fallo #8): `academia_grupo_ocupacion()` cuenta solo las matriculas
   que ocupan plaza de verdad. Superar el aforo exige marcar una casilla de confirmacion.
   El selector de grupo muestra la ocupacion.
6. **Horarios** (fallo #4): `academia_upsert_grupo()` deja de tocar el horario y aparece
   `academia_set_grupo_horarios()`, que admite varias clases por semana, valida las horas y
   descarta las filas incompletas. Editar un grupo ya no borra su horario.
7. **Extra encontrado al probar**: `es_menor_edad` se enviaba como `false`, que PDO manda
   como cadena vacia. En MySQL con modo estricto (produccion) **fallaba el alta de cualquier
   alumno mayor de edad**. Corregido con un `(int)`.
8. **Extra encontrado al revisar**: `academia_set_curso_profesores()` borraba
   `academia_curso_profesores` filtrando solo por `curso_id`. Con el id de un curso ajeno se
   podia vaciar el profesorado de otra academia. El DELETE ya va filtrado por academia.

Verificado con 40 comprobaciones de logica pura y 33 contra una MariaDB aislada con el
esquema real, mas navegacion HTTP de las siete secciones del panel de academia y las tres
del panel de alumno.

### Bloque 2 - CRUD completo y bandejas de solicitudes
Ficheros: `app/academia_repository.php`, `panel-academia.php`, `app/academia_ui.php`.

1. Edicion real de alumno, curso, grupo, nivel y profesor (aprovecha las funciones muertas).
2. Baja logica en lugar de borrado.
3. Bandeja de solicitudes de informacion con estados y notas.
4. Bandeja de solicitudes de matricula con aprobar / rechazar / lista de espera.
5. Conversion transaccional solicitud -> alumno + matricula, sin duplicar alumnos.
6. Busqueda, filtros y paginacion reales en todos los listados.

### Bloque 3 - Avisos y comunicaciones
Tablas nuevas `academia_avisos`, `academia_aviso_destinatarios`, `academia_aviso_lecturas`.
Aviso automatico al cancelar o cambiar un horario.

### Bloque 4 - Asistencia y progreso
Tablas `academia_asistencias` y `academia_progreso_alumno`. Pantalla de pase de lista por
grupo y fecha. Porcentaje de asistencia por alumno.

### Bloque 5 - Pagos y cuotas
Tablas `academia_cuotas` y `academia_pagos`. Sin pasarela: se deja el enlace opcional a
`pagos_stripe` preparado.

### Bloque 6 - Panel de alumno y de tutor
Panel de alumno completo segun el encargo y primeras pantallas de tutor sobre
`academia_tutores`.

### Bloque 7 - Materiales, horarios avanzados y calendario
`academia_materiales`, `academia_horario_excepciones`, vista de calendario y deteccion de
solapamientos de aula, profesor y grupo.

### Bloque 8 - Perfil publico ampliado, dashboard y capa visual
Logo, portada, galeria y control de visibilidad. Dashboard con clases de hoy, grupos casi
llenos y pagos pendientes. Tablas responsive, paginacion y estados vacios homogeneos.

### Transversal
`academia_actividad` (historial) se introduce en el Bloque 2 y se va alimentando en el resto.
Las tablas se añaden **a la vez** a la migracion nueva, a `database/schema.sql` y a
`db_bootstrap()`, cerrando de paso el fallo #11.

## 4. Pruebas obligatorias por bloque

Como el proyecto no tiene framework de tests, se verifica con scripts PHP contra la base de
datos y con navegacion HTTP real, que es como se valido la Fase 1:

- Administrador general, responsable, profesor, alumno, tutor y usuario sin permisos.
- Intento de acceder o escribir sobre datos de otra academia manipulando ids.
- Grupo completo, horarios solapados, solicitud duplicada, alumno de baja, pago pendiente.
- `php -l` sobre todo fichero tocado y carga real de cada pantalla.
