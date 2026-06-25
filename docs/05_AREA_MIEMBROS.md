# Area de Miembros

## Descripcion

El area de miembros sera una zona privada donde artistas, academias, penas, tablaos, festivales, profesionales y entidades colaboradoras podran gestionar su presencia dentro de Con Sabor Flamenco.

## Objetivo del documento

Definir las capacidades previstas para los miembros y documentar los tipos de miembros contemplados en la plataforma.

## Tipos de miembros previstos

- Artista.
- Academia.
- Tienda flamenca.
- Pena flamenca.
- Tablao.
- Festival.
- Profesional flamenco.
- Entidad colaboradora.

## Funciones previstas

Los miembros podran:

- Acceder a su panel privado.
- Editar su perfil publico.
- Ver un avatar de perfil en la cabecera con menu desplegable.
- Cambiar contrasena desde el area privada.
- Subir o gestionar imagenes.
- Anadir biografia.
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

El registro exige:

- Nombre o proyecto.
- Tipo de espacio: artista, academia, tienda flamenca, pena flamenca, tablao flamenco, festival o profesional flamenco.
- Nombre publico artistico.
- Descripcion artistica minima.
- Ciudad y provincia.
- Fotografia principal obligatoria.
- Email unico.
- Contrasena robusta.
- Aceptacion de terminos y condiciones.

El acceso redirige al panel privado inicial `panel-usuario.php`, que queda preparado para desarrollar sus modulos por fases.

La recuperacion de contrasena se gestiona mediante enlace temporal enviado por email desde `recuperar-contrasena.php` y restablecimiento en `restablecer-contrasena.php`.

## Panel de miembro

El panel de miembro se organiza con:

- Cabecera con avatar de usuario y menu desplegable para editar perfil, cambiar contrasena y cerrar sesion.
- Sidebar izquierdo oscuro con accesos a Perfil, Tarjeta de miembro, Banners y Seguridad.
- Cabecera privada tipo dashboard con fotografia principal, nombre publico, tipo de espacio, ubicacion y metricas de estado.
- Bloque de perfil con datos principales del miembro.
- Editor de perfil artistico con tipo de espacio, nombre publico, descripcion, ubicacion, contacto, redes y fotografia principal.
- Editor de curriculum artistico con formacion, experiencia escenica, docencia, actuaciones destacadas, premios, repertorio, disponibilidad y notas privadas.
- Control de visibilidad por bloques para decidir que datos se publican y que datos quedan privados.
- Bloques repetibles con boton para anadir nuevas entradas sin limite fijo inicial.
- Fechas en formacion, experiencia, docencia, actuaciones y premios para permitir orden cronologico ascendente o descendente.
- Boton para imprimir o guardar el curriculum en PDF desde el navegador.
- La plantilla PDF debe ser compacta, aprovechar el ancho del papel y mantener la foto en color.
- Bloque de tarjeta identificativa con imagen a pantalla completa, nombre y nivel de membresia visible.
- Bloque de banners para preparar la compra de espacios publicitarios mediante Stripe.

## Tarjeta de miembro

La tarjeta de miembro sera una tarjeta visual tipo tarjeta de visita.

Datos previstos:

- Imagen de fondo al 100%.
- Nombre visible del miembro.
- Especialidad o titular artistico.
- Especialidades.
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
- Nombre publico.
- Descripcion artistica.
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
- Datos profesionales: especialidades, trayectoria, disponibilidad, web, Instagram y contacto.
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
