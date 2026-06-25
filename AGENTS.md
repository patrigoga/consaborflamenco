# AGENTS.md - Instrucciones para agentes IA en el proyecto Con Sabor Flamenco

## 1. Descripción del proyecto

Este proyecto es la plataforma principal de Con Sabor Flamenco.

El objetivo es crear una web moderna tipo revista/comunidad para fomentar, promocionar y digitalizar el mundo del flamenco.

La plataforma tendrá:

- Parte pública tipo revista.
- Sección de artistas.
- Sección de academias.
- Sección de eventos.
- Sección de peñas flamencas.
- Sección de tablaos.
- Sección de festivales.
- Sección de servicios para miembros.
- Área privada para miembros.
- Panel de administración.
- Área privada para appointment setters.
- Sistema de leads.
- Sistema de ventas.
- Sistema de comisiones.
- Sistema de códigos promocionales.
- Sistema de solicitudes de cobro.
- Inbox para conversaciones y futuro agente IA.

## 2. Reglas generales de trabajo

Antes de modificar cualquier archivo, el agente debe:

1. Revisar la estructura actual del proyecto.
2. Entender qué archivos existen.
3. No eliminar archivos existentes salvo instrucción expresa.
4. No cambiar nombres de carpetas, rutas o archivos existentes sin justificación.
5. No romper funcionalidades ya implementadas.
6. Trabajar siempre por fases.
7. Documentar los cambios importantes.
8. Mantener el código limpio, ordenado y escalable.

## 3. Documentación obligatoria

El proyecto debe mantener documentación Markdown dentro de la carpeta:

/docs

Documentos principales:

- /docs/00_RESUMEN_PROYECTO.md
- /docs/01_ARQUITECTURA_GENERAL.md
- /docs/02_FASES_DESARROLLO.md
- /docs/03_MODELO_BASE_DATOS.md
- /docs/04_PANEL_ADMINISTRACION.md
- /docs/05_AREA_MIEMBROS.md
- /docs/06_AREA_SETTERS.md
- /docs/07_CODIGOS_PROMOCIONALES.md
- /docs/08_INBOX_AGENTE_IA.md
- /docs/09_SERVICIOS_Y_PLANES.md
- /docs/10_DECISIONES_TECNICAS.md
- /docs/11_PROMPTS_CODEX.md
- /docs/12_HISTORIAL_CAMBIOS.md

Cada vez que se realice una fase importante, se debe actualizar la documentación correspondiente.

## 4. Estilo visual del proyecto

El diseño debe ser moderno, elegante y visual.

Colores principales:

- Rojo suave.
- Azul suave.
- Negro.
- Blanco cálido.

La web debe transmitir:

- Flamenco.
- Cultura.
- Elegancia.
- Comunidad.
- Profesionalidad.
- Tecnología.
- Futuro.

Debe evitarse un diseño antiguo o sobrecargado.

## 5. Áreas principales del sistema

La plataforma se divide en estas áreas:

### Parte pública

Debe incluir:

- Inicio.
- Revista.
- Artistas.
- Academias.
- Eventos.
- Peñas flamencas.
- Tablaos.
- Festivales.
- Concursos.
- Servicios para miembros.
- Contacto.

### Panel de administración

El administrador podrá gestionar:

- Miembros.
- Artistas.
- Academias.
- Peñas.
- Tablaos.
- Festivales.
- Eventos.
- Artículos.
- Categorías.
- Servicios.
- Leads.
- Inbox.
- Appointment setters.
- Códigos promocionales.
- Clientes captados.
- Ventas.
- Comisiones.
- Solicitudes de cobro.
- Liquidaciones.
- Facturas o justificantes.

### Área de miembros

Los miembros podrán:

- Acceder a su panel privado.
- Editar su perfil público.
- Gestionar imágenes.
- Gestionar vídeos.
- Añadir biografía.
- Añadir redes sociales.
- Publicar o solicitar eventos.
- Ver solicitudes recibidas.
- Contratar servicios digitales.
- Ver estado de servicios contratados.

