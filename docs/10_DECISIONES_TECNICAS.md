# Decisiones Tecnicas

## Descripcion

Este documento registra las decisiones tecnicas iniciales de Con Sabor Flamenco. En esta fase las decisiones son orientativas y deberan ampliarse cuando se defina el stack tecnico.

## Objetivo del documento

Centralizar criterios tecnicos iniciales para mantener coherencia durante el desarrollo por fases.

## Decisiones iniciales

- El proyecto debe desarrollarse por fases.
- Se debe mantener documentacion Markdown actualizada.
- Se debe preparar una arquitectura escalable.
- No se debe implementar todo de golpe.
- El sistema debe estar preparado para API REST.
- El panel de administracion sera el centro de control.
- El area de setters sera independiente y solo mostrara informacion del setter autenticado.
- Los codigos promocionales seran creados solo por administracion.
- Cada codigo promocional pertenecera a un unico setter.
- La plataforma debera permitir futuras integraciones con IA.
- La primera version visual se crea sin logica compleja, priorizando una estructura escalable y un diseno tipo revista.
- La publicidad publica usara un sidebar derecho comun y reutilizable.
- La segmentacion inicial se hara por provincia elegida, sin geolocalizacion precisa ni consulta automatica de IP.
- La provincia se guardara localmente para visitantes y procedera del perfil para usuarios autenticados.
- La seleccion priorizara campanas provinciales y usara campanas nacionales como respaldo.
- Las categorias publicitarias procederan del navbar, excluyendo Servicios y Contacto.
- El prototipo usa inventario local de demostracion; la version productiva obtendra campanas activas desde administracion o API.
- El hero publico ocupara todo el ancho bajo el menu principal.
- El bloque de comunidad mostrara cuatro tarjetas ordenadas exclusivamente por visitas entre todas las categorias.
- Cada tarjeta usara la imagen principal, titulo, descripcion maxima de 30 caracteres y URL concreta del contenido.
- El menu sera horizontal en escritorio y hamburguesa accesible en tablet y movil.
- Los enlaces principales se mostraran en orden alfabetico, con Inicio, Flamenco y Revista primero y Servicios y Contacto al final.
- Flamenco sera una categoria principal con submenu acordeon accesible en escritorio, tablet y movil.
- Moda sera una categoria principal con submenu para Ropa, Calzado, Complementos y Moda infantil.
- Cursos se mostrara dentro del acordeon Academias para no saturar el menu, pero tendra pagina, ranking y publicidad independientes.
- El rail publicitario de escritorio tendra 560 px y pasara a formato horizontal cuando el ancho disponible no sea suficiente.
- La Revista usara una composicion editorial jerarquizada con noticia principal y contenidos secundarios.
- Todas las secciones publicas compartiran fondos completos, cabeceras editoriales oscuras y tarjetas modernas.
- Todas las categorias mostraran tres posiciones: primer puesto grande y segundo y tercero secundarios.
- El origen de cada posicion sera votacion o promocion pagada y las promociones se etiquetaran de forma visible.
- Moda conservara su ranking de tarjetas y sus accesos a Ropa, Calzado, Complementos y Moda infantil.
- Flamenco dejara de ser una seccion de portada y tendra pagina propia para Historia, Palos del flamenco y Llaves de Oro.
- Servicios dejara de ser una seccion de portada y tendra pagina publica propia.
- Concursos se desactivara temporalmente mediante comentarios, conservando su estructura para una futura reactivacion.
- El footer utilizara esquinas redondeadas para integrarse con el lenguaje visual de tarjetas y secciones.
- Las vistas publicas pasaran de `.html` a `.php` para permitir incorporar progresivamente logica de negocio, sesiones, formularios y consultas sin rehacer rutas.
- El registro y acceso usaran sesiones PHP, token CSRF, `password_hash`, recuperacion por token temporal y aceptacion obligatoria de terminos.
- El almacenamiento inicial de usuarios, recuperaciones y sesiones sera local en `storage/` con ficheros protegidos por `.htaccess`; se sustituira por base de datos cuando se consolide el modelo.
- Las paginas internas usaran `app/layout.php` para compartir cabecera, navegacion, footer, selector de provincia y estructura visual con la portada.
- El panel de miembro separara perfil, tarjeta identificativa, banners y seguridad para crecer por modulos.
- El area de usuario usara un lenguaje de dashboard moderno, con cabecera de identidad, metricas visibles y formularios por paneles de trabajo.
- El area de usuario aprovechara mas ancho util en escritorio y apilara bloques antes de que los indicadores se compriman.
- La tarjeta de miembro mostrara el nombre y el nivel de membresia; todo registro nuevo empieza como Miembro simpatizante.
- Los descuentos quedan reservados a Miembro VIP tras pago confirmado de membresia anual de 80 euros.
- El registro de miembros exigira un perfil artistico minimo con tipo de espacio y fotografia principal.
- Las fotografias principales y las imagenes de curriculum/pagina web se guardaran como runtime fuera del arbol de codigo por defecto en `../csf-uploads`, configurable con `CSF_UPLOADS_DIR`, y se serviran mediante `media.php`; solo se aceptaran JPG, PNG o WebP.
- El curriculum artistico usara secciones repetibles y controles de visibilidad; la primera fase lo guarda en JSON local y el modelo SQL queda preparado con `miembros_curriculum_items`.
- La exportacion inicial a PDF se resolvera mediante impresion del navegador con una plantilla limpia especifica para print.
- Las secciones repetibles se anadiran bajo demanda desde el panel, tendran fechas cuando proceda y podran ordenarse de forma ascendente o descendente.
- Las entradas principales del curriculum se trataran como piezas editoriales: titulo y descripcion obligatorios si hay contenido, imagen opcional y datos especificos de contexto.
- Los campos de configuracion de banners no se mostraran hasta que exista una contratacion activa; las fechas de contratacion se elegiran en el flujo de compra.
- El selector de diseno de tarjeta se previsualizara en cliente para evitar recargas y saltos de scroll.
- Los banners contratados por miembros dependeran de pago Stripe confirmado, estado activo y fechas validas para mostrarse en la web.
- `database/schema.sql` sera la referencia inicial para migrar del almacenamiento JSON a base de datos relacional.
- La base de datos principal se llamara `consaborflamenco` y usara `utf8mb4_unicode_ci`.
- La configuracion sensible de entorno se cargara desde `.env`, que no se versiona en Git.
- El entorno local usara por defecto MySQL de XAMPP: `127.0.0.1`, base `consaborflamenco`, usuario `root`.
- El entorno de produccion usara por defecto la base de Hostinger `u311361615_csf` con usuario `u311361615_admin`, esperando la contrasena en `.env`.
- En produccion no se intentara crear la base de datos; solo se conectara a la base ya asignada y ejecutara el bootstrap de tablas.
- `setup-prod-db.php` quedara como instalador temporal bloqueado por `storage/ALLOW_PROD_SETUP` para configurar `.env`, preparar tablas y migrar datos JSON si existen.

