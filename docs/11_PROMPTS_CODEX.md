# Prompts Codex

## Descripcion

Este documento guarda los prompts principales utilizados para guiar el desarrollo de Con Sabor Flamenco con Codex.

## Objetivo del documento

Mantener un historial de instrucciones relevantes para que el proyecto pueda continuar por fases con contexto claro.

## Prompt 1 - Fase 0 - Documentacion inicial

Fecha: 2026-06-08

```text
Estamos trabajando en el proyecto principal de Con Sabor Flamenco.

OBJETIVO GENERAL DEL PROYECTO
Crear una plataforma web principal moderna para fomentar, promocionar y digitalizar el mundo del flamenco.

La plataforma debe funcionar como:
- Revista digital flamenca.
- Comunidad de artistas, academias, penas, tablaos, festivales y profesionales.
- Directorio profesional de miembros.
- Plataforma de servicios digitales para miembros.
- Sistema comercial con appointment setters.
- Sistema de leads, ventas, comisiones y solicitudes de cobro.
- Web preparada para incorporar un agente IA conectado a una inbox.
- Proyecto preparado para crecer en el futuro con API REST y posible app movil.

IMPORTANTE
Antes de modificar cualquier archivo:
1. Revisa la estructura actual del proyecto.
2. No elimines archivos existentes.
3. No rompas funcionalidades existentes.
4. No cambies nombres de rutas, carpetas o archivos ya usados si no es necesario.
5. En esta primera fase NO implementes logica compleja.
6. En esta primera fase solo vamos a crear documentacion inicial del proyecto.
7. Todo debe quedar documentado para poder continuar por fases.

FASE ACTUAL
Fase 0 - Documentacion inicial del proyecto.

TAREA PRINCIPAL
Crear una carpeta llamada /docs.

Dentro de esa carpeta, crear los siguientes documentos Markdown:

/docs/00_RESUMEN_PROYECTO.md
/docs/01_ARQUITECTURA_GENERAL.md
/docs/02_FASES_DESARROLLO.md
/docs/03_MODELO_BASE_DATOS.md
/docs/04_PANEL_ADMINISTRACION.md
/docs/05_AREA_MIEMBROS.md
/docs/06_AREA_SETTERS.md
/docs/07_CODIGOS_PROMOCIONALES.md
/docs/08_INBOX_AGENTE_IA.md
/docs/09_SERVICIOS_Y_PLANES.md
/docs/10_DECISIONES_TECNICAS.md
/docs/11_PROMPTS_CODEX.md
/docs/12_HISTORIAL_CAMBIOS.md

CONTENIDO GENERAL QUE DEBEN RECOGER LOS DOCUMENTOS

1. El proyecto se llama Con Sabor Flamenco.
2. Es la web/plataforma principal del proyecto.
3. La web estara dedicada a fomentar el flamenco.
4. Tendra diseno moderno tipo revista.
5. Los colores principales seran rojo suave, azul suave, negro y blanco calido como color de apoyo.
6. La web tendra secciones publicas como Inicio, Revista, Artistas, Academias, Eventos, Penas flamencas, Tablaos, Festivales, Concursos, Servicios para miembros y Contacto.
7. La plataforma tendra un area de administracion.
8. La plataforma tendra un area privada para miembros.
9. La plataforma tendra un area privada para appointment setters.
10. La plataforma tendra un sistema de inbox para gestionar conversaciones, formularios y futuros mensajes del agente IA.

PANEL DE ADMINISTRACION

Documentar que el administrador podra gestionar miembros, artistas, academias, penas, tablaos, festivales, eventos, articulos, categorias, servicios, leads, inbox, appointment setters, codigos promocionales, clientes captados, ventas, comisiones, solicitudes de cobro, liquidaciones y facturas o justificantes de pago.

AREA DE MIEMBROS

Documentar que los miembros podran acceder a su panel privado, editar su perfil publico, subir o gestionar imagenes, anadir biografia, anadir redes sociales, anadir videos, publicar o solicitar publicacion de eventos, ver solicitudes recibidas, contratar servicios digitales y ver el estado de sus servicios contratados.

Tipos de miembros previstos:
- Artista.
- Academia.
- Pena flamenca.
- Tablao.
- Festival.
- Profesional flamenco.
- Entidad colaboradora.

AREA DE APPOINTMENT SETTERS

Documentar que cada setter tendra su propio panel privado para ver su dashboard, clientes, leads, ventas, comisiones, codigos promocionales asignados, solicitar el cobro de comisiones, ver pagos recibidos y descargar justificantes o facturas de liquidacion.

SISTEMA DE CODIGOS PROMOCIONALES

Documentar estas reglas:
1. El administrador crea los codigos promocionales desde el panel de administracion.
2. Cada codigo promocional debe ser unico.
3. Cada codigo promocional se asigna a un unico appointment setter.
4. El codigo sirve para aplicar descuento al cliente.
5. El codigo sirve para identificar que setter ha captado al cliente.
6. El cliente introducira el codigo en el formulario para obtener el descuento.
7. El sistema validara si el codigo existe, esta activo y no esta caducado.
8. Si el codigo es valido, el sistema asociara automaticamente el lead al setter propietario del codigo.
9. Si el lead se convierte en venta, la venta y la comision quedaran asociadas al setter.
10. Un setter podra tener uno o varios codigos promocionales.
11. La comision se calculara preferiblemente sobre el importe realmente cobrado tras aplicar el descuento.

SISTEMA COMERCIAL

Documentar que el sistema comercial debera permitir registrar leads, asociar leads a setters, convertir leads en clientes, convertir clientes en ventas, calcular comisiones, gestionar comisiones pendientes y pagadas, permitir solicitudes de cobro, generar liquidaciones y generar justificantes o facturas internas de pago.

INBOX Y AGENTE IA

Documentar que la plataforma estara preparada para tener agente IA publico en la web, formulario inteligente, inbox de administracion, historial de conversaciones, estados de conversacion, asignacion de conversaciones a miembros o setters y conversion de conversaciones en leads.

Estados posibles de conversacion:
- NUEVO.
- EN_CURSO.
- RESPONDIDO.
- CONVERTIDO.
- DESCARTADO.
- CERRADO.

FASES DEL PROYECTO

Documentar estas fases iniciales:
Fase 0 - Documentacion inicial.
Fase 1 - Estructura visual y menu principal.
Fase 2 - Diseno de home tipo revista.
Fase 3 - Modelo inicial de base de datos.
Fase 4 - Panel de administracion.
Fase 5 - Gestion de miembros.
Fase 6 - Perfiles publicos de miembros.
Fase 7 - Area de appointment setters.
Fase 8 - Codigos promocionales.
Fase 9 - Leads, ventas y comisiones.
Fase 10 - Solicitudes de cobro y liquidaciones.
Fase 11 - Inbox y agente IA.
Fase 12 - Servicios digitales y planes.
Fase 13 - API REST y preparacion para app movil.

DECISIONES TECNICAS INICIALES

Documentar estas decisiones:
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

FORMATO DE CADA DOCUMENTO

Cada documento debe tener:
1. Titulo principal.
2. Descripcion.
3. Objetivo del documento.
4. Apartados organizados.
5. Reglas o decisiones si corresponde.
6. Seccion "Pendiente de definir".
7. Seccion "Historial de cambios".

En /docs/11_PROMPTS_CODEX.md anadir este mismo prompt como primer prompt del proyecto, indicando que corresponde a la Fase 0.

En /docs/12_HISTORIAL_CAMBIOS.md anadir una entrada con la fecha actual indicando:
- Creada la documentacion inicial del proyecto.
- Definida la vision general de la plataforma.
- Definidas areas principales: publica, administracion, miembros, setters e inbox.
- Definido el sistema de codigos promocionales por setter.
- Definidas fases iniciales de desarrollo.

RESULTADO ESPERADO

Al finalizar esta tarea, deben existir los documentos Markdown dentro de /docs, con contenido inicial claro y bien estructurado.

No implementar todavia entidades, controladores, rutas, componentes ni base de datos.

Solo crear documentacion inicial.
```

