# Area de Academias

## Descripcion

Modulo educativo de Con Sabor Flamenco. Permite que cada academia de flamenco disponga de
un panel de gestion propio dentro de la plataforma y de una microweb publica en
`/academia/{slug}`.

Cubre academias de baile, cante, toque, percusion, compas y palmas, y otras disciplinas
relacionadas.

## Estado actual: Fase 1 implementada

Implementado:

- Modelo de datos multiacademia.
- Relacion usuarios-academias mediante entidad intermedia con rol.
- Seguridad por academia y por rol.
- Panel de academia para responsable y profesor.
- Panel basico de alumno.
- Microweb publica de la academia.
- Gestion de profesores, alumnos, niveles, cursos, grupos y matriculas.
- Solicitudes publicas de informacion y de matricula.
- Seccion de administracion general para aprobar y suspender academias.

Pendiente para fases posteriores (ver seccion Fases):

- Calendario, clases, asistencia, evaluaciones, progreso, objetivos y material educativo.
- Cuotas, cargos, pagos, recibos e informes economicos.
- Avisos, correo, WhatsApp, Google Calendar y pasarela de pago.
- Tutores y gestion de menores con pantallas propias.
- Certificados, importacion masiva, exportaciones e informes avanzados.

## Decision de arquitectura central

La academia NO es una entidad independiente con identificador propio. Es una **extension 1:1
de `miembros`**: la tabla `academias` usa `miembro_id` como clave primaria y como clave
foranea contra `miembros(id)`.

Motivos:

1. Es el patron que ya usan `tarjetas_miembro` y `appointment_setters` en este proyecto.
2. `academia_disciplinas.academia_id` ya apuntaba a `miembros(id)` antes de este modulo,
   y lo usa `csf_fetch_member_directory()` para filtrar el directorio publico.
3. Evita duplicar nombre, slug, ciudad, provincia, telefono, foto y redes sociales, que
   siguen viviendo en `miembros` y alimentan el directorio de `academias.php`.

La tabla `academias` solo anade lo que un miembro generico no tiene: razon social, CIF,
plan contratado, estado de aprobacion y configuracion.

## Roles

Los roles NO se guardan como un campo rigido en `usuarios`. Se guardan en la entidad
intermedia `academia_miembros`, que relaciona usuario, academia, rol, estado, fechas y
permisos adicionales. Esto permite que una persona pertenezca a varias academias y que
tenga varios roles en la misma academia.

| Rol | Alcance |
|---|---|
| RESPONSABLE | Gestiona toda su academia: datos, profesores, alumnos, cursos, grupos y matriculas |
| PROFESOR | Solo consulta sus cursos, sus grupos y los alumnos matriculados en ellos |
| ALUMNO | Solo accede a sus propios cursos, horario y datos de contacto |
| TUTOR | Modelo preparado en base de datos, sin pantallas en Fase 1 |

El administrador general de la plataforma (`usuarios.rol = 'ADMIN'`) accede a todas las
academias desde `panel-admin.php`, seccion Academias.

## Alta de una academia

1. La persona se registra en `registro.php` eligiendo el tipo de espacio "Academia" y el
   nombre publico de la academia. Ese nombre es el de su web (`/academia/{slug}`), se
   comprueba que no este ocupado y queda reservado: no se puede cambiar desde el panel.
   Ver "Nombre publico y URL" en `docs/05_AREA_MIEMBROS.md`.
2. `db_upsert_member_for_user()` crea su fila en `miembros` con `tipo_miembro = academia`.
3. `academia_sync_membership()` crea automaticamente su fila en `academias` con estado
   `PENDIENTE` y su fila en `academia_miembros` con rol `RESPONSABLE` y estado `ACTIVO`.
4. La academia puede entrar ya en `panel-academia.php` y preparar profesores, cursos y
   grupos, pero su microweb publica NO es visible hasta que un administrador cambie su
   estado a `ACTIVA` desde `panel-admin.php`.

