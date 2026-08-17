# GOVP for Magento / Adobe Commerce

Módulo abierto para emitir GOVP automáticamente al crear una expedición en
Magento Open Source o Adobe Commerce 2.4.

> Estado: candidato técnico `0.1.0`, todavía unidireccional. Requiere una prueba
> nativa limpia y no se presenta como extensión validada para producción.

## Qué hace

- se instala como módulo Composer `gemacode/module-govp`;
- configuración sin código por website en **Stores → Configuration → Sales → GOVP Exchange**;
- token protegido por el backend cifrado de configuración de Magento;
- observa `sales_order_shipment_save_commit_after` sin bloquear el checkout;
- publica únicamente el identificador de expedición en Message Queue Framework;
- usa AMQP, STOMP o base de datos según la conexión de cola de la instalación;
- reclama el trabajo en una tabla propia y conserva estado, intentos, código y URL;
- emite con clave idempotente por tienda y expedición;
- envía solo una huella SHA-256 de SKU, cantidad e identificadores mínimos;
- deja los fallos en `attention` para reintento/reconciliación.

La arquitectura sigue la documentación oficial de [registro de
componentes](https://developer.adobe.com/commerce/php/development/build/component-registration/)
y [Message Queue Framework](https://developer.adobe.com/commerce/php/development/components/message-queues/).

## Instalación de una candidata

```bash
composer config repositories.govp path /ruta/govp-for-magento
composer require gemacode/module-govp:0.1.0
bin/magento module:enable Gemacode_Govp
bin/magento setup:upgrade
bin/magento cache:flush
```

Configurar Exchange, token y emisor desde administración. Arrancar el consumidor:

```bash
bin/magento queue:consumers:start gemacode.govp.shipment.process --max-messages=100
```

## Desarrollo

```bash
composer validate --strict
find . -name '*.php' -not -path './vendor/*' -exec php -l {} \;
php tests/run.php
```

La comprobación automática de GOVP en recepción no forma parte de esta primera
candidata porque Magento es el sistema comercial emisor, no un ERP de entrada de
mercancías. Se añadirá cuando exista un flujo B2B receptor concreto que defina el
documento y la autoridad de aceptación.

## Puerta nativa

Consultar `NATIVE_ACCEPTANCE.md`. Deben verificarse Open Source y Adobe Commerce,
actualización, desinstalación, MSI, expedición parcial, devolución y cola real.

Magento y Adobe Commerce son marcas de Adobe. Este proyecto no está afiliado ni
certificado por Adobe.

## Licencia

Apache-2.0.
