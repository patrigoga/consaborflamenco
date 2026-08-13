# Historial de Cambios

## Descripcion

Este documento registra los cambios principales realizados en Con Sabor Flamenco durante el desarrollo por fases.

## Objetivo del documento

Mantener una trazabilidad clara de decisiones, avances y entregas relevantes del proyecto.

## Entradas

### 2026-06-08 - Fase 0 - Documentacion inicial

- Creada la documentacion inicial del proyecto.
- Definida la vision general de la plataforma.
- Definidas areas principales: publica, administracion, miembros, setters e inbox.
- Definido el sistema de codigos promocionales por setter.
- Definidas fases iniciales de desarrollo.

### 2026-06-08 - Fase 0 - Instrucciones para agentes IA

- Creado el archivo AGENTS.md en la raiz del proyecto.
- Definido AGENTS.md como archivo principal de instrucciones para agentes IA dentro del proyecto.
- Documentadas reglas generales para futuras modificaciones, fases, areas del sistema y mantenimiento de documentacion.

### 2026-06-08 - Fase 1 - Estructura visual publica

- Inicio de Fase 1.
- Creacion del header publico.
- Creacion de la home tipo revista.
- Creacion del footer publico.
- Preparacion visual inicial del proyecto.

### 2026-06-21 - Fase 2 - Publicidad local mediante banners

- Incorporado un sidebar publicitario derecho en la estructura publica.
- Anadido un popup accesible para seleccionar provincia en la primera visita.
- Guardada la preferencia de provincia en el dispositivo y preparado el enlace con el perfil registrado.
- Implementada la seleccion de banners por provincia y categoria activa.
- Anadidas las categorias Festivales y Concursos al navbar publico.
- Excluidas Servicios y Contacto como categorias publicitarias.
- Preparado el respaldo con campanas nacionales.
- Documentados el modelo de datos, la administracion y las decisiones tecnicas de publicidad.
- Reorganizada la composicion visual para mantener una jerarquia editorial clara.
- Simplificados el header, el hero y el sidebar a dos formatos publicitarios.
- Separada la presentacion publicitaria de escritorio y movil para evitar bloqueos o desbordamientos.
- Ampliado el hero a todo el ancho bajo el menu superior.
- Sustituido el resumen generico por un ranking visual de cuatro contenidos comunitarios.
- Preparado el ranking para artistas, academias, eventos y espacios ordenados por visitas reales.
- Convertido el ranking en cuatro tarjetas con imagen principal, titulo, descripcion corta y boton Ver mas.
- Aclarado que el ranking compara globalmente todas las categorias sin cuotas por tipo.
- Anadidas cuatro imagenes editoriales optimizadas para la demostracion visual.
- Recolocado el menu principal en la misma fila que el logo en escritorio.
- Alineados el contenido principal del hero y las tarjetas desde la misma altura superior.
- Anadida Fotografia al menu, a la home y a las categorias publicitarias.
- Reorganizado el hero en tres franjas: texto, tarjetas horizontales y acciones alineadas a la derecha.
- Centrado y elevado el bloque de titulo principal dentro del hero.
- Anadido menu hamburguesa responsive para tablet y movil.
- Reordenados alfabeticamente los enlaces del menu principal.
- Aplicadas excepciones de navegacion: Inicio primero; Servicios y Contacto al final.
- Anadida la categoria Flamenco con acordeon para Historia, Palos del flamenco y Llaves de Oro.
- Recolocada la categoria Flamenco inmediatamente despues de Inicio.
- Redisenada la seccion Revista con una composicion editorial moderna y fotografica.
- Duplicado el ancho del rail publicitario de escritorio a 560 px.
- Ampliado el contenedor general y reducidos los margenes laterales para aprovechar la pantalla.
- Extendido el lenguaje visual de Revista al resto de secciones publicas.
- Anadidos fondos de color y cabeceras editoriales de ancho completo por seccion.
- Modernizados eventos, directorios, perfiles y servicios con tarjetas consistentes.
- Ampliada la llamada a miembros al ancho general y reducida su separacion con el footer.
- Unificadas todas las secciones con rankings visuales de tres posiciones.
- Definido el primer puesto como tarjeta principal y segundo y tercero como tarjetas secundarias.
- Anadidas etiquetas visibles para resultados votados y promociones pagadas.
- Reordenadas Fotografia como penultima seccion y Flamenco como ultima.
- Anadida Moda con submenu para Ropa, Calzado, Complementos y Moda infantil.
- Anadido el ranking editorial y la categoria publicitaria de Moda.
- Convertida Academias en acordeon con acceso a Cursos presenciales, online e intensivos.
- Anadida la seccion Cursos con ranking y publicidad independientes.
- Recolocada Revista inmediatamente despues de Flamenco en el menu principal.

### 2026-06-22 - Fase 2 - Paginas independientes y limpieza de portada

- Comentados temporalmente Concursos en el menu y en la portada, conservando su codigo.
- Retirada la seccion Servicios destacados de la home.
- Creada `servicios.php` como pagina publica propia con ranking, publicidad y navegacion responsive.
- Retirada la seccion Historia, Palos del flamenco y Llaves de Oro de la home.
- Creada `flamenco.php` como pagina propia con accesos a sus tres contenidos.
- Mantenida Moda flamenca con sus tarjetas, subcategorias y estilo editorial.
- Actualizados los enlaces de navegacion y del footer hacia las nuevas paginas.
- Anadidas esquinas redondeadas al footer.

### 2026-06-23 - Fase 2 - Acceso directo a secciones publicas

- Anadido un boton "Entrar en esta seccion" en la cabecera de cada bloque publico de la portada.
- Anadido un bloque de acceso a Flamenco en la portada, enlazando a su pagina propia.
- Creadas paginas propias para Revista, Academias, Cursos, Artistas, Eventos, Festivales, Penas, Tablaos, Moda y Fotografia.
- Reutilizados en las nuevas paginas el header, la navegacion responsive, rankings, publicidad por provincia y footer.
- Actualizados los enlaces principales de navegacion para apuntar a paginas publicas independientes.