Tipos de miembros previstos:

- Artista.
- Academia.
- Peña flamenca.
- Tablao.
- Festival.
- Profesional flamenco.
- Entidad colaboradora.

### Área de appointment setters

Cada appointment setter tendrá su propio panel privado para:

- Ver su dashboard.
- Ver sus clientes.
- Ver sus leads.
- Ver sus ventas.
- Ver sus comisiones.
- Ver sus códigos promocionales asignados.
- Solicitar el cobro de comisiones.
- Ver pagos recibidos.
- Descargar justificantes o facturas de liquidación.

## 6. Sistema de códigos promocionales

Reglas obligatorias:

1. El administrador crea los códigos promocionales desde el panel de administración.
2. Cada código promocional debe ser único.
3. Cada código promocional se asigna a un único appointment setter.
4. El código sirve para aplicar descuento al cliente.
5. El código sirve para identificar qué setter ha captado al cliente.
6. El cliente introducirá el código en el formulario para obtener el descuento.
7. El sistema validará si el código existe, está activo y no está caducado.
8. Si el código es válido, el sistema asociará automáticamente el lead al setter propietario del código.
9. Si el lead se convierte en venta, la venta y la comisión quedarán asociadas al setter.
10. Un setter podrá tener uno o varios códigos promocionales.
11. La comisión se calculará preferiblemente sobre el importe realmente cobrado tras aplicar el descuento.

## 7. Sistema comercial

El sistema comercial deberá permitir:

- Registrar leads.
- Asociar leads a setters.
- Convertir leads en clientes.
- Convertir clientes en ventas.
- Calcular comisiones.
- Gestionar comisiones pendientes.
- Gestionar comisiones pagadas.
- Permitir solicitudes de cobro.
- Generar liquidaciones.
- Generar justificantes o facturas internas de pago.

## 8. Inbox y agente IA

La plataforma debe estar preparada para tener:

- Agente IA público en la web.
- Formulario inteligente.
- Inbox de administración.
- Historial de conversaciones.
- Estados de conversación.
- Asignación de conversaciones a miembros o setters.
- Conversión de conversaciones en leads.

Estados posibles de conversación:

- NUEVO.
- EN_CURSO.
- RESPONDIDO.
- CONVERTIDO.
- DESCARTADO.
- CERRADO.

## 9. Fases iniciales del proyecto

El proyecto debe desarrollarse por fases:

- Fase 0 - Documentación inicial.
- Fase 1 - Estructura visual y menú principal.
- Fase 2 - Diseño de home tipo revista.
- Fase 3 - Modelo inicial de base de datos.
- Fase 4 - Panel de administración.
- Fase 5 - Gestión de miembros.
- Fase 6 - Perfiles públicos de miembros.
- Fase 7 - Área de appointment setters.
- Fase 8 - Códigos promocionales.
- Fase 9 - Leads, ventas y comisiones.
- Fase 10 - Solicitudes de cobro y liquidaciones.
- Fase 11 - Inbox y agente IA.
- Fase 12 - Servicios digitales y planes.
- Fase 13 - API REST y preparación para app móvil.

## 10. Reglas para futuras modificaciones

Cuando se añada una nueva funcionalidad:

1. Revisar primero la documentación existente.
2. Añadir la funcionalidad sin romper lo anterior.
3. Mantener nombres claros.
4. Mantener separación entre lógica pública, administración, miembros y setters.
5. Actualizar el documento correspondiente dentro de /docs.
6. Actualizar /docs/12_HISTORIAL_CAMBIOS.md.
7. Si se usa un prompt importante, guardarlo en /docs/11_PROMPTS_CODEX.md.

## 11. Objetivo final

El objetivo es construir una de las mejores plataformas digitales dedicadas al flamenco, combinando:

- Revista.
- Comunidad.
- Promoción de artistas.
- Servicios digitales.
- Automatización.
- Agentes IA.
- Sistema comercial con setters.
- Gestión profesional de miembros, leads, ventas y comisiones.
