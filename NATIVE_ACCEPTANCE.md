# Aceptación nativa — Magento / Adobe Commerce

1. Instalar por Composer en Magento Open Source 2.4 compatible y ejecutar
   `module:enable`, `setup:upgrade`, compilación DI y modo producción.
2. Repetir en Adobe Commerce si se declara esa compatibilidad comercial.
3. Configurar dos websites sin compartir token, emisor ni estado.
4. Crear expedición completa, parcial y con MSI; comprobar una emisión por
   expedición y la huella de SKU/cantidad.
5. Reentregar el mensaje y ejecutar dos consumidores concurrentes; demostrar
   idempotencia y reclamación única.
6. Cortar Exchange, provocar 429 y 503, recuperar el servicio y reconciliar sin
   perder ni duplicar GOVP.
7. Probar devolución, cancelación posterior y política de revocación acordada.
8. Actualizar el módulo, desactivarlo y retirarlo conservando las referencias;
   eliminar la tabla solo mediante una operación de borrado separada y explícita.
9. Revisar permisos administrativos, cifrado del token, logs sin secretos,
   accesibilidad de configuración y tratamiento de datos personales.
10. Completar un piloto emisor por un usuario no técnico.

Guardar versiones, comandos y resultados reproducibles. Una prueba estructural
o simulada no supera esta puerta.