## Reglas y decisiones

- Guardar los prompts relevantes por fase.
- Mantener fecha y contexto de cada prompt.
- Evitar mezclar prompts de trabajo con decisiones tecnicas definitivas si no han sido validadas.

## Prompt 2 - Fase 0 - Archivo AGENTS.md

Fecha: 2026-06-08

```text
Falta crear el archivo AGENTS.md en la raiz del proyecto.

Crear el archivo:

AGENTS.md

IMPORTANTE:
No modificar funcionalidades existentes.
No eliminar archivos existentes.
No tocar logica del proyecto.
Solo crear el archivo AGENTS.md con instrucciones generales para trabajar en este proyecto.

Contenido que debe tener AGENTS.md:

Archivo principal de instrucciones para agentes IA dentro del proyecto Con Sabor Flamenco.
```

Resultado:

- Creado AGENTS.md en la raiz del proyecto.
- Definido como archivo principal de instrucciones para agentes IA.
- Documentadas reglas generales de trabajo, areas del sistema, fases y obligaciones de documentacion.

## Prompt 3 - Fase 1 - Estructura visual publica

Fecha: 2026-06-08

```text
Empezamos la Fase 1 del proyecto principal Con Sabor Flamenco.

Antes de modificar nada:
1. Revisa la estructura actual del proyecto.
2. Lee el archivo AGENTS.md.
3. Revisa la carpeta /docs.
4. No elimines archivos existentes.
5. No rompas funcionalidades existentes.
6. No implementes todavia logica compleja de base de datos, autenticacion, setters, pagos ni agente IA.
7. Esta fase se centra solo en estructura visual inicial, navegacion publica y preparacion de la home.

OBJETIVO DE LA FASE 1
Crear la estructura visual y navegacion principal de la web publica de Con Sabor Flamenco.

La web debe tener un diseno moderno tipo revista/comunidad flamenca.

Colores principales:
- Rojo suave.
- Azul suave.
- Negro.
- Blanco calido.

Debe transmitir:
- Flamenco.
- Cultura.
- Elegancia.
- Comunidad.
- Profesionalidad.
- Tecnologia.

TAREAS DE LA FASE 1

1. Crear o ajustar el header principal de la web publica.

El header debe incluir:
- Nombre o logo textual: Con Sabor Flamenco.
- Menu principal.
- Boton destacado: Hazte miembro.
- Boton o enlace: Acceder.

Menu principal inicial:
- Inicio.
- Revista.
- Artistas.
- Academias.
- Eventos.
- Penas.
- Tablaos.
- Servicios.
- Contacto.

2. Crear o ajustar la pagina de inicio.

La home debe quedar estructurada como una revista moderna con estas secciones:

- Hero principal.
- Articulos destacados.
- Artistas destacados.
- Proximos eventos.
- Academias destacadas.
- Servicios para miembros.
- Llamada a formar parte de la comunidad.

3. Hero principal.

El hero debe tener:
- Titulo principal potente.
- Subtitulo explicando la mision.
- Boton principal: Descubre la comunidad.
- Boton secundario: Hazte miembro.
- Diseno visual moderno con fondo oscuro, detalles en rojo suave y azul suave.

Texto sugerido para el hero:

Titulo:
Con Sabor Flamenco

Subtitulo:
La plataforma que une revista, comunidad y tecnologia para impulsar el arte flamenco.

Boton principal:
Descubre la comunidad

Boton secundario:
Hazte miembro

4. Seccion de articulos destacados.

Debe mostrar cards de ejemplo para articulos de revista.

Ejemplos de cards:
- La fuerza del baile flamenco actual.
- Nuevas voces del cante.
- El compas como raiz del flamenco.

5. Seccion de artistas destacados.

Debe mostrar cards de ejemplo para futuros miembros artistas.

Ejemplos:
- Bailaores y bailaoras.
- Cantaores y cantaoras.
- Guitarristas.
- Percusionistas.

6. Seccion de proximos eventos.

Debe mostrar cards de ejemplo para eventos.

Ejemplos:
- Festival flamenco.
- Clase magistral.
- Noche de pena flamenca.

7. Seccion de academias destacadas.

Debe mostrar cards de ejemplo para academias.

Ejemplos:
- Academia de baile.
- Escuela de guitarra.
- Formacion en compas y palmas.

8. Seccion de servicios para miembros.

Debe incluir tarjetas de servicios como:
- Pagina web para artistas.
- Web para academias.
- Agente IA para atender consultas.
- Dossier artistico profesional.
- Promocion en revista.
- Gestion de presencia digital.

9. Crear o ajustar el footer.

El footer debe incluir:
- Nombre Con Sabor Flamenco.
- Descripcion corta.
- Enlaces principales.
- Enlaces legales.
- Contacto.
- Redes sociales como placeholders.

10. Estilos.

Crear o ajustar estilos globales para:
- Colores principales.
- Tipografias.
- Botones.
- Cards.
- Secciones.
- Responsive basico.
- Diseno moderno, limpio y elegante.

Evitar:
- Diseno antiguo.
- Colores chillones.
- Menus sobrecargados.
- Codigo duplicado innecesario.

11. Documentacion obligatoria.

Al terminar, actualizar:

/docs/02_FASES_DESARROLLO.md
/docs/10_DECISIONES_TECNICAS.md
/docs/11_PROMPTS_CODEX.md
/docs/12_HISTORIAL_CAMBIOS.md

En /docs/02_FASES_DESARROLLO.md indicar que la Fase 1 corresponde a estructura visual, menu principal y home publica.

En /docs/10_DECISIONES_TECNICAS.md anadir que la primera version visual se crea sin logica compleja, priorizando estructura escalable y diseno tipo revista.

En /docs/11_PROMPTS_CODEX.md guardar este prompt como prompt de la Fase 1.

En /docs/12_HISTORIAL_CAMBIOS.md anadir una entrada indicando:
- Inicio de Fase 1.
- Creacion o ajuste del header publico.
- Creacion o ajuste de home tipo revista.
- Creacion o ajuste de footer.
- Preparacion visual inicial del proyecto.

RESULTADO ESPERADO

Al finalizar la Fase 1 debe existir una primera version visual navegable de la web publica con:

- Header principal.
- Menu publico.
- Home tipo revista.
- Secciones de ejemplo.
- Footer.
- Estilos globales.
- Documentacion actualizada.

No crear todavia:
- Login real.
- Panel de administracion.
- Panel de miembros.
- Panel de setters.
- Base de datos.
- Sistema de pagos.
- Sistema de comisiones.
- Agente IA real.

Solo preparar estructura visual publica inicial.
```