### 2026-06-23 - Fase 2 - Conversion de vistas publicas a PHP

- Renombradas todas las paginas publicas de `.html` a `.php`.
- Actualizados los enlaces internos, botones de seccion, submenus y footers hacia las nuevas rutas `.php`.
- Mantenida la estructura visual existente para preparar la incorporacion progresiva de logica de negocio.

### 2026-06-23 - Fase 3 - Registro, acceso y recuperacion de contrasena

- Creada la capa inicial `app/` para sesiones, CSRF, hash de contrasenas, usuarios y tokens de recuperacion.
- Creados `registro.php`, `acceso.php`, `recuperar-contrasena.php` y `restablecer-contrasena.php`.
- Anadida aceptacion obligatoria de terminos y condiciones en el registro.
- Creada la pagina `terminos-condiciones.php` y enlazada desde el footer.
- Creados `panel-usuario.php` y `panel-admin.php` como bases protegidas para desarrollar en fases posteriores.
- Anadido envio de email de recuperacion con respaldo local en `storage/mail_outbox.log` si el servidor local no tiene correo configurado.

### 2026-06-23 - Fase 3 - Fondo visual continuo

- Actualizado el fondo global de la web para que el degradado oscuro de cabecera continue hacia abajo y se funda con el blanco calido del contenido.

### 2026-06-23 - Fase 3 - Secciones con esquinas redondeadas

- Unificadas las esquinas redondeadas en hero, cabeceras interiores, secciones de contenido, llamadas a la accion, formularios, legales y footer.
- Anadida una variable global de radio visual para mantener una identidad moderna y consistente en escritorio y movil.
- Anadido espaciado superior entre secciones y borde dorado fino para reforzar una identidad visual premium.

### 2026-06-23 - Fase 3 - Layout comun para paginas internas

- Creado `app/layout.php` como layout compartido para cabecera, menu, footer, selector de provincia y paginas de seccion.
- Refactorizadas las paginas publicas internas para usar el mismo formato visual de Inicio.
- Unificadas las paginas de registro, acceso, recuperacion, paneles y terminos con la cabecera y footer principales.
- Anadida una rejilla reutilizable para tarjetas internas de panel y futuras areas privadas.
- Ajustadas las cabeceras interiores a ancho completo y sin borde dorado para evitar repeticion visual.
- Creado un motivo SVG sutil de fondo para las cabeceras interiores con referencias modernas al flamenco.
- Sustituido el motivo inicial por una composicion mas adulta basada en manton, abanico, roseton de guitarra y trazos abstractos.
- Reorientado el fondo de cabecera hacia siluetas parciales de bailaores, bailaoras y cantaores mediante lineas abstractas.
- Sustituido el SVG manual por una imagen editorial generada con cantaor, bailaora y bailaor insinuados en claroscuro.
- Aplicada la misma imagen editorial al hero de Inicio para reforzar la personalidad visual de la marca.
- Ajustado el hero de Inicio a ancho completo y sin borde para dar mayor impacto visual.
- Eliminado el microfono del cantaor en la imagen editorial de cabecera para mantener una composicion mas limpia y atemporal.

### 2026-06-23 - Fase 5 - Panel de miembro y modelo inicial

- Creado `database/schema.sql` con tablas iniciales para usuarios, miembros, tarjetas, banners, pagos Stripe y articulos.
- Convertido `panel-usuario.php` en un primer panel de miembro con perfil, sidebar, tarjeta identificativa, banners y seguridad.
- Incorporadas las imagenes `tarjeta-bailaor.png` y `tarjeta-bailaora.png` como fondos configurables de tarjeta de miembro.
- Anadido menu de perfil con avatar y desplegable en la cabecera comun para usuarios autenticados.
- Conectada la cabecera de Inicio al layout comun para mostrar tambien el menu de perfil si hay sesion iniciada.
- Documentadas las reglas de codigo `CSF-...`, estado activo/inactivo y visibilidad de banners pagados.

### 2026-06-23 - Fase 1 - Mega menu principal

- Convertidos los submenus de escritorio en mega menus horizontales que se abren al pasar el cursor.
- Mantenido el comportamiento de acordeon por click en tablet y movil.
- Anadida transicion visual, apertura hacia abajo y derecha, y area segura para evitar cierres al mover el cursor.

### 2026-06-23 - Fase 1 - Favicon de marca

- Creado `assets/images/favicon.svg` con identidad CSF, fondo oscuro y gesto rojo flamenco.
- Enlazado el favicon en la portada y en el layout comun de paginas internas.

### 2026-06-23 - Fase 1 - Limpieza de codificacion de textos

- Revisados los textos visibles para eliminar conversiones incorrectas de acentos y simbolos.
- Reparado `index.php` para usar caracteres UTF-8 literales aceptados por la web.
- Anadido control de escaneo para detectar restos de mojibake en archivos de texto.

### 2026-06-24 - Fase 3 - Nombre de base de datos

- Definido `consaborflamenco` como nombre de la base de datos principal.
- Creado `database/create_database.sql` con creacion del esquema en `utf8mb4`.

### 2026-06-24 - Fase 3 - Mejora visual de registro

- Anadida cabecera visual a `registro.php` con titulo mas compacto y fondo editorial.
- Creada e integrada `assets/images/auth/registro-flamenco.png` como imagen lateral de registro.
- Revisados los textos del formulario de registro con acentos UTF-8 literales.
- Compactado el formulario de registro y ajustada la imagen lateral para adaptarse a su altura.

### 2026-06-24 - Fase 3 - Mejora visual de acceso

- Aplicado a `acceso.php` el mismo patron visual compacto de registro.
- Creada e integrada `assets/images/auth/acceso-flamenco.png` como imagen lateral de acceso privado.
- Revisados los textos del formulario de acceso con acentos UTF-8 literales.

### 2026-06-24 - Fase 5 - Membresia simpatizante y tarjeta

