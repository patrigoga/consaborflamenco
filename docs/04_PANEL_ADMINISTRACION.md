# Panel de Administracion

## Descripcion

El panel de administracion sera el centro de control de Con Sabor Flamenco. Permitira gestionar contenidos, comunidad, servicios, sistema comercial, inbox, appointment setters y liquidaciones.

## Objetivo del documento

Documentar las responsabilidades previstas del administrador y dejar claro el alcance inicial del area de administracion para fases posteriores.

## Funciones previstas

El administrador podra gestionar:

- Miembros.
- Artistas.
- Academias.
- Penas.
- Tablaos.
- Festivales.
- Eventos.
- Articulos.
- Categorias.
- Servicios.
- Leads.
- Inbox.
- Appointment setters.
- Codigos promocionales.
- Clientes captados.
- Ventas.
- Comisiones.
- Solicitudes de cobro.
- Liquidaciones.
- Facturas o justificantes de pago.
- Anunciantes.
- Campanas y banners.
- Provincias y cobertura geografica.
- Categorias y ubicaciones publicitarias.
- Impresiones, clics y rendimiento publicitario.

## Gestion de publicidad

El administrador podra crear anunciantes, subir creatividades, definir fechas, prioridad, provincia o cobertura nacional, categoria, formato y ubicacion. Tambien podra pausar campanas y consultar sus metricas.

Cada campana debera indicar como minimo:

- Anunciante y nombre interno.
- Estado y periodo de publicacion.
- Provincia o cobertura nacional.
- Una o varias categorias permitidas.
- Formato y ubicacion del banner.
- URL de destino y texto alternativo.
- Prioridad o modalidad comercial.

## Rankings por categoria

El administrador podra consultar votos, aprobar promociones pagadas, asignar periodos y revisar las tres posiciones visibles de cada categoria. Toda posicion de pago debera mostrarse como Promocionado en la web publica.

## Gestion editorial

El administrador debera poder publicar y organizar articulos, categorias, eventos y contenidos destacados para mantener el enfoque de revista digital flamenca.

## Gestion de comunidad

El administrador debera poder revisar y administrar miembros, perfiles publicos, tipos de miembro y solicitudes relacionadas con eventos o servicios.

## Acceso administrativo inicial

El primer usuario registrado se marca inicialmente como `admin` para poder acceder a `panel-admin.php`.

Este panel queda protegido por sesion y preparado como base, sin definir todavia su contenido interno definitivo.

## Gestion comercial

El panel debera centralizar leads, clientes, ventas, appointment setters, codigos promocionales, comisiones, solicitudes de cobro y liquidaciones.

## Gestion de inbox

El administrador debera poder revisar conversaciones, cambiar estados, asignarlas y convertirlas en leads cuando corresponda.

## Reglas y decisiones

- El panel de administracion sera el centro de control principal.
- Los codigos promocionales solo podran ser creados desde administracion.
- Las liquidaciones y justificantes internos se gestionaran desde administracion.
- La administracion tendra visibilidad global del sistema.
- El panel de administracion requerira sesion iniciada y rol administrativo.

## Pendiente de definir

- Roles administrativos y permisos.
- Estructura exacta del menu del panel.
- Paneles de metricas.
- Filtros y buscadores necesarios.
- Flujo de aprobacion de contenidos.
- Tarifas, inventario disponible y reglas de sobreventa publicitaria.

## Historial de cambios

- 2026-06-08: Documentadas las funciones iniciales del panel de administracion.
- 2026-06-21: Definida la futura gestion administrativa de publicidad y metricas.
- 2026-06-23: Documentado el acceso administrativo inicial y el panel protegido base.
