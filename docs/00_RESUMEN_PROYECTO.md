# Con Sabor Flamenco - Resumen del Proyecto

## Descripcion

Con Sabor Flamenco es la web y plataforma principal del proyecto. Su proposito es fomentar, promocionar y digitalizar el mundo del flamenco mediante una experiencia moderna tipo revista, una comunidad profesional y herramientas digitales para miembros, administracion y appointment setters.

## Objetivo del documento

Definir la vision general del proyecto, las areas principales de la plataforma y los criterios iniciales que deben guiar el desarrollo por fases.

## Vision general

La plataforma debe funcionar como:

- Revista digital flamenca.
- Comunidad de artistas, academias, penas flamencas, tablaos, festivales y profesionales.
- Directorio profesional de miembros.
- Plataforma de servicios digitales para miembros.
- Sistema comercial con appointment setters.
- Sistema de leads, ventas, comisiones y solicitudes de cobro.
- Web preparada para incorporar un agente IA conectado a una inbox.
- Base preparada para crecer con API REST y posible app movil.
- Canal de monetizacion mediante banners segmentados por provincia y seccion publica.

## Identidad visual inicial

La web tendra un diseno moderno tipo revista, con una combinacion visual elegante y cultural. Los colores principales previstos son:

- Rojo suave.
- Azul suave.
- Negro.
- Blanco calido como color de apoyo.

## Secciones publicas previstas

- Inicio.
- Revista, con pagina propia y acceso desde portada.
- Fotografia, con pagina propia y acceso desde portada.
- Moda: Ropa, Calzado, Complementos y Moda infantil, con pagina propia y acceso desde portada.
- Artistas, con pagina propia y acceso desde portada.
- Academias, con pagina propia y acceso desde portada.
- Cursos presenciales, online e intensivos, con pagina propia y acceso desde portada.
- Eventos, con pagina propia y acceso desde portada.
- Penas flamencas, con pagina propia y acceso desde portada.
- Tablaos, con pagina propia y acceso desde portada.
- Festivales, con pagina propia y acceso desde portada.
- Flamenco: pagina propia con Historia, Palos del flamenco y Llaves de Oro.
- Concursos, reservado y temporalmente oculto en la navegacion y la portada.
- Servicios para miembros mediante pagina propia.
- Contacto.

## Monetizacion publicitaria

La publicidad mediante banners sera una de las fuentes de ingresos principales. La web mostrara un sidebar publicitario en las paginas publicas y priorizara campanas de la provincia del visitante. Las categorias publicitarias se corresponden con las secciones del menu, excepto Servicios y Contacto.

La provincia se obtendra del perfil cuando el usuario este registrado. Para visitantes se solicitara mediante un selector voluntario y se guardara en el dispositivo. Si no existe una provincia disponible, se mostraran campanas nacionales.

La portada incluira cuatro tarjetas con los contenidos mas visitados de toda la comunidad, sin reservar posiciones por categoria. Cada tarjeta mostrara la imagen principal, tipo, titulo, una descripcion de hasta 30 caracteres y un enlace a su pagina concreta.

Las secciones publicas utilizaran rankings de tres posiciones. El primer puesto tendra una tarjeta principal de mayor tamano y el segundo y tercero tarjetas secundarias. Las posiciones podran proceder de votacion o promocion pagada, mostrando siempre la etiqueta Promocionado cuando corresponda.

## Areas principales

- Area publica: contenidos editoriales, directorios, eventos y servicios visibles para visitantes.
- Area de administracion: centro de control para gestionar contenidos, miembros, ventas, inbox, setters, comisiones y liquidaciones.
- Area privada de miembros: panel para perfiles, imagenes, biografias, redes, videos, eventos y servicios contratados.
- Area privada de appointment setters: panel independiente para leads, clientes, ventas, comisiones, codigos promocionales y cobros.
- Inbox y agente IA: sistema preparado para conversaciones, formularios inteligentes, estados y conversion a leads.

## Reglas y decisiones iniciales

- El proyecto se desarrollara por fases.
- En la Fase 0 solo se crea documentacion inicial.
- No se deben implementar todavia entidades, controladores, rutas, componentes ni base de datos.
- La documentacion Markdown debe mantenerse actualizada durante todo el proyecto.
- La arquitectura debe prepararse para escalar hacia API REST y app movil.

## Pendiente de definir

- Tipografias finales.
- Frameworks o stack tecnico definitivo.
- Estructura exacta de navegacion.
- Linea editorial de la revista.
- Modelo comercial detallado por tipo de servicio.

## Historial de cambios

- 2026-06-08: Creado y estructurado el resumen inicial del proyecto.
- 2026-06-21: Incorporada la publicidad local por provincia y categoria como fuente principal de ingresos.
- 2026-06-21: Definido el ranking de cuatro contenidos comunitarios mas visitados en la portada.
- 2026-06-22: Trasladadas las secciones Flamenco y Servicios a paginas propias y ocultado temporalmente Concursos.
- 2026-06-23: Anadidos accesos "Entrar en esta seccion" y paginas propias para las secciones publicas principales.