- Definido el registro inicial como Miembro simpatizante en la cuenta local.
- Sustituida la etiqueta visual ACTIVO por Miembro simpatizante en el panel y la tarjeta.
- Reservados los descuentos y el codigo visible para Miembro VIP.
- Recolocados los datos de la tarjeta: arriba a la izquierda para bailaora y arriba a la derecha para bailaor.
- Actualizado el esquema SQL para que el estado inicial de miembros sea `SIMPATIZANTE`.
- Cambiada la ruta de sesiones PHP a `storage/sessions` para evitar permisos externos a la carpeta del proyecto.
- Ampliado el registro con tipo de espacio, nombre publico, descripcion, ciudad, provincia y fotografia principal obligatoria.
- Anadido editor de perfil artistico en el panel de miembro para artista, academia, tienda, pena, tablao, festival y profesional flamenco.
- Creada la carpeta `assets/uploads/member-photos` para almacenar fotografias principales validadas.
- Redisenada el area de usuario con cabecera privada, sidebar oscuro, metricas de perfil y paneles visuales mas modernos.
- Ensanchada el area privada hasta 1660 px y reajustadas las metricas para evitar solapes en pantallas medianas.
- Corregida la metrica de descuentos para mostrar "No activos" en miembros simpatizantes y anadida la referencia de VIP anual por 80 euros.

### 2026-06-25 - Fase 5 - Curriculum artistico avanzado

- Sustituido el formulario simple de perfil por un editor de curriculum artistico con identidad, biografia, formacion, experiencia, docencia, actuaciones, premios y repertorio.
- Anadidos controles para decidir que bloques se mostraran publicamente y cuales quedaran privados.
- Incorporado boton para imprimir o guardar el curriculum como PDF usando una plantilla de impresion limpia.
- Retiradas las metricas de perfil, membresia, descuentos y VIP anual de la cabecera del area de usuario.
- Preparado `database/schema.sql` con la tabla `miembros_curriculum_items` para almacenar items repetibles de curriculum.
- Ajustada la tarjeta de miembro con tipo de espacio, nombre reposicionado, estado en el borde inferior y marca `consaborflamenco.com`.
- Sustituidas las filas fijas por botones para anadir formacion, experiencia, docencia, actuaciones, premios, repertorio y redes sociales.
- Anadidas fechas y selector de orden cronologico en los bloques profesionales.
- Mejorada la plantilla PDF con foto en color y pie `Creado con consaborflamenco.com`.
- Ocultados los campos de banner cuando no hay banner activo o contratado.
- Evitado el salto al cambiar el diseno de tarjeta con previsualizacion instantanea sin recargar la pagina.
- Compactado el PDF para aprovechar mejor el ancho del papel.
- Convertidas las entradas principales del curriculum en bloques con titulo, descripcion obligatoria e imagen opcional.
- Separados titulo y descripcion en filas completas para mejorar la lectura del editor de curriculum.
- Reorganizadas las entradas con imagen a la izquierda, contenido a la derecha y previsualizacion inmediata al seleccionar archivo.
- Anadidos titular artistico, especialidades y pegatina redonda de marca en la tarjeta de miembro.

### 2026-06-25 - Fase 2 - Portada convertida en landing guiada

- Transformada `index.php` en una landing enfocada en conversion sin alterar cabecera, menu, fondo ni paleta de color.
- Sustituido el bloque de tarjetas y secciones extensas por una narrativa principal con mensaje claro y llamadas a la accion.
- Anadida animacion de entrada del titulo principal desde la izquierda para reforzar impacto visual inicial.
- Implementado un wizard de 3 pasos dentro del hero para explicar la propuesta de valor con menos friccion.
- Mantenido el footer y la compatibilidad con selector de provincia y scripts comunes de navegacion/publicidad.
- Anadido `assets/js/landing-home.js` para controlar la animacion y la logica del wizard de portada.

### 2026-06-25 - Fase 2 - Refinamiento de landing tras revision visual

- Sustituido el wizard por una portada mas limpia y directa para reducir friccion en la primera visita.
- Reforzada la cabecera con un bloque de valor rapido, perfiles objetivo visibles y llamadas a la accion inmediatas.
- Anadida una seccion de recorrido en 3 pasos con animaciones suaves de aparicion al hacer scroll.
- Anadida una seccion especifica de perfiles (artistas, academias, penas/tablaos y festivales/eventos).
- Simplificado `assets/js/landing-home.js` para mantener animacion del titulo, rotador de mensaje y reveals visuales.

### 2026-06-25 - Fase 2 - Refuerzo visual de portada con tarjeta editorial

- Anadida una tarjeta destacada en el hero de `index.php` con imagen flamenca existente del proyecto.
- Incorporada una explicacion breve orientada a conversion y enlace directo a perfiles de artistas.
- Aplicados estilos especificos para equilibrar impacto visual en escritorio y movil sin romper la estructura actual.

### 2026-06-25 - Fase 2 - Limpieza de portada por feedback

- Retirados de `index.php` los bloques de copy, botones y secciones de explicacion que no encajaban con la direccion visual deseada.
- Simplificada la home a una cabecera minima con titulo principal, manteniendo menu, fondo e identidad de color.
- Ajustado `assets/css/styles.css` para que el hero quede centrado y sin columna vacia tras la limpieza.

### 2026-06-25 - Fase 1 - Footer a ancho completo

- Ajustado `assets/css/styles.css` para que `site-footer` ocupe todo el ancho de pantalla.
- Eliminado el limite de ancho compartido con secciones de contenido para el pie de pagina.
- Adaptado el footer a formato de banda horizontal continua manteniendo su estilo visual.

### 2026-06-25 - Fase 2 - Slider narrativo estatico en portada

- Recolocado el titular principal de `index.php` mas cerca del navbar para priorizar jerarquia visual.
- Anadida una banda full-width con slider de 3 slides para empezar la historia de la plataforma.
- Creada la carpeta `slider/` con `slider01.php`, `slider02.php` y `slider03.php` como fuentes estaticas editables.
- Definidos layouts alternos de imagen izquierda/derecha/izquierda y bloque de cuatro botones de color por slide.
- Implementada navegacion de slider (anterior, siguiente, puntos) en `assets/js/landing-home.js`.

### 2026-07-02 - Fase 6 - Landings por usuario conectadas extremo a extremo