Mientras esta en `PENDIENTE`, `/academia/{slug}` responde 404. Tampoco hay puerta trasera
por `/artista/{slug}`: desde que existen prefijos por tipo, esa URL redirige (301) a
`/academia/{slug}`, que es la canonica del miembro. Antes de ese cambio, una academia sin
aprobar seguia siendo visible en `/artista/{slug}`.

## Seguridad multiacademia

Reglas aplicadas en el backend, nunca solo ocultando botones en la interfaz:

- `academia_require_role()` resuelve la academia del usuario a partir de su sesion, no de
  ningun campo enviado por el formulario. Si se recibe `academia_id` por query string, se
  comprueba que el usuario pertenezca realmente a esa academia.
- Toda consulta de listado filtra por `academia_id` obtenido en el servidor.
- Antes de crear una matricula o un grupo se verifica con
  `academia_verify_alumno_ownership()` / `academia_verify_curso_ownership()` que el alumno
  y el curso pertenecen a la academia del usuario.
- El PROFESOR usa consultas propias (`academia_list_*_for_profesor`) que pasan siempre por
  `academia_curso_profesores`. Nunca ve el listado completo de alumnos de la academia.
- El ALUMNO solo accede a filas cuyo `usuario_id` coincide con el suyo.
- Todos los formularios POST validan CSRF con `verify_csrf()`.
- Toda la salida se escapa con `e()`.

## Proteccion de datos

`academia_alumnos` deja preparada la estructura tecnica para el tratamiento de datos de
menores:

- `fecha_nacimiento` y `es_menor_edad`, este ultimo derivado en servidor a partir de la
  fecha, nunca de un campo libre enviado por el cliente.
- `consentimiento_imagen`, `consentimiento_fecha` y `consentimiento_version` para registrar
  el consentimiento y su version.
- `academia_tutores` permite vincular uno o varios tutores a un alumno, con indicadores de
  si pueden pagar y si pueden autorizar.

Un alumno puede existir sin cuenta de usuario (`usuario_id` nulo), para menores que no
tienen acceso propio y a los que gestiona la academia o su tutor.

## Entidades de Fase 1

| Tabla | Finalidad |
|---|---|
| `academias` | Extension de `miembros`: datos fiscales, plan, estado y configuracion |
| `academia_miembros` | Relacion usuario-academia-rol con estado y permisos |
| `academia_profesores` | Datos docentes del rol PROFESOR |
| `academia_alumnos` | Ficha del alumno, con o sin cuenta de usuario |
| `academia_tutores` | Vinculo tutor-alumno, preparado para fases posteriores |
| `academia_niveles` | Niveles propios de cada academia, no cerrados en codigo |
| `academia_cursos` | Cursos con disciplina, nivel, modalidad, precio y estados |
| `academia_curso_profesores` | Asignacion de profesores a cursos |
| `academia_grupos` | Grupos dentro de un curso |
| `academia_horarios_grupo` | Horario semanal de cada grupo |
| `academia_matriculas` | Matricula de un alumno en un curso y grupo |
| `academia_solicitudes_info` | Solicitudes publicas de informacion |
| `academia_solicitudes_matricula` | Solicitudes publicas de matricula |

Todas incluyen marcas de tiempo y, donde aplica, `created_by` / `updated_by`. El borrado es
logico mediante el campo `estado`: no se borran filas para conservar el historial.

## Ficheros

Nuevos:

- `academia.php` - microweb publica de la academia
- `panel-academia.php` - panel de responsable y profesor
- `panel-alumno.php` - panel del alumno
- `app/academia_repository.php` - consultas del modulo, prefijo `academia_*`
- `app/academia_security.php` - guards de rol y de propiedad de recursos
- `app/academia_ui.php` - secciones y utilidades de interfaz del panel
- `database/20260802_academias_fase1.sql` - migracion de Fase 1

Modificados:

- `.htaccess` - regla de reescritura `/academia/{slug}`
- `academias.php` - corregido el enlace del directorio, que apuntaba a `artista/`
- `app/auth.php` - llamada a `academia_sync_membership()` tras crear o actualizar el miembro
- `app/admin_ui.php` - seccion Academias y nuevos estados en `admin_badge_class()`
- `app/admin_repository.php` - contadores de academias, alumnos, profesores y cursos
- `panel-admin.php` - seccion de administracion de academias
- `panel-usuario.php` - accesos a "Mi academia" y "Mis clases" cuando corresponde

