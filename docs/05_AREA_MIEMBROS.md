# Area de Miembros

## Descripcion

El area de miembros sera una zona privada donde artistas, academias, penas, tablaos, festivales, profesionales y entidades colaboradoras podran gestionar su presencia dentro de Con Sabor Flamenco.

## Objetivo del documento

Definir las capacidades previstas para los miembros y documentar los tipos de miembros contemplados en la plataforma.

## Tipos de miembros

Todo el que se da de alta en `registro.php` es un miembro. Lo que elige en el alta no es
una categoria decorativa: define el tipo de espacio, el panel al que accede y la raiz de su
URL publica.

Tipos implementados, con su prefijo de URL:

| Tipo | Etiqueta en el alta | URL publica | Pagina que la sirve |
|---|---|---|---|
| `artista` | Artista | `/artista/{slug}` | `artista.php` |
| `academia` | Academia | `/academia/{slug}` | `academia.php` |
| `asociacion` | Asociacion flamenca | `/asociacion/{slug}` | `artista.php` |
| `tienda` | Tienda flamenca | `/tienda/{slug}` | `artista.php` |
| `pena` | Pena flamenca | `/pena/{slug}` | `artista.php` |
| `tablao` | Tablao flamenco | `/tablao/{slug}` | `artista.php` |
| `festival` | Festival | `/festival/{slug}` | `artista.php` |
| `profesional` | Profesional flamenco | `/profesional/{slug}` | `artista.php` |

La lista vive en `member_type_options()` y los prefijos en `member_type_url_prefixes()`,
ambas en `app/auth.php`. Las claves de las dos deben coincidir. Los prefijos tienen que
estar tambien en las reglas de reescritura de `.htaccess`.

Pendiente de decidir como tipo propio: entidad colaboradora.

## Nombre publico y URL

El nombre publico es el nombre de la web del miembro, asi que se trata como un recurso
reservado:

1. Se elige en el alta (`registro.php`), junto al tipo de espacio. El formulario muestra en
   vivo como quedara la URL.
2. Antes de crear la cuenta se comprueba con `member_slug_in_use()` que el slug este libre.
   El slug es unico para toda la plataforma, no por tipo: no puede haber una academia y un
   artista con el mismo nombre.
3. Al crear la cuenta se guarda `slug_locked_at` en el perfil. A partir de ahi el panel
   muestra el nombre publico y el slug en solo lectura: para cambiarlos hay que solicitarlo.
   Es el mismo criterio que ya se aplicaba al nombre de cuenta.
4. El tipo de espacio **tampoco se cambia desde el panel**: se muestra en solo lectura. No es
   una preferencia visual, decide el prefijo de la URL publica, el directorio en el que
   aparece el miembro y, en el caso de las academias, un modulo entero con alumnos, cursos y
   matriculas colgando de el. `member_profile_from_input()` conserva el tipo guardado e
   ignora lo que llegue por formulario; solo el alta, que aun no tiene tipo previo, lo fija.

Las cuentas anteriores a esta regla no tienen `slug_locked_at` y conservan el boton
"Guardar URL". La primera vez que lo usan, su URL queda reservada igual que en las altas
nuevas.

### Canonico

Como el slug es unico, el prefijo no forma parte de la identidad: solo dice de que tipo de
espacio se trata. Si se entra por el prefijo equivocado (`/artista/mi-academia`), la pagina
publica responde un 301 al prefijo correcto. Lo resuelven `member_public_path()` y
`member_public_url()`, que son las funciones que hay que usar siempre para construir el
enlace publico de un miembro, en lugar de concatenar `'artista/' . $slug`.

## Funciones previstas

Los miembros podran:

- Acceder a su panel privado.
- Editar su perfil publico.
- Ver un avatar de perfil en la cabecera con menu desplegable.
- Cambiar contrasena desde el area privada.
- Subir o gestionar imagenes.
- Anadir redes sociales.
- Anadir videos.
- Publicar o solicitar publicacion de eventos.
- Ver solicitudes recibidas.
- Contratar servicios digitales.
- Configurar su tarjeta identificativa de miembro.
- Consultar su numero de miembro y codigo `CSF-...`.
- Contratar banners publicitarios asociados a su cuenta.
- Ver el estado de sus servicios contratados.

## Acceso y registro inicial

La plataforma incorpora una primera base de registro y acceso para miembros mediante `registro.php` y `acceso.php`.