- Conectada la sincronizacion de miembros PHP con el microsite de artista mediante `app/artist_claim.php` desde la capa de persistencia en `app/auth.php`.
- Anadida resolucion de slug unico por miembro en guardado a base de datos para evitar colisiones entre perfiles publicos.
- Aplicada sincronizacion selectiva al microsite solo cuando cambian slug o nombre publico, evitando llamadas innecesarias en inicios de sesion.
- Reforzado `artist-microsite/src/pages/api/artists/claim.ts` para usar `externalUserId` como identidad estable, controlar colisiones de slug y evitar reclamaciones cruzadas.
- Corregido el import de Prisma en `artist-microsite/src/pages/api/artists/[slug].ts`.
- Sustituida la pagina `artistas.php` por un directorio dinamico de miembros activos con enlaces reales a paginas personales de artista.
- Actualizados los enlaces del ranking de artistas en `assets/js/section-rankings.js` para llevar al directorio publico real.
- 2026-07-03: Corregida la ficha publica por slug para buscar miembros sin provocar error 500 cuando falta la columna en produccion.
- 2026-07-03: Anadido guardado independiente de URL publica en el panel de usuario con validacion de duplicados y migracion del campo `miembros.slug`.
- 2026-07-04: Retirada la cabecera secundaria "Ficha artistica" del area de usuario para evitar redundancia con el resumen superior.
- 2026-07-04: Movidos el estado de perfil y la impresion del curriculum al sidebar izquierdo como acciones rapidas.
- 2026-07-04: Anadido QR visible dentro de la tarjeta de miembro, enlazado a la tarjeta digital y preparado para futura validacion de invitaciones a eventos.
- 2026-07-04: Sustituido el estado de perfil por una barra porcentual en el sidebar y convertido "Imprimir curriculum PDF" en accion mas discreta.
- 2026-07-04: Anadida la seccion "Pagina web" al panel de miembro para configurar cabecera, galeria de hasta 9 imagenes y contacto.
- 2026-07-04: Convertida `artista.php` en una pagina publica one-page con menu interno que solo muestra secciones con contenido.
- 2026-07-09: Anadida ruta limpia `/artista/{slug}` mediante `.htaccess`, actualizados los enlaces publicos y retirada la cabecera/footer global de las paginas personales.

### 2026-07-10 - Fase 5 - Persistencia de imagenes y datos de usuario en panel

- Corregido `panel-usuario.php` para soportar guardado de imagenes con accion dedicada `update_profile_images`.
- Anadido guardado automatico al seleccionar fotografia principal o fondo de cabecera de curriculum.
- Reforzada la persistencia de imagenes del perfil sincronizando `foto_principal_path` y `perfil_json` directamente en `miembros` tras cada guardado.
- Anadida edicion del nombre de usuario (cuenta) desde el panel, manteniendo email de acceso en solo lectura.
- Actualizado `app/layout.php` para mostrar foto de perfil en el avatar de cabecera cuando existe.
- Corregidas las rutas absolutas de assets para que `/artista/{slug}` cargue CSS, JS e imagenes subidas correctamente.
- Redisenada la vista publica `artista.php` con estructura de microsite, hero editorial, galeria/contacto mas cuidados y footer propio.

### 2026-07-11 - Fase 6 - Refinamiento de pagina publica de miembro

- Reordenada `artista.php` para usar un menu superior sticky con la fotografia del perfil como logotipo del artista.
- Simplificado el hero publico retirando marca CSF visible, tipo/titular artistico, ubicacion, botones rojos y tarjeta lateral.
- Ajustada la navegacion de la pagina publica para escritorio y movil, manteniendo enlaces internos solo a secciones con contenido.
- Reforzada la normalizacion de rutas de imagenes publicas para resolver correctamente `assets/...` desde `/artista/{slug}`, limpiar prefijos antiguos y usar fondo de reserva si falla la imagen personalizada.
- Movido el menu publico junto a la identidad del artista, usando la imagen de cabecera como marca en la barra superior e incorporando enlace `Inicio` a la pagina principal.

### 2026-07-13 - Fase 5 - Refinamiento visual del area de miembro

- Eliminados los botones duplicados `Guardar curriculum` e `Imprimir / guardar PDF` del editor principal de perfil.
- Anadida una barra moderna de guardado como accion unica del formulario de perfil.
- Ampliado y refinado el sidebar izquierdo del area de usuario, con mejor jerarquia visual y adaptacion responsive.
- Modernizadas las secciones de Identidad artistica y Datos de perfil e imagen con tarjetas, campos mas limpios y comportamiento movil mejorado.
- Reforzada la persistencia de imagenes: foto principal y fondo de cabecera se suben con guardado aislado, las imagenes de articulos guardan automaticamente el perfil completo, y las carpetas de uploads quedan protegidas e ignoradas como runtime.

### 2026-07-14 - Fase 5 - Mejora estetica de identidad de miembro

- Redisenadas las tarjetas de Identidad artistica y Datos de perfil e imagen con cabecera editorial oscura, mejor jerarquia tipografica, campos mas ligeros y URL publica integrada.
- Anadida una previsualizacion lateral del perfil publico dentro de Identidad artistica e integrado el boton Guardar perfil en esa composicion.

### 2026-07-18 - Reorganizacion de menu, paginas y filtros publicos

- Actualizado el navbar principal para que el submenu Flamenco apunte a `historia-flamenco.php`, `palos-flamenco.php` y `llaves-de-oro.php`.
- Creadas las paginas individuales de Historia, Palos del flamenco y Llaves de Oro reutilizando `page_head()`, `page_header()`, `section_page()`, `page_footer()` y `province_modal()`.
- Convertidos `artistas.php` y `academias.php` en directorios con pestanas reales por disciplina (`todos`, `baile`, `cante`, `toque`, `percusion`) y filtrado PHP compatible sin JavaScript.
- Anadido helper compartido `app/directory_helpers.php` para validar disciplinas, renderizar filtros y consultar directorios con parametros preparados.
- Preparada la migracion no destructiva `database/20260718_disciplinas.sql` para normalizar disciplinas y relaciones de artistas/academias en una fase posterior.
- Anadidas categorias de publicidad y rankings para Historia, Palos del flamenco y Llaves de Oro.

