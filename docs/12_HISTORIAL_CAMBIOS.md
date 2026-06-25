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