Todo usuario registrado entra inicialmente como Miembro simpatizante. Este nivel permite acceder al area privada y ver su tarjeta, pero no activa descuentos.

El registro inicial se simplifica para no frenar el alta. Exige:

- Nombre y apellidos.
- Tipo de espacio: artista, academia, tienda flamenca, pena flamenca, tablao flamenco, festival o profesional flamenco.
- Email unico.
- Contrasena de al menos 8 caracteres.
- Repeticion de contrasena.
- Aceptacion de terminos y condiciones.

El nombre artistico, ciudad, provincia, fotografia principal y curriculum se completan despues desde el area privada.

El acceso redirige al panel privado inicial `panel-usuario.php`, que queda preparado para desarrollar sus modulos por fases.

La recuperacion de contrasena se gestiona mediante enlace temporal enviado por email desde `recuperar-contrasena.php` y restablecimiento en `restablecer-contrasena.php`.

## Panel de miembro

El panel de miembro se organiza con:

- Cabecera con avatar de usuario y menu desplegable para editar perfil, cambiar contrasena y cerrar sesion.
- Sidebar izquierdo oscuro con barra de porcentaje de perfil completo, accion discreta para imprimir curriculum y accesos a Perfil, Pagina web, Tarjeta de miembro, Banners y Seguridad.
- Cabecera privada tipo dashboard con fotografia principal, nombre artistico, tipo de espacio, ubicacion y metricas de estado.
- La fotografia principal se edita desde la propia imagen de cabecera mediante hover, evitando duplicar la misma imagen en el panel.
- Bloque de perfil con datos principales del miembro, sin cabecera secundaria redundante bajo el resumen principal.
- Editor de perfil artistico con tipo de espacio, nombre publico, titular artistico, ubicacion, contacto, redes y fotografia principal.
- El nombre publico y su URL quedan reservados al crear la cuenta: se muestran en solo lectura y para cambiarlos hay que solicitarlo. En cuentas antiguas sin reserva, la URL se guarda con un boton propio, valida duplicados y queda reservada al guardarse.
- La vista previa de la URL publica cambia de prefijo al cambiar el tipo de espacio (`/artista/`, `/academia/`, `/asociacion/`...).
- Editor de curriculum artistico con formacion, experiencia escenica, docencia, actuaciones destacadas, premios, repertorio, disponibilidad y notas privadas.
- Configuracion de pagina web de una sola pagina con cabecera, galeria de hasta 9 imagenes, videos, eventos, actualidad y contacto.
- La pagina publica mostrara en su menu interno solo las secciones con contenido guardado, manteniendo la cabecera siempre visible.
- Las cabeceras de seccion de la pagina publica muestran solo el nombre de la seccion, centrado y en tamano grande, sin numeracion ni antetitulo.
- La galeria publica muestra hasta 3 fotos en rejilla y pasa a carrusel con flechas, puntos y gesto tactil cuando hay 4 o mas.
- Cada foto de la galeria y el visor ampliado incluyen botones para compartir en WhatsApp, Facebook, X y Telegram, compartir con el menu nativo del movil y copiar el enlace. El enlace compartido (`?foto=N`) abre esa foto ampliada al entrar.
- Control de visibilidad por bloques para decidir que datos se publican y que datos quedan privados.
- Bloques repetibles con boton para anadir nuevas entradas sin limite fijo inicial.
- Fechas en formacion, experiencia, docencia, actuaciones y premios para permitir orden cronologico ascendente o descendente.
- Formacion y experiencia permiten activar/desactivar la seccion completa, activar/desactivar cada entrada y ordenar entradas de forma manual.
- Boton para imprimir o guardar el curriculum en PDF desde el navegador.
- La plantilla PDF debe ser compacta, aprovechar el ancho del papel y mantener la foto en color.
- La cabecera del PDF usa el titular artistico como H1, puede tener imagen de fondo personalizada y no muestra el tipo de espacio.
- El fondo de cabecera del PDF se cambia desde una previsualizacion clicable.
- Bloque de tarjeta identificativa con imagen a pantalla completa, nombre y nivel de membresia visible.
- La tarjeta incluye un QR visible con enlace a la tarjeta digital del miembro, preparado para evolucionar como pase de acceso a eventos con invitacion confirmada.
- Bloque de banners para preparar la compra de espacios publicitarios mediante Stripe.

## Tarjeta de miembro

La tarjeta de miembro sera una tarjeta visual tipo tarjeta de visita.

