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

La base de datos crea o migra un usuario administrador. Si no existe ningun admin, el sistema crea una cuenta por defecto configurable mediante variables de entorno.

Valores locales por defecto:

- Email: `admin@consaborflamenco.com`.
- Contrasena: `Admin1234!`.

Cuando exista un usuario admin migrado desde `storage/users.json`, se respeta y no se pisa.

El panel queda protegido por sesion y rol administrativo.

## Panel funcional inicial

`panel-admin.php` permite ya:

- Ver una vista general de KPIs agrupados por comunidad, miembros, perfiles, setters, revista, banners, ventas, leads y sistema.
- Ver miembros registrados con tipo de espacio, membresia, numero y estado de perfil.
- Ver appointment setters registrados y sus estados de cuenta, documentacion, comisiones y codigo.
- Crear categorias editoriales.
- Crear articulos con categoria y estado.
- Ver articulos recientes.
- Ver banners contratados y sus fechas de publicacion/contratacion.

## Redisenado administrativo por fases

Se ha iniciado la Fase 1 del redisenado completo del panel:

- Navegacion lateral agrupada por Panel, Usuarios, Contenido, Publicidad, Finanzas y Contacto.
- Seccion activa sincronizada mediante `section=` en la URL para preparar filtros, paginacion y enlaces compartibles.
- Vista general con tarjetas accionables y bloques de actividad reciente.
- Componentes reutilizables en `app/admin_ui.php` para secciones, URLs internas, badges de estado e imagenes seguras.
- Categorias de articulos separadas del editor de articulos.
- Apartado independiente de Comisiones preparado sin inventar una tabla detallada hasta la fase comercial correspondiente.
- Sidebar movil plegable con estado `aria-expanded` y seleccion activa.

Quedan para fases posteriores los listados con paginacion real, operaciones masivas, modales accesibles, auditoria administrativa, imagen principal avanzada de articulos, formulario completo de banners y flujo detallado de comisiones.

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
- 2026-06-28: Implementado panel admin inicial conectado a BD para miembros, setters, categorias, articulos y banners.
- 2026-06-30: Corregida la carga del panel admin para evitar error 500 si falta una tabla de metricas; el bootstrap crea `usos_codigo_descuento` y las lecturas admin son tolerantes a migraciones incompletas.
- 2026-06-30: Ampliada la vista general del panel admin con KPIs operativos completos de usuarios, verificacion, membresias, perfiles, setters, contenidos, banners, leads, pagos e ingresos.
- 2026-07-18: Iniciada la Fase 1 del redisenado del panel admin con sidebar agrupado, secciones por URL, tarjetas de resumen, actividad reciente, categorias separadas y base visual responsive.
