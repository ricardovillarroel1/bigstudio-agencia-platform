/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `admin_action_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admin_action_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `admin_id` bigint unsigned DEFAULT NULL COMMENT 'Usuario admin que ejecutó la acción',
  `admin_email` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Email denormalizado para registro histórico',
  `action` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'marcar_pagada | emitir_dte | pausar | reanudar | reiniciar_ciclo',
  `target_type` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'factura_servicio | suscripcion',
  `target_id` bigint unsigned DEFAULT NULL,
  `target_user_id` bigint unsigned DEFAULT NULL COMMENT 'Cliente afectado (para filtrar todas las acciones sobre un cliente)',
  `metadata` json DEFAULT NULL COMMENT 'Contexto adicional: antes/después, monto, folio, etc.',
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `admin_action_logs_admin_id_index` (`admin_id`),
  KEY `admin_action_logs_target_user_id_index` (`target_user_id`),
  KEY `admin_action_logs_target_type_target_id_index` (`target_type`,`target_id`),
  KEY `admin_action_logs_action_index` (`action`),
  KEY `admin_action_logs_created_at_index` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `agencia_cliente_servicio`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `agencia_cliente_servicio` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `agencia_cliente_id` bigint unsigned NOT NULL,
  `agencia_servicio_id` bigint unsigned NOT NULL,
  `precio_acordado` decimal(12,0) NOT NULL DEFAULT '0',
  `inversion_publicidad` decimal(12,0) DEFAULT NULL,
  `plataforma_publicidad` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notas_internas` text COLLATE utf8mb4_unicode_ci,
  `estado` enum('activo','pausado','cancelado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'activo',
  `fecha_inicio` date DEFAULT NULL,
  `fecha_fin` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `agencia_cliente_servicio_agencia_cliente_id_foreign` (`agencia_cliente_id`),
  KEY `agencia_cliente_servicio_agencia_servicio_id_foreign` (`agencia_servicio_id`),
  CONSTRAINT `agencia_cliente_servicio_agencia_cliente_id_foreign` FOREIGN KEY (`agencia_cliente_id`) REFERENCES `agencia_clientes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `agencia_cliente_servicio_agencia_servicio_id_foreign` FOREIGN KEY (`agencia_servicio_id`) REFERENCES `agencia_servicios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `agencia_clientes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `agencia_clientes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefono` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rut` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `razon_social` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `giro` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `direccion_fiscal` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ciudad` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `region` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `comuna` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notas` text COLLATE utf8mb4_unicode_ci,
  `estado` enum('activo','inactivo') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'activo',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `agencia_cobros`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `agencia_cobros` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `agencia_cliente_id` bigint unsigned NOT NULL,
  `agencia_suscripcion_id` bigint unsigned DEFAULT NULL,
  `concepto` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cuota_numero` smallint unsigned DEFAULT NULL,
  `cuota_total` smallint unsigned DEFAULT NULL,
  `grupo_cuotas` varchar(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `monto` decimal(12,0) NOT NULL,
  `estado` enum('pendiente','pagado','anulado','vencido') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendiente',
  `metodo_pago` enum('transferencia','flow','otro') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `flow_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `comprobante_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `comprobante_original_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notas_admin` text COLLATE utf8mb4_unicode_ci,
  `pagado_at` timestamp NULL DEFAULT NULL,
  `vence_at` timestamp NULL DEFAULT NULL,
  `lioren_folio` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lioren_tipo_doc` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lioren_pdf_url` longtext COLLATE utf8mb4_unicode_ci,
  `lioren_xml_url` longtext COLLATE utf8mb4_unicode_ci,
  `factura_estado` enum('no_emitida','emitida','error','emitiendo') COLLATE utf8mb4_unicode_ci DEFAULT 'no_emitida',
  `factura_error` longtext COLLATE utf8mb4_unicode_ci,
  `reminder_2dias_at` timestamp NULL DEFAULT NULL,
  `reminder_dia_at` timestamp NULL DEFAULT NULL,
  `factura_enviada_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `agencia_cobros_agencia_cliente_id_foreign` (`agencia_cliente_id`),
  KEY `agencia_cobros_agencia_suscripcion_id_foreign` (`agencia_suscripcion_id`),
  CONSTRAINT `agencia_cobros_agencia_cliente_id_foreign` FOREIGN KEY (`agencia_cliente_id`) REFERENCES `agencia_clientes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `agencia_cobros_agencia_suscripcion_id_foreign` FOREIGN KEY (`agencia_suscripcion_id`) REFERENCES `agencia_suscripciones` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `agencia_correos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `agencia_correos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `agencia_cliente_id` bigint unsigned DEFAULT NULL,
  `destinatario_email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `destinatario_nombre` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `asunto` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `vista_previa` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contenido` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `adjuntos` json DEFAULT NULL,
  `estado` enum('borrador','enviado','error') COLLATE utf8mb4_unicode_ci DEFAULT 'borrador',
  `enviado_at` timestamp NULL DEFAULT NULL,
  `error_mensaje` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cliente` (`agencia_cliente_id`),
  KEY `idx_estado` (`estado`),
  KEY `idx_enviado` (`enviado_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `agencia_cotizacion_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `agencia_cotizacion_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `agencia_cotizacion_id` bigint unsigned NOT NULL,
  `agencia_servicio_id` bigint unsigned DEFAULT NULL,
  `codigo` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `descripcion` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cantidad` int NOT NULL DEFAULT '1',
  `precio_unitario_neto` int NOT NULL DEFAULT '0',
  `total_neto` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cotizacion` (`agencia_cotizacion_id`),
  CONSTRAINT `agencia_cotizacion_items_ibfk_1` FOREIGN KEY (`agencia_cotizacion_id`) REFERENCES `agencia_cotizaciones` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `agencia_cotizaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `agencia_cotizaciones` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `numero` int unsigned NOT NULL DEFAULT '10000',
  `agencia_cliente_id` bigint unsigned DEFAULT NULL,
  `cliente_nombre` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cliente_rut` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cliente_email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cliente_telefono` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cliente_direccion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cliente_giro` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subtotal_neto` int NOT NULL DEFAULT '0',
  `descuento_porcentaje` decimal(5,2) NOT NULL DEFAULT '0.00',
  `descuento_monto` int NOT NULL DEFAULT '0',
  `total_neto` int NOT NULL DEFAULT '0',
  `iva` int NOT NULL DEFAULT '0',
  `total` int NOT NULL DEFAULT '0',
  `notas` text COLLATE utf8mb4_unicode_ci,
  `estado` enum('borrador','enviada','aceptada','pagada','facturada','vencida','cancelada') COLLATE utf8mb4_unicode_ci DEFAULT 'borrador',
  `valida_hasta` date DEFAULT NULL,
  `flow_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `flow_order` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `factura_estado` enum('pendiente','emitida','error') COLLATE utf8mb4_unicode_ci DEFAULT 'pendiente',
  `lioren_dte_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lioren_folio` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lioren_pdf_url` longtext COLLATE utf8mb4_unicode_ci,
  `lioren_xml_url` longtext COLLATE utf8mb4_unicode_ci,
  `pagado_at` timestamp NULL DEFAULT NULL,
  `facturado_at` timestamp NULL DEFAULT NULL,
  `enviada_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_numero` (`numero`),
  KEY `idx_cliente` (`agencia_cliente_id`),
  KEY `idx_estado` (`estado`),
  KEY `idx_flow` (`flow_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `agencia_servicios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `agencia_servicios` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `precio` decimal(12,0) NOT NULL DEFAULT '0',
  `moneda` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'CLP',
  `precio_uf` decimal(10,4) DEFAULT NULL,
  `periodicidad` enum('mensual','trimestral','semestral','anual','unico') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'mensual',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `agencia_suscripcion_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `agencia_suscripcion_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `agencia_suscripcion_id` bigint unsigned NOT NULL,
  `agencia_servicio_id` bigint unsigned DEFAULT NULL,
  `descripcion` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `monto_neto` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_suscripcion` (`agencia_suscripcion_id`),
  CONSTRAINT `agencia_suscripcion_items_ibfk_1` FOREIGN KEY (`agencia_suscripcion_id`) REFERENCES `agencia_suscripciones` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `agencia_suscripciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `agencia_suscripciones` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `agencia_cliente_id` bigint unsigned NOT NULL,
  `agencia_cliente_servicio_id` bigint unsigned DEFAULT NULL,
  `concepto` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `monto` decimal(12,0) NOT NULL,
  `periodicidad` enum('mensual','trimestral','semestral','anual') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'mensual',
  `estado` enum('activa','pausada','cancelada','vencida') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'activa',
  `fecha_inicio` date NOT NULL,
  `proximo_cobro` date NOT NULL,
  `fecha_fin` date DEFAULT NULL,
  `facturacion_automatica` tinyint(1) NOT NULL DEFAULT '1',
  `dias_anticipacion_factura` int NOT NULL DEFAULT '5',
  `reminder_sent` tinyint(1) NOT NULL DEFAULT '0',
  `reminder_sent_day` tinyint(1) NOT NULL DEFAULT '0',
  `factura_ciclo_emitida` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `agencia_suscripciones_agencia_cliente_id_foreign` (`agencia_cliente_id`),
  KEY `agencia_suscripciones_agencia_cliente_servicio_id_foreign` (`agencia_cliente_servicio_id`),
  CONSTRAINT `agencia_suscripciones_agencia_cliente_id_foreign` FOREIGN KEY (`agencia_cliente_id`) REFERENCES `agencia_clientes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `agencia_suscripciones_agencia_cliente_servicio_id_foreign` FOREIGN KEY (`agencia_cliente_servicio_id`) REFERENCES `agencia_cliente_servicio` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `boletas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `boletas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `shopify_order_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lioren_id` int DEFAULT NULL,
  `tipodoc` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '39',
  `folio` int DEFAULT NULL,
  `fecha` date NOT NULL,
  `receptor_rut` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `receptor_nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `receptor_email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `monto_neto` decimal(10,2) NOT NULL DEFAULT '0.00',
  `monto_exento` decimal(10,2) NOT NULL DEFAULT '0.00',
  `monto_iva` decimal(10,2) NOT NULL DEFAULT '0.00',
  `monto_total` decimal(10,2) NOT NULL DEFAULT '0.00',
  `pdf_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `xml_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pdf_base64` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `xml_base64` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `detalles` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `pagos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `observaciones` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `error_message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `retry_count` int unsigned DEFAULT '0',
  `last_retry_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_boletas_order_user_folio` (`shopify_order_id`,`user_id`,`folio`),
  KEY `boletas_user_id_foreign` (`user_id`),
  CONSTRAINT `boletas_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `boletas_chk_1` CHECK (json_valid(`detalles`)),
  CONSTRAINT `boletas_chk_2` CHECK (json_valid(`pagos`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `categorias_gasto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categorias_gasto` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `color` varchar(7) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '#6366f1',
  `icono` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activa` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `centros_costo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `centros_costo` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `chat_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `chat_messages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `chat_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `mensaje` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `archivo_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `archivo_nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `leido` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `chat_messages_chat_id_foreign` (`chat_id`),
  KEY `chat_messages_user_id_foreign` (`user_id`),
  CONSTRAINT `chat_messages_chat_id_foreign` FOREIGN KEY (`chat_id`) REFERENCES `chats` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chat_messages_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `chats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `chats` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `cliente_id` bigint unsigned NOT NULL,
  `plan_id` bigint unsigned DEFAULT NULL,
  `contexto` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado` enum('activo','cerrado','abierto') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'activo',
  `mensaje_count` int NOT NULL DEFAULT '0',
  `ultimo_mensaje_at` timestamp NULL DEFAULT NULL,
  `cerrado_at` timestamp NULL DEFAULT NULL,
  `cerrado_por` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `chats_cliente_id_foreign` (`cliente_id`),
  KEY `chats_plan_id_foreign` (`plan_id`),
  CONSTRAINT `chats_cliente_id_foreign` FOREIGN KEY (`cliente_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chats_plan_id_foreign` FOREIGN KEY (`plan_id`) REFERENCES `planes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cierres_iva`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cierres_iva` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `mes` int NOT NULL,
  `anio` int NOT NULL,
  `iva_debito` decimal(15,2) NOT NULL DEFAULT '0.00',
  `iva_credito` decimal(15,2) NOT NULL DEFAULT '0.00',
  `remanente_anterior` decimal(15,2) NOT NULL DEFAULT '0.00',
  `iva_a_pagar` decimal(15,2) NOT NULL DEFAULT '0.00',
  `remanente_siguiente` decimal(15,2) NOT NULL DEFAULT '0.00',
  `estado` enum('abierto','cerrado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'abierto',
  `cerrado_por` bigint unsigned DEFAULT NULL,
  `cerrado_at` timestamp NULL DEFAULT NULL,
  `notas` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cierres_iva_mes_anio_unique` (`mes`,`anio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cliente_webhooks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cliente_webhooks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `solicitud_id` bigint unsigned DEFAULT NULL,
  `webhook_shopify_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `topic` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cliente_webhooks_user_id_foreign` (`user_id`),
  KEY `cliente_webhooks_solicitud_id_foreign` (`solicitud_id`),
  CONSTRAINT `cliente_webhooks_solicitud_id_foreign` FOREIGN KEY (`solicitud_id`) REFERENCES `solicitudes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `cliente_webhooks_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `clientes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `clientes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `empresa` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `razon_social` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rut` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefono` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefono_secundario` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `direccion` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `ciudad` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `region` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `codigo_postal` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `giro` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notas` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `estado` enum('activo','inactivo') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'activo',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `clientes_user_id_foreign` (`user_id`),
  CONSTRAINT `clientes_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cobros_asignados`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cobros_asignados` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `admin_id` bigint unsigned NOT NULL,
  `cliente_id` bigint unsigned NOT NULL,
  `concepto` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `monto` int NOT NULL,
  `estado` enum('pendiente','pagado','anulado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendiente',
  `flow_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_id` bigint unsigned DEFAULT NULL,
  `factura_servicio_id` bigint unsigned DEFAULT NULL,
  `pagado_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `admin_id` (`admin_id`),
  KEY `cliente_id` (`cliente_id`),
  CONSTRAINT `cobros_asignados_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`),
  CONSTRAINT `cobros_asignados_ibfk_2` FOREIGN KEY (`cliente_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `correos_integracion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `correos_integracion` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `destinatario_nombre` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `destinatario_email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `asunto` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contenido` text COLLATE utf8mb4_unicode_ci,
  `estado` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'enviado',
  `tipo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'individual',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cuentas_bancarias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cuentas_bancarias` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `banco` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo_cuenta` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'corriente',
  `numero_cuenta` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `moneda` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'CLP',
  `saldo_actual` decimal(15,2) NOT NULL DEFAULT '0.00',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cuentas_banco`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cuentas_banco` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `banco` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo_cuenta` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `numero_cuenta` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `titular` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rut_titular` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `saldo_actual` decimal(12,0) NOT NULL DEFAULT '0',
  `activa` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `empresas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `empresas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `disponible` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `empresas_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `facturas_compra`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `facturas_compra` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `proveedor_nombre` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `proveedor_rut` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `numero_factura` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha_emision` date NOT NULL,
  `fecha_vencimiento` date DEFAULT NULL,
  `monto_neto` decimal(12,0) NOT NULL DEFAULT '0',
  `monto_iva` decimal(12,0) NOT NULL DEFAULT '0',
  `monto_total` decimal(12,0) NOT NULL DEFAULT '0',
  `categoria_id` bigint unsigned DEFAULT NULL,
  `centro_costo_id` bigint unsigned DEFAULT NULL,
  `estado` enum('pendiente','pagada','vencida','anulada') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendiente',
  `metodo_pago` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pagada_at` timestamp NULL DEFAULT NULL,
  `archivo_pdf` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notas` text COLLATE utf8mb4_unicode_ci,
  `movimiento_banco_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `facturas_compra_categoria_id_foreign` (`categoria_id`),
  KEY `facturas_compra_centro_costo_id_foreign` (`centro_costo_id`),
  CONSTRAINT `facturas_compra_categoria_id_foreign` FOREIGN KEY (`categoria_id`) REFERENCES `categorias_gasto` (`id`) ON DELETE SET NULL,
  CONSTRAINT `facturas_compra_centro_costo_id_foreign` FOREIGN KEY (`centro_costo_id`) REFERENCES `centros_costo` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `facturas_emitidas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `facturas_emitidas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `shopify_order_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `shopify_order_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo_documento` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '33',
  `lioren_factura_id` int DEFAULT NULL,
  `folio` int DEFAULT NULL,
  `rut_receptor` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `razon_social` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `receptor_email` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `giro` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `direccion` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `comuna_id` int unsigned DEFAULT NULL,
  `ciudad_id` int unsigned DEFAULT NULL,
  `monto_neto` decimal(10,2) NOT NULL DEFAULT '0.00',
  `monto_iva` decimal(10,2) NOT NULL DEFAULT '0.00',
  `monto_total` decimal(10,2) NOT NULL DEFAULT '0.00',
  `detalles` json DEFAULT NULL,
  `pdf_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `xml_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pdf_base64` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `xml_base64` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `error_message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `retry_count` int unsigned DEFAULT '0',
  `last_retry_at` timestamp NULL DEFAULT NULL,
  `emitida_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `facturas_emitidas_user_id_foreign` (`user_id`),
  KEY `facturas_emitidas_shopify_order_id_index` (`shopify_order_id`),
  KEY `idx_facturas_order_user` (`shopify_order_id`,`user_id`),
  CONSTRAINT `facturas_emitidas_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `facturas_servicio`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `facturas_servicio` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `suscripcion_id` bigint unsigned DEFAULT NULL,
  `plan_id` bigint unsigned DEFAULT NULL,
  `payment_id` bigint unsigned DEFAULT NULL,
  `numero_factura` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `concepto` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `documentos_incluidos` int NOT NULL DEFAULT '0',
  `documentos_emitidos` int NOT NULL DEFAULT '0',
  `documentos_extra` int NOT NULL DEFAULT '0',
  `precio_extra_uf` decimal(10,4) NOT NULL DEFAULT '0.0000',
  `monto_extra_clp` decimal(12,0) NOT NULL DEFAULT '0',
  `monto_plan_clp` decimal(12,0) NOT NULL DEFAULT '0',
  `monto` decimal(12,2) NOT NULL DEFAULT '0.00',
  `monto_neto` decimal(12,0) NOT NULL DEFAULT '0',
  `monto_iva` decimal(12,0) NOT NULL DEFAULT '0',
  `moneda` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'CLP',
  `periodo_inicio` date DEFAULT NULL,
  `periodo_fin` date DEFAULT NULL,
  `estado` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pagada',
  `tipo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ciclo',
  `lioren_factura_id` int DEFAULT NULL,
  `folio` int DEFAULT NULL,
  `pdf_base64` longtext COLLATE utf8mb4_unicode_ci,
  `flow_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pagada_at` timestamp NULL DEFAULT NULL,
  `valor_uf_usado` decimal(12,2) NOT NULL DEFAULT '0.00',
  `pdf_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `facturas_servicio_user_id_foreign` (`user_id`),
  CONSTRAINT `facturas_servicio_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `gastos_operativos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `gastos_operativos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `concepto` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `monto` decimal(12,0) NOT NULL,
  `categoria_id` bigint unsigned DEFAULT NULL,
  `centro_costo_id` bigint unsigned DEFAULT NULL,
  `dia_pago` int NOT NULL DEFAULT '1',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `gastos_operativos_categoria_id_foreign` (`categoria_id`),
  KEY `gastos_operativos_centro_costo_id_foreign` (`centro_costo_id`),
  CONSTRAINT `gastos_operativos_categoria_id_foreign` FOREIGN KEY (`categoria_id`) REFERENCES `categorias_gasto` (`id`) ON DELETE SET NULL,
  CONSTRAINT `gastos_operativos_centro_costo_id_foreign` FOREIGN KEY (`centro_costo_id`) REFERENCES `centros_costo` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `importaciones_cartola`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `importaciones_cartola` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `cuenta_id` bigint unsigned NOT NULL,
  `archivo_original` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_movimientos` int NOT NULL DEFAULT '0',
  `duplicados_omitidos` int NOT NULL DEFAULT '0',
  `fecha_desde` date DEFAULT NULL,
  `fecha_hasta` date DEFAULT NULL,
  `importado_por` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `importaciones_cartola_cuenta_id_foreign` (`cuenta_id`),
  CONSTRAINT `importaciones_cartola_cuenta_id_foreign` FOREIGN KEY (`cuenta_id`) REFERENCES `cuentas_banco` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ingresos_manuales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ingresos_manuales` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `concepto` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `monto_neto` decimal(12,0) NOT NULL DEFAULT '0',
  `monto_iva` decimal(12,0) NOT NULL DEFAULT '0',
  `monto_total` decimal(12,0) NOT NULL DEFAULT '0',
  `fecha` date NOT NULL,
  `categoria` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cliente_nombre` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cliente_rut` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `numero_documento` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `movimiento_banco_id` bigint unsigned DEFAULT NULL,
  `notas` text COLLATE utf8mb4_unicode_ci,
  `centro_costo_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `integracion_configs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `integracion_configs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `solicitud_id` bigint unsigned DEFAULT NULL,
  `shopify_tienda` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `shopify_token` text COLLATE utf8mb4_unicode_ci,
  `shopify_client_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `shopify_client_secret` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `shopify_secret` text COLLATE utf8mb4_unicode_ci,
  `auth_method` enum('manual','oauth') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'oauth',
  `oauth_installed_at` timestamp NULL DEFAULT NULL,
  `shop_domain` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lioren_api_key` text COLLATE utf8mb4_unicode_ci,
  `facturacion_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `shopify_visibility_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `notas_credito_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `documentos_postventa_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `sync_inventario_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `default_bodega_id` int DEFAULT NULL,
  `order_limit_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `monthly_order_limit` int DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `ultima_sincronizacion` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `integracion_configs_user_id_activo_unique` (`user_id`,`activo`),
  KEY `integracion_configs_solicitud_id_foreign` (`solicitud_id`),
  KEY `integracion_configs_activo_index` (`activo`),
  CONSTRAINT `integracion_configs_solicitud_id_foreign` FOREIGN KEY (`solicitud_id`) REFERENCES `solicitudes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `integracion_configs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `iva_mensual`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `iva_mensual` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `anio` int NOT NULL,
  `mes` int NOT NULL,
  `debito_fiscal` decimal(12,0) NOT NULL DEFAULT '0',
  `credito_fiscal` decimal(12,0) NOT NULL DEFAULT '0',
  `remanente_anterior` decimal(12,0) NOT NULL DEFAULT '0',
  `iva_a_pagar` decimal(12,0) NOT NULL DEFAULT '0',
  `remanente_siguiente` decimal(12,0) NOT NULL DEFAULT '0',
  `estado` enum('borrador','cerrado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'borrador',
  `cerrado_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `iva_mensual_anio_mes_unique` (`anio`,`mes`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `location_bodega_mappings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `location_bodega_mappings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `shopify_location_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `shopify_location_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `lioren_bodega_id` int NOT NULL,
  `lioren_bodega_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `location_bodega_mappings_user_id_shopify_location_id_unique` (`user_id`,`shopify_location_id`),
  CONSTRAINT `location_bodega_mappings_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `meta_ad_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `meta_ad_accounts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `agencia_cliente_id` bigint unsigned DEFAULT NULL,
  `nombre_cuenta` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `act_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `moneda` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'CLP',
  `estado` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'activa',
  `reporte_emails` text COLLATE utf8mb4_unicode_ci,
  `reporte_dias` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reporte_activo` tinyint(1) NOT NULL DEFAULT '0',
  `reporte_token` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reporte_ultimo_envio` timestamp NULL DEFAULT NULL,
  `ultima_sync_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `meta_ad_accounts_act_id_unique` (`act_id`),
  KEY `meta_ad_accounts_agencia_cliente_id_index` (`agencia_cliente_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `meta_ad_insights`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `meta_ad_insights` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `meta_ad_account_id` bigint unsigned NOT NULL,
  `periodo` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nivel` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'cuenta',
  `objeto_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `objeto_nombre` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `inversion` bigint NOT NULL DEFAULT '0',
  `ventas` bigint NOT NULL DEFAULT '0',
  `compras` int NOT NULL DEFAULT '0',
  `alcance` bigint NOT NULL DEFAULT '0',
  `impresiones` bigint NOT NULL DEFAULT '0',
  `clicks` int NOT NULL DEFAULT '0',
  `extra` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `meta_ad_insights_meta_ad_account_id_periodo_nivel_index` (`meta_ad_account_id`,`periodo`,`nivel`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `model_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `model_has_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_roles` (
  `role_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `movimientos_bancarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `movimientos_bancarios` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `cuenta_bancaria_id` bigint unsigned NOT NULL,
  `fecha` date NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `monto` decimal(15,2) NOT NULL,
  `tipo` enum('ingreso','egreso') COLLATE utf8mb4_unicode_ci NOT NULL,
  `saldo` decimal(15,2) DEFAULT NULL,
  `estado_conciliacion` enum('pendiente','conciliado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendiente',
  `match_tipo` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `match_referencia_id` bigint unsigned DEFAULT NULL,
  `match_descripcion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `numero_operacion` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `movimientos_bancarios_cuenta_bancaria_id_fecha_index` (`cuenta_bancaria_id`,`fecha`),
  KEY `movimientos_bancarios_estado_conciliacion_index` (`estado_conciliacion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `movimientos_banco`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `movimientos_banco` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `cuenta_id` bigint unsigned NOT NULL,
  `fecha` date NOT NULL,
  `descripcion` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `referencia` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tipo` enum('ingreso','egreso') COLLATE utf8mb4_unicode_ci NOT NULL,
  `monto` decimal(12,0) NOT NULL,
  `saldo` decimal(12,0) DEFAULT NULL,
  `estado_conciliacion` enum('pendiente','conciliado','ignorado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendiente',
  `conciliado_con_tipo` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `conciliado_con_id` bigint unsigned DEFAULT NULL,
  `conciliado_at` timestamp NULL DEFAULT NULL,
  `importacion_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `movimientos_banco_importacion_id_foreign` (`importacion_id`),
  KEY `movimientos_banco_cuenta_id_fecha_index` (`cuenta_id`,`fecha`),
  KEY `movimientos_banco_estado_conciliacion_index` (`estado_conciliacion`),
  CONSTRAINT `movimientos_banco_cuenta_id_foreign` FOREIGN KEY (`cuenta_id`) REFERENCES `cuentas_banco` (`id`) ON DELETE CASCADE,
  CONSTRAINT `movimientos_banco_importacion_id_foreign` FOREIGN KEY (`importacion_id`) REFERENCES `importaciones_cartola` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notas_credito`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notas_credito` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `shopify_order_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `shopify_order_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo_documento_original` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `folio_original` int NOT NULL,
  `lioren_nota_id` int DEFAULT NULL,
  `folio` int DEFAULT NULL,
  `rut_receptor` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `razon_social` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `monto_neto` decimal(10,2) NOT NULL DEFAULT '0.00',
  `monto_iva` decimal(10,2) NOT NULL DEFAULT '0.00',
  `monto_total` decimal(10,2) NOT NULL DEFAULT '0.00',
  `pdf_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `xml_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pdf_base64` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `xml_base64` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `glosa` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `error_message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `emitida_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pago_transferencias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pago_transferencias` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `plan_id` bigint unsigned NOT NULL,
  `periodo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'mensual',
  `monto` int NOT NULL,
  `comprobante_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `comprobante_original_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pendiente','aprobado','rechazado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendiente',
  `notas_admin` text COLLATE utf8mb4_unicode_ci,
  `revisado_por` bigint unsigned DEFAULT NULL,
  `revisado_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pago_transferencias_user_id_foreign` (`user_id`),
  KEY `pago_transferencias_plan_id_foreign` (`plan_id`),
  KEY `pago_transferencias_revisado_por_foreign` (`revisado_por`),
  CONSTRAINT `pago_transferencias_plan_id_foreign` FOREIGN KEY (`plan_id`) REFERENCES `planes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pago_transferencias_revisado_por_foreign` FOREIGN KEY (`revisado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pago_transferencias_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_resets` (
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `flow_token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'CLP',
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment_method` int NOT NULL,
  `status` int NOT NULL DEFAULT '0',
  `flow_response` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `paid_at` timestamp NULL DEFAULT NULL,
  `periodo_inicio` date DEFAULT NULL,
  `periodo_fin` date DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `solicitud_id` bigint unsigned DEFAULT NULL,
  `suscripcion_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payments_order_id_unique` (`order_id`),
  KEY `payments_user_id_foreign` (`user_id`),
  KEY `payments_status_created_at_index` (`status`,`created_at`),
  KEY `payments_flow_token_index` (`flow_token`),
  KEY `payments_solicitud_id_foreign` (`solicitud_id`),
  KEY `payments_suscripcion_id_foreign` (`suscripcion_id`),
  CONSTRAINT `payments_solicitud_id_foreign` FOREIGN KEY (`solicitud_id`) REFERENCES `solicitudes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `payments_suscripcion_id_foreign` FOREIGN KEY (`suscripcion_id`) REFERENCES `suscripciones` (`id`) ON DELETE SET NULL,
  CONSTRAINT `payments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `payments_chk_1` CHECK (json_valid(`flow_response`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pending_location_mappings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pending_location_mappings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `shopify_location_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `shopify_location_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `affected_products_count` int NOT NULL DEFAULT '0',
  `status` enum('pending','notified','resolved') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `first_detected_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_notified_at` timestamp NULL DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pending_location_mappings_user_id_shopify_location_id_unique` (`user_id`,`shopify_location_id`),
  CONSTRAINT `pending_location_mappings_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `planes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `planes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `empresa_id` bigint unsigned NOT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `caracteristicas` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `facturacion_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `boletas_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `shopify_visibility_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `notas_credito_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `sync_inventario_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `documentos_postventa_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `order_limit_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `monthly_order_limit` int DEFAULT NULL,
  `limite_documentos` int DEFAULT NULL,
  `precio` decimal(10,2) NOT NULL,
  `precio_anual` decimal(10,2) DEFAULT NULL,
  `descuento_anual` int DEFAULT NULL,
  `plan_anual_activo` tinyint(1) NOT NULL DEFAULT '0',
  `moneda` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'CLP',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `planes_empresa_id_foreign` (`empresa_id`),
  CONSTRAINT `planes_empresa_id_foreign` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `presupuestos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `presupuestos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `anio` int NOT NULL,
  `mes` int NOT NULL,
  `categoria_id` bigint unsigned NOT NULL,
  `monto_presupuestado` decimal(12,0) NOT NULL DEFAULT '0',
  `monto_real` decimal(12,0) NOT NULL DEFAULT '0',
  `desviacion` decimal(5,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `presupuestos_anio_mes_categoria_id_unique` (`anio`,`mes`,`categoria_id`),
  KEY `presupuestos_categoria_id_foreign` (`categoria_id`),
  CONSTRAINT `presupuestos_categoria_id_foreign` FOREIGN KEY (`categoria_id`) REFERENCES `categorias_gasto` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `product_mappings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_mappings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `shopify_product_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `shopify_variant_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lioren_product_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sku` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `product_title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `stock` int NOT NULL DEFAULT '0',
  `sync_status` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `sync_error` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `last_synced_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_mappings_user_id_sku_index` (`user_id`,`sku`),
  KEY `product_mappings_shopify_product_id_index` (`shopify_product_id`),
  KEY `product_mappings_lioren_product_id_index` (`lioren_product_id`),
  KEY `product_mappings_sku_index` (`sku`),
  CONSTRAINT `product_mappings_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `role_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `role_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `settings_key_unique` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `solicitudes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `solicitudes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `cliente_id` bigint unsigned NOT NULL,
  `plan_id` bigint unsigned DEFAULT NULL,
  `tienda_shopify` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `descripcion` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `telefono` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `access_token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `api_secret` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `api_key` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `estado` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `integracion_conectada` tinyint(1) NOT NULL DEFAULT '0',
  `fecha_conexion` timestamp NULL DEFAULT NULL,
  `notas_admin` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `fecha_pago` timestamp NULL DEFAULT NULL,
  `flow_token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `solicitudes_cliente_id_foreign` (`cliente_id`),
  KEY `solicitudes_plan_id_foreign` (`plan_id`),
  KEY `solicitudes_estado_index` (`estado`),
  KEY `solicitudes_integracion_conectada_index` (`integracion_conectada`),
  CONSTRAINT `solicitudes_cliente_id_foreign` FOREIGN KEY (`cliente_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `solicitudes_plan_id_foreign` FOREIGN KEY (`plan_id`) REFERENCES `planes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `suscripciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `suscripciones` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `plan_id` bigint unsigned NOT NULL,
  `estado` enum('activa','vencida','cancelada') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'activa',
  `pausada` tinyint(1) NOT NULL DEFAULT '0',
  `pausada_at` timestamp NULL DEFAULT NULL,
  `motivo_pausa` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `origen` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pago',
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `proximo_pago` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `reminder_7d_sent` tinyint(1) NOT NULL DEFAULT '0',
  `reminder_3d_sent` tinyint(1) NOT NULL DEFAULT '0',
  `reminder_0d_sent` tinyint(1) NOT NULL DEFAULT '0',
  `suspension_email_sent` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `suscripciones_plan_id_foreign` (`plan_id`),
  KEY `suscripciones_user_id_estado_index` (`user_id`,`estado`),
  KEY `suscripciones_proximo_pago_index` (`proximo_pago`),
  CONSTRAINT `suscripciones_plan_id_foreign` FOREIGN KEY (`plan_id`) REFERENCES `planes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `suscripciones_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sync_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sync_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `sync_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'manual',
  `direction` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'bidirectional',
  `entity_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'product',
  `entity_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sku` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'success',
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `retry_count` int NOT NULL DEFAULT '0',
  `next_retry_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sync_logs_user_id_created_at_index` (`user_id`,`created_at`),
  KEY `sync_logs_status_next_retry_at_index` (`status`,`next_retry_at`),
  CONSTRAINT `sync_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sync_logs_chk_1` CHECK (json_valid(`data`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sync_queue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sync_queue` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `operation` enum('create','update','delete','sync_inventory') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `platform` enum('shopify','lioren') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `sku` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pending','processing','completed','failed') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `attempts` int NOT NULL DEFAULT '0',
  `max_attempts` int NOT NULL DEFAULT '3',
  `last_error` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `scheduled_at` timestamp NULL DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sync_queue_status_scheduled_at_index` (`status`,`scheduled_at`),
  KEY `sync_queue_user_id_status_index` (`user_id`,`status`),
  CONSTRAINT `sync_queue_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sync_queue_chk_1` CHECK (json_valid(`payload`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `system_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `system_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `system_settings_key_unique` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_module_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_module_permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `permission_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_module_permissions_user_id_permission_name_unique` (`user_id`,`permission_name`),
  KEY `user_module_permissions_user_id_index` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `connector_key` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `profile_photo_path` varchar(2048) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notif_dismissed` json DEFAULT NULL,
  `role` enum('admin','cliente','colaborador') COLLATE utf8mb4_unicode_ci DEFAULT 'cliente',
  `datos_facturacion_completos` tinyint(1) NOT NULL DEFAULT '0',
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `appstore_login_token` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `appstore_login_expires_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `connector_key` (`connector_key`),
  KEY `idx_appstore_token` (`appstore_login_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `warehouse_mappings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `warehouse_mappings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `sync_mode` enum('simple','advanced') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'simple',
  `default_bodega_id` int DEFAULT NULL,
  `default_bodega_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `warehouse_mappings_user_id_unique` (`user_id`),
  CONSTRAINT `warehouse_mappings_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

INSERT INTO `migrations` VALUES (1,'2014_10_12_000000_create_users_table',1);
INSERT INTO `migrations` VALUES (2,'2014_10_12_100000_create_password_resets_table',1);
INSERT INTO `migrations` VALUES (3,'2019_08_19_000000_create_failed_jobs_table',1);
INSERT INTO `migrations` VALUES (4,'2019_12_14_000001_create_personal_access_tokens_table',1);
INSERT INTO `migrations` VALUES (5,'2024_11_22_000000_create_integracion_configs_table',1);
INSERT INTO `migrations` VALUES (6,'2025_11_10_232435_add_role_to_users_table',1);
INSERT INTO `migrations` VALUES (7,'2025_12_08_210000_create_product_sync_tables',1);
INSERT INTO `migrations` VALUES (8,'2025_12_12_005834_add_role_to_users_table',1);
INSERT INTO `migrations` VALUES (9,'2025_12_12_012712_create_permission_tables',1);
INSERT INTO `migrations` VALUES (10,'2025_12_13_230748_create_clientes_table',1);
INSERT INTO `migrations` VALUES (11,'2025_12_15_150849_create_empresas_and_planes_tables',1);
INSERT INTO `migrations` VALUES (12,'2025_12_15_182321_create_chats_and_messages_tables',1);
INSERT INTO `migrations` VALUES (13,'2025_12_17_174416_create_solicitudes_table',1);
INSERT INTO `migrations` VALUES (14,'2026_01_01_005924_add_shopify_visibility_to_integracion_configs',1);
INSERT INTO `migrations` VALUES (15,'2026_01_01_012955_add_order_limit_to_integracion_configs',1);
INSERT INTO `migrations` VALUES (16,'2026_01_01_024438_create_notas_credito_table',1);
INSERT INTO `migrations` VALUES (17,'2026_01_01_024559_add_notas_credito_enabled_to_integracion_configs',1);
INSERT INTO `migrations` VALUES (18,'2026_01_08_235435_create_facturas_emitidas_table',1);
INSERT INTO `migrations` VALUES (19,'2026_01_10_002344_create_boletas_table',1);
INSERT INTO `migrations` VALUES (20,'2026_01_04_043052_create_payments_table',2);
INSERT INTO `migrations` VALUES (21,'2026_01_05_034818_add_solicitud_id_to_payments_table',2);
INSERT INTO `migrations` VALUES (22,'2026_01_12_201334_add_shopify_order_id_to_boletas_table',3);
INSERT INTO `migrations` VALUES (23,'2026_01_12_201934_add_shopify_order_id_to_boletas_table',3);
INSERT INTO `migrations` VALUES (24,'2026_01_16_000000_change_pdf_storage_to_file_path',4);
INSERT INTO `migrations` VALUES (25,'2026_01_17_000001_create_suscripciones_table',4);
INSERT INTO `migrations` VALUES (26,'2026_01_17_000002_add_suscripcion_fields_to_payments',4);
INSERT INTO `migrations` VALUES (27,'2026_01_17_000003_add_lioren_features_to_planes',99);
INSERT INTO `migrations` VALUES (28,'2026_01_17_180000_add_multicliente_integration_support',99);
INSERT INTO `migrations` VALUES (29,'2026_01_29_033431_create_jobs_table',99);
INSERT INTO `migrations` VALUES (30,'2026_03_19_001019_add_origen_and_facturas_servicio',100);
INSERT INTO `migrations` VALUES (31,'2026_03_19_192401_create_settings_table',101);
INSERT INTO `migrations` VALUES (32,'2026_03_19_230700_create_sessions_table',101);
INSERT INTO `migrations` VALUES (33,'2026_03_19_204240_extend_billing_system',102);
INSERT INTO `migrations` VALUES (34,'2026_03_20_010223_create_sessions_table',99);
INSERT INTO `migrations` VALUES (35,'2026_03_20_090828_add_documentos_postventa',103);
INSERT INTO `migrations` VALUES (36,'2026_03_25_000001_create_pago_transferencias_table',104);
INSERT INTO `migrations` VALUES (37,'2026_03_25_200000_create_servicios_agencia_tables',105);
INSERT INTO `migrations` VALUES (38,'2026_04_02_172649_add_retry_columns_to_emissions',106);
INSERT INTO `migrations` VALUES (39,'2026_04_02_172834_add_detalles_to_facturas_emitidas',107);
INSERT INTO `migrations` VALUES (47,'2026_05_25_000001_create_admin_action_logs_table',108);
INSERT INTO `migrations` VALUES (48,'2026_05_29_000001_add_profile_photo_to_users_table',109);
INSERT INTO `migrations` VALUES (49,'2026_05_29_100000_add_reminders_to_agencia_cobros',110);
INSERT INTO `migrations` VALUES (50,'2026_05_30_000001_add_notif_dismissed_to_users',111);
INSERT INTO `migrations` VALUES (51,'2026_05_30_000002_create_meta_ads_tables',112);
INSERT INTO `migrations` VALUES (52,'2026_05_30_000004_add_reporte_envio_to_meta_accounts',113);
