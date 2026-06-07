# Integración BCI API Market - Business Notifications
## Documentación guardada el 03/04/2026

---

## 1. Descripción General

La API **Business Notifications Sandbox** de BCI permite recibir notificaciones en tiempo real sobre transferencias y movimientos de una cuenta corriente de un cliente Empresa BCI. Funciona como un sistema de webhooks: BCI envía una notificación HTTP POST a una URL de callback cada vez que ocurre una transferencia en línea.

Portal público: https://www.bci.cl/apimarket

Portal de desarrolladores: https://developers.bci.cl/products

---

## 2. Credenciales de Acceso al Portal de Desarrolladores

| Campo | Valor |
|-------|-------|
| URL de login | https://developers.bci.cl/signin |
| Email | ricardoavillarroelgonzalez@gmail.com |
| Contraseña | Ravg9016* |
| Nombre | Ricardo Villarroel |
| Fecha de registro | 04/03/2026 |
| Suscripciones activas | Ninguna (pendiente de suscribirse al producto) |

---

## 3. Endpoints de la API

### 3.1 createSubscription

Registra una cuenta corriente empresa BCI para recibir notificaciones en una URL de callback.

| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| URL | POST | `https://apipartner.bci.cl/sandbox/v2/api-business-notifications/subscription` |
| OrganizationName | string | Nombre de la organización/empresa |
| Account | string | Número de cuenta corriente BCI |
| RUT | string | RUT de la empresa (sin dígito verificador) |
| CheckDigit | string | Dígito verificador del RUT |
| URLCallback | string | URL pública donde BCI enviará las notificaciones |
| APIKey | string | API key para validar las notificaciones recibidas |

**Headers requeridos:**

| Header | Valor |
|--------|-------|
| Content-Type | application/json |
| Cache-Control | no-cache |
| x-apikey | (API key de la suscripción) |
| Subscription key | (se obtiene al suscribirse al producto) |

### 3.2 simulateNotification

Simula una notificación de transferencia (solo disponible en ambiente sandbox para pruebas).

| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| URL | POST | `https://apipartner.bci.cl/sandbox/v2/api-business-notifications/simulations` |
| URLCallback | string | URL donde se enviará la notificación simulada |
| Amount | string | Monto de la transferencia simulada |
| MovementType | string | Tipo de movimiento (entrada/salida) |
| APIKey | string | API key para validar |

---

## 4. Flujo de Integración Propuesto

El flujo para implementar la integración cuando se decida avanzar es el siguiente:

**Paso 1:** Suscribirse al producto "Business Notifications Sandbox" desde el portal de desarrolladores para obtener la Subscription Key.

**Paso 2:** Crear un endpoint webhook en el servidor Laravel (por ejemplo `/api/bci/webhook`) que reciba las notificaciones POST de BCI.

**Paso 3:** Llamar al endpoint `createSubscription` con los datos de la cuenta corriente empresa BCI y la URL del webhook.

**Paso 4:** Procesar cada notificación recibida y registrarla automáticamente en la tabla `movimientos_banco` del sistema.

**Paso 5:** Conectar los movimientos recibidos con el módulo de conciliación bancaria existente para matchear transferencias con facturas/cobros.

---

## 5. Costos

El ambiente **Sandbox** (pruebas) es completamente gratuito, aunque tiene un límite de uso. Al pasar a **Producción** como Partner de BCI, podría existir un costo asociado que BCI informa durante el proceso de onboarding. No se especifica el monto públicamente.

---

## 6. Capacidades y Limitaciones

La API permite recibir notificaciones en tiempo real de transferencias en línea (entrantes y salientes) de cuentas corrientes empresa BCI. Esto es útil para automatizar el registro de movimientos bancarios y facilitar la conciliación.

Sin embargo, la API presenta las siguientes limitaciones: no permite consultar el saldo actual de la cuenta, no permite descargar cartolas históricas ni movimientos pasados, no cubre movimientos que no sean transferencias en línea (como cheques, PAC, cargos automáticos), y está disponible exclusivamente para cuentas de tipo Empresa BCI (no personas naturales).

---

## 7. Datos Necesarios para la Implementación

Cuando se decida avanzar, se necesitará contar con el RUT de la empresa y su dígito verificador, el número de cuenta corriente BCI empresa, un dominio o IP pública del servidor para recibir las notificaciones (URLCallback), y completar la suscripción al producto en el portal de desarrolladores.

---

## 8. Archivos Relacionados en el Servidor

La integración se conectaría con los siguientes componentes del sistema existente:

| Componente | Ubicación |
|------------|-----------|
| Controlador de finanzas | `/var/www/shopify-integrator/app/Http/Controllers/FinanzasController.php` |
| Vista de conciliación bancaria | `/var/www/shopify-integrator/resources/views/finanzas/banco.blade.php` |
| Tabla de movimientos | `movimientos_banco` |
| Tabla de cuentas | `cuentas_banco` |

---

*Documento generado por Manus - Pendiente de implementación futura.*