## Criterios de desarrollo

- Mantener cambios pequenos y revisables.
- Documentar cada fase antes de implementar funcionalidades complejas.
- Evitar renombrar rutas, carpetas o archivos existentes si no es necesario.
- No eliminar archivos existentes.
- No romper funcionalidades existentes.
- Separar la estructura visual publica de futuras capas de base de datos, autenticacion, pagos, setters y agente IA.
- Mostrar siempre la etiqueta Publicidad en cada creatividad y mantener textos alternativos accesibles.
- Mantener respuestas genericas en recuperacion de contrasena para no revelar si un email existe.
- Los documentos legales se gestionan desde base de datos mediante `legal_documents` y `legal_document_versions`, con edicion restringida a rol admin, CSRF, consultas preparadas y sanitizacion de HTML permitido.
- El footer usa URLs reales para terminos, aviso legal, privacidad y cookies; JavaScript solo mejora la experiencia abriendo una modal accesible.
- El consentimiento de cookies se guarda como cookie necesaria `csf_cookie_consent` con version, fecha y categorias. No hay Analytics, Meta Pixel ni publicidad personalizada de terceros cargada actualmente; los cargadores opcionales quedan centralizados para activarlos solo tras consentimiento.

## Preparacion para escalabilidad

La arquitectura debera permitir crecimiento hacia API REST, integraciones con IA, inbox multicanal y posible app movil.

## Seguridad y permisos

El proyecto debera separar permisos entre administradores, miembros y setters. El area de setters debera limitar siempre la informacion al usuario autenticado.

## Pendiente de definir

- Lenguaje principal.
- Framework backend.
- Framework frontend.
- Motor de base de datos.
- Sistema de autenticacion.
- Migracion del almacenamiento inicial de autenticacion a base de datos.
- Convenciones de despliegue.
- Plataforma de analitica, consentimiento y prevencion de impresiones fraudulentas.

## Historial de cambios

- 2026-06-08: Registradas las decisiones tecnicas iniciales.
- 2026-06-08: Registrada la decision de crear la primera version visual sin logica compleja.
- 2026-06-21: Registradas las decisiones de segmentacion publicitaria provincial y categorias del navbar.
- 2026-06-21: Registrado el hero a ancho completo y el ranking comunitario por visitas.
- 2026-06-22: Registrada la separacion de Servicios y Flamenco, la pausa de Concursos y el redondeado del footer.
- 2026-06-23: Registrada la conversion de las vistas publicas a PHP como base para logica de negocio.
- 2026-06-23: Registrada la base de autenticacion con registro, acceso, recuperacion de contrasena, terminos y paneles iniciales protegidos.
- 2026-06-23: Registrada la creacion de un layout comun para mantener consonancia visual entre Inicio y el resto de paginas.
- 2026-06-23: Registrada la decision de modelar tarjeta de miembro, codigo de descuento activo/inactivo y banners con Stripe.
- 2026-06-24: Registrado `consaborflamenco` como nombre definitivo de la base de datos principal.
- 2026-06-24: Registrado el cambio de nivel inicial a Miembro simpatizante y descuentos solo para Miembro VIP.
- 2026-06-24: Registrada la decision de guardar sesiones PHP dentro de `storage/sessions`.
- 2026-06-24: Registrado el requisito de perfil artistico minimo con fotografia principal obligatoria.
- 2026-06-24: Registrado el redisenado del area privada de usuario como dashboard moderno.
- 2026-06-24: Registrado el reajuste responsive y de ancho del area privada de usuario.
- 2026-06-24: Registrado que la membresia VIP cuesta 80 euros al ano y es necesaria para activar descuentos.
- 2026-06-25: Registrada la estrategia de curriculum artistico repetible, privacidad por bloque e impresion PDF.
- 2026-06-25: Registrada la incorporacion de filas dinamicas, orden cronologico y pie de marca en PDF.
- 2026-06-25: Registrado que la configuracion de banners queda oculta hasta contratacion activa, que la tarjeta cambia sin recargar y que el PDF debe ser compacto.
- 2026-06-25: Registrado que las entradas principales del curriculum requieren titulo y descripcion y admiten imagen.
- 2026-06-29: Registrada la separacion local/produccion mediante `.env` y el instalador temporal `setup-prod-db.php` para preparar la base Hostinger.
- 2026-07-18: Registrada la arquitectura de documentos legales administrables, modal legal progresiva y consentimiento de cookies por categorias.
