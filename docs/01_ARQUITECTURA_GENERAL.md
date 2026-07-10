# Arquitectura General

## Descripcion

Este documento recoge la arquitectura funcional inicial de Con Sabor Flamenco como plataforma web principal. La arquitectura se define de forma conceptual en esta fase, sin implementar codigo ni estructura tecnica definitiva.

## Objetivo del documento

Organizar las areas principales del sistema y dejar una base clara para que el desarrollo posterior avance por fases sin mezclar responsabilidades.

## Areas de la plataforma

La plataforma se organizara inicialmente en cinco grandes areas:

- Area publica.
- Area de administracion.
- Area privada de miembros.
- Area privada de appointment setters.
- Inbox y agente IA.

## Area publica

El area publica sera la parte visible para visitantes. Debe tener un diseno moderno tipo revista y servir para difundir contenido, mostrar perfiles y captar oportunidades comerciales.

Secciones previstas:

- Inicio.
- Revista, mediante pagina propia y bloque destacado en portada.
- Fotografia, mediante pagina propia y bloque destacado en portada.
- Moda, mediante pagina propia y bloque destacado en portada.
- Artistas, mediante pagina propia y bloque destacado en portada.
- Academias, mediante pagina propia y bloque destacado en portada.
- Cursos, mediante pagina propia y bloque destacado en portada.
- Eventos, mediante pagina propia y bloque destacado en portada.
- Penas flamencas, mediante pagina propia y bloque destacado en portada.
- Tablaos, mediante pagina propia y bloque destacado en portada.
- Festivales, mediante pagina propia y bloque destacado en portada.
- Flamenco, mediante pagina propia.
- Concursos, temporalmente oculto pero conservado para su futura activacion.
- Servicios para miembros, mediante pagina propia.
- Contacto.

### Capa publicitaria publica

Las vistas publicas utilizaran una estructura comun de contenido principal y sidebar derecho. El sidebar mostrara banners segun la provincia conocida del visitante y la categoria de la seccion activa.

Orden de resolucion de provincia:

1. Provincia del perfil del usuario autenticado.
2. Provincia elegida por el visitante y guardada en su dispositivo.
3. Cobertura nacional como alternativa.

Categorias publicitarias iniciales:

- Inicio.
- Revista.
- Fotografia.
- Moda.
- Artistas.
- Academias.
- Cursos.
- Eventos.
- Penas.
- Tablaos.
- Festivales.
- Flamenco.
- Concursos.

La categoria Flamenco utilizara un submenu acordeon con Historia, Palos del flamenco y Llaves de Oro.

La categoria Moda utilizara un submenu acordeon con Ropa, Calzado, Complementos y Moda infantil.

Academias utilizara un submenu con acceso al directorio de academias, Cursos presenciales, Cursos online y Talleres intensivos. Cursos mantendra seccion, ranking y categoria publicitaria propios.

Servicios y Contacto no crean categorias publicitarias. En esas vistas se podran mantener campanas generales o nacionales.

La portada concentra los rankings de las categorias activas y cada cabecera de seccion incluye un acceso directo "Entrar en esta seccion" hacia su pagina publica. Revista, Academias, Cursos, Artistas, Eventos, Festivales, Penas, Tablaos, Moda, Fotografia, Servicios y Flamenco disponen de vistas independientes en `.php` que reutilizan header, navegacion responsive, selector de provincia, rankings, publicidad y footer. Las paginas personales de miembros usan URLs limpias `/artista/{slug}` y no muestran el header ni el footer global para funcionar como microsites independientes. La seccion y el enlace de Concursos permanecen comentados para poder recuperarlos sin reconstruir su estructura.

### Ranking de comunidad

El hero de la portada mostrara los cuatro contenidos comunitarios con mas visitas, comparando todas las categorias con el mismo criterio. Por tanto, las cuatro posiciones podran pertenecer a tipos distintos o repetirse si son realmente los mas visitados.

Cada tarjeta del ranking incluira:

- Imagen principal del contenido.
- Categoria o tipo.
- Titulo.
- Descripcion de un maximo de 30 caracteres.
- Boton Ver mas enlazado a la pagina concreta.

## Area de administracion

El panel de administracion sera el centro de control de la plataforma. Desde esta zona se gestionaran contenidos, miembros, servicios, leads, inbox, appointment setters, ventas, comisiones y liquidaciones.

## Area de miembros

Los miembros tendran un panel privado para gestionar su presencia publica, sus datos, imagenes, biografia, redes sociales, videos, eventos y servicios digitales contratados.

## Area de appointment setters

Los appointment setters tendran un panel privado independiente. Cada setter solo vera su informacion: dashboard, clientes, leads, ventas, comisiones, codigos promocionales, solicitudes de cobro y pagos recibidos.

## Inbox y agente IA

La plataforma estara preparada para integrar un agente IA publico, formularios inteligentes y una inbox de administracion donde gestionar conversaciones, estados y conversiones a leads.

## Preparacion para API REST

La arquitectura debe permitir que en fases futuras se expongan datos y operaciones mediante API REST. Esta preparacion facilitara integraciones externas y una posible app movil.

## Reglas y decisiones

- Separar responsabilidades entre areas.
- Evitar implementar todo de golpe.
- Mantener una arquitectura escalable.
- Mantener la documentacion sincronizada con cada fase.
- Preparar la plataforma para futuras integraciones de IA.
- Centralizar la seleccion de banners para reutilizar la misma regla en todas las paginas publicas y en la futura API.

## Pendiente de definir

- Stack tecnico definitivo.
- Estructura exacta de carpetas.
- Politica de autenticacion y permisos.
- Convenciones de rutas.
- Criterios de versionado de API.

## Historial de cambios

- 2026-06-08: Creado el documento inicial de arquitectura general.
- 2026-06-21: Definida la capa publicitaria por provincia, categoria y sidebar comun.
- 2026-06-21: Definido el ranking comunitario de cuatro contenidos por visitas.
- 2026-06-22: Documentadas las paginas independientes de Servicios y Flamenco y la desactivacion temporal de Concursos.
- 2026-06-23: Documentadas las paginas independientes del resto de secciones publicas y el boton de entrada por cabecera.
- 2026-06-23: Documentada la conversion de las vistas publicas a PHP para preparar logica de negocio.