### 2026-07-18 - Secciones legales y consentimiento de cookies

- Creadas las rutas `terminos.php`, `aviso-legal.php`, `privacidad.php` y `cookies.php` con contenido obtenido desde base de datos.
- Anadido repositorio `app/legal_repository.php`, endpoint `api/legal-document.php` y modal legal reutilizable con mejora progresiva desde enlaces reales del footer.
- Anadido panel de administracion "Contenido legal" con edicion protegida por CSRF, rol admin, sanitizacion de HTML y versiones anteriores.
- Preparada la migracion no destructiva `database/20260718_legal_documents.sql`.
- Implementado banner/configurador de cookies con categorias necesarias, preferencias, analitica y publicidad, persistido en cookie necesaria versionada.

### 2026-07-18 - Servicios administrables y contacto profesional

- Anadido repositorio `app/site_content_repository.php` para servicios, configuracion de contacto profesional y mensajes de contacto.
- Preparada la migracion no destructiva `database/20260718_services_contact.sql`.
- Ampliado `panel-admin.php` con secciones de Gestion de servicios, Area profesional de contacto y Mensajes de contacto, usando CSRF y validaciones de servidor.
- Convertida `servicios.php` en una pagina publica alimentada por servicios activos, manteniendo la misma ruta.
- Anadidas en la portada las secciones de servicios destacados y contacto profesional, con formulario publico, privacidad, honeypot, rate limit basico y notificacion por correo/log.
- Anadidos estilos responsive para tarjetas de servicios, bloque de contacto, formulario publico y controles administrativos.
- Movido el formulario publico de contacto desde la portada a `contacto.php`, manteniendo la misma gestion administrativa y de mensajes.
- Refinada la estetica de `contacto.php` con estilos especificos para el bloque profesional, campos del formulario, foco, privacidad y composicion responsive.

### 2026-07-18 - Fase 1 redisenado del panel de administracion

- Anadido `app/admin_ui.php` con definicion de secciones, URLs internas, badges de estado e imagen segura para componentes administrativos.
- Reorganizado el sidebar del panel por grupos visuales: Panel, Usuarios, Contenido, Publicidad, Finanzas y Contacto.
- Sincronizada la seccion activa del panel mediante parametro `section=` en la URL y actualizado `assets/js/admin-sidebar.js`.
- Redisenada la vista general con tarjetas accionables y bloques de actividad reciente.
- Separada la gestion de categorias de articulos en su propia seccion.
- Anadida seccion independiente de Comisiones como base para la fase financiera sin crear estados ficticios.
- Anadida una capa visual responsive para sidebar, tarjetas, actividad reciente, paneles compactos y badges de estado.

## Reglas y decisiones

- Registrar cambios por fecha.
- Indicar la fase relacionada cuando corresponda.
- Mantener entradas claras y resumidas.
- No usar este documento como sustituto de la documentacion especifica de cada area.

## Pendiente de definir

- Formato definitivo de versionado.
- Criterio para registrar cambios menores.
- Relacion con commits o releases si se usa Git en el futuro.

## Historial de cambios

