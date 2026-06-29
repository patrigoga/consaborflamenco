# Modelo Base de Datos

## Descripcion

Este documento recoge el modelo de datos previsto y la primera conexion real a base de datos de Con Sabor Flamenco.

## Objetivo del documento

Mantener una base documental para disenar y evolucionar el modelo de base de datos sin improvisar tablas ni relaciones durante la implementacion.

## Entidades conceptuales previstas

- Usuarios administradores.
- Usuarios de acceso.
- Miembros.
- Tipos de miembros.
- Tarjetas de miembro.
- Perfiles publicos.
- Imagenes.
- Videos.
- Redes sociales.
- Articulos.
- Categorias.
- Cursos y modalidades formativas.
- Eventos.
- Servicios.
- Planes.
- Contrataciones de servicios.
- Appointment setters.
- Codigos promocionales.
- Leads.
- Clientes.
- Ventas.
- Comisiones.
- Solicitudes de cobro.
- Liquidaciones.
- Facturas o justificantes internos.
- Conversaciones de inbox.
- Mensajes de inbox.
- Provincias.
- Anunciantes.
- Categorias publicitarias.
- Campanas publicitarias.
- Creatividades o banners.
- Banners contratados por miembros.
- Pagos Stripe.
- Ubicaciones publicitarias.
- Impresiones publicitarias.
- Clics publicitarios.
- Visitas a contenidos y perfiles publicos.
- Votos de usuarios.
- Promociones de posicionamiento por categoria.

## Tipos de miembros previstos

- Artista.
- Academia.
- Tienda flamenca.
- Pena flamenca.
- Tablao.
- Festival.
- Profesional flamenco.
- Entidad colaboradora.

## Relaciones iniciales

- Un usuario podra tener rol administrador, miembro o appointment setter.
- Un miembro tendra un tipo de miembro.
- Un miembro pertenecera a un usuario de acceso.
- Un miembro tendra una numeracion unica y un codigo `CSF-...` unico.
- El registro inicial creara miembros simpatizantes por defecto.
- El codigo de miembro solo sera utilizable para descuentos si el miembro ha pasado a Miembro VIP mediante pago.
- Un miembro tendra una tarjeta identificativa configurable con imagen, nombre visible, nivel de membresia, numero y codigo.
- Un miembro debera tener tipo de espacio, nombre artistico, descripcion, ciudad, provincia y fotografia principal para considerar completo su perfil.
- Un miembro podra tener multiples items de curriculum clasificados como formacion, experiencia, docencia, actuaciones, premios, repertorio y redes sociales.
- Los items principales de curriculum tendran titulo y descripcion obligatorios cuando se publiquen como entrada, y podran incluir una imagen asociada.
- Cada item de curriculum podra marcarse como visible publico o privado.
- Un miembro podra tener perfil publico, imagenes, videos, redes sociales y eventos.
- Un miembro podra contratar uno o varios banners.
- Un banner de miembro tendra titulo, URL, imagen, fechas de publicacion y fechas de contratacion.
- Un banner solo sera visible si esta pagado, activo y dentro de sus fechas de publicacion/contratacion.
- Un pago de Stripe podra activar un banner o un servicio contratado.
- Un appointment setter podra tener uno o varios codigos promocionales.
- Cada codigo promocional pertenecera a un unico setter.
- Un lead podra asociarse a un setter mediante codigo promocional.
- Un lead podra convertirse en cliente.
- Un cliente podra generar una o varias ventas.
- Una venta podra generar una comision para el setter asociado.
- Una comision podra estar pendiente, solicitada, pagada o liquidada.
- Una conversacion de inbox podra convertirse en lead.
- Un usuario o miembro podra tener una provincia asociada a su perfil.
- Un anunciante podra tener varias campanas.
- Una campana podra dirigirse a una o varias provincias y categorias.
- Una campana podra tener varias creatividades adaptadas a distintos formatos.
- Una impresion o clic se asociara, cuando proceda, con campana, banner, ubicacion, categoria y provincia.
- Las visitas permitiran calcular un ranking global entre todos los tipos de contenido, por periodo y provincia, sin contabilizar trafico automatizado.
- Todo contenido candidato al ranking debera exponer tipo, titulo, imagen principal, descripcion corta y URL publica.
- La descripcion corta usada en las tarjetas tendra un maximo de 30 caracteres.
- Cada ranking de categoria mostrara tres posiciones con origen VOTACION o PROMOCION_PAGADA.
- Las promociones pagadas tendran fecha de inicio, fecha de fin, categoria, posicion contratada y estado.
- Los contenidos promocionados se identificaran publicamente para no confundirlos con resultados organicos.