Resultado:

- Creada la primera version navegable de la web publica.
- Creado header con menu principal, acceso y llamada a miembros.
- Creada home tipo revista con hero, secciones de ejemplo y servicios.
- Creado footer publico con enlaces, contacto y placeholders sociales.
- Creados estilos globales iniciales sin logica compleja.

## Prompt 4 - Fase 2 - Publicidad local mediante banners

Fecha: 2026-06-21

```text
La web tendra los banners como una de sus fuentes de ingresos principales. Cada usuario debe ver publicidad de su localizacion, obteniendo la provincia desde su registro o mediante un popup para visitantes. Todas las paginas deben tener un sidebar derecho. Los banners se mostraran por categorias definidas por el navbar, excepto Servicios y Contacto.
```

Resultado:

- Creado un selector de provincia persistente para visitantes.
- Preparada la integracion con la provincia del perfil registrado.
- Creado el sidebar publicitario responsive.
- Implementado el cambio de categoria segun la seccion activa.
- Definido el respaldo mediante publicidad nacional.
- Documentado el futuro modelo de administracion, campanas y medicion.

## Prompt 5 - Fase 2 - Tarjetas de comunidad mas visitadas

Fecha: 2026-06-21

```text
El bloque Comunidad debe mostrar cuatro tarjetas elegidas entre todas las categorias segun el numero de visitas. Cada tarjeta tendra titulo, imagen principal, descripcion de no mas de 30 caracteres y boton Ver mas enlazado a la pagina concreta.
```