Datos previstos:

- Imagen de fondo al 100%.
- Nombre visible del miembro.
- Especialidad o titular artistico.
- Nivel visible: Miembro simpatizante o Miembro VIP.
- Numero de miembro unico.
- Codigo de descuento unico con formato `CSF-...`.
- Sello redondo de Con Sabor Flamenco en pequeno.

Regla principal:

- El miembro simpatizante no tiene acceso a descuentos.
- Solo el Miembro VIP, tras pago confirmado de la membresia anual de 80 euros, podra usar descuentos.
- En la tarjeta con bailaora, los datos se muestran arriba a la izquierda.
- En la tarjeta con bailaor, los datos se muestran arriba a la derecha.
- En la tarjeta con bailaora, el sello se coloca abajo a la izquierda.
- En la tarjeta con bailaor, el sello se coloca abajo a la derecha.

## Requisitos minimos del espacio

Cada miembro debe tener un espacio publico minimo desde el registro. Para considerarse completo, el perfil debe incluir:

- Tipo de espacio.
- Nombre artistico.
- Ciudad.
- Provincia.
- Al menos una fotografia principal.
- Al menos una entrada de formacion, experiencia o actuacion.

## Curriculum artistico

El curriculum artistico debe permitir registrar:

- Formacion flamenca: periodo, centro o maestro, disciplina y profesor/a referente.
- Experiencia artistica: periodo, rol, compania o proyecto y lugar.
- Docencia: periodo, academia o entidad, asignatura/nivel y lugar.
- Actuaciones destacadas: ano, evento o festival, tablao/teatro/espacio y ciudad.
- Premios y reconocimientos: ano, titulo y entidad.
- Repertorio y palos: palo/estilo y notas.
- Redes sociales y enlaces: plataforma, URL y descripcion.
- Datos profesionales: trayectoria, disponibilidad, web, Instagram y contacto.
- Opcion para ocultar los datos profesionales en la impresion PDF sin eliminarlos del perfil.
- Notas privadas no publicables.
- Pie de pagina del PDF con la marca `Creado con consaborflamenco.com`.

Las secciones Formacion flamenca, Experiencia artistica, Docencia, Actuaciones destacadas y Premios y reconocimientos deben funcionar como entradas editoriales del curriculum. Cada entrada con contenido tendra titulo y descripcion obligatorios, imagen opcional y campos especificos para fecha, entidad, lugar o maestro segun corresponda.

La mayoria de bloques deberan tener control de visibilidad para decidir si aparecen en el perfil publico o quedan como informacion privada del miembro.

## Banners del miembro

Cada miembro podra contratar banners desde su panel privado.

Campos previstos:

- Titulo.
- URL de destino.
- Imagen del banner.
- Fecha de inicio de publicacion.
- Fecha de fin de publicacion.
- Fecha de inicio de contratacion.
- Fecha de fin de contratacion.
- Estado de pago.
- Estado de visibilidad.

El banner solo se vera en la web si el pago esta confirmado, el estado es activo y las fechas son validas.
Mientras no exista una contratacion activa, el panel no mostrara campos de configuracion de banner. Las fechas de inicio y fin de contratacion se elegiran durante el flujo de compra o activacion.

## Perfil publico

Cada miembro podra tener un perfil publico adaptado a su tipo de actividad. El perfil debera servir como ficha profesional dentro del directorio flamenco.

## Servicios digitales

Los miembros podran contratar servicios digitales ofrecidos por la plataforma. El area privada debera mostrar el estado de cada servicio contratado.

## Reglas y decisiones

- El area de miembros sera privada.
- Cada miembro solo podra gestionar su propia informacion.
- Los perfiles publicos deberan ser revisables desde administracion.
- Los servicios contratados deberan tener estados claros.
- El area privada requerira sesion iniciada.
- La recuperacion de contrasena no debe revelar si un email esta registrado.
- El codigo de tarjeta debe validarse siempre contra el estado del miembro antes de aplicar descuentos.
- Los banners contratados no deben mostrarse si no estan pagados, activos y dentro de fecha.

## Pendiente de definir

- Campos especificos por tipo de miembro.
- Estados de perfil publico.
- Estados de servicios contratados.
- Flujo de revision de eventos.
- Limites de imagenes y videos.

## Historial de cambios