## Migracion

```powershell
php tools/run_migration.php database/20260802_academias_fase1.sql
```

Es idempotente: se puede volver a ejecutar sin efectos secundarios.

La migracion crea tambien `disciplinas`, `miembro_disciplinas` y `academia_disciplinas`.
Nacieron en `20260718_disciplinas.sql`, pero al comprobar el volcado de produccion del
2026-08-01 se vio que **esa migracion nunca llego a ejecutarse alli**: la base de datos
tenia 18 tablas y ninguna de disciplinas. Como `academia_cursos` declara una clave foranea
contra `disciplinas(id)`, sin ese bloque la migracion se quedaba a medias, creando seis
tablas y abortando. Se repiten con `CREATE TABLE IF NOT EXISTS` / `INSERT IGNORE` para que
sea autosuficiente y no rompa nada si ya existen.

Efecto secundario util: al crear `disciplinas`, los filtros por disciplina del directorio
publico (`csf_fetch_member_directory()`) dejan de degradar al modo texto y pasan a usar la
relacion real.

El codigo degrada con elegancia si la migracion todavia no se ha ejecutado: tanto
`panel-usuario.php` como `panel-admin.php` capturan el error y ocultan lo relativo a
academias en lugar de romper la pagina.

## Trampas del modelo heredado que afectan a este modulo

Dos comportamientos de `app/auth.php` que hay que respetar al ampliar el modulo:

1. **`$user['id']` NO es el id numerico**, es el uuid (ver `db_user_from_row()`). El id al
   que apuntan todas las claves foraneas es `$user['db_id']`. Se usa el helper
   `academia_user_id()` de `app/academia_security.php` para no volver a equivocarse.
2. **`miembros` tiene doble fuente de verdad**: las columnas y `perfil_json`.
   `db_upsert_member_for_user()` reescribe las columnas desde `perfil_json` en cada inicio
   de sesion, y ademas vacia siempre la columna `biografia`. Por eso
   `academia_update_profile()` escribe en ambos sitios y la descripcion publica se guarda
   en `perfil_json.short_description`, que es lo que lee `academia_descripcion()`.

## Verificacion realizada

Fase 1 se probo contra una copia del esquema real de produccion, levantando MariaDB
aparte e importando el volcado del 2026-08-01:

- Migracion ejecutada sobre el esquema real y repetida para confirmar idempotencia.
- Las 44 funciones del repositorio y de seguridad ejercitadas contra la base de datos.
- Alta automatica de academia al registrarse, incluida su repeticion.
- Navegacion HTTP real con sesion iniciada de las siete secciones del panel.
- Rol PROFESOR: menu reducido, sin acceso a Matriculas, y solo ve los alumnos de los
  cursos que tiene asignados.
- Rechazo de acciones de responsable ejecutadas por un profesor y de POST sin CSRF valido.
- Intento de asignar a un curso un profesor de otra academia: descartado en el INSERT.
- Ficha publica con slug real (200) y con slug inexistente (404).

## Fases

- Fase 1 (implementada): estructura principal, roles, seguridad, panel, perfil publico,
  profesores, alumnos, cursos, grupos y matriculas.
- Fase 2: calendario, clases, asistencia, evaluaciones, progreso, objetivos y material.
- Fase 3: cuotas, cargos, pagos, recibos, estados e informes basicos.
- Fase 4: avisos, correo, WhatsApp, Google Calendar, pasarela de pago y notificaciones.
- Fase 5: tutores, menores, certificados, clases online, PWA, importacion y exportaciones.

## Planes

`academias.plan` admite `BASICO`, `PROFESIONAL` y `AVANZADO`, y `academias.configuracion_json`
guarda la configuracion por academia. En Fase 1 se almacenan como dato, sin motor de limites:
el control efectivo de cuotas de alumnos y profesores por plan se definira cuando se
concreten los numeros de cada plan.