Resultado:

- Sustituido el listado del hero por una cuadricula de cuatro tarjetas.
- Anadidas imagen principal, categoria, titulo, descripcion corta y enlace.
- Definido el ranking global entre todas las categorias, sin cuotas por tipo.
- Generadas y optimizadas cuatro imagenes editoriales provisionales.

## Prompt 6 - Fase 2 - Rankings globales por categoria

Fecha: 2026-06-21

```text
Todas las secciones deben usar el formato editorial de tres posiciones. La imagen principal representa el primer puesto y las dos secundarias el segundo y tercer puesto. Las posiciones se obtendran por votacion o pagando promocion. Fotografia sera la penultima seccion y Flamenco, con Historia, Palos y Llaves de Oro, sera la ultima.
```

Resultado:

- Creada una plantilla reutilizable para rankings de tres posiciones.
- Aplicada la plantilla a todas las categorias publicas.
- Identificados visualmente los resultados Mas votado y Promocionado.
- Reordenadas Fotografia y Flamenco como las dos ultimas secciones de contenido.

## Prompt 7 - Fase 2 - Categoria Moda

Fecha: 2026-06-21

```text
Anadir Moda al menu como desplegable con Ropa, Calzado, Complementos y alguna categoria adicional adecuada.
```

Resultado:

- Anadida Moda al menu principal con acordeon responsive.
- Definidas Ropa, Calzado, Complementos y Moda infantil.
- Creado el ranking visual de Moda y su categoria publicitaria.
- Adaptado el controlador de navegacion para varios acordeones.

## Prompt 8 - Fase 2 - Cursos dentro de Academias

Fecha: 2026-06-21

```text
Crear Cursos y decidir si debe aparecer como menu independiente o dentro de Academias.
```

Resultado:

- Agrupado Cursos dentro del acordeon Academias para mantener limpio el menu.
- Creada una seccion independiente de Cursos.
- Definidas modalidades presencial, online y talleres intensivos.
- Anadidos ranking y publicidad propios para Cursos.

## Prompt 9 - Fase 2 - Paginas independientes y limpieza de portada

Fecha: 2026-06-22

```text
Comentar temporalmente Concursos en el menu y en la portada. Retirar Servicios destacados de la pagina principal y convertir Servicios en una pagina propia. Mantener Moda flamenca con sus tarjetas y estilo. Retirar Historia, Palos del flamenco y Llaves de Oro de la portada y convertir Flamenco en una pagina propia. Redondear las esquinas del footer.
```

Resultado:

- Comentados temporalmente el enlace y la seccion de Concursos.
- Creada una pagina publica propia para Servicios.
- Creada una pagina publica propia para Flamenco y sus tres apartados.
- Conservado el ranking visual de Moda flamenca.
- Anadidas esquinas redondeadas al footer.

## Pendiente de definir

- Formato final para prompts futuros.
- Criterios para archivar prompts repetidos.
- Relacion entre prompts y entradas del historial de cambios.

## Historial de cambios

- 2026-06-08: Registrado el primer prompt del proyecto correspondiente a la Fase 0.
- 2026-06-08: Registrada la creacion de AGENTS.md como archivo principal de instrucciones para agentes IA.
- 2026-06-08: Registrado el prompt de la Fase 1 sobre estructura visual publica.
- 2026-06-21: Registrado el prompt de la Fase 2 sobre publicidad local mediante banners.
- 2026-06-21: Registrado el prompt sobre tarjetas comunitarias ordenadas por visitas.
- 2026-06-21: Registrado el prompt sobre rankings por votos y promociones pagadas.
- 2026-06-21: Registrado el prompt para la nueva categoria Moda.
- 2026-06-21: Registrado el prompt para Cursos dentro de Academias.
- 2026-06-22: Registrado el prompt para separar Servicios y Flamenco y simplificar la portada.