- 2026-06-08: Documentada el area privada de miembros.
- 2026-06-23: Documentado el flujo inicial de registro, acceso, recuperacion de contrasena y panel privado base.
- 2026-06-23: Documentados el panel de miembro, tarjeta identificativa, codigo de descuento y banners contratables.
- 2026-06-24: Definido Miembro simpatizante como nivel inicial tras registro y Miembro VIP como nivel con acceso a descuentos.
- 2026-06-24: Anadidos los requisitos minimos de perfil artistico y fotografia principal obligatoria.
- 2026-06-24: Modernizado el area de usuario con cabecera tipo dashboard, sidebar oscuro y paneles de edicion mas actuales.
- 2026-06-24: Aclarado que los descuentos no estan activos para simpatizantes y requieren membresia VIP anual de 80 euros.
- 2026-06-25: Convertido el perfil en curriculum artistico con secciones repetibles, visibilidad publica/privada y boton de impresion PDF.
- 2026-06-25: Ajustadas las secciones repetibles para anadir filas bajo demanda, ordenar por fecha e incluir redes sociales.
- 2026-06-25: Ocultados los campos de banner mientras no haya contratacion activa, evitada la recarga al cambiar el diseno de tarjeta y compactado el formato PDF.
- 2026-06-25: Anadidos titulo, descripcion obligatoria e imagen opcional a las entradas principales del curriculum artistico.
- 2026-06-25: Anadidos titular artistico, especialidades y sello redondo de marca a la tarjeta de miembro.
- 2026-06-28: Simplificado el registro a tipo de espacio, email, contrasena, repeticion y terminos; el perfil completo se rellena desde el panel privado.
- 2026-06-29: Corregida la persistencia de fotografia principal aunque el perfil siga pendiente y refinado el editor de experiencia profesional con mas controles de formato.
- 2026-06-29: Bloqueado el acceso al area de usuario hasta verificar email y revisado el formato de impresion del curriculum con cabecera directa, descripcion limpia, fechas compactas y sello de marca en el pie.
- 2026-06-29: Ajustado el flujo de verificacion para que un miembro sin email validado no cree sesion ni aparezca como logueado; la pantalla pendiente permite reenviar el enlace por email.
- 2026-06-30: Retirados los campos Especialidades y Descripcion breve publica del panel, la tarjeta y el PDF; ya no cuentan para completar el perfil.
- 2026-06-30: Anadido nombre y apellidos al registro, movida la bienvenida a la verificacion correcta, eliminada la imagen duplicada del perfil y compactados los controles de PDF por seccion.
- 2026-06-30: Movido el QR de tarjeta a la cabecera del panel, corregido el editor enriquecido con fuente/peso por defecto y preparada la impresion exclusiva de la tarjeta de miembro.
- 2026-07-03: Refinado el aspecto visual del area de usuario con sidebar mas sobrio, cabecera mas editorial, tarjetas limpias, formularios mas elegantes y QR menos invasivo.
- 2026-07-10: Corregida la persistencia de fotografia principal y fondo de cabecera del curriculum con guardado automatico al seleccionar imagen y sincronizacion directa en la tabla de miembros.
- 2026-07-10: Anadida edicion del nombre de usuario (cuenta) desde el panel y uso de foto principal en el avatar de cabecera.
- 2026-07-10: Refinada la pagina publica `/artista/{slug}` como microsite del miembro, con assets compatibles con URL limpia, hero cuidado, galeria/contacto y footer propio.
- 2026-07-11: Reordenada la pagina publica del miembro con menu superior sticky, fotografia de perfil como marca, hero limpio sin datos redundantes ni botones y navegacion adaptable a movil.
- 2026-07-11: Reforzada la resolucion de imagenes en `/artista/{slug}` para evitar rutas rotas bajo `/artista/assets`, limpiar prefijos antiguos y mantener fondo de reserva.
- 2026-07-11: Ajustado el menu publico para usar la imagen de cabecera del perfil como marca visual y anadir enlace `Inicio` hacia la pagina principal.
- 2026-07-13: Modernizada la vista de perfil del area de miembro con sidebar mas ancho, bloques de identidad/datos mas elegantes, botones simplificados y responsive reforzado.
- 2026-07-13: Reforzado el guardado real de imagenes del perfil: foto principal, fondo e imagenes de articulos se suben al servidor, y las carpetas runtime quedan preparadas con proteccion e ignore.
- 2026-07-14: Redisenadas las secciones Identidad artistica y Datos de perfil e imagen con cabecera editorial, campos mas limpios y URL publica integrada.
- 2026-07-14: Convertida Identidad artistica en una composicion con campos y previsualizacion lateral del perfil publico, integrando el guardado dentro del propio bloque.
- 2026-07-14: Reforzada la persistencia real de la fotografia principal: la columna `miembros.foto_principal_path` pasa a ser la fuente canonica, la subida automatica confirma la ruta en base de datos y la interfaz evita mostrar rutas de imagen inexistentes.
- 2026-07-14: Cerrada la persistencia de imagenes de entradas del curriculum: cada articulo conserva su `image_path`, se limpia si el archivo no existe y el guardado confirma que las rutas quedan dentro de `miembros.perfil_json`.
- 2026-07-14: Simplificada la identidad artistica retirando la vista publica redundante, convirtiendo la URL publica en boton de acceso, bloqueando el nombre de cuenta una vez reservado y normalizando espacios del slug a guiones.
- 2026-07-14: Reforzado el guardado de imagenes de articulos del curriculum con subida AJAX previa, ruta oculta por entrada y guardado posterior del perfil completo; ajustada la pagina publica con banda negra centrada para el nombre artistico.
- 2026-07-14: Cambiada la gestion de fotografias antiguas con ruta rota para limpiar automaticamente `main_photo_path` en BD y evitar errores rojos persistentes.
- 2026-07-14: Sustituida la cabecera simple de la pagina publica por un slider configurable de 3 imagenes con titulo, descripcion y CTA opcional desde el apartado Pagina web.
- 2026-07-14: Movido el almacenamiento de nuevas imagenes de usuario a runtime persistente fuera del repositorio (`../csf-uploads` o `CSF_UPLOADS_DIR`) y servido por `media.php` para que los despliegues Git no borren fotos de perfil, cabeceras, slides o articulos.
- 2026-07-27: Anadidas las secciones Videos y Actualidad a la pagina publica del miembro, con alta, edicion y eliminacion desde el apartado Pagina web.
- 2026-07-30: Simplificadas las cabeceras de seccion de la pagina publica (solo el rotulo, centrado y mayor), convertida la galeria en carrusel a partir de 4 fotos, anadidos botones de compartir por foto y en el visor, y revisada la vista completa para movil.
- 2026-08-13: La pestana Perfil se organiza en tarjetas: "Mi perfil" con todos los datos del espacio y una tarjeta por seccion del curriculum (Formacion, Experiencia profesional y seccion personalizada), cada una con su listado de entradas, botones Ver / Editar / Borrar y un panel lateral derecho para crear y modificar. La cabecera del panel muestra el tipo de miembro y el nombre publico en lugar del titulo generico. Retiradas las notas privadas del formulario.
- 2026-08-13: El panel abre en una portada de tarjetas (nombre, imagen y descripcion) que llevan a cada pantalla: Mi perfil, Formacion, Experiencia profesional, la seccion personalizada, Pagina web, Tarjeta de miembro, Banners, Seguridad y, si procede, Mi academia y Mis clases. Cada seccion del curriculum muestra indicadores de sus articulos (entradas, activas en el PDF, con imagen y periodo) sobre el listado. Las academias no tienen apartado Pagina web: su web publica es la ficha `/academia/{slug}`.
- 2026-08-23: Anadido el apartado "Inicio": presentacion por articulos (ano, titulo, descripcion, imagen opcional, orden y visible) que abre la microweb publica debajo del hero y encabeza su menu. Se gestiona desde una pantalla propia del panel, con listado y acciones Ver / Editar / Ocultar / Borrar, y se guarda en `perfil_json` como `intro_articles`. Los articulos ocultos siguen en el panel pero no salen en la web; si no hay ninguno visible, la web pasa directamente del hero a la galeria. No disponible para academias, que tienen su propia ficha publica.
- 2026-08-23: El panel se reorganiza como cuadro de mando sin barra lateral: cabecera con identidad y estado, portada de tarjetas en tres bloques (herramientas, contenido de la web, servicios y cuenta) y migas de pan en las pantallas interiores. Curriculum y pagina web quedan separados: "Mi curriculum" agrupa Formacion, Experiencia profesional, Premios y reconocimientos y la seccion personalizada, mas los ajustes del PDF y su descarga; "Mi pagina web" reune solo los contenidos publicos de la microweb. "Mi perfil" muestra bloques de resumen que despliegan el formulario al pulsar Editar.