## Reglas iniciales de publicidad

- Las categorias se derivan del navbar publico: Inicio, Revista, Fotografia, Moda, Flamenco, Artistas, Academias, Cursos, Eventos, Penas, Tablaos, Festivales y Concursos.
- Servicios y Contacto quedan excluidos como categorias publicitarias.
- Las campanas admitiran cobertura provincial o nacional.
- La seleccion comprobara estado activo, fechas, ubicacion, categoria, provincia, prioridad y disponibilidad.
- La provincia del usuario registrado tendra prioridad sobre la preferencia local del navegador.
- La medicion evitara guardar datos personales innecesarios y debera respetar la politica de consentimiento aplicable.

## Esquema SQL inicial

Se crea `database/schema.sql` como primera propuesta tecnica del modelo relacional.

La base de datos del proyecto se llamara `consaborflamenco`.

El script `database/create_database.sql` crea la base de datos con `utf8mb4` y selecciona el esquema antes de aplicar tablas.

Tablas incluidas en esta fase:

- `provincias`.
- `usuarios`.
- `tipos_miembro`.
- `miembros`.
- `tarjetas_miembro`.
- `categorias_articulos`.
- `articulos`.
- `pagos_stripe`.
- `banners_miembro`.
- `appointment_setters`.
- `password_reset_tokens`.
- `usos_codigo_descuento`.

Este esquema pasa a ser la fuente principal para autenticacion, miembros, articulos, categorias, banners y setters cuando MySQL esta disponible. El almacenamiento JSON queda como respaldo local durante la transicion.

La aplicacion incorpora un bootstrap automatico que crea la base `consaborflamenco` en local, crea o actualiza tablas, siembra tipos de miembro/categorias editoriales, migra usuarios existentes desde `storage/users.json` cuando exista y crea un administrador por defecto solo si no hay ningun admin.

En produccion se usa la base ya creada en Hostinger:

- Base de datos: `u311361615_csf`.
- Usuario MySQL: `u311361615_admin`.
- Host esperado: `localhost`.
- La contrasena se guarda en `.env` y no se versiona.

## Estados previstos

Estados de conversacion:

- NUEVO.
- EN_CURSO.
- RESPONDIDO.
- CONVERTIDO.
- DESCARTADO.
- CERRADO.

Estados comerciales a definir:

- Lead nuevo.
- Lead contactado.
- Lead convertido.
- Lead descartado.
- Comision pendiente.
- Comision pagada.

## Reglas y decisiones

- El modelo debe estar preparado para API REST.
- Las relaciones deben permitir separar administracion, miembros y setters.
- La informacion visible por cada setter debe limitarse a sus propios datos.
- La informacion de miembros debe permitir perfiles publicos completos.
- El sistema de inbox debe permitir historial y conversion a lead.

## Pendiente de definir

- Tablas definitivas.
- Campos obligatorios.
- Indices y claves unicas.
- Politicas de borrado y archivado.
- Auditoria de cambios.
- Modelo de facturacion por periodo, impresiones, clics o posicion fija.
- Limites de frecuencia y criterios de rotacion de campanas.
- Ventana temporal y formula definitiva del ranking de comunidad.

## Historial de cambios

- 2026-06-08: Documentado el modelo conceptual inicial de datos.
- 2026-06-21: Anadidas las entidades y reglas conceptuales del sistema publicitario.
- 2026-06-21: Anadida la medicion conceptual de visitas para el ranking de portada.
- 2026-06-23: Anadido esquema SQL inicial con usuarios, miembros, tarjetas, banners, pagos Stripe y articulos.
- 2026-06-24: Definido `consaborflamenco` como nombre de la base de datos y creado el script de inicializacion.
- 2026-06-24: Ajustado el estado inicial de miembros a simpatizante y reservados los descuentos para Miembro VIP.
- 2026-06-24: Anadidos campos previstos para ubicacion, telefono, fotografia principal y perfil completo de miembros.
- 2026-06-25: Anadida tabla prevista `miembros_curriculum_items` para curriculum artistico repetible y privacidad por item.
- 2026-06-25: Ampliado el curriculum previsto con redes sociales, fechas, orden cronologico, titulo, descripcion e imagen por item principal.
- 2026-06-28: Activada la conexion inicial a MySQL con bootstrap de tablas, migracion desde JSON, admin por defecto y almacenamiento de perfil completo en `miembros.perfil_json`.
- 2026-06-29: Anadida configuracion especifica para la base de produccion `u311361615_csf` y separacion de credenciales mediante `.env`.