- 2026-06-08: Creado el historial de cambios inicial del proyecto.
- 2026-06-08: Registrada la creacion de AGENTS.md como archivo principal de instrucciones para agentes IA.
- 2026-06-08: Registrada la preparacion visual inicial de la Fase 1.
- 2026-06-21: Registrada la base funcional y documental del sistema de publicidad local.
- 2026-06-27: Ajustada la animacion del hero para separar "Con Sabor" en rojo y "Flamenco" en blanco, con entrada desde lados opuestos y slides transparentes desde vertices.
- 2026-06-27: Creada la primera imagen propia del slider como esquema de comunidad flamenca conectado a consaborflamenco.com.
- 2026-06-27: Limpiado el texto interno de las imagenes del slider y creada la segunda imagen sobre presencia web para miembros.
- 2026-06-27: Creada la tercera imagen de llamada al registro y anadidos iconos transparentes a los botones de los sliders.
- 2026-06-27: Sustituidas las pastillas de los sliders por accesos de icono grande con texto inferior.
- 2026-06-27: Ampliados y centrados los iconos de acceso de los sliders para ocupar mejor la columna de texto.
- 2026-06-28: Versionados los scripts de portada y reforzada la inicializacion del slider para evitar bloqueos por cache o recargas restauradas.
- 2026-06-28: Ignorados los archivos runtime de `storage` y mantenida versionada solo la proteccion `.htaccess`.
- 2026-06-28: Conectada la autenticacion inicial a MySQL con bootstrap de tablas, migracion JSON, admin por defecto, registro ligero y panel admin funcional para miembros, setters, articulos y banners.
- 2026-06-29: Separada la configuracion local/produccion mediante `.env`, anadidos defaults de la BD Hostinger y creado `setup-prod-db.php` para preparar produccion.
- 2026-06-29: Ajustado el panel de usuario para guardar fotografia principal en perfiles pendientes, mejorar el editor de experiencia profesional y compactar la imagen del email de verificacion.
- 2026-06-29: Reforzada la verificacion obligatoria de email antes del panel privado y redisenada la salida impresa del curriculum para evitar elementos del panel, conservar texto enriquecido y compactar fechas.
- 2026-06-29: Corregido el acceso de usuarios no verificados para impedir la creacion de sesion tras registro o login y permitir reenvio de verificacion sin estar autenticado.
- 2026-06-30: Ampliado el curriculum del area de usuario con nombre artistico, titular como H1 del PDF, fondo de cabecera personalizable, datos profesionales opcionales en impresion, secciones Formacion/Experiencia activables, entradas activables, orden manual y controles de fuente/tamano en el editor.
- 2026-06-30: Simplificado el perfil artistico retirando el campo Biografia/resumen curricular y usando Descripcion breve publica como texto de perfil en el PDF.
- 2026-06-30: Retirados Especialidades y Descripcion breve publica del area de usuario, tarjeta y PDF, y eliminado su peso en el calculo de perfil completo.
- 2026-06-30: Refinada el area de usuario con edicion de fotografia por hover, fondo de curriculum clicable, controles de seccion mas discretos, articulos con imagen mayor, nombre y apellidos en registro y bienvenida tras verificar email.
- 2026-06-30: Ajustado el editor enriquecido para escribir sin elegir fuente, movido el QR de tarjeta a la cabecera del miembro y anadida impresion especifica de tarjeta digital.
- 2026-06-30: Corregida la carga de `panel-admin.php` en produccion anadiendo la tabla de usos de codigo al bootstrap y haciendo tolerantes las metricas/listados ante tablas pendientes.
- 2026-06-30: Ampliada la vista general del panel admin con KPIs agrupados de comunidad, perfiles, setters, revista, banners, leads, pagos, ingresos y sistema.
- 2026-06-30: Corregida la visibilidad de la vista general del admin para que el sidebar muestre el bloque completo de KPIs y versionado `admin-sidebar.js` para evitar cache antigua.
- 2026-06-30: Permitido que usuarios personalicen el nombre de la seccion custom en su curriculum, agregando input editable en lugar de etiqueta hardcodeada, mejorando la flexibilidad del perfil artistico.
- 2026-07-03: Refinado el area de usuario con una capa visual mas elegante para sidebar, cabecera, tabs, tarjetas, formularios y secciones repetibles.
- 2026-07-14: Corregida la persistencia de la fotografia principal del miembro usando `miembros.foto_principal_path` como fuente canonica, subida asincrona con confirmacion de base de datos y fallback visual cuando la ruta no existe en disco.
- 2026-07-14: Corregida la persistencia de imagenes de articulos del curriculum manteniendo `image_path` por entrada, validando existencia del archivo y confirmando su guardado en `miembros.perfil_json`.
- 2026-07-14: Ajustada Identidad artistica del panel de miembro: eliminada la vista publica duplicada, anadido CTA para abrir la URL publica, bloqueado el nombre de cuenta guardado y normalizado el slug sin espacios.
- 2026-07-14: Anadida subida AJAX previa para imagenes de entradas del curriculum y redisenada la cabecera del microsite publico con banda negra a ancho completo y nombre artistico centrado.
- 2026-07-14: Sustituido el error persistente de fotografia principal inexistente por limpieza automatica de ruta rota en el perfil y base de datos.
- 2026-07-14: Anadido slider de cabecera configurable en la pagina publica del miembro, con tres slides persistidos en `web_page.hero_slides` y CTA opcional por slide.
- 2026-07-14: Cambiado el almacenamiento de nuevas imagenes de usuario a una carpeta runtime externa al repositorio (`../csf-uploads`, configurable con `CSF_UPLOADS_DIR`) y anadido `media.php` para servirlas sin depender de `assets/uploads` versionado.
- 2026-07-27: Anadido el enlace completo a la pagina publica tambien en la seccion "Pagina web" del panel de miembro y sincronizado con los cambios de slug.
- 2026-07-27: Usada la fotografia principal del perfil como imagen superior en la pagina publica del artista y en la tarjeta lateral del panel de miembro.
- 2026-07-27: Anadidas secciones condicionales Videos y Actualidad en la pagina publica del miembro, con gestion desde el panel para insertar, modificar y eliminar contenidos.
- 2026-07-28: Homogeneizado el fondo oscuro de la pagina publica del artista y refinadas Galeria, Eventos, Contacto y footer para mejorar contraste y elegancia.
- 2026-07-28: Ajustada la seccion Eventos del microsite publico a formato de tarjeta compacta y reforzado el fondo unico sin cortes claros entre secciones.
- 2026-07-28: Corregida la colision del ID eventos con fondos globales claros para mantener el microsite publico con fondo oscuro homogeneo.
- 2026-07-28: Aislados los estilos visuales del microsite publico desde artista.php para evitar herencias de fondos y cabeceras globales en Galeria, Eventos y Contacto.
- 2026-07-28: Redisenada la presentacion publica del artista con hero protagonista, secciones proporcionadas, galeria visual, eventos compactos y contacto profesional.
- 2026-07-30: Redisenado el microsite publico del artista con lenguaje editorial oscuro: hoja propia `assets/css/artist-microsite.css` con namespace `ms-`, hero a pantalla completa con pie por slide, bloque de perfil, galeria con lightbox, agenda en lista, contacto con etiquetas legibles y aparicion progresiva al hacer scroll.
- 2026-07-30: Ajustado el microsite publico del artista: retirados tipo de miembro y localidad del hero, eliminada la seccion Perfil, cabeceras de seccion en banda a todo el ancho con titulo centrado y mayor, y pie de pagina con enlaces a las secciones con contenido.
- 2026-07-30: Enriquecidas las cabeceras de seccion del microsite con lenguaje «luz de escenario»: numeracion editorial automatica, filete vertical dorado, cono de luz cenital, grano fino y ornamento con rombo. Unificadas en el helper `artist_render_section_band()`.
- 2026-07-30: Simplificadas las cabeceras de seccion del microsite publico dejando solo el rotulo centrado y a mayor tamano (fuera numeracion y antetitulo), convertida la galeria en carrusel por paginas con flechas, puntos y gesto tactil cuando hay mas de 3 fotos, anadida barra de compartir (WhatsApp, Facebook, X, Telegram, compartir nativo y copiar enlace) sobre cada foto y en el visor ampliado con enlace directo `?foto=N`, y revisado el microsite completo en movil (barra superior en dos filas con redes visibles, titulares escalados, visor y contacto adaptados).
- 2026-07-31: Ajustado el hero del microsite publico a pantalla completa (100vh/100dvh) en todos los tamanos. Anadidos numero de foto, icono de zoom y contador de fotografias a la galeria. Ordenados los eventos de Agenda por fecha y anadido un listado lateral sticky con todas las fechas y scrollspy. Destacada la entrada mas reciente de Actualidad en formato editorial de dos columnas.
- 2026-08-01: Eliminado el subproyecto Next.js `artist-microsite/` (scaffold sin actividad desde el 2026-07-02) por quedar reemplazado por el microsite publico en PHP (`artista.php`). Retirados tambien el codigo muerto de sincronizacion asociado: `app/artist_claim.php`, la funcion `db_sync_member_to_artist_microsite()` y su llamada en `app/auth.php`, y la referencia al subproyecto en `CLAUDE.md`.
- 2026-08-01: Homogeneizado el panel de miembro: unificadas a una misma paleta calida las superficies oscuras que estaban duplicadas en `styles.css` con tonos de azul-petroleo distintos (barra lateral, cabecera del dashboard y leyendas del formulario de Perfil), y alineadas las cabeceras de tarjeta de Eventos y Redes sociales, que usaban degradados rojo/azul invertidos entre si. Retirado un listener JS muerto (`profile-tab-button`).
- 2026-08-02: Verificada la Fase 1 de Academias contra una copia del esquema real de produccion y corregidos tres fallos que solo aparecian al ejecutarla: (1) la migracion abortaba a medias porque `disciplinas` nunca se habia migrado en produccion, asi que ahora crea tambien esas tablas de forma idempotente; (2) todo el modulo usaba `$user['id']`, que es el uuid y no el id numerico, por lo que los guards de rol rechazaban a todo el mundo y las escrituras iban con `created_by = 0` (nuevo helper `academia_user_id()`); (3) `academia_update_profile()` solo escribia las columnas de `miembros`, asi que `db_upsert_member_for_user()` revertia la edicion en el siguiente inicio de sesion (ahora escribe tambien `perfil_json`). Anadida ademas la asignacion de profesores a cursos, sin la cual el panel del profesor salia siempre vacio.
- 2026-08-02: Fase 1 del area de Academias. Creado el modelo multiacademia como extension 1:1 de `miembros` (`academias`) con entidad intermedia de roles (`academia_miembros`), mas profesores, alumnos, tutores, niveles, cursos, grupos, horarios, matriculas y solicitudes publicas. Anadidos `panel-academia.php` (responsable y profesor), `panel-alumno.php`, la microweb publica `academia.php` en `/academia/{slug}` y la seccion Academias del panel de administracion. Seguridad por academia y por rol en backend mediante `app/academia_security.php`. Corregido de paso el enlace del directorio en `academias.php`, que apuntaba a `artista/`. Documentado en `docs/15_AREA_ACADEMIAS.md`.
- 2026-08-03: Convertidos los tipos de miembro en el eje del alta y de la URL publica. Anadido el tipo `asociacion` a `member_type_options()` y creado el mapa `member_type_url_prefixes()`, de forma que cada tipo se publica bajo su propia raiz (`/artista/`, `/academia/`, `/asociacion/`, `/tienda/`, `/pena/`, `/tablao/`, `/festival/`, `/profesional/`) con reglas nuevas en `.htaccess`; `artista.php` sirve todos salvo academia y responde 301 al prefijo canonico si no coincide con el tipo real del miembro. `registro.php` pide ahora el nombre publico junto al tipo, muestra en vivo la URL resultante, comprueba con `member_slug_in_use()` que el slug este libre y guarda `slug_locked_at`: el nombre publico y su URL quedan reservados y el panel los muestra en solo lectura, igual que ya ocurria con el nombre de cuenta. `member_slug_in_use()` se ha movido de `panel-usuario.php` a `app/auth.php` (el alta tambien la necesita) y comprueba tambien el almacenamiento legacy en JSON. Nuevas funciones `member_public_path()` / `member_public_url()` para construir enlaces publicos sin concatenar prefijos a mano.
- 2026-08-03: Corregido `slugify()`, que dependia del locale del sistema: en Windows `iconv('//TRANSLIT')` convertia la enye en `~n` y dejaba "Pena El Compas" (con enye y tildes) como `pe-na-el-comp-as`. Ahora transcribe explicitamente los caracteres acentuados del espanol y descarta los acentos sueltos (texto en NFD) antes de recurrir a `iconv()`. Importa mas que antes porque el slug pasa a ser la URL reservada de por vida del miembro.
- 2026-08-04: Academias, analisis previo a la Fase 2 y Bloque 1 (seguridad y correcciones criticas). Documentado el estado real del modulo en `docs/16_ACADEMIAS_ANALISIS_Y_PLAN.md` con 13 fallos detectados y un plan por bloques. Corregidos los criticos: (1) al matricular, `grupo_id` llegaba crudo desde el formulario y no se comprobaba que el grupo fuese de la academia ni del curso, con `academia_verify_grupo_ownership()` escrita pero sin usar en ningun sitio; (2) el panel de alumno era inalcanzable porque `academia_alumnos.usuario_id` no se rellenaba nunca, ahora se vincula por email y se crea el rol ALUMNO; (3) una academia SUSPENDIDA o de BAJA conservaba el panel completo, ahora pasa a solo lectura; (4) editar un grupo borraba todo su horario y reinsertaba como mucho uno, ahora el horario se gestiona aparte y admite varias clases por semana; (5) los ENUM llegaban crudos desde POST, ahora hay catalogos unicos que sirven para pintar los select y para validar en servidor; (6) no se comprobaba el aforo del grupo al matricular, ahora hace falta confirmacion explicita para superarlo. Encontrados de paso dos fallos mas: `es_menor_edad` se enviaba como `false`, que PDO manda como cadena vacia y MySQL en modo estricto rechaza, asi que en produccion fallaba el alta de todo alumno mayor de edad; y `academia_set_curso_profesores()` borraba filtrando solo por `curso_id`, con lo que se podia vaciar el profesorado de otra academia.
- 2026-08-04: Corregido el contraste de `.button-secondary` sobre superficies claras. Ese boton nacio para fondos oscuros (texto blanco sobre blanco translucido) y dentro de `.content-section`, cuyo fondo es `#f6efe6`, quedaba en un contraste de 1.14:1: en la seccion Academias del panel de administracion el boton "Guardar" era invisible y no habia forma de saber como confirmar el cambio de estado. Ahora usa tinta sobre fondo claro (13.89:1) dentro de `.content-section`, excluyendo `.member-panel-section`, donde la seccion es transparente y no se puede dar por hecho que el fondo sea claro. Afecta tambien a "Crear categoria" del admin, a "Buscar", "Actualizar" y "Anadir nivel" del panel de academia y al boton de solicitar informacion de la ficha publica.
- 2026-08-04: Rediseñada la seccion Academias del panel de administracion. Eliminado el titulo duplicado: `assets/js/admin-sidebar.js` reescribe el `h1` del hero con el nombre de la seccion activa, asi que el `h2` de cada seccion repetia esa misma palabra en tamano gigante y empujaba la tabla casi una pantalla hacia abajo; ahora hay una cabecera compacta (`.admin-section-heading`) y el `h2` aporta dato ("3 academias registradas"). Anadidas cuatro tarjetas de resumen por estado, filtro por estado (que el repositorio ya soportaba y no se usaba desde ninguna pantalla), aviso destacado cuando hay academias pendientes de aprobar, y en la tabla el enlace a la web publica solo cuando la academia esta activa, contadores de alumnos, profesores y cursos, solicitudes sin leer y estados vacios explicativos. La tabla pasa a tarjetas por debajo de 860px en lugar de obligar a hacer scroll lateral sobre 760px de ancho minimo. Anadida la utilidad `.visually-hidden`, que no existia, para las etiquetas de los select de cada fila.
- 2026-08-04: El tipo de espacio deja de ser editable desde el panel de miembro. Se muestra en solo lectura junto al nombre publico, con la misma via de cambio bajo solicitud. No es una preferencia visual: decide el prefijo de la URL publica, el directorio en el que aparece el miembro y, en el caso de las academias, un modulo entero con alumnos, cursos y matriculas colgando de el; permitir que una academia se convirtiera en artista desde un desplegable dejaba esos datos huerfanos. El bloqueo esta en `member_profile_from_input()`, que conserva el tipo ya guardado e ignora lo que llegue por formulario, asi que no depende de ocultar el campo en la vista; solo el alta, que aun no tiene tipo previo, puede fijarlo. Retirado tambien el listener de JavaScript que ajustaba el prefijo de la vista previa al cambiar el desplegable, que ya no existe.
- 2026-08-04: Corregidas las imagenes rotas en las paginas con URL limpia y repasado el diseño de la ficha publica de academia y del resumen del panel. Las rutas de imagen se guardan como `media.php?file=...`, que es relativa, asi que en `/academia/{slug}` el navegador pedia `/academia/media.php` y tanto la foto de la academia como el avatar de la cabecera salian rotos; nuevo helper `csf_media_url_absolute()` en `app/config.php`, usado por `academia.php` y `app/layout.php`. Corregido de paso un fallo preexistente en `csf_media_file_from_path()`: el `#` sin escapar dentro de `[^&#]` cerraba el delimitador del patron, asi que esa rama nunca se evaluaba y emitia un warning de PHP en cada llamada. La ficha publica pasa a tener cabecera con ubicacion y disciplinas, portada con proporcion fija, ficha de datos de contacto, tarjetas de curso con precio, modalidad y fecha, y textos utiles cuando la academia aun no ha publicado nada. El resumen del panel deja de repetir el nombre en dos titulares gigantes, sus tarjetas enlazan a la seccion correspondiente y aparece una guia del orden recomendado cuando la academia esta vacia.
- 2026-08-04: Rehecha la ficha publica de academia con componentes propios. El intento anterior quedo roto por tres motivos que solo se ven al renderizar: (1) `.content-section` no tiene padding superior porque da por supuesto que la seccion abre con la banda oscura `.section-heading`, asi que al quitarla el contenido se salia por arriba; (2) existe una regla global `#cursos { background: #e8eef2 }` para la portada, y la seccion de cursos de la academia usaba ese mismo id, de modo que el fondo azulado ganaba por especificidad de id (misma colision que ya hubo con `#eventos`; las anclas pasan a `#academia-cursos`, `#academia-profesores` y `#academia-contacto`); (3) el bloque de datos de contacto se pintaba como una caja con borde y nada dentro cuando la academia no tenia telefono, web ni Instagram. La pagina usa ahora paneles claros con cabecera compacta (`.academia-panel`) en lugar de encadenar tres bandas oscuras a sangre. Ademas, la foto se omite si el fichero ya no existe en disco, en vez de dejar el icono de imagen rota, y el arreglo de contraste de `.button-secondary` se extiende a los paneles nuevos, donde el boton "Solicitar informacion" volvia a quedar invisible.
- 2026-08-13: Reorganizada la pestana Perfil del panel de miembro en tarjetas. La cabecera generica ("Area privada / Panel de miembro / Bienvenido") se sustituye por la propia identidad del espacio: tipo de miembro y nombre publico a ancho de pagina, con la foto editable y el QR de la tarjeta. El formulario, que era una pila de seis `fieldset` seguidos, pasa a cuatro tarjetas: "Mi perfil" (identidad, datos visibles e imagenes, plegada tras un boton) y una tarjeta por cada seccion del curriculum: Formacion, Experiencia profesional y la seccion personalizada. Cada tarjeta muestra un listado con titulo, fechas, lugar y miniatura de sus entradas, y botones Ver, Editar y Borrar; crear o editar abre un panel lateral derecho (`.member-entry-fields`, posicionado en fijo pero dentro del formulario, asi que se envia con el resto del panel) con dos modos, vista de solo lectura y edicion. Cuando una seccion esta vacia se ofrece directamente el boton de crear en lugar de una fila en blanco, que era el comportamiento anterior. Borrar quita la fila del DOM y se confirma al guardar, aprovechando que `clean_cv_entries()` reconstruye la seccion con lo que llega en el POST. Eliminado el bloque "Notas privadas": `member_profile_from_input()` conserva el valor guardado cuando el campo no llega, asi que los textos existentes no se pierden. Los ajustes de PDF por seccion (activa, orden de seccion y orden de entradas) se recogen en un `details` dentro de cada tarjeta. Anadido un listener del evento `invalid` que despliega la tarjeta "Mi perfil" si el navegador encuentra dentro un campo obligatorio vacio, porque un control oculto no se puede enfocar y el envio se quedaba mudo.
