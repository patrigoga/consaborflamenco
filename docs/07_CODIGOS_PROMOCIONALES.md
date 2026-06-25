# Codigos Promocionales

## Descripcion

El sistema de codigos promocionales permitira aplicar descuentos a clientes y atribuir la captacion comercial al appointment setter correspondiente.

## Objetivo del documento

Definir las reglas iniciales del sistema de codigos promocionales y su relacion con leads, ventas y comisiones.

## Reglas del sistema

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

## Flujo previsto

- Administracion crea un codigo promocional.
- Administracion asigna el codigo a un setter.
- El cliente introduce el codigo en un formulario.
- El sistema valida el codigo.
- El sistema aplica el descuento si corresponde.
- El lead queda asociado al setter propietario.
- Si el lead se convierte en venta, se registra la comision.

## Datos iniciales previstos

- Codigo.
- Setter propietario.
- Porcentaje o importe de descuento.
- Fecha de inicio.
- Fecha de caducidad.
- Estado activo o inactivo.
- Numero de usos.
- Limite de usos, si aplica.

## Reglas y decisiones

- Los codigos promocionales solo podran ser creados por administracion.
- Un codigo no podra pertenecer a mas de un setter.
- El sistema comercial dependera de esta asociacion para calcular ventas y comisiones.

## Pendiente de definir

- Tipos de descuento.
- Limites de uso.
- Caducidad por campana.
- Reglas ante codigos duplicados.
- Politica de modificacion de codigos ya usados.

## Historial de cambios

- 2026-06-08: Documentadas las reglas iniciales de codigos promocionales.
