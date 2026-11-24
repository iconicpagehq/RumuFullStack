-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: 127.0.0.1    Database: rumu_v2
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `admin_broadcasts`
--

DROP TABLE IF EXISTS `admin_broadcasts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin_broadcasts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `admin_id` bigint(20) unsigned DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `audience` varchar(255) NOT NULL,
  `enterprise_id` bigint(20) unsigned DEFAULT NULL,
  `recipients_count` int(10) unsigned NOT NULL DEFAULT 0,
  `status` varchar(255) NOT NULL DEFAULT 'queued',
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `admin_broadcasts_admin_id_foreign` (`admin_id`),
  KEY `admin_broadcasts_enterprise_id_foreign` (`enterprise_id`),
  KEY `admin_broadcasts_audience_created_at_index` (`audience`,`created_at`),
  CONSTRAINT `admin_broadcasts_admin_id_foreign` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `admin_broadcasts_enterprise_id_foreign` FOREIGN KEY (`enterprise_id`) REFERENCES `enterprises` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_broadcasts`
--

LOCK TABLES `admin_broadcasts` WRITE;
/*!40000 ALTER TABLE `admin_broadcasts` DISABLE KEYS */;
/*!40000 ALTER TABLE `admin_broadcasts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `billing_notification_templates`
--

DROP TABLE IF EXISTS `billing_notification_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `billing_notification_templates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `body_content` longtext NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `billing_notification_templates_type_unique` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `billing_notification_templates`
--

LOCK TABLES `billing_notification_templates` WRITE;
/*!40000 ALTER TABLE `billing_notification_templates` DISABLE KEYS */;
/*!40000 ALTER TABLE `billing_notification_templates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `billing_notifications`
--

DROP TABLE IF EXISTS `billing_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `billing_notifications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `type` varchar(255) NOT NULL,
  `subscription_id` bigint(20) unsigned DEFAULT NULL,
  `payment_id` bigint(20) unsigned DEFAULT NULL,
  `invoice_id` bigint(20) unsigned DEFAULT NULL,
  `channel` enum('email','in_app','both') NOT NULL DEFAULT 'both',
  `status` enum('pending','sent','failed') NOT NULL DEFAULT 'pending',
  `error_message` text DEFAULT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `billing_notifications_user_id_foreign` (`user_id`),
  KEY `billing_notifications_subscription_id_foreign` (`subscription_id`),
  KEY `billing_notifications_payment_id_foreign` (`payment_id`),
  KEY `billing_notifications_invoice_id_foreign` (`invoice_id`),
  CONSTRAINT `billing_notifications_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `billing_notifications_payment_id_foreign` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `billing_notifications_subscription_id_foreign` FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `billing_notifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `billing_notifications`
--

LOCK TABLES `billing_notifications` WRITE;
/*!40000 ALTER TABLE `billing_notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `billing_notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `billing_settings`
--

DROP TABLE IF EXISTS `billing_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `billing_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) NOT NULL,
  `value` text DEFAULT NULL,
  `group` varchar(255) NOT NULL DEFAULT 'general',
  `type` varchar(255) NOT NULL DEFAULT 'string',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `billing_settings_key_unique` (`key`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `billing_settings`
--

LOCK TABLES `billing_settings` WRITE;
/*!40000 ALTER TABLE `billing_settings` DISABLE KEYS */;
INSERT INTO `billing_settings` VALUES (1,'company_name','RUMU CRM','company','string','2026-07-27 09:09:26','2026-07-27 09:09:26'),(2,'company_legal_name','RUMU Solutions Pvt Ltd.','company','string','2026-07-27 09:09:26','2026-07-27 09:09:26'),(3,'company_gst','GSTIN1234567890','company','string','2026-07-27 09:09:26','2026-07-27 09:09:26'),(4,'company_pan','ABCDE1234F','company','string','2026-07-27 09:09:26','2026-07-27 09:09:26'),(5,'company_email','billing@rumu.com','company','string','2026-07-27 09:09:26','2026-07-30 03:38:28'),(6,'company_phone','+1 234 567 8900','company','string','2026-07-27 09:09:26','2026-07-27 09:09:26'),(7,'company_address','123 Business Avenue, Suite 100, Tech District, City','company','string','2026-07-27 09:09:26','2026-07-27 09:09:26'),(8,'company_logo','','company','string','2026-07-27 09:09:26','2026-07-27 09:09:26'),(9,'company_signature','','company','string','2026-07-27 09:09:26','2026-07-27 09:09:26'),(10,'invoice_prefix','RUMU-INV-','invoice','string','2026-07-27 09:09:26','2026-07-27 09:09:26'),(11,'invoice_start_number','1000','invoice','integer','2026-07-27 09:09:26','2026-07-27 09:09:26'),(12,'invoice_footer','Thank you for your business. Please remit payment within 15 days.','invoice','string','2026-07-27 09:09:26','2026-07-27 09:09:26'),(13,'invoice_payment_terms','Net 15','invoice','string','2026-07-27 09:09:26','2026-07-27 09:09:26'),(14,'invoice_currency','USD','invoice','string','2026-07-27 09:09:26','2026-07-27 09:09:26'),(15,'tax_enabled','1','tax','boolean','2026-07-27 09:09:26','2026-07-27 09:09:26'),(16,'tax_default_gst_percent','18','tax','float','2026-07-27 09:09:26','2026-07-27 09:09:26'),(17,'tax_label','GST','tax','string','2026-07-27 09:09:26','2026-07-27 09:09:26'),(18,'tax_inclusive','0','tax','boolean','2026-07-27 09:09:26','2026-07-27 09:09:26'),(19,'sub_default_trial_days','14','subscription','integer','2026-07-27 09:09:26','2026-07-27 09:09:26'),(20,'sub_grace_period_days','3','subscription','integer','2026-07-27 09:09:26','2026-07-27 09:09:26'),(21,'sub_auto_expire','1','subscription','boolean','2026-07-27 09:09:26','2026-07-27 09:09:26'),(22,'payment_default_gateway','stripe','payment','string','2026-07-27 09:09:26','2026-07-27 09:09:26'),(23,'payment_test_mode','1','payment','boolean','2026-07-27 09:09:26','2026-07-27 09:09:26'),(24,'payment_retry_attempts','3','payment','integer','2026-07-27 09:09:26','2026-07-27 09:09:26'),(25,'notify_invoice_email_enabled','1','notification','boolean','2026-07-27 09:09:26','2026-07-27 09:09:26'),(26,'notify_payment_success_enabled','1','notification','boolean','2026-07-27 09:09:26','2026-07-27 09:09:26'),(27,'notify_renewal_reminder_enabled','1','notification','boolean','2026-07-27 09:09:26','2026-07-27 09:09:26'),(28,'reminder_sub_expiry_days','7','reminder','integer','2026-07-27 09:09:26','2026-07-27 09:09:26');
/*!40000 ALTER TABLE `billing_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `billing_settings_audits`
--

DROP TABLE IF EXISTS `billing_settings_audits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `billing_settings_audits` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `setting_key` varchar(255) NOT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `billing_settings_audits_user_id_foreign` (`user_id`),
  CONSTRAINT `billing_settings_audits_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `billing_settings_audits`
--

LOCK TABLES `billing_settings_audits` WRITE;
/*!40000 ALTER TABLE `billing_settings_audits` DISABLE KEYS */;
INSERT INTO `billing_settings_audits` VALUES (1,1,'company_email','billing@rumucrm.com','billing@rumu.com','2026-07-30 03:38:28','2026-07-30 03:38:28');
/*!40000 ALTER TABLE `billing_settings_audits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `call_logs`
--

DROP TABLE IF EXISTS `call_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `call_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL,
  `edition` varchar(16) NOT NULL,
  `realtor_user_id` bigint(20) unsigned DEFAULT NULL,
  `enterprise_id` bigint(20) unsigned DEFAULT NULL,
  `enterprise_membership_id` bigint(20) unsigned DEFAULT NULL,
  `team_id` bigint(20) unsigned DEFAULT NULL,
  `initiated_by` bigint(20) unsigned DEFAULT NULL,
  `lead_id` bigint(20) unsigned DEFAULT NULL,
  `campaign_id` bigint(20) unsigned DEFAULT NULL,
  `lead_name` varchar(255) DEFAULT NULL,
  `lead_phone` varchar(32) DEFAULT NULL,
  `provider` varchar(32) DEFAULT NULL,
  `provider_call_id` varchar(191) DEFAULT NULL,
  `direction` varchar(16) NOT NULL DEFAULT 'outbound',
  `source` varchar(24) NOT NULL DEFAULT 'app',
  `status` varchar(24) NOT NULL DEFAULT 'initiating',
  `disposition` varchar(32) DEFAULT NULL,
  `disposition_notes` text DEFAULT NULL,
  `from_number` varchar(32) DEFAULT NULL,
  `to_number` varchar(32) DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `answered_at` timestamp NULL DEFAULT NULL,
  `ended_at` timestamp NULL DEFAULT NULL,
  `duration_seconds` int(10) unsigned NOT NULL DEFAULT 0,
  `recording_status` varchar(24) NOT NULL DEFAULT 'none',
  `recording_disk` varchar(32) DEFAULT NULL,
  `recording_path` varchar(255) DEFAULT NULL,
  `recording_mime` varchar(64) DEFAULT NULL,
  `recording_size` bigint(20) unsigned DEFAULT NULL,
  `recording_duration_seconds` int(10) unsigned DEFAULT NULL,
  `recording_sha256` varchar(64) DEFAULT NULL,
  `recording_deleted_at` timestamp NULL DEFAULT NULL,
  `retention_hold` tinyint(1) NOT NULL DEFAULT 0,
  `consent_announced` tinyint(1) NOT NULL DEFAULT 0,
  `cost` decimal(10,4) DEFAULT NULL,
  `provider_payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`provider_payload`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `client_call_id` varchar(64) DEFAULT NULL,
  `device_uuid` varchar(64) DEFAULT NULL,
  `consent_confirmed_at` timestamp NULL DEFAULT NULL,
  `uploaded_at` timestamp NULL DEFAULT NULL,
  `matched_via` varchar(16) DEFAULT NULL,
  `client_metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`client_metadata`)),
  PRIMARY KEY (`id`),
  UNIQUE KEY `call_logs_uuid_unique` (`uuid`),
  UNIQUE KEY `call_logs_client_call_id_unique` (`client_call_id`),
  KEY `call_logs_realtor_user_id_foreign` (`realtor_user_id`),
  KEY `call_logs_enterprise_membership_id_foreign` (`enterprise_membership_id`),
  KEY `call_logs_team_id_foreign` (`team_id`),
  KEY `call_logs_campaign_id_foreign` (`campaign_id`),
  KEY `call_logs_edition_realtor_user_id_created_at_index` (`edition`,`realtor_user_id`,`created_at`),
  KEY `call_logs_enterprise_scope_idx` (`enterprise_id`,`enterprise_membership_id`,`created_at`),
  KEY `call_logs_lead_id_created_at_index` (`lead_id`,`created_at`),
  KEY `call_logs_initiated_by_status_index` (`initiated_by`,`status`),
  KEY `call_logs_edition_index` (`edition`),
  KEY `call_logs_provider_call_id_index` (`provider_call_id`),
  KEY `call_logs_status_index` (`status`),
  KEY `call_logs_disposition_index` (`disposition`),
  KEY `call_logs_recording_status_index` (`recording_status`),
  KEY `call_logs_recording_sha256_index` (`recording_sha256`),
  KEY `call_logs_device_uuid_index` (`device_uuid`),
  KEY `call_logs_lead_id_started_at_index` (`lead_id`,`started_at`),
  CONSTRAINT `call_logs_campaign_id_foreign` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`) ON DELETE SET NULL,
  CONSTRAINT `call_logs_enterprise_id_foreign` FOREIGN KEY (`enterprise_id`) REFERENCES `enterprises` (`id`) ON DELETE SET NULL,
  CONSTRAINT `call_logs_enterprise_membership_id_foreign` FOREIGN KEY (`enterprise_membership_id`) REFERENCES `enterprise_memberships` (`id`) ON DELETE SET NULL,
  CONSTRAINT `call_logs_initiated_by_foreign` FOREIGN KEY (`initiated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `call_logs_lead_id_foreign` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE SET NULL,
  CONSTRAINT `call_logs_realtor_user_id_foreign` FOREIGN KEY (`realtor_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `call_logs_team_id_foreign` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `call_logs`
--

LOCK TABLES `call_logs` WRITE;
/*!40000 ALTER TABLE `call_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `call_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `call_numbers`
--

DROP TABLE IF EXISTS `call_numbers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `call_numbers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `number` varchar(32) NOT NULL,
  `label` varchar(64) DEFAULT NULL,
  `provider` varchar(32) NOT NULL DEFAULT 'exotel',
  `provider_number_id` varchar(191) DEFAULT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `call_numbers_user_id_number_unique` (`user_id`,`number`),
  CONSTRAINT `call_numbers_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `call_numbers`
--

LOCK TABLES `call_numbers` WRITE;
/*!40000 ALTER TABLE `call_numbers` DISABLE KEYS */;
/*!40000 ALTER TABLE `call_numbers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `call_recording_access_logs`
--

DROP TABLE IF EXISTS `call_recording_access_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `call_recording_access_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `call_log_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(24) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `call_recording_access_logs_user_id_foreign` (`user_id`),
  KEY `call_recording_access_logs_call_log_id_action_index` (`call_log_id`,`action`),
  CONSTRAINT `call_recording_access_logs_call_log_id_foreign` FOREIGN KEY (`call_log_id`) REFERENCES `call_logs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `call_recording_access_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `call_recording_access_logs`
--

LOCK TABLES `call_recording_access_logs` WRITE;
/*!40000 ALTER TABLE `call_recording_access_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `call_recording_access_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `call_webhook_events`
--

DROP TABLE IF EXISTS `call_webhook_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `call_webhook_events` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `provider` varchar(32) NOT NULL,
  `event_id` varchar(191) NOT NULL,
  `call_log_id` bigint(20) unsigned DEFAULT NULL,
  `provider_call_id` varchar(191) DEFAULT NULL,
  `event_type` varchar(64) NOT NULL,
  `status` varchar(24) NOT NULL DEFAULT 'processed',
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payload`)),
  `error` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `call_webhook_events_provider_event_id_unique` (`provider`,`event_id`),
  KEY `call_webhook_events_call_log_id_foreign` (`call_log_id`),
  CONSTRAINT `call_webhook_events_call_log_id_foreign` FOREIGN KEY (`call_log_id`) REFERENCES `call_logs` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `call_webhook_events`
--

LOCK TABLES `call_webhook_events` WRITE;
/*!40000 ALTER TABLE `call_webhook_events` DISABLE KEYS */;
/*!40000 ALTER TABLE `call_webhook_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `campaigns`
--

DROP TABLE IF EXISTS `campaigns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `campaigns` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `enterprise_id` bigint(20) unsigned DEFAULT NULL,
  `team_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','paused','completed') NOT NULL DEFAULT 'active',
  `budget` decimal(15,2) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `meta_lead_form_id` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `campaigns_user_id_foreign` (`user_id`),
  KEY `campaigns_team_id_foreign` (`team_id`),
  KEY `campaigns_enterprise_id_status_index` (`enterprise_id`,`status`),
  CONSTRAINT `campaigns_enterprise_id_foreign` FOREIGN KEY (`enterprise_id`) REFERENCES `enterprises` (`id`) ON DELETE SET NULL,
  CONSTRAINT `campaigns_team_id_foreign` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE SET NULL,
  CONSTRAINT `campaigns_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `campaigns`
--

LOCK TABLES `campaigns` WRITE;
/*!40000 ALTER TABLE `campaigns` DISABLE KEYS */;
INSERT INTO `campaigns` VALUES (1,1,NULL,NULL,'Lead','asd','paused',NULL,NULL,NULL,NULL,'2026-01-19 13:59:26','2026-01-29 13:45:26','2026-01-29 13:45:26'),(2,3,NULL,NULL,'Mundhwa Flat','aencjka','active',NULL,NULL,NULL,NULL,'2026-01-29 13:22:20','2026-01-29 13:22:20',NULL),(3,1,NULL,NULL,'Q1 2026 Lead Generation Campaign',NULL,'active',NULL,NULL,NULL,NULL,'2026-01-29 15:11:03','2026-01-29 15:11:03',NULL),(4,4,NULL,NULL,'Pristine O2 world','✨ Pristine O₂ World – Kharadi ✨\r\nA Landmark Luxury Address in East Pune\r\n\r\n🏙️ Premium Residences Available\r\n2 | 3 | 3.5 | 4 | 4.5 BHK\r\n\r\n💰 2 BHK starting from ₹1.10 Cr*\r\n\r\n🌿 Why Pristine O₂ World?\r\n✔️ 100+ World-class amenities\r\n✔️ Ultra-luxury township living\r\n✔️ Prime Kharadi location\r\n✔️ Excellent connectivity to IT hubs\r\n✔️ High appreciation & rental demand\r\n\r\n📍 Near EON IT Park | World Trade Center | Top Schools & Hospitals\r\n\r\n📞 Call / WhatsApp now for:\r\n• Best price & exclusive offers\r\n• Floor plans & brochure\r\n• Site visit arrangement\r\n\r\n🚫 Zero Brokerage\r\n✅ Direct from Authorized Channel Partner\r\n\r\nReply “O2” to get full details instantly 💬','active',NULL,NULL,NULL,NULL,'2026-01-31 08:54:08','2026-01-31 08:54:08',NULL),(5,2,NULL,NULL,'Fb lead',NULL,'active',NULL,NULL,NULL,NULL,'2026-02-02 17:49:34','2026-06-06 15:34:25','2026-06-06 15:34:25'),(6,5,NULL,NULL,'Lodha','W','active',NULL,NULL,NULL,NULL,'2026-02-06 17:36:56','2026-02-06 17:36:56',NULL),(7,6,NULL,NULL,'Mankar Colony Test Home','2 3 bhk configution','active',NULL,NULL,NULL,NULL,'2026-04-20 09:57:43','2026-04-20 09:57:43',NULL),(8,7,NULL,NULL,'WYNG',NULL,'active',NULL,NULL,NULL,NULL,'2026-05-08 07:57:53','2026-05-08 07:57:53',NULL),(9,8,NULL,NULL,'WNYG Commercial Property',NULL,'active',NULL,NULL,NULL,NULL,'2026-06-02 07:59:06','2026-06-02 07:59:06',NULL),(10,11,NULL,NULL,'Ghfg','Vfjj','active',NULL,NULL,NULL,NULL,'2026-06-05 20:32:36','2026-06-05 20:32:36',NULL),(14,2,NULL,NULL,'Wyng Commercial Property','Office Spaces','active',NULL,NULL,NULL,NULL,'2026-06-17 16:32:58','2026-06-17 16:32:58',NULL),(16,16,NULL,NULL,'asd','asdf','active',NULL,NULL,NULL,NULL,'2026-07-10 07:39:59','2026-07-10 07:39:59',NULL),(17,15,NULL,NULL,'sdf','sdf','active',NULL,NULL,NULL,'all','2026-07-10 13:01:41','2026-07-18 02:54:49',NULL),(18,1,NULL,NULL,'Demo','Demo','active',NULL,NULL,NULL,NULL,'2026-07-30 03:50:58','2026-07-30 03:50:58',NULL),(19,19,NULL,NULL,'Demo','Demo','active',NULL,NULL,NULL,NULL,'2026-07-31 12:05:28','2026-07-31 12:05:28',NULL),(20,2,NULL,NULL,'Demo','Demo','active',NULL,NULL,NULL,NULL,'2026-08-14 01:31:00','2026-08-14 01:31:00',NULL),(21,19,NULL,NULL,'Demo',NULL,'active',NULL,NULL,NULL,NULL,'2026-10-31 05:29:18','2026-10-31 05:29:18',NULL);
/*!40000 ALTER TABLE `campaigns` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `coupon_usages`
--

DROP TABLE IF EXISTS `coupon_usages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `coupon_usages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `coupon_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `payment_id` bigint(20) unsigned NOT NULL,
  `subscription_id` bigint(20) unsigned DEFAULT NULL,
  `discount_amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `coupon_usages_coupon_id_foreign` (`coupon_id`),
  KEY `coupon_usages_user_id_foreign` (`user_id`),
  KEY `coupon_usages_payment_id_foreign` (`payment_id`),
  KEY `coupon_usages_subscription_id_foreign` (`subscription_id`),
  CONSTRAINT `coupon_usages_coupon_id_foreign` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE CASCADE,
  CONSTRAINT `coupon_usages_payment_id_foreign` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `coupon_usages_subscription_id_foreign` FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `coupon_usages_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `coupon_usages`
--

LOCK TABLES `coupon_usages` WRITE;
/*!40000 ALTER TABLE `coupon_usages` DISABLE KEYS */;
/*!40000 ALTER TABLE `coupon_usages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `coupons`
--

DROP TABLE IF EXISTS `coupons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `coupons` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `discount_type` enum('percentage','fixed') NOT NULL,
  `discount_value` decimal(10,2) NOT NULL,
  `max_discount_amount` decimal(10,2) DEFAULT NULL,
  `min_purchase_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `applicable_plans` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`applicable_plans`)),
  `start_date` datetime DEFAULT NULL,
  `expiration_date` datetime DEFAULT NULL,
  `usage_limit` int(11) DEFAULT NULL,
  `customer_usage_limit` int(11) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `coupons_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `coupons`
--

LOCK TABLES `coupons` WRITE;
/*!40000 ALTER TABLE `coupons` DISABLE KEYS */;
/*!40000 ALTER TABLE `coupons` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `enterprise_audit_logs`
--

DROP TABLE IF EXISTS `enterprise_audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `enterprise_audit_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `enterprise_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `subject_type` varchar(255) DEFAULT NULL,
  `subject_id` bigint(20) unsigned DEFAULT NULL,
  `description` text DEFAULT NULL,
  `meta` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `enterprise_audit_logs_user_id_foreign` (`user_id`),
  KEY `enterprise_audit_logs_enterprise_id_created_at_index` (`enterprise_id`,`created_at`),
  KEY `enterprise_audit_logs_enterprise_id_action_index` (`enterprise_id`,`action`),
  CONSTRAINT `enterprise_audit_logs_enterprise_id_foreign` FOREIGN KEY (`enterprise_id`) REFERENCES `enterprises` (`id`) ON DELETE CASCADE,
  CONSTRAINT `enterprise_audit_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `enterprise_audit_logs`
--

LOCK TABLES `enterprise_audit_logs` WRITE;
/*!40000 ALTER TABLE `enterprise_audit_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `enterprise_audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `enterprise_invitations`
--

DROP TABLE IF EXISTS `enterprise_invitations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `enterprise_invitations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `enterprise_id` bigint(20) unsigned NOT NULL,
  `email` varchar(255) NOT NULL,
  `role` varchar(255) NOT NULL DEFAULT 'employee',
  `team_id` bigint(20) unsigned DEFAULT NULL,
  `invited_by` bigint(20) unsigned DEFAULT NULL,
  `token_hash` varchar(64) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `accepted_at` timestamp NULL DEFAULT NULL,
  `revoked_at` timestamp NULL DEFAULT NULL,
  `accepted_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `reports_to_membership_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `enterprise_invitations_token_hash_unique` (`token_hash`),
  KEY `enterprise_invitations_team_id_foreign` (`team_id`),
  KEY `enterprise_invitations_invited_by_foreign` (`invited_by`),
  KEY `enterprise_invitations_accepted_by_foreign` (`accepted_by`),
  KEY `enterprise_invitations_enterprise_id_email_index` (`enterprise_id`,`email`),
  KEY `enterprise_invitations_enterprise_id_role_index` (`enterprise_id`,`role`),
  KEY `ent_invitations_reports_idx` (`reports_to_membership_id`),
  CONSTRAINT `ent_invitations_reports_fk` FOREIGN KEY (`reports_to_membership_id`) REFERENCES `enterprise_memberships` (`id`) ON DELETE SET NULL,
  CONSTRAINT `enterprise_invitations_accepted_by_foreign` FOREIGN KEY (`accepted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `enterprise_invitations_enterprise_id_foreign` FOREIGN KEY (`enterprise_id`) REFERENCES `enterprises` (`id`) ON DELETE CASCADE,
  CONSTRAINT `enterprise_invitations_invited_by_foreign` FOREIGN KEY (`invited_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `enterprise_invitations_team_id_foreign` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `enterprise_invitations`
--

LOCK TABLES `enterprise_invitations` WRITE;
/*!40000 ALTER TABLE `enterprise_invitations` DISABLE KEYS */;
/*!40000 ALTER TABLE `enterprise_invitations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `enterprise_memberships`
--

DROP TABLE IF EXISTS `enterprise_memberships`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `enterprise_memberships` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `enterprise_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `role` varchar(255) NOT NULL DEFAULT 'employee',
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `designation` varchar(255) DEFAULT NULL,
  `employee_code` varchar(255) DEFAULT NULL,
  `joined_at` timestamp NULL DEFAULT NULL,
  `suspended_at` timestamp NULL DEFAULT NULL,
  `invited_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `removed_at` timestamp NULL DEFAULT NULL,
  `reports_to_membership_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `enterprise_memberships_user_id_unique` (`user_id`),
  UNIQUE KEY `enterprise_memberships_enterprise_id_user_id_unique` (`enterprise_id`,`user_id`),
  KEY `enterprise_memberships_invited_by_foreign` (`invited_by`),
  KEY `enterprise_memberships_enterprise_id_role_status_index` (`enterprise_id`,`role`,`status`),
  KEY `enterprise_memberships_role_index` (`role`),
  KEY `enterprise_memberships_status_index` (`status`),
  KEY `ent_memberships_enterprise_reports_idx` (`enterprise_id`,`reports_to_membership_id`),
  KEY `ent_memberships_reports_idx` (`reports_to_membership_id`),
  CONSTRAINT `ent_memberships_reports_fk` FOREIGN KEY (`reports_to_membership_id`) REFERENCES `enterprise_memberships` (`id`) ON DELETE SET NULL,
  CONSTRAINT `enterprise_memberships_enterprise_id_foreign` FOREIGN KEY (`enterprise_id`) REFERENCES `enterprises` (`id`) ON DELETE CASCADE,
  CONSTRAINT `enterprise_memberships_invited_by_foreign` FOREIGN KEY (`invited_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `enterprise_memberships_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `enterprise_memberships`
--

LOCK TABLES `enterprise_memberships` WRITE;
/*!40000 ALTER TABLE `enterprise_memberships` DISABLE KEYS */;
/*!40000 ALTER TABLE `enterprise_memberships` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `enterprise_seat_requests`
--

DROP TABLE IF EXISTS `enterprise_seat_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `enterprise_seat_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `enterprise_id` bigint(20) unsigned NOT NULL,
  `seat_type` varchar(255) NOT NULL,
  `quantity` int(10) unsigned NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `note` text DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `requested_by` bigint(20) unsigned DEFAULT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `enterprise_seat_requests_requested_by_foreign` (`requested_by`),
  KEY `enterprise_seat_requests_approved_by_foreign` (`approved_by`),
  KEY `enterprise_seat_requests_enterprise_id_status_index` (`enterprise_id`,`status`),
  KEY `enterprise_seat_requests_status_index` (`status`),
  CONSTRAINT `enterprise_seat_requests_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `enterprise_seat_requests_enterprise_id_foreign` FOREIGN KEY (`enterprise_id`) REFERENCES `enterprises` (`id`) ON DELETE CASCADE,
  CONSTRAINT `enterprise_seat_requests_requested_by_foreign` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `enterprise_seat_requests`
--

LOCK TABLES `enterprise_seat_requests` WRITE;
/*!40000 ALTER TABLE `enterprise_seat_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `enterprise_seat_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `enterprises`
--

DROP TABLE IF EXISTS `enterprises`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `enterprises` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `legal_name` varchar(255) DEFAULT NULL,
  `slug` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `business_type` varchar(255) DEFAULT NULL,
  `company_size` varchar(255) DEFAULT NULL,
  `owner_id` bigint(20) unsigned DEFAULT NULL,
  `plan_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `trial_ends_at` timestamp NULL DEFAULT NULL,
  `settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`settings`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `rera_number` varchar(255) DEFAULT NULL,
  `working_hours` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`working_hours`)),
  `extra_managers` int(10) unsigned NOT NULL DEFAULT 0,
  `extra_employees` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `enterprises_slug_unique` (`slug`),
  UNIQUE KEY `enterprises_email_unique` (`email`),
  KEY `enterprises_owner_id_foreign` (`owner_id`),
  KEY `enterprises_plan_id_foreign` (`plan_id`),
  KEY `enterprises_status_index` (`status`),
  CONSTRAINT `enterprises_owner_id_foreign` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `enterprises_plan_id_foreign` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `enterprises`
--

LOCK TABLES `enterprises` WRITE;
/*!40000 ALTER TABLE `enterprises` DISABLE KEYS */;
/*!40000 ALTER TABLE `enterprises` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_floors`
--

DROP TABLE IF EXISTS `inventory_floors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory_floors` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `inventory_tower_id` bigint(20) unsigned NOT NULL,
  `inventory_wing_id` bigint(20) unsigned DEFAULT NULL,
  `floor_number` int(11) NOT NULL,
  `layout_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `inv_floors_tower_wing_floor_unique` (`inventory_tower_id`,`inventory_wing_id`,`floor_number`),
  KEY `inventory_floors_inventory_wing_id_foreign` (`inventory_wing_id`),
  CONSTRAINT `inventory_floors_inventory_tower_id_foreign` FOREIGN KEY (`inventory_tower_id`) REFERENCES `inventory_towers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inventory_floors_inventory_wing_id_foreign` FOREIGN KEY (`inventory_wing_id`) REFERENCES `inventory_wings` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_floors`
--

LOCK TABLES `inventory_floors` WRITE;
/*!40000 ALTER TABLE `inventory_floors` DISABLE KEYS */;
INSERT INTO `inventory_floors` VALUES (1,1,1,1,NULL,'2026-10-31 03:22:09','2026-10-31 03:22:09'),(2,1,NULL,5,NULL,'2026-10-31 03:37:36','2026-10-31 03:37:36'),(3,2,3,301,NULL,'2026-11-03 07:41:54','2026-11-03 07:41:54');
/*!40000 ALTER TABLE `inventory_floors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_projects`
--

DROP TABLE IF EXISTS `inventory_projects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory_projects` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `enterprise_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `inventory_projects_user_id_slug_unique` (`user_id`,`slug`),
  KEY `inventory_projects_enterprise_id_status_index` (`enterprise_id`,`status`),
  CONSTRAINT `inventory_projects_enterprise_id_foreign` FOREIGN KEY (`enterprise_id`) REFERENCES `enterprises` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inventory_projects_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_projects`
--

LOCK TABLES `inventory_projects` WRITE;
/*!40000 ALTER TABLE `inventory_projects` DISABLE KEYS */;
INSERT INTO `inventory_projects` VALUES (1,19,NULL,'Indra Heights','indra-heights','Hadapasar','Pune',NULL,'active','2026-10-31 03:10:53','2026-10-31 03:10:53',NULL),(2,19,NULL,'Indra Heights','indra-heights-1','Pune','Pune','Demo','active','2026-11-03 07:34:20','2026-11-03 07:34:20',NULL),(3,19,NULL,'Indra Heights','indra-heights-2','Hadapasar','Pune',NULL,'active','2026-11-03 08:56:00','2026-11-03 08:56:00',NULL);
/*!40000 ALTER TABLE `inventory_projects` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_towers`
--

DROP TABLE IF EXISTS `inventory_towers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory_towers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `inventory_project_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `inventory_towers_inventory_project_id_name_unique` (`inventory_project_id`,`name`),
  CONSTRAINT `inventory_towers_inventory_project_id_foreign` FOREIGN KEY (`inventory_project_id`) REFERENCES `inventory_projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_towers`
--

LOCK TABLES `inventory_towers` WRITE;
/*!40000 ALTER TABLE `inventory_towers` DISABLE KEYS */;
INSERT INTO `inventory_towers` VALUES (1,1,'Row House',10,'2026-10-31 03:21:34','2026-10-31 03:21:34'),(2,2,'Row House',1,'2026-11-03 07:41:15','2026-11-03 07:41:15'),(3,3,'A',1,'2026-11-03 09:14:57','2026-11-03 09:14:57'),(4,3,'Row House 1',0,'2026-11-03 09:15:33','2026-11-03 09:15:33');
/*!40000 ALTER TABLE `inventory_towers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_unit_status_logs`
--

DROP TABLE IF EXISTS `inventory_unit_status_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory_unit_status_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `inventory_unit_id` bigint(20) unsigned NOT NULL,
  `old_status` varchar(255) DEFAULT NULL,
  `new_status` varchar(255) NOT NULL,
  `changed_by` bigint(20) unsigned DEFAULT NULL,
  `lead_id` bigint(20) unsigned DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `inventory_unit_status_logs_inventory_unit_id_foreign` (`inventory_unit_id`),
  KEY `inventory_unit_status_logs_changed_by_foreign` (`changed_by`),
  KEY `inventory_unit_status_logs_lead_id_foreign` (`lead_id`),
  CONSTRAINT `inventory_unit_status_logs_changed_by_foreign` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inventory_unit_status_logs_inventory_unit_id_foreign` FOREIGN KEY (`inventory_unit_id`) REFERENCES `inventory_units` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inventory_unit_status_logs_lead_id_foreign` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_unit_status_logs`
--

LOCK TABLES `inventory_unit_status_logs` WRITE;
/*!40000 ALTER TABLE `inventory_unit_status_logs` DISABLE KEYS */;
INSERT INTO `inventory_unit_status_logs` VALUES (1,1,'available','booked',19,NULL,NULL,'2026-10-31 03:23:23');
/*!40000 ALTER TABLE `inventory_unit_status_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_units`
--

DROP TABLE IF EXISTS `inventory_units`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory_units` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `inventory_floor_id` bigint(20) unsigned NOT NULL,
  `unit_number` varchar(255) NOT NULL,
  `layout` enum('studio','1_bhk','2_bhk','3_bhk','4_bhk','4+_bhk') DEFAULT NULL,
  `carpet_area` decimal(10,2) DEFAULT NULL,
  `built_up_area` decimal(10,2) DEFAULT NULL,
  `facing` varchar(255) DEFAULT NULL,
  `base_price` decimal(15,2) DEFAULT NULL,
  `availability_status` enum('available','hold','booked','sold') NOT NULL DEFAULT 'available',
  `layout_image` varchar(255) DEFAULT NULL,
  `lead_id` bigint(20) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `inv_units_floor_number_unique` (`inventory_floor_id`,`unit_number`),
  KEY `inventory_units_lead_id_foreign` (`lead_id`),
  CONSTRAINT `inventory_units_inventory_floor_id_foreign` FOREIGN KEY (`inventory_floor_id`) REFERENCES `inventory_floors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inventory_units_lead_id_foreign` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_units`
--

LOCK TABLES `inventory_units` WRITE;
/*!40000 ALTER TABLE `inventory_units` DISABLE KEYS */;
INSERT INTO `inventory_units` VALUES (1,1,'A1',NULL,1000.00,1200.00,NULL,650000.00,'booked',NULL,NULL,NULL,'2026-10-31 03:23:09','2026-10-31 03:23:23',NULL),(2,1,'A2',NULL,1000.00,1200.00,NULL,650000.00,'available',NULL,NULL,NULL,'2026-10-31 03:23:09','2026-10-31 03:23:09',NULL),(3,1,'A3',NULL,1000.00,1200.00,NULL,650000.00,'available',NULL,NULL,NULL,'2026-10-31 03:23:10','2026-10-31 03:23:10',NULL),(4,1,'A4',NULL,1000.00,1200.00,NULL,650000.00,'available',NULL,NULL,NULL,'2026-10-31 03:23:10','2026-10-31 03:23:10',NULL),(5,1,'A5',NULL,1000.00,1200.00,NULL,650000.00,'available',NULL,NULL,NULL,'2026-10-31 03:23:10','2026-10-31 03:23:10',NULL),(6,1,'A6',NULL,1000.00,1200.00,NULL,650000.00,'available',NULL,NULL,NULL,'2026-10-31 03:23:10','2026-10-31 03:23:10',NULL),(7,1,'A7',NULL,1000.00,1200.00,NULL,650000.00,'available',NULL,NULL,NULL,'2026-10-31 03:23:10','2026-10-31 03:23:10',NULL),(8,1,'A8',NULL,1000.00,1200.00,NULL,650000.00,'available',NULL,NULL,NULL,'2026-10-31 03:23:10','2026-10-31 03:23:10',NULL),(9,1,'A9',NULL,1000.00,1200.00,NULL,650000.00,'available',NULL,NULL,NULL,'2026-10-31 03:23:10','2026-10-31 03:23:10',NULL),(10,1,'A10',NULL,1000.00,1200.00,NULL,650000.00,'available',NULL,NULL,NULL,'2026-10-31 03:23:10','2026-10-31 03:23:10',NULL),(11,3,'A301','1_bhk',5000.00,5000.00,NULL,6500000.00,'available',NULL,NULL,NULL,'2026-11-03 07:42:25','2026-11-03 07:42:25',NULL),(12,3,'A302','1_bhk',5000.00,5000.00,NULL,6500000.00,'available',NULL,NULL,NULL,'2026-11-03 07:42:25','2026-11-03 07:42:25',NULL),(13,3,'A303','1_bhk',5000.00,5000.00,NULL,6500000.00,'available',NULL,NULL,NULL,'2026-11-03 07:42:25','2026-11-03 07:42:25',NULL),(14,3,'A304','1_bhk',5000.00,5000.00,NULL,6500000.00,'available',NULL,NULL,NULL,'2026-11-03 07:42:25','2026-11-03 07:42:25',NULL),(15,3,'A305','1_bhk',5000.00,5000.00,NULL,6500000.00,'available',NULL,NULL,NULL,'2026-11-03 07:42:25','2026-11-03 07:42:25',NULL),(16,3,'A306','1_bhk',5000.00,5000.00,NULL,6500000.00,'available',NULL,NULL,NULL,'2026-11-03 07:42:25','2026-11-03 07:42:25',NULL),(17,3,'A307','1_bhk',5000.00,5000.00,NULL,6500000.00,'available',NULL,NULL,NULL,'2026-11-03 07:42:25','2026-11-03 07:42:25',NULL),(18,3,'A308','1_bhk',5000.00,5000.00,NULL,6500000.00,'available',NULL,NULL,NULL,'2026-11-03 07:42:25','2026-11-03 07:42:25',NULL),(19,3,'A309','1_bhk',5000.00,5000.00,NULL,6500000.00,'available',NULL,NULL,NULL,'2026-11-03 07:42:25','2026-11-03 07:42:25',NULL),(20,3,'A310','1_bhk',5000.00,5000.00,NULL,6500000.00,'available',NULL,NULL,NULL,'2026-11-03 07:42:25','2026-11-03 07:42:25',NULL);
/*!40000 ALTER TABLE `inventory_units` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_wings`
--

DROP TABLE IF EXISTS `inventory_wings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory_wings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `inventory_tower_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `inventory_wings_inventory_tower_id_name_unique` (`inventory_tower_id`,`name`),
  CONSTRAINT `inventory_wings_inventory_tower_id_foreign` FOREIGN KEY (`inventory_tower_id`) REFERENCES `inventory_towers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_wings`
--

LOCK TABLES `inventory_wings` WRITE;
/*!40000 ALTER TABLE `inventory_wings` DISABLE KEYS */;
INSERT INTO `inventory_wings` VALUES (1,1,'A',10,'2026-10-31 03:21:59','2026-10-31 03:21:59'),(2,1,'B',0,'2026-10-31 03:36:47','2026-10-31 03:36:47'),(3,2,'East',1,'2026-11-03 07:41:27','2026-11-03 07:41:27');
/*!40000 ALTER TABLE `inventory_wings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `invoice_items`
--

DROP TABLE IF EXISTS `invoice_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `invoice_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` bigint(20) unsigned NOT NULL,
  `description` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `invoice_items_invoice_id_foreign` (`invoice_id`),
  CONSTRAINT `invoice_items_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `invoice_items`
--

LOCK TABLES `invoice_items` WRITE;
/*!40000 ALTER TABLE `invoice_items` DISABLE KEYS */;
INSERT INTO `invoice_items` VALUES (1,1,'Pro Plan - 12 Months',1,1500.00,1500.00,'2026-07-27 10:30:32','2026-07-27 10:30:32'),(2,2,'Pro Plan - 12 Months',1,1500.00,1500.00,'2026-07-28 03:49:01','2026-07-28 03:49:01'),(3,3,'Preminum Plan - 12 Months',1,2000.00,2000.00,'2026-07-29 02:59:48','2026-07-29 02:59:48'),(4,4,'Pro Plan - 12 Months',1,1500.00,1500.00,'2026-07-29 06:50:30','2026-07-29 06:50:30'),(10,10,'Pro Plan - 12 Months',1,1500.00,1500.00,'2026-07-29 07:06:33','2026-07-29 07:06:33'),(11,11,'Pro Plan - 12 Months',1,1500.00,1500.00,'2026-07-29 07:09:13','2026-07-29 07:09:13'),(12,12,'Preminum Plan - 12 Months',1,2000.00,2000.00,'2026-07-29 07:35:57','2026-07-29 07:35:57'),(13,13,'Pro Plan - 12 Months',1,1500.00,1500.00,'2026-07-29 07:45:26','2026-07-29 07:45:26'),(14,14,'Preminum Plan - 12 Months',1,2000.00,2000.00,'2026-07-29 07:50:11','2026-07-29 07:50:11'),(15,15,'Pro Plan - 12 Months',1,1500.00,1500.00,'2026-07-29 09:17:14','2026-07-29 09:17:14'),(16,16,'Pro Plan - 12 Months',1,1500.00,1500.00,'2026-07-30 14:05:59','2026-07-30 14:05:59'),(17,17,'Pro Plan - 12 Months',1,1500.00,1500.00,'2026-07-30 14:12:30','2026-07-30 14:12:30');
/*!40000 ALTER TABLE `invoice_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `invoices`
--

DROP TABLE IF EXISTS `invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `invoices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_number` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `subscription_id` bigint(20) unsigned DEFAULT NULL,
  `payment_id` bigint(20) unsigned DEFAULT NULL,
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tax` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` varchar(255) NOT NULL DEFAULT 'draft',
  `pdf_path` varchar(255) DEFAULT NULL,
  `emailed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoices_invoice_number_unique` (`invoice_number`),
  KEY `invoices_user_id_foreign` (`user_id`),
  KEY `invoices_subscription_id_foreign` (`subscription_id`),
  KEY `invoices_payment_id_foreign` (`payment_id`),
  KEY `invoices_invoice_number_index` (`invoice_number`),
  KEY `invoices_status_index` (`status`),
  CONSTRAINT `invoices_payment_id_foreign` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `invoices_subscription_id_foreign` FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `invoices_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `invoices`
--

LOCK TABLES `invoices` WRITE;
/*!40000 ALTER TABLE `invoices` DISABLE KEYS */;
INSERT INTO `invoices` VALUES (1,'RUMU-INV-001000',17,14,14,1500.00,0.00,0.00,1500.00,'paid','invoices/RUMU-INV-001000.pdf',NULL,'2026-07-27 10:30:32','2026-07-28 04:49:11'),(2,'RUMU-INV-001001',18,15,15,1500.00,0.00,0.00,1500.00,'paid',NULL,NULL,'2026-07-28 03:49:01','2026-07-28 04:49:11'),(3,'RUMU-INV-001002',18,16,16,2000.00,360.00,0.00,2360.00,'paid','invoices/RUMU-INV-001002.pdf',NULL,'2026-07-29 02:59:48','2026-07-29 03:00:09'),(4,'RUMU-INV-001003',18,17,17,1500.00,270.00,0.00,1770.00,'paid',NULL,NULL,'2026-07-29 06:50:30','2026-07-29 06:50:30'),(10,'RUMU-INV-001004',18,19,19,1500.00,270.00,0.00,1770.00,'paid','invoices/RUMU-INV-001004.pdf',NULL,'2026-07-29 07:06:32','2026-07-29 07:06:33'),(11,'RUMU-INV-001005',19,20,20,1500.00,270.00,0.00,1770.00,'paid','invoices/RUMU-INV-001005.pdf',NULL,'2026-07-29 07:09:13','2026-07-29 07:09:13'),(12,'RUMU-INV-001006',19,21,21,2000.00,360.00,0.00,2360.00,'paid','invoices/RUMU-INV-001006.pdf',NULL,'2026-07-29 07:35:57','2026-07-29 07:36:04'),(13,'RUMU-INV-001007',19,22,22,1500.00,270.00,0.00,1770.00,'paid','invoices/RUMU-INV-001007.pdf',NULL,'2026-07-29 07:45:26','2026-07-29 07:45:27'),(14,'RUMU-INV-001008',19,25,25,2000.00,360.00,0.00,2360.00,'paid','invoices/RUMU-INV-001008.pdf',NULL,'2026-07-29 07:50:11','2026-07-29 07:50:12'),(15,'RUMU-INV-001009',19,27,27,1500.00,270.00,0.00,1770.00,'paid','invoices/RUMU-INV-001009.pdf',NULL,'2026-07-29 09:17:14','2026-07-29 09:17:21'),(16,'RUMU-INV-001010',19,29,29,1500.00,270.00,0.00,1770.00,'paid','invoices/RUMU-INV-001010.pdf',NULL,'2026-07-30 14:05:59','2026-07-30 14:06:05'),(17,'RUMU-INV-001011',19,30,30,1500.00,270.00,0.00,1770.00,'paid','invoices/RUMU-INV-001011.pdf',NULL,'2026-07-30 14:12:30','2026-07-30 14:12:37');
/*!40000 ALTER TABLE `invoices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB AUTO_INCREMENT=77 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
INSERT INTO `jobs` VALUES (1,'default','{\"uuid\":\"982be113-8471-4ee7-a140-031143e50a98\",\"displayName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"30,120,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"command\":\"O:33:\\\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\\\":2:{s:6:\\\"leadId\\\";i:15;s:9:\\\"crmStatus\\\";s:3:\\\"new\\\";}\"},\"createdAt\":1784363092,\"delay\":null}',0,NULL,1784363092,1784363092),(2,'default','{\"uuid\":\"ffe97835-a751-485a-8145-955de08dc38d\",\"displayName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"30,120,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"command\":\"O:33:\\\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\\\":2:{s:6:\\\"leadId\\\";i:16;s:9:\\\"crmStatus\\\";s:3:\\\"new\\\";}\"},\"createdAt\":1784363092,\"delay\":null}',0,NULL,1784363092,1784363092),(3,'default','{\"uuid\":\"a38f03d0-90d2-4a13-8758-bb86ba99dbeb\",\"displayName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"30,120,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"command\":\"O:33:\\\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\\\":2:{s:6:\\\"leadId\\\";i:17;s:9:\\\"crmStatus\\\";s:3:\\\"new\\\";}\"},\"createdAt\":1784363092,\"delay\":null}',0,NULL,1784363092,1784363092),(4,'default','{\"uuid\":\"fe71f656-479e-4fde-8bed-79d1aaa24cd0\",\"displayName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"30,120,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"command\":\"O:33:\\\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\\\":2:{s:6:\\\"leadId\\\";i:18;s:9:\\\"crmStatus\\\";s:3:\\\"new\\\";}\"},\"createdAt\":1784363092,\"delay\":null}',0,NULL,1784363092,1784363092),(5,'default','{\"uuid\":\"9688fe97-8ede-46b8-8f49-44ce04e4a1f2\",\"displayName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"30,120,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"command\":\"O:33:\\\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\\\":2:{s:6:\\\"leadId\\\";i:19;s:9:\\\"crmStatus\\\";s:3:\\\"new\\\";}\"},\"createdAt\":1784363092,\"delay\":null}',0,NULL,1784363092,1784363092),(6,'default','{\"uuid\":\"778927ba-736d-496c-87f0-0494e3968de1\",\"displayName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"30,120,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"command\":\"O:33:\\\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\\\":2:{s:6:\\\"leadId\\\";i:20;s:9:\\\"crmStatus\\\";s:3:\\\"new\\\";}\"},\"createdAt\":1784363092,\"delay\":null}',0,NULL,1784363092,1784363092),(7,'default','{\"uuid\":\"a586cb6d-3aa3-4f69-9202-2a27bd33b55a\",\"displayName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"30,120,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"command\":\"O:33:\\\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\\\":2:{s:6:\\\"leadId\\\";i:21;s:9:\\\"crmStatus\\\";s:3:\\\"new\\\";}\"},\"createdAt\":1784363092,\"delay\":null}',0,NULL,1784363092,1784363092),(8,'default','{\"uuid\":\"af249f2b-3c9a-46fd-89d1-2997340d8863\",\"displayName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"30,120,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"command\":\"O:33:\\\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\\\":2:{s:6:\\\"leadId\\\";i:22;s:9:\\\"crmStatus\\\";s:3:\\\"new\\\";}\"},\"createdAt\":1784363092,\"delay\":null}',0,NULL,1784363092,1784363092),(9,'default','{\"uuid\":\"ad9d15ea-084c-4391-8893-19d0b92bcbfe\",\"displayName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"30,120,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"command\":\"O:33:\\\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\\\":2:{s:6:\\\"leadId\\\";i:23;s:9:\\\"crmStatus\\\";s:3:\\\"new\\\";}\"},\"createdAt\":1784363092,\"delay\":null}',0,NULL,1784363092,1784363092),(10,'default','{\"uuid\":\"e727f99b-9723-4d0e-aca1-cd2162e08763\",\"displayName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"30,120,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"command\":\"O:33:\\\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\\\":2:{s:6:\\\"leadId\\\";i:24;s:9:\\\"crmStatus\\\";s:3:\\\"new\\\";}\"},\"createdAt\":1784363092,\"delay\":null}',0,NULL,1784363092,1784363092),(11,'default','{\"uuid\":\"b3c48ca4-e200-4629-98a8-832fd9fbf066\",\"displayName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"30,120,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"command\":\"O:33:\\\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\\\":2:{s:6:\\\"leadId\\\";i:25;s:9:\\\"crmStatus\\\";s:3:\\\"new\\\";}\"},\"createdAt\":1784363092,\"delay\":null}',0,NULL,1784363092,1784363092),(12,'default','{\"uuid\":\"2d579fb8-ab0b-4872-9809-e8b75adccd26\",\"displayName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"30,120,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"command\":\"O:33:\\\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\\\":2:{s:6:\\\"leadId\\\";i:26;s:9:\\\"crmStatus\\\";s:3:\\\"new\\\";}\"},\"createdAt\":1784363092,\"delay\":null}',0,NULL,1784363092,1784363092),(13,'default','{\"uuid\":\"97af00dd-d450-4b92-9ecf-d137a0a5afc2\",\"displayName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"30,120,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"command\":\"O:33:\\\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\\\":2:{s:6:\\\"leadId\\\";i:27;s:9:\\\"crmStatus\\\";s:3:\\\"new\\\";}\"},\"createdAt\":1784363092,\"delay\":null}',0,NULL,1784363092,1784363092),(14,'default','{\"uuid\":\"1942980b-179d-4052-ab2a-3cde5ac45061\",\"displayName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"30,120,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"command\":\"O:33:\\\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\\\":2:{s:6:\\\"leadId\\\";i:28;s:9:\\\"crmStatus\\\";s:3:\\\"new\\\";}\"},\"createdAt\":1784363092,\"delay\":null}',0,NULL,1784363092,1784363092),(15,'default','{\"uuid\":\"6b3854fb-aad1-48ed-8aa9-f6ba53b0ddd0\",\"displayName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"30,120,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"command\":\"O:33:\\\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\\\":2:{s:6:\\\"leadId\\\";i:29;s:9:\\\"crmStatus\\\";s:3:\\\"new\\\";}\"},\"createdAt\":1784363092,\"delay\":null}',0,NULL,1784363092,1784363092),(16,'default','{\"uuid\":\"2a1c8b99-6d5c-4f07-b441-16a00cb8da70\",\"displayName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"30,120,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"command\":\"O:33:\\\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\\\":2:{s:6:\\\"leadId\\\";i:30;s:9:\\\"crmStatus\\\";s:3:\\\"new\\\";}\"},\"createdAt\":1784363092,\"delay\":null}',0,NULL,1784363092,1784363092),(17,'default','{\"uuid\":\"ef59e32e-b8ad-4371-8b82-65a5cf3f7f63\",\"displayName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"30,120,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"command\":\"O:33:\\\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\\\":2:{s:6:\\\"leadId\\\";i:31;s:9:\\\"crmStatus\\\";s:3:\\\"new\\\";}\"},\"createdAt\":1784363092,\"delay\":null}',0,NULL,1784363092,1784363092),(18,'default','{\"uuid\":\"a3399923-8979-42f1-9bab-1101ba48982a\",\"displayName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"30,120,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"command\":\"O:33:\\\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\\\":2:{s:6:\\\"leadId\\\";i:32;s:9:\\\"crmStatus\\\";s:3:\\\"new\\\";}\"},\"createdAt\":1784363092,\"delay\":null}',0,NULL,1784363092,1784363092),(19,'default','{\"uuid\":\"8bacdc8c-6eac-469f-919f-28d10bf4c1a6\",\"displayName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"30,120,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"command\":\"O:33:\\\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\\\":2:{s:6:\\\"leadId\\\";i:33;s:9:\\\"crmStatus\\\";s:3:\\\"new\\\";}\"},\"createdAt\":1784363092,\"delay\":null}',0,NULL,1784363092,1784363092),(20,'default','{\"uuid\":\"b9058acb-3499-479b-abcc-e629ce8e1bc0\",\"displayName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"30,120,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"command\":\"O:33:\\\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\\\":2:{s:6:\\\"leadId\\\";i:34;s:9:\\\"crmStatus\\\";s:3:\\\"new\\\";}\"},\"createdAt\":1784363092,\"delay\":null}',0,NULL,1784363092,1784363092),(21,'default','{\"uuid\":\"7ee512e2-4a64-4d65-9971-90fdd5457def\",\"displayName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"30,120,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"command\":\"O:33:\\\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\\\":2:{s:6:\\\"leadId\\\";i:35;s:9:\\\"crmStatus\\\";s:3:\\\"new\\\";}\"},\"createdAt\":1784363092,\"delay\":null}',0,NULL,1784363092,1784363092),(22,'default','{\"uuid\":\"1063e407-2206-44c8-906d-48ee529deb5a\",\"displayName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"30,120,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"command\":\"O:33:\\\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\\\":2:{s:6:\\\"leadId\\\";i:36;s:9:\\\"crmStatus\\\";s:3:\\\"new\\\";}\"},\"createdAt\":1784363092,\"delay\":null}',0,NULL,1784363092,1784363092),(23,'default','{\"uuid\":\"b22eca2a-170a-4411-908e-13513affca2e\",\"displayName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"30,120,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"command\":\"O:33:\\\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\\\":2:{s:6:\\\"leadId\\\";i:37;s:9:\\\"crmStatus\\\";s:3:\\\"new\\\";}\"},\"createdAt\":1784363092,\"delay\":null}',0,NULL,1784363092,1784363092),(24,'default','{\"uuid\":\"df547ed3-74e8-48ef-a20c-6459ad8459a0\",\"displayName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"30,120,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"command\":\"O:33:\\\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\\\":2:{s:6:\\\"leadId\\\";i:38;s:9:\\\"crmStatus\\\";s:3:\\\"new\\\";}\"},\"createdAt\":1784363092,\"delay\":null}',0,NULL,1784363092,1784363092),(25,'default','{\"uuid\":\"ffb7ca7b-59aa-422d-988a-b8341d72d993\",\"displayName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"30,120,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"command\":\"O:33:\\\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\\\":2:{s:6:\\\"leadId\\\";i:39;s:9:\\\"crmStatus\\\";s:3:\\\"new\\\";}\"},\"createdAt\":1784363092,\"delay\":null}',0,NULL,1784363092,1784363092),(26,'default','{\"uuid\":\"9eba6916-2efd-4dd1-b8d2-5f1c43542cab\",\"displayName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"30,120,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"command\":\"O:33:\\\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\\\":2:{s:6:\\\"leadId\\\";i:40;s:9:\\\"crmStatus\\\";s:3:\\\"new\\\";}\"},\"createdAt\":1784363092,\"delay\":null}',0,NULL,1784363092,1784363092),(27,'default','{\"uuid\":\"21239dcc-7a6f-45a7-9880-1f0b7ce550d3\",\"displayName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"30,120,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"command\":\"O:33:\\\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\\\":2:{s:6:\\\"leadId\\\";i:41;s:9:\\\"crmStatus\\\";s:3:\\\"new\\\";}\"},\"createdAt\":1784363092,\"delay\":null}',0,NULL,1784363092,1784363092),(28,'default','{\"uuid\":\"7bad959b-fb83-450d-8e3e-bb214dd14efc\",\"displayName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"30,120,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"command\":\"O:33:\\\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\\\":2:{s:6:\\\"leadId\\\";i:42;s:9:\\\"crmStatus\\\";s:3:\\\"new\\\";}\"},\"createdAt\":1784363092,\"delay\":null}',0,NULL,1784363092,1784363092),(29,'default','{\"uuid\":\"63ef273a-e9fa-4d7f-9e6a-f122945704ca\",\"displayName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"30,120,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"command\":\"O:33:\\\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\\\":2:{s:6:\\\"leadId\\\";i:43;s:9:\\\"crmStatus\\\";s:3:\\\"new\\\";}\"},\"createdAt\":1784363092,\"delay\":null}',0,NULL,1784363092,1784363092),(30,'default','{\"uuid\":\"32f9d929-e3c1-4a5d-b2ca-235cdfabdc7a\",\"displayName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"30,120,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"command\":\"O:33:\\\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\\\":2:{s:6:\\\"leadId\\\";i:44;s:9:\\\"crmStatus\\\";s:3:\\\"new\\\";}\"},\"createdAt\":1784363092,\"delay\":null}',0,NULL,1784363092,1784363092),(31,'default','{\"uuid\":\"2069cb2a-79c7-4e03-b72a-f51a6bf7830a\",\"displayName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"30,120,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"command\":\"O:33:\\\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\\\":2:{s:6:\\\"leadId\\\";i:45;s:9:\\\"crmStatus\\\";s:3:\\\"new\\\";}\"},\"createdAt\":1784363093,\"delay\":null}',0,NULL,1784363093,1784363093),(32,'default','{\"uuid\":\"83ea95c7-0201-4069-ac74-f3c079d95749\",\"displayName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"30,120,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"command\":\"O:33:\\\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\\\":2:{s:6:\\\"leadId\\\";i:46;s:9:\\\"crmStatus\\\";s:3:\\\"new\\\";}\"},\"createdAt\":1784363093,\"delay\":null}',0,NULL,1784363093,1784363093),(33,'default','{\"uuid\":\"79f604ce-51a8-4475-9de6-71de2c85813e\",\"displayName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"30,120,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"command\":\"O:33:\\\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\\\":2:{s:6:\\\"leadId\\\";i:47;s:9:\\\"crmStatus\\\";s:3:\\\"new\\\";}\"},\"createdAt\":1784363093,\"delay\":null}',0,NULL,1784363093,1784363093),(34,'default','{\"uuid\":\"1e92f72c-70a7-46a6-a931-7c06175e68c7\",\"displayName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"30,120,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"command\":\"O:33:\\\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\\\":2:{s:6:\\\"leadId\\\";i:48;s:9:\\\"crmStatus\\\";s:3:\\\"new\\\";}\"},\"createdAt\":1784363093,\"delay\":null}',0,NULL,1784363093,1784363093),(35,'default','{\"uuid\":\"ba6dda45-5d29-41a6-acdc-43318d94ee7b\",\"displayName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"30,120,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"command\":\"O:33:\\\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\\\":2:{s:6:\\\"leadId\\\";i:49;s:9:\\\"crmStatus\\\";s:3:\\\"new\\\";}\"},\"createdAt\":1784363093,\"delay\":null}',0,NULL,1784363093,1784363093),(36,'default','{\"uuid\":\"b132e9f1-db35-49bb-8627-6f9bb3e59255\",\"displayName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"30,120,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"command\":\"O:33:\\\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\\\":2:{s:6:\\\"leadId\\\";i:50;s:9:\\\"crmStatus\\\";s:3:\\\"new\\\";}\"},\"createdAt\":1784363093,\"delay\":null}',0,NULL,1784363093,1784363093),(37,'default','{\"uuid\":\"f6cd8807-9277-4b65-8760-47fbb3e7d5c0\",\"displayName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"30,120,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"command\":\"O:33:\\\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\\\":2:{s:6:\\\"leadId\\\";i:51;s:9:\\\"crmStatus\\\";s:3:\\\"new\\\";}\"},\"createdAt\":1784363093,\"delay\":null}',0,NULL,1784363093,1784363093),(38,'default','{\"uuid\":\"feeb36e7-d144-445e-bcb1-7a7bdbc35b2d\",\"displayName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"30,120,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"command\":\"O:33:\\\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\\\":2:{s:6:\\\"leadId\\\";i:52;s:9:\\\"crmStatus\\\";s:3:\\\"new\\\";}\"},\"createdAt\":1784363093,\"delay\":null}',0,NULL,1784363093,1784363093),(39,'default','{\"uuid\":\"ae3784a6-fcb5-4c5b-804b-434651efeefa\",\"displayName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"30,120,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"command\":\"O:33:\\\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\\\":2:{s:6:\\\"leadId\\\";i:53;s:9:\\\"crmStatus\\\";s:3:\\\"new\\\";}\"},\"createdAt\":1784363093,\"delay\":null}',0,NULL,1784363093,1784363093),(40,'default','{\"uuid\":\"c9854ae4-ea3b-4b0d-9212-319f62d95016\",\"displayName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"30,120,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"command\":\"O:33:\\\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\\\":2:{s:6:\\\"leadId\\\";i:54;s:9:\\\"crmStatus\\\";s:3:\\\"new\\\";}\"},\"createdAt\":1784363093,\"delay\":null}',0,NULL,1784363093,1784363093),(41,'default','{\"uuid\":\"27888814-e422-4451-a18f-a387d4af03e6\",\"displayName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"30,120,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"command\":\"O:33:\\\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\\\":2:{s:6:\\\"leadId\\\";i:55;s:9:\\\"crmStatus\\\";s:3:\\\"new\\\";}\"},\"createdAt\":1784363093,\"delay\":null}',0,NULL,1784363093,1784363093),(42,'default','{\"uuid\":\"18baecaf-c29a-490d-b2c6-ec3494fb0e2c\",\"displayName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"30,120,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"command\":\"O:33:\\\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\\\":2:{s:6:\\\"leadId\\\";i:56;s:9:\\\"crmStatus\\\";s:3:\\\"new\\\";}\"},\"createdAt\":1784363093,\"delay\":null}',0,NULL,1784363093,1784363093),(43,'default','{\"uuid\":\"39c9ef96-fece-42a7-82fe-4bd78cdd3ad1\",\"displayName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"30,120,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"command\":\"O:33:\\\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\\\":2:{s:6:\\\"leadId\\\";i:57;s:9:\\\"crmStatus\\\";s:3:\\\"new\\\";}\"},\"createdAt\":1784363093,\"delay\":null}',0,NULL,1784363093,1784363093),(44,'default','{\"uuid\":\"0fd9f2ed-a867-444e-b7e4-75121f977ac8\",\"displayName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"30,120,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"command\":\"O:33:\\\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\\\":2:{s:6:\\\"leadId\\\";i:58;s:9:\\\"crmStatus\\\";s:3:\\\"new\\\";}\"},\"createdAt\":1784363093,\"delay\":null}',0,NULL,1784363093,1784363093),(45,'default','{\"uuid\":\"52e74a7f-3af0-45cc-a05a-b64d8be199ab\",\"displayName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"30,120,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"command\":\"O:33:\\\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\\\":2:{s:6:\\\"leadId\\\";i:59;s:9:\\\"crmStatus\\\";s:3:\\\"new\\\";}\"},\"createdAt\":1784363093,\"delay\":null}',0,NULL,1784363093,1784363093),(46,'default','{\"uuid\":\"0ca6984a-dcf2-4a2f-a3eb-467b015919f7\",\"displayName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"30,120,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"command\":\"O:33:\\\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\\\":2:{s:6:\\\"leadId\\\";i:60;s:9:\\\"crmStatus\\\";s:3:\\\"new\\\";}\"},\"createdAt\":1784363093,\"delay\":null}',0,NULL,1784363093,1784363093),(47,'default','{\"uuid\":\"fd7b28e8-9a3e-4dfe-9c83-6c760fdc3149\",\"displayName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"30,120,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"command\":\"O:33:\\\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\\\":2:{s:6:\\\"leadId\\\";i:61;s:9:\\\"crmStatus\\\";s:3:\\\"new\\\";}\"},\"createdAt\":1784363093,\"delay\":null}',0,NULL,1784363093,1784363093),(48,'default','{\"uuid\":\"5e312ea9-6622-4621-8cc9-a6f3194a16e3\",\"displayName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"30,120,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"command\":\"O:33:\\\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\\\":2:{s:6:\\\"leadId\\\";i:62;s:9:\\\"crmStatus\\\";s:3:\\\"new\\\";}\"},\"createdAt\":1784363093,\"delay\":null}',0,NULL,1784363093,1784363093),(49,'default','{\"uuid\":\"6f106a36-98f7-4ff8-b76b-7b31800bd4df\",\"displayName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"30,120,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"command\":\"O:33:\\\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\\\":2:{s:6:\\\"leadId\\\";i:63;s:9:\\\"crmStatus\\\";s:3:\\\"new\\\";}\"},\"createdAt\":1784363093,\"delay\":null}',0,NULL,1784363093,1784363093),(50,'default','{\"uuid\":\"b221a38f-1cd6-4f5f-8ce6-658ea2ad5f93\",\"displayName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"30,120,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"command\":\"O:33:\\\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\\\":2:{s:6:\\\"leadId\\\";i:64;s:9:\\\"crmStatus\\\";s:3:\\\"new\\\";}\"},\"createdAt\":1784363093,\"delay\":null}',0,NULL,1784363093,1784363093),(51,'default','{\"uuid\":\"7f717e59-71ca-4c0d-a770-e261c437bea7\",\"displayName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"30,120,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"command\":\"O:33:\\\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\\\":2:{s:6:\\\"leadId\\\";i:65;s:9:\\\"crmStatus\\\";s:3:\\\"new\\\";}\"},\"createdAt\":1784363093,\"delay\":null}',0,NULL,1784363093,1784363093),(52,'default','{\"uuid\":\"5947e818-9ba4-48ae-b140-dd495bb1d445\",\"displayName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"30,120,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"command\":\"O:33:\\\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\\\":2:{s:6:\\\"leadId\\\";i:66;s:9:\\\"crmStatus\\\";s:3:\\\"new\\\";}\"},\"createdAt\":1784363093,\"delay\":null}',0,NULL,1784363093,1784363093),(53,'default','{\"uuid\":\"274a98e3-0072-40d0-ab15-7e78a5efbba5\",\"displayName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"30,120,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"command\":\"O:33:\\\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\\\":2:{s:6:\\\"leadId\\\";i:67;s:9:\\\"crmStatus\\\";s:3:\\\"new\\\";}\"},\"createdAt\":1784363093,\"delay\":null}',0,NULL,1784363093,1784363093),(54,'default','{\"uuid\":\"08f41e2f-5786-4d0b-86de-03310244b231\",\"displayName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"30,120,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"command\":\"O:33:\\\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\\\":2:{s:6:\\\"leadId\\\";i:68;s:9:\\\"crmStatus\\\";s:3:\\\"new\\\";}\"},\"createdAt\":1784363093,\"delay\":null}',0,NULL,1784363093,1784363093),(55,'default','{\"uuid\":\"7fba88b8-7ed0-4575-87ac-6989fc6c7589\",\"displayName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"30,120,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"command\":\"O:33:\\\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\\\":2:{s:6:\\\"leadId\\\";i:69;s:9:\\\"crmStatus\\\";s:3:\\\"new\\\";}\"},\"createdAt\":1784363093,\"delay\":null}',0,NULL,1784363093,1784363093),(56,'default','{\"uuid\":\"5f905a0f-4487-4952-940a-84164ed90d16\",\"displayName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"30,120,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"command\":\"O:33:\\\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\\\":2:{s:6:\\\"leadId\\\";i:70;s:9:\\\"crmStatus\\\";s:3:\\\"new\\\";}\"},\"createdAt\":1784363093,\"delay\":null}',0,NULL,1784363093,1784363093),(57,'default','{\"uuid\":\"e3a70907-70e4-4cc3-920d-abd26196971f\",\"displayName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"30,120,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"command\":\"O:33:\\\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\\\":2:{s:6:\\\"leadId\\\";i:71;s:9:\\\"crmStatus\\\";s:3:\\\"new\\\";}\"},\"createdAt\":1784363093,\"delay\":null}',0,NULL,1784363093,1784363093),(58,'default','{\"uuid\":\"b2027a15-202d-4978-8d33-359fcba57eb1\",\"displayName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"30,120,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"command\":\"O:33:\\\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\\\":2:{s:6:\\\"leadId\\\";i:72;s:9:\\\"crmStatus\\\";s:3:\\\"new\\\";}\"},\"createdAt\":1784363093,\"delay\":null}',0,NULL,1784363093,1784363093),(59,'default','{\"uuid\":\"56dd5e46-8cb8-42a7-bdfa-4093a8e46855\",\"displayName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"30,120,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"command\":\"O:33:\\\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\\\":2:{s:6:\\\"leadId\\\";i:73;s:9:\\\"crmStatus\\\";s:3:\\\"new\\\";}\"},\"createdAt\":1784363093,\"delay\":null}',0,NULL,1784363093,1784363093),(60,'default','{\"uuid\":\"3df97c87-c46b-4de6-9f92-c2d16b60bb8a\",\"displayName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"30,120,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"command\":\"O:33:\\\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\\\":2:{s:6:\\\"leadId\\\";i:74;s:9:\\\"crmStatus\\\";s:3:\\\"new\\\";}\"},\"createdAt\":1784363093,\"delay\":null}',0,NULL,1784363093,1784363093),(61,'default','{\"uuid\":\"61dd828f-314d-44eb-9a4f-4100f4f63181\",\"displayName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"30,120,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"command\":\"O:33:\\\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\\\":2:{s:6:\\\"leadId\\\";i:75;s:9:\\\"crmStatus\\\";s:3:\\\"new\\\";}\"},\"createdAt\":1784363093,\"delay\":null}',0,NULL,1784363093,1784363093),(62,'default','{\"uuid\":\"ab946627-606e-4b17-8e36-5d069960b6f8\",\"displayName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"30,120,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"command\":\"O:33:\\\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\\\":2:{s:6:\\\"leadId\\\";i:76;s:9:\\\"crmStatus\\\";s:3:\\\"new\\\";}\"},\"createdAt\":1784363093,\"delay\":null}',0,NULL,1784363093,1784363093),(63,'default','{\"uuid\":\"36a046e0-6c7b-4791-8eb5-8f9c696063de\",\"displayName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"30,120,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"command\":\"O:33:\\\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\\\":2:{s:6:\\\"leadId\\\";i:77;s:9:\\\"crmStatus\\\";s:3:\\\"new\\\";}\"},\"createdAt\":1784363093,\"delay\":null}',0,NULL,1784363093,1784363093),(64,'default','{\"uuid\":\"a64b5203-b6f2-4347-9f44-bb9f6fca5aef\",\"displayName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"30,120,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"command\":\"O:33:\\\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\\\":2:{s:6:\\\"leadId\\\";i:78;s:9:\\\"crmStatus\\\";s:3:\\\"new\\\";}\"},\"createdAt\":1784363093,\"delay\":null}',0,NULL,1784363093,1784363093),(65,'default','{\"uuid\":\"21c607af-4d9d-4e67-85dd-d1ae2365efa6\",\"displayName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"30,120,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"command\":\"O:33:\\\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\\\":2:{s:6:\\\"leadId\\\";i:79;s:9:\\\"crmStatus\\\";s:3:\\\"new\\\";}\"},\"createdAt\":1784363093,\"delay\":null}',0,NULL,1784363093,1784363093),(66,'default','{\"uuid\":\"9603a444-cfe5-4912-98d4-aac0d901cd50\",\"displayName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"30,120,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"command\":\"O:33:\\\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\\\":2:{s:6:\\\"leadId\\\";i:80;s:9:\\\"crmStatus\\\";s:3:\\\"new\\\";}\"},\"createdAt\":1784363093,\"delay\":null}',0,NULL,1784363093,1784363093),(67,'default','{\"uuid\":\"e2f7192e-bbf0-4009-86e6-4b1fc4d7e239\",\"displayName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"30,120,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"command\":\"O:33:\\\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\\\":2:{s:6:\\\"leadId\\\";i:81;s:9:\\\"crmStatus\\\";s:3:\\\"new\\\";}\"},\"createdAt\":1784363093,\"delay\":null}',0,NULL,1784363093,1784363093),(68,'default','{\"uuid\":\"e79679ab-469d-49f8-92a2-842d3606d798\",\"displayName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"30,120,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"command\":\"O:33:\\\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\\\":2:{s:6:\\\"leadId\\\";i:82;s:9:\\\"crmStatus\\\";s:3:\\\"new\\\";}\"},\"createdAt\":1784363093,\"delay\":null}',0,NULL,1784363093,1784363093),(69,'default','{\"uuid\":\"56e80d42-3f53-4ce5-94f9-3db0a49010dc\",\"displayName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"30,120,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"command\":\"O:33:\\\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\\\":2:{s:6:\\\"leadId\\\";i:83;s:9:\\\"crmStatus\\\";s:3:\\\"new\\\";}\"},\"createdAt\":1784363093,\"delay\":null}',0,NULL,1784363093,1784363093),(70,'default','{\"uuid\":\"e2b43261-7357-49ec-98fe-65bc23ea0246\",\"displayName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"30,120,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"command\":\"O:33:\\\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\\\":2:{s:6:\\\"leadId\\\";i:84;s:9:\\\"crmStatus\\\";s:3:\\\"new\\\";}\"},\"createdAt\":1784363093,\"delay\":null}',0,NULL,1784363093,1784363093),(71,'default','{\"uuid\":\"737086a4-dd91-4e36-b457-5c34f2411b79\",\"displayName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"30,120,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"command\":\"O:33:\\\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\\\":2:{s:6:\\\"leadId\\\";i:85;s:9:\\\"crmStatus\\\";s:3:\\\"new\\\";}\"},\"createdAt\":1784363093,\"delay\":null}',0,NULL,1784363093,1784363093),(72,'default','{\"uuid\":\"6a3c3d6a-039d-44fb-940a-3d7c86b54556\",\"displayName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"30,120,300\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\",\"command\":\"O:33:\\\"App\\\\Jobs\\\\SendMetaCapiLeadEventJob\\\":2:{s:6:\\\"leadId\\\";i:86;s:9:\\\"crmStatus\\\";s:3:\\\"new\\\";}\"},\"createdAt\":1784363093,\"delay\":null}',0,NULL,1784363093,1784363093),(73,'default','{\"uuid\":\"4dfad3b0-1303-423b-a66d-a831273e059a\",\"displayName\":\"App\\\\Jobs\\\\GenerateInvoiceJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\GenerateInvoiceJob\",\"command\":\"O:27:\\\"App\\\\Jobs\\\\GenerateInvoiceJob\\\":1:{s:7:\\\"invoice\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\Invoice\\\";s:2:\\\"id\\\";i:1;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}\"},\"createdAt\":1785168033,\"delay\":null}',0,NULL,1785168033,1785168033),(74,'default','{\"uuid\":\"df5fbdd2-d260-43a3-a6e5-5fa1a1eef830\",\"displayName\":\"App\\\\Jobs\\\\GenerateInvoiceJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\GenerateInvoiceJob\",\"command\":\"O:27:\\\"App\\\\Jobs\\\\GenerateInvoiceJob\\\":1:{s:7:\\\"invoice\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\Invoice\\\";s:2:\\\"id\\\";i:2;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}\"},\"createdAt\":1785230343,\"delay\":null}',0,NULL,1785230344,1785230344),(75,'default','{\"uuid\":\"164760d5-7cf0-42d8-9bc4-ca070a9a6947\",\"displayName\":\"App\\\\Jobs\\\\GenerateInvoiceJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\GenerateInvoiceJob\",\"command\":\"O:27:\\\"App\\\\Jobs\\\\GenerateInvoiceJob\\\":1:{s:7:\\\"invoice\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\Invoice\\\";s:2:\\\"id\\\";i:3;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}\"},\"createdAt\":1785313790,\"delay\":null}',0,NULL,1785313790,1785313790),(76,'default','{\"uuid\":\"763840f0-f48c-40eb-b896-c00f354d4644\",\"displayName\":\"App\\\\Jobs\\\\GenerateInvoiceJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\GenerateInvoiceJob\",\"command\":\"O:27:\\\"App\\\\Jobs\\\\GenerateInvoiceJob\\\":1:{s:7:\\\"invoice\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:18:\\\"App\\\\Models\\\\Invoice\\\";s:2:\\\"id\\\";i:4;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}\"},\"createdAt\":1785327632,\"delay\":null}',0,NULL,1785327632,1785327632);
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lead_activities`
--

DROP TABLE IF EXISTS `lead_activities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lead_activities` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `enterprise_id` bigint(20) unsigned NOT NULL,
  `lead_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `type` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `meta` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lead_activities_lead_id_foreign` (`lead_id`),
  KEY `lead_activities_user_id_foreign` (`user_id`),
  KEY `lead_activities_enterprise_id_lead_id_created_at_index` (`enterprise_id`,`lead_id`,`created_at`),
  KEY `lead_activities_enterprise_id_type_created_at_index` (`enterprise_id`,`type`,`created_at`),
  CONSTRAINT `lead_activities_enterprise_id_foreign` FOREIGN KEY (`enterprise_id`) REFERENCES `enterprises` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lead_activities_lead_id_foreign` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lead_activities_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lead_activities`
--

LOCK TABLES `lead_activities` WRITE;
/*!40000 ALTER TABLE `lead_activities` DISABLE KEYS */;
/*!40000 ALTER TABLE `lead_activities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lead_notes`
--

DROP TABLE IF EXISTS `lead_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lead_notes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `lead_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `note` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `lead_notes_lead_id_foreign` (`lead_id`),
  KEY `lead_notes_user_id_foreign` (`user_id`),
  CONSTRAINT `lead_notes_lead_id_foreign` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lead_notes_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lead_notes`
--

LOCK TABLES `lead_notes` WRITE;
/*!40000 ALTER TABLE `lead_notes` DISABLE KEYS */;
INSERT INTO `lead_notes` VALUES (1,1,1,'adawd','2026-01-19 13:59:45'),(2,1,1,'ji','2026-01-19 14:37:01'),(3,2,3,'Test Tets','2026-01-29 13:38:59'),(4,2,3,'uaisk','2026-01-29 13:42:17'),(5,2,3,'zxc','2026-01-29 13:42:23'),(6,2,1,'sa','2026-01-29 23:46:49'),(7,4,6,'r','2026-04-20 10:00:45');
/*!40000 ALTER TABLE `lead_notes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `leads`
--

DROP TABLE IF EXISTS `leads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `leads` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `campaign_id` bigint(20) unsigned DEFAULT NULL,
  `enterprise_id` bigint(20) unsigned DEFAULT NULL,
  `team_id` bigint(20) unsigned DEFAULT NULL,
  `assigned_to` bigint(20) unsigned DEFAULT NULL,
  `assigned_manager_id` bigint(20) unsigned DEFAULT NULL,
  `inventory_unit_id` bigint(20) unsigned DEFAULT NULL,
  `inventory_project_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `company` varchar(255) DEFAULT NULL,
  `status` enum('new','contacted','qualified','proposal','negotiation','won','lost') NOT NULL DEFAULT 'new',
  `enterprise_stage` varchar(255) DEFAULT NULL,
  `priority` varchar(255) NOT NULL DEFAULT 'medium',
  `source` varchar(255) NOT NULL DEFAULT 'manual',
  `meta_lead_id` varchar(255) DEFAULT NULL,
  `custom_fields` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`custom_fields`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `budget_min` decimal(15,2) DEFAULT NULL,
  `budget_max` decimal(15,2) DEFAULT NULL,
  `preferred_location` varchar(255) DEFAULT NULL,
  `preferred_configuration` varchar(255) DEFAULT NULL,
  `next_follow_up_at` timestamp NULL DEFAULT NULL,
  `last_contacted_at` timestamp NULL DEFAULT NULL,
  `assigned_at` timestamp NULL DEFAULT NULL,
  `deal_value` decimal(15,2) DEFAULT NULL,
  `lost_reason` text DEFAULT NULL,
  `won_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `leads_campaign_id_foreign` (`campaign_id`),
  KEY `leads_meta_lead_id_index` (`meta_lead_id`),
  KEY `leads_inventory_unit_id_foreign` (`inventory_unit_id`),
  KEY `leads_team_id_foreign` (`team_id`),
  KEY `leads_assigned_to_foreign` (`assigned_to`),
  KEY `leads_assigned_manager_id_foreign` (`assigned_manager_id`),
  KEY `leads_inventory_project_id_foreign` (`inventory_project_id`),
  KEY `leads_enterprise_id_enterprise_stage_index` (`enterprise_id`,`enterprise_stage`),
  KEY `leads_enterprise_assignee_stage_index` (`enterprise_id`,`assigned_to`,`enterprise_stage`),
  KEY `leads_enterprise_team_stage_index` (`enterprise_id`,`team_id`,`enterprise_stage`),
  KEY `leads_enterprise_stage_index` (`enterprise_stage`),
  KEY `leads_next_follow_up_at_index` (`next_follow_up_at`),
  CONSTRAINT `leads_assigned_manager_id_foreign` FOREIGN KEY (`assigned_manager_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `leads_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `leads_campaign_id_foreign` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`) ON DELETE CASCADE,
  CONSTRAINT `leads_enterprise_id_foreign` FOREIGN KEY (`enterprise_id`) REFERENCES `enterprises` (`id`) ON DELETE SET NULL,
  CONSTRAINT `leads_inventory_project_id_foreign` FOREIGN KEY (`inventory_project_id`) REFERENCES `inventory_projects` (`id`) ON DELETE SET NULL,
  CONSTRAINT `leads_inventory_unit_id_foreign` FOREIGN KEY (`inventory_unit_id`) REFERENCES `inventory_units` (`id`) ON DELETE SET NULL,
  CONSTRAINT `leads_team_id_foreign` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=87 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leads`
--

LOCK TABLES `leads` WRITE;
/*!40000 ALTER TABLE `leads` DISABLE KEYS */;
INSERT INTO `leads` VALUES (1,1,NULL,NULL,NULL,NULL,NULL,NULL,'Hitesh Bhagwat','hiteshbhagwat97@gmail.com','07038214873','Iconicpages','won',NULL,'medium','manual',NULL,NULL,'2026-01-19 13:59:45','2026-01-19 14:37:40',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(2,2,NULL,NULL,NULL,NULL,NULL,NULL,'Hitesh Bhagwat','hitesh.bhagwat@iconicpages.com','9087654321','Iconicpages Pvt Ltd','proposal',NULL,'medium','manual',NULL,NULL,'2026-01-29 13:38:59','2026-01-29 14:54:06',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(3,4,NULL,NULL,NULL,NULL,NULL,NULL,'Satyakam rai','satyakam.rai@gmail.com','7066805008','Na','qualified',NULL,'medium','Meta Ads',NULL,NULL,'2025-09-18 11:31:18','2025-09-18 11:31:18',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(4,7,NULL,NULL,NULL,NULL,NULL,NULL,'omka naik','teamiconicpages@gmail.com','08087744220','rumu','negotiation',NULL,'medium','manual',NULL,NULL,'2026-04-20 09:59:49','2026-04-20 11:28:54',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(5,8,NULL,NULL,NULL,NULL,NULL,NULL,'HItesh Bhagwat','hiteshbhagwat97@gmail.com','7666228107','Rumu','qualified',NULL,'medium','manual',NULL,NULL,'2026-05-08 08:06:55','2026-05-08 08:07:47',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(7,14,NULL,NULL,NULL,NULL,NULL,NULL,'Hitesh Bhagwat Extra','companyiconicpages@gmail.com','7038214873','iconicpages','qualified',NULL,'medium','manual',NULL,NULL,'2026-06-17 16:34:16','2026-06-17 16:34:27',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(15,17,NULL,NULL,NULL,NULL,NULL,NULL,'Uttam','uttam219@gmail.com','+917008813250',NULL,'won',NULL,'medium','meta_ads','961202166943527',NULL,'2026-07-18 02:54:49','2026-07-20 03:11:45',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(16,17,NULL,NULL,NULL,NULL,NULL,NULL,'Indostyle','surajnr1989@yahoo.in','+919998100440',NULL,'new',NULL,'medium','meta_ads','1065881285874988','{\"lead_source\":\"facebook\",\"created_time\":\"2026-07-11T19:31:37+0000\"}','2026-07-18 02:54:52','2026-07-18 02:54:52',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(17,17,NULL,NULL,NULL,NULL,NULL,NULL,'Rupam Banerjee','rupambnrj@gmail.com','+917303299192',NULL,'new',NULL,'medium','meta_ads','1011752918139958','{\"lead_source\":\"facebook\",\"created_time\":\"2026-07-11T18:39:17+0000\"}','2026-07-18 02:54:52','2026-07-18 02:54:52',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(18,17,NULL,NULL,NULL,NULL,NULL,NULL,'Mahendra','prajapatimahen792@gmail.com','+917678052220',NULL,'new',NULL,'medium','meta_ads','1043639568141141','{\"lead_source\":\"facebook\",\"created_time\":\"2026-07-11T17:17:14+0000\"}','2026-07-18 02:54:52','2026-07-18 02:54:52',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(19,17,NULL,NULL,NULL,NULL,NULL,NULL,'Dev Dutt Das','devduttdas@hotmail.com','+919437018900',NULL,'new',NULL,'medium','meta_ads','27780390868223211','{\"lead_source\":\"facebook\",\"created_time\":\"2026-07-11T12:15:39+0000\"}','2026-07-18 02:54:52','2026-07-18 02:54:52',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(20,17,NULL,NULL,NULL,NULL,NULL,NULL,'Rajaram','Ceo@volumezero.in','+919842767948',NULL,'new',NULL,'medium','meta_ads','1647568906331322','{\"lead_source\":\"facebook\",\"created_time\":\"2026-07-11T09:04:01+0000\"}','2026-07-18 02:54:52','2026-07-18 02:54:52',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(21,17,NULL,NULL,NULL,NULL,NULL,NULL,'Ashok Mehta','ashokmehta964@gmail.com','+919414165831',NULL,'new',NULL,'medium','meta_ads','2616387102165120','{\"lead_source\":\"facebook\",\"created_time\":\"2026-07-11T07:03:52+0000\"}','2026-07-18 02:54:52','2026-07-18 02:54:52',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(22,17,NULL,NULL,NULL,NULL,NULL,NULL,'Vatsal Dayal','dayalvatsal@gmail.com','+917303244877',NULL,'new',NULL,'medium','meta_ads','5473258452898472','{\"lead_source\":\"facebook\",\"created_time\":\"2026-07-10T16:03:34+0000\"}','2026-07-18 02:54:52','2026-07-18 02:54:52',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(23,17,NULL,NULL,NULL,NULL,NULL,NULL,'Vijay Kanade','topvijay@rediffmail.com','+919881736055',NULL,'new',NULL,'medium','meta_ads','1695101238423916','{\"lead_source\":\"facebook\",\"created_time\":\"2026-07-10T06:47:27+0000\"}','2026-07-18 02:54:52','2026-07-18 02:54:52',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(24,17,NULL,NULL,NULL,NULL,NULL,NULL,'sk Choudhary','delhi.stepbrand@gmail.com','+918076922003',NULL,'new',NULL,'medium','meta_ads','3411844522326635','{\"lead_source\":\"facebook\",\"created_time\":\"2026-07-10T06:04:44+0000\"}','2026-07-18 02:54:52','2026-07-18 02:54:52',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(25,17,NULL,NULL,NULL,NULL,NULL,NULL,'Laxmi Narayan Saini','laxmisaini14@yahoo.com','+917838345866',NULL,'new',NULL,'medium','meta_ads','1761217508626748','{\"lead_source\":\"facebook\",\"created_time\":\"2026-07-10T02:13:14+0000\"}','2026-07-18 02:54:52','2026-07-18 02:54:52',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(26,17,NULL,NULL,NULL,NULL,NULL,NULL,'Tarun Sati','Hiticreations@gmail.com','+919971705642',NULL,'new',NULL,'medium','meta_ads','2124477268130095','{\"lead_source\":\"facebook\",\"created_time\":\"2026-07-09T12:25:58+0000\"}','2026-07-18 02:54:52','2026-07-18 02:54:52',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(27,17,NULL,NULL,NULL,NULL,NULL,NULL,'Sanjay Jain','smbjain@gmail.com','+919820953650',NULL,'new',NULL,'medium','meta_ads','878893018625697','{\"lead_source\":\"facebook\",\"created_time\":\"2026-07-09T11:51:41+0000\"}','2026-07-18 02:54:52','2026-07-18 02:54:52',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(28,17,NULL,NULL,NULL,NULL,NULL,NULL,'Vishal Kaushik','Kaushikvishal@outlook.com','+919760000090',NULL,'new',NULL,'medium','meta_ads','1709931186862992','{\"lead_source\":\"facebook\",\"created_time\":\"2026-07-09T04:32:51+0000\"}','2026-07-18 02:54:52','2026-07-18 02:54:52',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(29,17,NULL,NULL,NULL,NULL,NULL,NULL,'prashil sheth','Prashilsheth@gmail.com','+917984392452',NULL,'new',NULL,'medium','meta_ads','27998735783089100','{\"lead_source\":\"facebook\",\"created_time\":\"2026-07-08T20:16:10+0000\"}','2026-07-18 02:54:52','2026-07-18 02:54:52',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(30,17,NULL,NULL,NULL,NULL,NULL,NULL,'Tauheed Ali','tauheedali248@gmail.com','+918081408063',NULL,'new',NULL,'medium','meta_ads','1329079052201961','{\"lead_source\":\"facebook\",\"created_time\":\"2026-07-08T18:00:15+0000\"}','2026-07-18 02:54:52','2026-07-18 02:54:52',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(31,17,NULL,NULL,NULL,NULL,NULL,NULL,'Rahul Agrawal','Info@maxbreeches.com','+918052003993',NULL,'new',NULL,'medium','meta_ads','4357320551154094','{\"lead_source\":\"facebook\",\"created_time\":\"2026-07-08T17:21:58+0000\"}','2026-07-18 02:54:52','2026-07-18 02:54:52',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(32,17,NULL,NULL,NULL,NULL,NULL,NULL,'SAHIL','sahilchaurasiya016@gmail.com','+919867638610',NULL,'new',NULL,'medium','meta_ads','1511328680472101','{\"lead_source\":\"facebook\",\"created_time\":\"2026-07-08T14:27:13+0000\"}','2026-07-18 02:54:52','2026-07-18 02:54:52',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(33,17,NULL,NULL,NULL,NULL,NULL,NULL,'Rohan','gobua@gmail.com','+916355124655',NULL,'new',NULL,'medium','meta_ads','1651210699294800','{\"lead_source\":\"facebook\",\"created_time\":\"2026-07-08T13:29:17+0000\"}','2026-07-18 02:54:52','2026-07-18 02:54:52',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(34,17,NULL,NULL,NULL,NULL,NULL,NULL,'sudheer','puthursudheer73@gmail.com','+919495092629',NULL,'new',NULL,'medium','meta_ads','1325548439230532','{\"lead_source\":\"facebook\",\"created_time\":\"2026-07-08T05:47:45+0000\"}','2026-07-18 02:54:52','2026-07-18 02:54:52',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(35,17,NULL,NULL,NULL,NULL,NULL,NULL,'Roshan Jewel','roshan_jewel@yahoo.com','+919098890989',NULL,'new',NULL,'medium','meta_ads','1401767352004980','{\"lead_source\":\"facebook\",\"created_time\":\"2026-07-08T04:25:41+0000\"}','2026-07-18 02:54:52','2026-07-18 02:54:52',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(36,17,NULL,NULL,NULL,NULL,NULL,NULL,'Vikash Kumar','abhushanjewellers003@gmail.com','+917874143003',NULL,'new',NULL,'medium','meta_ads','4589777884677994','{\"lead_source\":\"facebook\",\"created_time\":\"2026-07-07T13:48:23+0000\"}','2026-07-18 02:54:52','2026-07-18 02:54:52',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(37,17,NULL,NULL,NULL,NULL,NULL,NULL,'Purab Bethala','purab_bethala2023@cms.ac.in','+919535095350',NULL,'new',NULL,'medium','meta_ads','1558719939029255','{\"lead_source\":\"facebook\",\"created_time\":\"2026-07-07T11:17:49+0000\"}','2026-07-18 02:54:52','2026-07-18 02:54:52',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(38,17,NULL,NULL,NULL,NULL,NULL,NULL,'Ankit','jp24oct@gmail.com','+919540312410',NULL,'new',NULL,'medium','meta_ads','2113931072522312','{\"lead_source\":\"facebook\",\"created_time\":\"2026-07-07T11:16:51+0000\"}','2026-07-18 02:54:52','2026-07-18 02:54:52',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(39,17,NULL,NULL,NULL,NULL,NULL,NULL,'Jay','Jayjain789@gmail.com','+919967450703',NULL,'new',NULL,'medium','meta_ads','2119062178992228','{\"lead_source\":\"facebook\",\"created_time\":\"2026-07-07T10:03:46+0000\"}','2026-07-18 02:54:52','2026-07-18 02:54:52',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(40,17,NULL,NULL,NULL,NULL,NULL,NULL,'Nikky thakur','ravi9808.eeee@gmail.com','+919229878309',NULL,'new',NULL,'medium','meta_ads','1493012828795606','{\"lead_source\":\"facebook\",\"created_time\":\"2026-07-07T07:20:39+0000\"}','2026-07-18 02:54:52','2026-07-18 02:54:52',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(41,17,NULL,NULL,NULL,NULL,NULL,NULL,'Arthmen | Wedding Wear for Men','arthmen.info@gmail.com','+919849809941',NULL,'new',NULL,'medium','meta_ads','1008555868635449','{\"lead_source\":\"facebook\",\"created_time\":\"2026-07-07T06:02:27+0000\"}','2026-07-18 02:54:52','2026-07-18 02:54:52',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(42,17,NULL,NULL,NULL,NULL,NULL,NULL,'Sri Krishna Tex','sktexvdm@gmail.com','+918247013753',NULL,'new',NULL,'medium','meta_ads','1528640952235998','{\"lead_source\":\"facebook\",\"created_time\":\"2026-06-21T15:26:57+0000\"}','2026-07-18 02:54:52','2026-07-18 02:54:52',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(43,17,NULL,NULL,NULL,NULL,NULL,NULL,'Gurneet Marwah','gurneetmarwah97@gmail.com','+918699303637',NULL,'new',NULL,'medium','meta_ads','1304573895217585','{\"lead_source\":\"facebook\",\"created_time\":\"2026-06-21T14:46:48+0000\"}','2026-07-18 02:54:52','2026-07-18 02:54:52',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(44,17,NULL,NULL,NULL,NULL,NULL,NULL,'Mayank Kalani','mayank.kalani2009@gmail.com','+919953670839',NULL,'new',NULL,'medium','meta_ads','1506020364337413','{\"lead_source\":\"facebook\",\"created_time\":\"2026-06-21T11:33:24+0000\"}','2026-07-18 02:54:52','2026-07-18 02:54:52',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(45,17,NULL,NULL,NULL,NULL,NULL,NULL,'Sher Ali','sher.ali05@gmail.com','+918874838386',NULL,'new',NULL,'medium','meta_ads','4025066951131895','{\"lead_source\":\"facebook\",\"created_time\":\"2026-06-21T04:30:24+0000\"}','2026-07-18 02:54:53','2026-07-18 02:54:53',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(46,17,NULL,NULL,NULL,NULL,NULL,NULL,'Dheeraj Gupta','shivamgupta542005@gmail.com','+918858630219',NULL,'new',NULL,'medium','meta_ads','2226236524878564','{\"lead_source\":\"facebook\",\"created_time\":\"2026-06-21T02:26:58+0000\"}','2026-07-18 02:54:53','2026-07-18 02:54:53',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(47,17,NULL,NULL,NULL,NULL,NULL,NULL,'Rupam Banerjee','rupambnrj@gmail.com','+917303299192',NULL,'new',NULL,'medium','meta_ads','1216409200521632','{\"lead_source\":\"facebook\",\"created_time\":\"2026-06-21T02:01:49+0000\"}','2026-07-18 02:54:53','2026-07-18 02:54:53',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(48,17,NULL,NULL,NULL,NULL,NULL,NULL,'URBANBOYS CLUB','makhijarj@gmail.com','+919909304079',NULL,'new',NULL,'medium','meta_ads','1717939222965999','{\"lead_source\":\"facebook\",\"created_time\":\"2026-06-22T05:12:04+0000\"}','2026-07-18 02:54:53','2026-07-18 02:54:53',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(49,17,NULL,NULL,NULL,NULL,NULL,NULL,'Manish Ujainiya','manish92209@gmail.com','+918866858543',NULL,'new',NULL,'medium','meta_ads','877012125463813','{\"lead_source\":\"facebook\",\"created_time\":\"2026-06-21T16:36:45+0000\"}','2026-07-18 02:54:53','2026-07-18 02:54:53',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(50,17,NULL,NULL,NULL,NULL,NULL,NULL,'sunil','skmr132@gmail.com','+919736610090',NULL,'new',NULL,'medium','meta_ads','1055426117158288','{\"lead_source\":\"facebook\",\"created_time\":\"2026-06-21T10:06:01+0000\"}','2026-07-18 02:54:53','2026-07-18 02:54:53',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(51,17,NULL,NULL,NULL,NULL,NULL,NULL,'Sk Monirul Islam','sk.monirulislam.business@gmail.com','+916289701417',NULL,'new',NULL,'medium','meta_ads','2589357921478963','{\"lead_source\":\"facebook\",\"created_time\":\"2026-06-20T20:15:21+0000\"}','2026-07-18 02:54:53','2026-07-18 02:54:53',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(52,17,NULL,NULL,NULL,NULL,NULL,NULL,'Mithilesh Kumar Amit','mithileshyadav577@gmail.com','+919451108453',NULL,'new',NULL,'medium','meta_ads','1000202572606468','{\"lead_source\":\"facebook\",\"created_time\":\"2026-06-20T10:41:07+0000\"}','2026-07-18 02:54:53','2026-07-18 02:54:53',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(53,17,NULL,NULL,NULL,NULL,NULL,NULL,'Pynshai Wann Lyngdoh','pynshailyngdoh400@gmail.com','+919863848628',NULL,'new',NULL,'medium','meta_ads','1643651281101623','{\"lead_source\":\"facebook\",\"created_time\":\"2026-06-20T10:32:54+0000\"}','2026-07-18 02:54:53','2026-07-18 02:54:53',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(54,17,NULL,NULL,NULL,NULL,NULL,NULL,'Gurudev Chhaliya Gurudev Chhaliya','sudhakarkumarsudhakarkumar83@gmail.com','+18303839507',NULL,'new',NULL,'medium','meta_ads','2842696616106707','{\"lead_source\":\"facebook\",\"created_time\":\"2026-06-20T06:56:36+0000\"}','2026-07-18 02:54:53','2026-07-18 02:54:53',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(55,17,NULL,NULL,NULL,NULL,NULL,NULL,'HIÈR','kamalthakur1005@gmail.com','+919920511572',NULL,'new',NULL,'medium','meta_ads','1677565176796287','{\"lead_source\":\"facebook\",\"created_time\":\"2026-06-19T14:16:51+0000\"}','2026-07-18 02:54:53','2026-07-18 02:54:53',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(56,17,NULL,NULL,NULL,NULL,NULL,NULL,'MD Usman','mdusmannn35@gmail.com','+919776067860',NULL,'new',NULL,'medium','meta_ads','2160904984456065','{\"lead_source\":\"facebook\",\"created_time\":\"2026-06-19T13:01:08+0000\"}','2026-07-18 02:54:53','2026-07-18 02:54:53',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(57,17,NULL,NULL,NULL,NULL,NULL,NULL,'Rajalakshmi D Raji','rajmunis193@gmail.com','+918489358125',NULL,'new',NULL,'medium','meta_ads','1610536077337837','{\"lead_source\":\"facebook\",\"created_time\":\"2026-06-19T11:22:31+0000\"}','2026-07-18 02:54:53','2026-07-18 02:54:53',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(58,17,NULL,NULL,NULL,NULL,NULL,NULL,'bhavesh rajpara','bhaveshrajpara@yahoo.com','+919904178967',NULL,'new',NULL,'medium','meta_ads','1583299733357851','{\"lead_source\":\"facebook\",\"created_time\":\"2026-06-19T06:06:53+0000\"}','2026-07-18 02:54:53','2026-07-18 02:54:53',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(59,17,NULL,NULL,NULL,NULL,NULL,NULL,'Krishna Kumar','drops9259@gmail.com','+917034797166',NULL,'new',NULL,'medium','meta_ads','1738069710865894','{\"lead_source\":\"facebook\",\"created_time\":\"2026-06-18T11:34:18+0000\"}','2026-07-18 02:54:53','2026-07-18 02:54:53',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(60,17,NULL,NULL,NULL,NULL,NULL,NULL,'Hafiz Saiyed','hafizsaiyed22@gmail.com','+919727177864',NULL,'new',NULL,'medium','meta_ads','1956499338393350','{\"lead_source\":\"facebook\",\"created_time\":\"2026-06-18T10:41:20+0000\"}','2026-07-18 02:54:53','2026-07-18 02:54:53',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(61,17,NULL,NULL,NULL,NULL,NULL,NULL,'Sumit Katre','katresumit8@gmail.com','+919767038607',NULL,'new',NULL,'medium','meta_ads','1618172246977756','{\"lead_source\":\"facebook\",\"created_time\":\"2026-06-18T10:36:11+0000\"}','2026-07-18 02:54:53','2026-07-18 02:54:53',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(62,17,NULL,NULL,NULL,NULL,NULL,NULL,'Urvashi Gamit','urvashigamit825@gmail.com','+918306355747',NULL,'new',NULL,'medium','meta_ads','941955485533656','{\"lead_source\":\"facebook\",\"created_time\":\"2026-06-18T10:29:46+0000\"}','2026-07-18 02:54:53','2026-07-18 02:54:53',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(63,17,NULL,NULL,NULL,NULL,NULL,NULL,'Rupam Banerjee','rupambnrj@gmail.com','+917303299192',NULL,'new',NULL,'medium','meta_ads','1563837295292369','{\"lead_source\":\"facebook\",\"created_time\":\"2026-06-18T09:29:20+0000\"}','2026-07-18 02:54:53','2026-07-18 02:54:53',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(64,17,NULL,NULL,NULL,NULL,NULL,NULL,'Sher Ali','sher.ali05@gmail.com','+918874838386',NULL,'new',NULL,'medium','meta_ads','1548910623682671','{\"lead_source\":\"facebook\",\"created_time\":\"2026-06-18T06:07:20+0000\"}','2026-07-18 02:54:53','2026-07-18 02:54:53',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(65,17,NULL,NULL,NULL,NULL,NULL,NULL,'Hafiz','shaikhhafiz7075@gmail.com','+919890454714',NULL,'new',NULL,'medium','meta_ads','1342449797837437','{\"lead_source\":\"facebook\",\"created_time\":\"2026-06-12T07:40:58+0000\"}','2026-07-18 02:54:53','2026-07-18 02:54:53',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(66,17,NULL,NULL,NULL,NULL,NULL,NULL,'Sahil Gulati','sahilgulati383@gmail.com','+918178007424',NULL,'new',NULL,'medium','meta_ads','1546730090392197','{\"lead_source\":\"facebook\",\"created_time\":\"2026-06-12T04:09:41+0000\"}','2026-07-18 02:54:53','2026-07-18 02:54:53',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(67,17,NULL,NULL,NULL,NULL,NULL,NULL,'mansoori_786','sm5899241@gmail.com','+919773688075',NULL,'new',NULL,'medium','meta_ads','1994532354521875','{\"lead_source\":\"facebook\",\"created_time\":\"2026-06-11T13:38:47+0000\"}','2026-07-18 02:54:53','2026-07-18 02:54:53',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(68,17,NULL,NULL,NULL,NULL,NULL,NULL,'Bishakha Saha','bishakha@brandfizz.com','+919810289193',NULL,'new',NULL,'medium','meta_ads','2179217752880853','{\"lead_source\":\"facebook\",\"created_time\":\"2026-04-28T15:36:37+0000\"}','2026-07-18 02:54:53','2026-07-18 02:54:53',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(69,17,NULL,NULL,NULL,NULL,NULL,NULL,'Ashish','ashish@calidadhealthcare.com','+918269175658',NULL,'new',NULL,'medium','meta_ads','969568699159578','{\"lead_source\":\"facebook\",\"created_time\":\"2026-04-28T14:22:13+0000\"}','2026-07-18 02:54:53','2026-07-18 02:54:53',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(70,17,NULL,NULL,NULL,NULL,NULL,NULL,'Vini','snvineethvini@gmail.com','+917618739556',NULL,'new',NULL,'medium','meta_ads','1289873565855162','{\"lead_source\":\"facebook\",\"created_time\":\"2026-04-28T12:52:15+0000\"}','2026-07-18 02:54:53','2026-07-18 02:54:53',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(71,17,NULL,NULL,NULL,NULL,NULL,NULL,'Range Of Himalayas','justashailav02@gmail.com','+919015118744',NULL,'new',NULL,'medium','meta_ads','1623066862673705','{\"lead_source\":\"facebook\",\"created_time\":\"2026-04-28T12:31:51+0000\"}','2026-07-18 02:54:53','2026-07-18 02:54:53',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(72,17,NULL,NULL,NULL,NULL,NULL,NULL,'Ishan Mishra','medhaavidigital@gmail.com','+919009231652',NULL,'new',NULL,'medium','meta_ads','986593450389650','{\"lead_source\":\"facebook\",\"created_time\":\"2026-04-28T12:24:37+0000\"}','2026-07-18 02:54:53','2026-07-18 02:54:53',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(73,17,NULL,NULL,NULL,NULL,NULL,NULL,'Ammad Ansari','ammad.a.ansari@gmail.com','+919920723165',NULL,'new',NULL,'medium','meta_ads','971335435385259','{\"lead_source\":\"facebook\",\"created_time\":\"2026-04-28T01:56:02+0000\"}','2026-07-18 02:54:53','2026-07-18 02:54:53',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(74,17,NULL,NULL,NULL,NULL,NULL,NULL,'Shivani Bhatt','shivanidimri@gmail.com','+917700007190',NULL,'new',NULL,'medium','meta_ads','1738364280655853','{\"lead_source\":\"facebook\",\"created_time\":\"2026-04-28T00:57:28+0000\"}','2026-07-18 02:54:53','2026-07-18 02:54:53',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(75,17,NULL,NULL,NULL,NULL,NULL,NULL,'Jeny Fitwala','jenymashruwala367@gmail.com','+916354871399',NULL,'new',NULL,'medium','meta_ads','834567585791556','{\"lead_source\":\"facebook\",\"created_time\":\"2026-04-27T17:00:46+0000\"}','2026-07-18 02:54:53','2026-07-18 02:54:53',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(76,17,NULL,NULL,NULL,NULL,NULL,NULL,'Dilip kumar','dilip.newrra@gmail.com','+916200507640',NULL,'new',NULL,'medium','meta_ads','907155632353578','{\"lead_source\":\"facebook\",\"created_time\":\"2026-04-27T15:19:33+0000\"}','2026-07-18 02:54:53','2026-07-18 02:54:53',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(77,17,NULL,NULL,NULL,NULL,NULL,NULL,'Tejas Varpe','varpetejas88@gmail.com','+917057974626',NULL,'new',NULL,'medium','meta_ads','1004256901971469','{\"lead_source\":\"facebook\",\"created_time\":\"2026-04-27T11:09:20+0000\"}','2026-07-18 02:54:53','2026-07-18 02:54:53',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(78,17,NULL,NULL,NULL,NULL,NULL,NULL,'renuka agarwal','renukasanjay9999@gmail.com','+919167893215',NULL,'new',NULL,'medium','meta_ads','941240452023709','{\"lead_source\":\"facebook\",\"created_time\":\"2026-04-27T04:05:27+0000\"}','2026-07-18 02:54:53','2026-07-18 02:54:53',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(79,17,NULL,NULL,NULL,NULL,NULL,NULL,'Rahul Pandey','hariram29pandey@gmail.com','+918800542142',NULL,'new',NULL,'medium','meta_ads','916096321458229','{\"lead_source\":\"facebook\",\"created_time\":\"2026-04-26T19:10:27+0000\"}','2026-07-18 02:54:53','2026-07-18 02:54:53',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(80,17,NULL,NULL,NULL,NULL,NULL,NULL,'Vikram Kumar','bhawna.vk7@gmail.com','+918295384222',NULL,'new',NULL,'medium','meta_ads','1122872340027218','{\"lead_source\":\"facebook\",\"created_time\":\"2026-04-26T17:09:48+0000\"}','2026-07-18 02:54:53','2026-07-18 02:54:53',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(81,17,NULL,NULL,NULL,NULL,NULL,NULL,'Vaibhav Satchidanand Bagawe','winspiredivine@gmail.com','+919619480252',NULL,'new',NULL,'medium','meta_ads','1695349924969366','{\"lead_source\":\"facebook\",\"created_time\":\"2026-04-26T12:22:39+0000\"}','2026-07-18 02:54:53','2026-07-18 02:54:53',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(82,17,NULL,NULL,NULL,NULL,NULL,NULL,'Sanjay Singh','info.neotravels@gmail.com','+919711995418',NULL,'new',NULL,'medium','meta_ads','896420010072727','{\"lead_source\":\"facebook\",\"created_time\":\"2026-04-26T05:37:22+0000\"}','2026-07-18 02:54:53','2026-07-18 02:54:53',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(83,17,NULL,NULL,NULL,NULL,NULL,NULL,'Rocksmins Crystals','rocksmins@gmail.com','+919315110435',NULL,'new',NULL,'medium','meta_ads','1685246142480393','{\"lead_source\":\"facebook\",\"created_time\":\"2026-04-26T01:10:01+0000\"}','2026-07-18 02:54:53','2026-07-18 02:54:53',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(84,17,NULL,NULL,NULL,NULL,NULL,NULL,'Sentu','amazonsaheb@gmail.com','+917903844824',NULL,'new',NULL,'medium','meta_ads','731816539958949','{\"lead_source\":\"facebook\",\"created_time\":\"2026-04-25T19:56:00+0000\"}','2026-07-18 02:54:53','2026-07-18 02:54:53',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(85,17,NULL,NULL,NULL,NULL,NULL,NULL,'Jashandeep Jimmy','jimmyphermill@gmail.com','+917355232944',NULL,'new',NULL,'medium','meta_ads','1491039402572227','{\"lead_source\":\"facebook\",\"created_time\":\"2026-04-25T17:38:23+0000\"}','2026-07-18 02:54:53','2026-07-18 02:54:53',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(86,17,NULL,NULL,NULL,NULL,NULL,NULL,'Sanjay shah','binsfashion7@gmail.com','+917666572252',NULL,'new',NULL,'medium','meta_ads','962881046584228','{\"lead_source\":\"facebook\",\"created_time\":\"2026-04-25T14:43:26+0000\"}','2026-07-18 02:54:53','2026-07-18 02:54:53',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `leads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mcp_oauth_authorization_codes`
--

DROP TABLE IF EXISTS `mcp_oauth_authorization_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mcp_oauth_authorization_codes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code_hash` varchar(64) NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `client_id` varchar(255) NOT NULL,
  `redirect_uri` text NOT NULL,
  `scopes` varchar(255) DEFAULT NULL,
  `code_challenge` varchar(128) DEFAULT NULL,
  `code_challenge_method` varchar(10) DEFAULT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `consumed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mcp_oauth_authorization_codes_code_hash_unique` (`code_hash`),
  KEY `mcp_oauth_authorization_codes_user_id_foreign` (`user_id`),
  KEY `mcp_oauth_authorization_codes_client_id_index` (`client_id`),
  CONSTRAINT `mcp_oauth_authorization_codes_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mcp_oauth_authorization_codes`
--

LOCK TABLES `mcp_oauth_authorization_codes` WRITE;
/*!40000 ALTER TABLE `mcp_oauth_authorization_codes` DISABLE KEYS */;
INSERT INTO `mcp_oauth_authorization_codes` VALUES (1,'814a2358e8a964dee8cba97f79b6c8d3e3af8b7a5063155979b8f635cff6dc69',20,'mcp_qvcqs2nwPgBxpbc6jTfnLVZr461oNIx5','http://localhost:9999/callback','mcp','bwfKxZIvSE7bqTyle8hLQSnhe87BJJCSyCUksaWKINU','S256','2026-11-09 05:58:18','2026-11-09 00:28:18','2026-11-09 00:28:18','2026-11-09 00:28:18');
/*!40000 ALTER TABLE `mcp_oauth_authorization_codes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mcp_oauth_clients`
--

DROP TABLE IF EXISTS `mcp_oauth_clients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mcp_oauth_clients` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `client_id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `client_secret` varchar(255) DEFAULT NULL,
  `redirect_uris` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`redirect_uris`)),
  `grant_types` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`grant_types`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mcp_oauth_clients_client_id_unique` (`client_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mcp_oauth_clients`
--

LOCK TABLES `mcp_oauth_clients` WRITE;
/*!40000 ALTER TABLE `mcp_oauth_clients` DISABLE KEYS */;
INSERT INTO `mcp_oauth_clients` VALUES (1,'mcp_rWIA6OokCMCtpa6bnWk12PGgDWel7FCB','Test Client',NULL,'[\"http:\\/\\/localhost:9999\\/callback\"]','[\"authorization_code\"]','2026-11-09 00:08:33','2026-11-09 00:08:33'),(2,'mcp_Phr43cSavpuPD6ntGqs4GNL6gqLYxFl7','Smoke Client',NULL,'[\"http:\\/\\/localhost:9999\\/callback\"]','[\"authorization_code\"]','2026-11-09 00:27:00','2026-11-09 00:27:00'),(3,'mcp_qvcqs2nwPgBxpbc6jTfnLVZr461oNIx5','Smoke Client',NULL,'[\"http:\\/\\/localhost:9999\\/callback\"]','[\"authorization_code\"]','2026-11-09 00:28:17','2026-11-09 00:28:17'),(4,'rumu-chatgpt','ChatGPT Custom GPT','rumu_sec_chatgpt_948f2a1b7e6c5d0e','[\"https:\\/\\/chat.openai.com\\/aip\\/*\",\"https:\\/\\/chatgpt.com\\/aip\\/*\",\"https:\\/\\/chatgpt.com\\/*\"]','[\"authorization_code\"]','2026-11-09 03:43:00','2026-11-09 03:43:00');
/*!40000 ALTER TABLE `mcp_oauth_clients` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `meta_campaign_forms`
--

DROP TABLE IF EXISTS `meta_campaign_forms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `meta_campaign_forms` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `campaign_id` bigint(20) unsigned NOT NULL,
  `page_id` varchar(255) NOT NULL,
  `form_id` varchar(255) NOT NULL,
  `form_name` varchar(255) NOT NULL,
  `last_webhook_received_at` datetime DEFAULT NULL,
  `last_manual_sync_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `meta_campaign_forms_campaign_id_index` (`campaign_id`),
  KEY `meta_campaign_forms_page_id_index` (`page_id`),
  CONSTRAINT `meta_campaign_forms_campaign_id_foreign` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `meta_campaign_forms`
--

LOCK TABLES `meta_campaign_forms` WRITE;
/*!40000 ALTER TABLE `meta_campaign_forms` DISABLE KEYS */;
INSERT INTO `meta_campaign_forms` VALUES (1,17,'111958791970913','all','Mapped Form',NULL,'2026-07-18 10:20:34','2026-07-18 02:54:32','2026-07-18 04:50:34');
/*!40000 ALTER TABLE `meta_campaign_forms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `meta_capi_event_logs`
--

DROP TABLE IF EXISTS `meta_capi_event_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `meta_capi_event_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `lead_id` bigint(20) unsigned NOT NULL,
  `meta_lead_id` varchar(255) DEFAULT NULL,
  `event_name` varchar(255) NOT NULL,
  `crm_status` varchar(255) NOT NULL,
  `success` tinyint(1) NOT NULL DEFAULT 0,
  `meta_event_id` varchar(255) DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `meta_capi_event_logs_lead_id_crm_status_unique` (`lead_id`,`crm_status`),
  KEY `meta_capi_event_logs_meta_lead_id_index` (`meta_lead_id`),
  CONSTRAINT `meta_capi_event_logs_lead_id_foreign` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `meta_capi_event_logs`
--

LOCK TABLES `meta_capi_event_logs` WRITE;
/*!40000 ALTER TABLE `meta_capi_event_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `meta_capi_event_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `meta_capi_events`
--

DROP TABLE IF EXISTS `meta_capi_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `meta_capi_events` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `lead_id` bigint(20) unsigned NOT NULL,
  `campaign_id` bigint(20) unsigned NOT NULL,
  `event_name` varchar(255) NOT NULL,
  `event_id` char(36) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `response` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`response`)),
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `meta_capi_events_event_id_unique` (`event_id`),
  KEY `meta_capi_events_lead_id_foreign` (`lead_id`),
  KEY `meta_capi_events_campaign_id_foreign` (`campaign_id`),
  CONSTRAINT `meta_capi_events_campaign_id_foreign` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`) ON DELETE CASCADE,
  CONSTRAINT `meta_capi_events_lead_id_foreign` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `meta_capi_events`
--

LOCK TABLES `meta_capi_events` WRITE;
/*!40000 ALTER TABLE `meta_capi_events` DISABLE KEYS */;
/*!40000 ALTER TABLE `meta_capi_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `meta_capi_settings`
--

DROP TABLE IF EXISTS `meta_capi_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `meta_capi_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `campaign_id` bigint(20) unsigned NOT NULL,
  `pixel_id` varchar(255) NOT NULL,
  `access_token` text NOT NULL,
  `test_event_code` varchar(255) DEFAULT NULL,
  `status_mapping` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`status_mapping`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `meta_capi_settings_campaign_id_foreign` (`campaign_id`),
  CONSTRAINT `meta_capi_settings_campaign_id_foreign` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `meta_capi_settings`
--

LOCK TABLES `meta_capi_settings` WRITE;
/*!40000 ALTER TABLE `meta_capi_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `meta_capi_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `meta_pages`
--

DROP TABLE IF EXISTS `meta_pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `meta_pages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `page_id` varchar(255) NOT NULL,
  `page_name` varchar(255) NOT NULL,
  `access_token` text NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'connected',
  `token_last_synced_at` datetime DEFAULT NULL,
  `consecutive_failures` int(10) unsigned NOT NULL DEFAULT 0,
  `last_successful_api_request_at` timestamp NULL DEFAULT NULL,
  `last_failed_api_request_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `enterprise_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `meta_pages_user_id_index` (`user_id`),
  KEY `meta_pages_page_id_index` (`page_id`),
  KEY `meta_pages_enterprise_id_index` (`enterprise_id`),
  CONSTRAINT `meta_pages_enterprise_id_foreign` FOREIGN KEY (`enterprise_id`) REFERENCES `enterprises` (`id`) ON DELETE SET NULL,
  CONSTRAINT `meta_pages_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `meta_pages`
--

LOCK TABLES `meta_pages` WRITE;
/*!40000 ALTER TABLE `meta_pages` DISABLE KEYS */;
INSERT INTO `meta_pages` VALUES (1,15,'110913518764214','Iconicpages','eyJpdiI6InZidUFJb25tV09OcmozRmUxZUM0ekE9PSIsInZhbHVlIjoiR0x4cFRPeVNWNUZUbTdwdko2R0dlTjFMZUZRNGM3Ull6R2dNRHlaYTZjekpVN05uT1VjaXFKbDFzNzlFb1ozcHAvek5qbmYyZm5NdmkzNVo1aHA4dDRqZ1RDY3pqanJtTW1uL0lFTWhNYlViRzZDdm54TExJenpRQjB1OWxaQkZjQWhYMG1OenZXK2hIVzJSZVcrMzNtMThhcTRWMXloRFUzamc5OXRjT3hyWTUxTldxUTFickw0cmlzWFRld01JRk5vNVArSVZaSDVuMytDR3p6SUVaKy83Mjk5Zm85R3FaRHlJdDlzZGsxaVpMZ0VpVTJxcHk1UG1BVDZWenBRa3poQVdnQ2dENW9nTVozVC81TU0yRjJaMmN0cmZFdzBudXVGVDVCZ1NzbWs9IiwibWFjIjoiYzMxYzhiNDQxMWIyZWZlOTFmNGQzZTU0ZGFlOWJlYWY0ODQwMWU0MTdmMjI2NTM0ODgzN2Y1MDRiY2UzNTBlZCIsInRhZyI6IiJ9','connected','2026-07-18 10:23:36',0,'2026-07-18 01:12:53',NULL,'2026-07-13 03:09:46','2026-07-18 04:53:36',NULL),(2,15,'379279975264729','Detailory','eyJpdiI6Im80dWNINmN0Y0lud2EvNXhYUEFwRnc9PSIsInZhbHVlIjoidDZabVE1MGRjN29ObmEwalc2ZGs2bVVUZTlxYnc0NmhLK0FEbzRqMDcrSnlkajFzc3dvVUk2MmIwZDM3U2l4dHd2UDMxUldRZTBKNzJPck5WZnFybzVmYXplUWExNVhMa2pYZEhoTmhHdDREbTV2eEs0THJvcUJGTVd3bnZjSW05SnZGQXl5Y1VwSi9CVXBJdmxVSWVxSDdmS25BYVBOTFNhTmFMek5rSFVvRkYremVHTlZ0a2xvMkMrWStCUmFJSVhSb2hrVk9DSy90MW5rRXk1M1NqK3Q4VXhLenBZakg0REtTZTBLdHJkQ2YwWUxWaWJHR3NNTDhXT2ROZndYZ0dxR2dZWU1uUlVBb0Q5N1o3eUJ2RGc9PSIsIm1hYyI6IjJjMTcwZmQ2ZjM3MmJlOTYwNWY3MDE4MzdjM2JmODM4OWYyZWIzOWMwYzc3MWE2ODg5ZjQ5ODRjMTA2M2QzY2UiLCJ0YWciOiIifQ==','connected','2026-07-18 10:23:37',0,NULL,NULL,'2026-07-18 02:39:21','2026-07-18 04:53:37',NULL),(3,15,'340204242513466','Lead List','eyJpdiI6Im05YVVYeWlCWVZQVWVraXdRcVY3Q0E9PSIsInZhbHVlIjoiSWFMRUF6NGhGQ1B5ZHk1aU1qRTYrOUhhY2ZNNGZ1U09ONTRzNHNvSTNaS01Kd0JYWDg0S1d6ZStVdU5DS3NGYU1qS0NPUi95R2xvV0lVaUUxM0VvaUlIcEFuZzFPeXl1YnAxamNWNUNqM0ZXVXpwS040eDFiaGZseDl2WU9DRFovYWlrT2U5NG9sQ1dvOC9qMkpsbUR1anFycWdsTVFmTHMrNHJnSnRDN3lsekt0cC8vNzJla0l6QWdlb1JBUWdRYllMcithSWIzeG1rOTQxaGJtaFZNeXZyL0JZcUQ3Njcxbzgvb1hIcUxFOElxM0JMd2NzaVJyUnNLNVhrc1lXRkFKa09PYkU5N1huUmRHMndnQ01XT0pON3Vnb3dTSmZtS3R4TzhXRjJqZnc9IiwibWFjIjoiYjhmYmZhMWEzMTMwMTc0MDQ3NmIxZGQ0ZDIwOGE3YjhjNzEzYTA0ZTlkNTZiMGY1MDhkNjdmMjIxNzhjOTQ1NCIsInRhZyI6IiJ9','connected','2026-07-18 10:23:40',0,NULL,NULL,'2026-07-18 02:39:22','2026-07-18 04:53:40',NULL),(4,15,'111958791970913','Iconicpageshq','eyJpdiI6IkhlWHRhMUdCWElZaWlqTGVlWWhHaHc9PSIsInZhbHVlIjoia2wxNlArT2JYd0hUeUwrbzBxYkRrUVR4VWViejVKU2tmd1d3YkhIaGs2S2pWYTdHYnh3eENMVzFqYnRrWjA5RVp3UFB2NVVoemVBUXR6bWg4NmFYbnloSXU0SmswOGc3VFh1MWNoNlZlNGhkV2NVL3A5eSt2ekxWYWlZSmFTeUN0L3IzTVFrMFdBUS8wYzUxNUs5V2xpSE1VMnRjdWo5WEFqSFF3cHJOSXh5TlZaWkZGc0NweElFN3dzQ0t5L3RDVVZFOFByQjJIcldhSVcxdXBGWEZPOWNYWGtzdE9mUlB5OUR5bk1rUW5TMXAwUTZGM2cyNnIweGEwaHU2d0MrckpMeFFVSWtudytVMXB3OSs3dUZYN2c9PSIsIm1hYyI6IjI3ODhhMjRjZGJiNzkzNmU1ZDQzZTBjNzYwM2RkY2I4ZDA2OWEzMmE0NDM2ZGYwMDQ0NTk1YmZjOTU3NjY5ZTciLCJ0YWciOiIifQ==','connected','2026-07-18 10:23:41',0,'2026-07-18 04:50:34',NULL,'2026-07-18 02:39:23','2026-07-18 04:53:41',NULL);
/*!40000 ALTER TABLE `meta_pages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `meta_sync_logs`
--

DROP TABLE IF EXISTS `meta_sync_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `meta_sync_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `campaign_id` bigint(20) unsigned DEFAULT NULL,
  `type` varchar(255) NOT NULL,
  `event_type` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `context` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`context`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `meta_sync_logs_user_id_index` (`user_id`),
  KEY `meta_sync_logs_campaign_id_index` (`campaign_id`),
  KEY `meta_sync_logs_event_type_index` (`event_type`),
  CONSTRAINT `meta_sync_logs_campaign_id_foreign` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`) ON DELETE SET NULL,
  CONSTRAINT `meta_sync_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `meta_sync_logs`
--

LOCK TABLES `meta_sync_logs` WRITE;
/*!40000 ALTER TABLE `meta_sync_logs` DISABLE KEYS */;
INSERT INTO `meta_sync_logs` VALUES (1,15,NULL,'success','connected','Facebook account linked and pages synced successfully.','{\"facebook_id\":\"878707245306699\"}','2026-07-13 03:09:49','2026-07-13 03:09:49'),(2,15,NULL,'info','disconnected','Facebook account disconnected. Associated pages marked as disconnected.','{\"facebook_id\":\"878707245306699\"}','2026-07-13 03:20:19','2026-07-13 03:20:19'),(3,15,NULL,'success','connected','Facebook account linked and pages synced successfully.','{\"facebook_id\":\"878707245306699\"}','2026-07-15 04:26:27','2026-07-15 04:26:27'),(4,15,NULL,'info','disconnected','Facebook account disconnected. Associated pages marked as disconnected.','{\"facebook_id\":\"878707245306699\"}','2026-07-15 04:39:05','2026-07-15 04:39:05'),(5,15,NULL,'success','connected','Facebook account linked and pages synced successfully.','{\"facebook_id\":\"878707245306699\"}','2026-07-15 04:39:43','2026-07-15 04:39:43'),(6,15,NULL,'success','connected','Facebook account linked and pages synced successfully.','{\"facebook_id\":\"878707245306699\"}','2026-07-15 04:40:06','2026-07-15 04:40:06'),(7,15,NULL,'success','connected','Facebook account linked and pages synced successfully.','{\"facebook_id\":\"878707245306699\"}','2026-07-15 04:44:40','2026-07-15 04:44:40'),(8,15,NULL,'success','connected','Facebook account linked and pages synced successfully.','{\"facebook_id\":\"878707245306699\"}','2026-07-15 04:52:39','2026-07-15 04:52:39'),(9,15,NULL,'success','connected','Facebook account linked and pages synced successfully.','{\"facebook_id\":\"878707245306699\"}','2026-07-15 04:56:13','2026-07-15 04:56:13'),(10,15,NULL,'success','connected','Facebook account linked and pages synced successfully.','{\"facebook_id\":\"878707245306699\"}','2026-07-15 05:00:20','2026-07-15 05:00:20'),(11,15,NULL,'success','connected','Facebook account linked and pages synced successfully.','{\"facebook_id\":\"878707245306699\"}','2026-07-15 05:05:54','2026-07-15 05:05:54'),(12,15,NULL,'success','connected','Facebook account linked and pages synced successfully.','{\"facebook_id\":\"878707245306699\"}','2026-07-15 05:08:09','2026-07-15 05:08:09'),(13,15,NULL,'success','connected','Facebook account linked and pages synced successfully.','{\"facebook_id\":\"878707245306699\"}','2026-07-15 05:09:14','2026-07-15 05:09:14'),(14,15,NULL,'info','disconnected','Facebook account disconnected. Associated pages marked as disconnected.','{\"facebook_id\":\"878707245306699\"}','2026-07-15 05:11:47','2026-07-15 05:11:47'),(15,15,NULL,'success','connected','Facebook account linked and pages synced successfully.','{\"facebook_id\":\"878707245306699\"}','2026-07-15 05:12:29','2026-07-15 05:12:29'),(16,15,17,'success','manual_sync','Synced 0 lead(s) from Meta.','{\"imported\":0,\"skipped\":0,\"failed\":0}','2026-07-15 05:17:53','2026-07-15 05:17:53'),(17,15,17,'success','manual_sync','Synced 0 lead(s) from Meta.','{\"imported\":0,\"skipped\":0,\"failed\":0}','2026-07-15 05:23:19','2026-07-15 05:23:19'),(18,15,17,'success','manual_sync','Synced 0 lead(s) from Meta.','{\"imported\":0,\"skipped\":0,\"failed\":0}','2026-07-15 05:23:31','2026-07-15 05:23:31'),(19,15,17,'success','manual_sync','Synced 0 lead(s) from Meta. Skipped 1 duplicate(s).','{\"imported\":0,\"skipped\":1,\"failed\":0}','2026-07-15 05:32:24','2026-07-15 05:32:24'),(20,15,NULL,'info','disconnected','Facebook account disconnected. Associated pages marked as disconnected.','{\"facebook_id\":\"878707245306699\"}','2026-07-16 05:03:15','2026-07-16 05:03:15'),(21,15,NULL,'success','connected','Facebook account linked and pages synced successfully.','{\"facebook_id\":\"878707245306699\"}','2026-07-16 05:03:51','2026-07-16 05:03:51'),(22,15,17,'success','manual_sync','Synced 0 lead(s) from Meta. Skipped 1 duplicate(s).','{\"imported\":0,\"skipped\":1,\"failed\":0}','2026-07-16 05:04:00','2026-07-16 05:04:00'),(23,15,17,'success','manual_sync','Synced 0 lead(s) from Meta. Skipped 1 duplicate(s).','{\"imported\":0,\"skipped\":1,\"failed\":0}','2026-07-18 01:12:51','2026-07-18 01:12:51'),(24,15,17,'success','manual_sync','Synced 0 lead(s) from Meta. Skipped 1 duplicate(s).','{\"imported\":0,\"skipped\":1,\"failed\":0}','2026-07-18 01:12:53','2026-07-18 01:12:53'),(25,15,NULL,'success','connected','Facebook account linked and pages synced successfully.','{\"facebook_id\":\"878707245306699\"}','2026-07-18 01:45:46','2026-07-18 01:45:46'),(26,15,NULL,'success','connected','Facebook account linked and pages synced successfully.','{\"facebook_id\":\"878707245306699\"}','2026-07-18 02:22:06','2026-07-18 02:22:06'),(27,15,NULL,'success','connected','Facebook account linked and pages synced successfully.','{\"facebook_id\":\"878707245306699\"}','2026-07-18 02:23:57','2026-07-18 02:23:57'),(28,15,NULL,'success','connected','Facebook account linked and pages synced successfully.','{\"facebook_id\":\"878707245306699\"}','2026-07-18 02:27:22','2026-07-18 02:27:22'),(29,15,NULL,'success','connected','Facebook account linked and pages synced successfully.','{\"facebook_id\":\"878707245306699\"}','2026-07-18 02:28:36','2026-07-18 02:28:36'),(30,15,NULL,'success','connected','Facebook account linked and pages synced successfully.','{\"facebook_id\":\"878707245306699\"}','2026-07-18 02:33:19','2026-07-18 02:33:19'),(31,15,NULL,'success','connected','Facebook account linked and pages synced successfully.','{\"facebook_id\":\"878707245306699\"}','2026-07-18 02:34:28','2026-07-18 02:34:28'),(32,15,NULL,'success','connected','Facebook account linked and pages synced successfully.','{\"facebook_id\":\"878707245306699\"}','2026-07-18 02:39:24','2026-07-18 02:39:24'),(33,15,17,'success','manual_sync','Synced 72 lead(s) from Meta. Skipped 1 duplicate(s).','{\"imported\":72,\"skipped\":1,\"failed\":0}','2026-07-18 02:54:53','2026-07-18 02:54:53'),(34,15,17,'success','manual_sync','Synced 0 lead(s) from Meta. Skipped 73 duplicate(s).','{\"imported\":0,\"skipped\":73,\"failed\":0}','2026-07-18 04:50:34','2026-07-18 04:50:34'),(35,15,NULL,'info','disconnected','Facebook account disconnected. Associated pages marked as disconnected.','{\"facebook_id\":\"878707245306699\"}','2026-07-18 04:52:41','2026-07-18 04:52:41'),(36,15,NULL,'success','connected','Facebook account linked and pages synced successfully.','{\"facebook_id\":\"878707245306699\"}','2026-07-18 04:53:43','2026-07-18 04:53:43');
/*!40000 ALTER TABLE `meta_sync_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `meta_tokens`
--

DROP TABLE IF EXISTS `meta_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `meta_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `meta_user_id` varchar(255) NOT NULL,
  `access_token` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `enterprise_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `meta_tokens_user_id_foreign` (`user_id`),
  KEY `meta_tokens_enterprise_id_index` (`enterprise_id`),
  CONSTRAINT `meta_tokens_enterprise_id_foreign` FOREIGN KEY (`enterprise_id`) REFERENCES `enterprises` (`id`) ON DELETE SET NULL,
  CONSTRAINT `meta_tokens_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `meta_tokens`
--

LOCK TABLES `meta_tokens` WRITE;
/*!40000 ALTER TABLE `meta_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `meta_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=76 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2026_01_19_000001_add_role_to_users_table',1),(5,'2026_01_19_000002_create_plans_table',1),(6,'2026_01_19_000003_add_plan_id_to_users_table',1),(7,'2026_01_19_000004_create_campaigns_table',1),(8,'2026_01_19_000005_create_leads_table',1),(9,'2026_01_19_000006_create_lead_notes_table',1),(10,'2026_01_19_072816_create_personal_access_tokens_table',1),(11,'2026_01_29_184346_add_key_to_plans_table',2),(12,'2026_01_29_191942_add_profile_fields_to_users_table',2),(13,'2026_01_29_192737_add_avatar_to_users_table',2),(14,'2026_01_29_194405_add_settings_fields_to_users_table',3),(15,'2026_01_29_201534_create_plan_upgrade_requests_table',4),(16,'2026_01_29_202645_add_expires_at_to_plan_upgrade_requests_table',5),(17,'2026_01_29_210507_create_notifications_table',6),(18,'2026_06_02_102404_add_google_id_to_users_table',7),(19,'2026_06_02_105259_add_google_avatar_to_users_table',7),(20,'2026_06_02_144711_add_facebook_fields_to_users_table',7),(21,'2026_06_02_153705_add_meta_ads_fields_for_lead_import',7),(22,'2026_06_02_180000_create_meta_capi_event_logs_table',7),(23,'2026_06_02_200000_create_projects_table',7),(24,'2026_06_02_200001_create_project_freelancer_table',7),(25,'2026_06_03_100000_add_template_key_to_projects_table',7),(26,'2026_06_03_120000_add_is_active_to_plans_table',7),(27,'2026_06_17_000000_drop_projects_tables',8),(28,'2026_06_17_100000_add_sort_order_to_plans_table',8),(29,'2026_06_17_100000_create_settings_table',8),(30,'2026_06_17_110000_add_header_color_to_plans_table',8),(31,'2026_06_17_120000_add_subscription_suspend_fields_to_users_table',8),(32,'2026_07_07_162708_change_avatar_columns_to_text_in_users_table',9),(33,'2026_07_08_000000_rename_freelancer_role_to_realtor',10),(34,'2026_07_08_100000_create_inventory_tables',10),(35,'2026_07_10_125158_create_meta_lead_ads_tables',11),(36,'2026_07_12_104403_add_facebook_page_name_to_users_table',12),(37,'2026_07_12_104411_add_sync_fields_to_meta_campaign_forms_table',12),(38,'2026_07_12_104421_create_meta_sync_logs_table',12),(39,'2026_07_12_105231_add_status_and_sync_fields_to_meta_pages_table',13),(40,'2026_07_12_110612_add_health_metrics_and_indexes_to_meta_tables',14),(41,'2026_07_18_154127_create_meta_capi_settings_table',15),(42,'2026_07_18_154130_create_meta_capi_events_table',15),(43,'2026_07_27_133158_create_subscriptions_table',16),(44,'2026_07_27_133159_create_payments_table',16),(45,'2026_07_27_133200_create_invoices_table',16),(46,'2026_07_27_133201_create_invoice_items_table',16),(47,'2026_07_27_133202_add_billing_fields_to_subscriptions_table',16),(48,'2026_07_27_140112_add_gateway_fields_to_billing_tables',16),(49,'2026_07_27_141155_create_promotions_and_referrals_tables',17),(50,'2026_07_27_142837_create_billing_notifications_tables',18),(51,'2026_07_27_143754_create_billing_settings_tables',19),(52,'2026_07_27_145756_add_billing_indexes',20),(53,'2026_07_27_161259_remove_referral_functionality',21),(55,'2026_07_30_075646_change_facebook_avatar_type_in_users_table',22),(56,'2026_08_16_151827_create_password_reset_token_table',22),(57,'2026_10_25_100000_create_enterprises_table',23),(58,'2026_10_25_100100_create_enterprise_organization_tables',23),(59,'2026_10_25_100200_add_enterprise_ownership_to_campaigns_and_inventory',23),(60,'2026_10_25_100300_extend_leads_for_enterprise',23),(61,'2026_10_25_100400_create_enterprise_sales_tables',23),(62,'2026_10_25_100500_add_enterprise_limits_to_plans',23),(63,'2026_10_25_110000_add_enterprise_role_to_users_table',23),(64,'2026_10_26_100000_add_enterprise_branding_and_seat_columns',23),(65,'2026_10_26_100100_create_enterprise_seat_requests_table',23),(66,'2026_10_26_100200_add_enterprise_scope_to_meta_tables',23),(67,'2026_10_26_100300_create_realtor_sales_tables',23),(68,'2026_10_26_100400_create_admin_broadcasts_table',23),(69,'2026_10_26_100500_create_platform_audit_logs_table',23),(70,'2026_10_31_100000_add_reporting_line_to_enterprise_memberships',24),(71,'2026_11_01_100000_create_call_tables',24),(72,'2026_11_08_120000_add_app_calling_to_call_logs',24),(73,'2026_11_08_120100_create_mobile_devices_table',24),(74,'2026_11_07_100000_create_mcp_oauth_tables',25),(75,'2026_11_09_150000_allow_oauth_client_secret_and_optional_pkce',26);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mobile_devices`
--

DROP TABLE IF EXISTS `mobile_devices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mobile_devices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `device_uuid` varchar(64) NOT NULL,
  `platform` varchar(16) NOT NULL DEFAULT 'android',
  `model` varchar(128) DEFAULT NULL,
  `os_version` varchar(32) DEFAULT NULL,
  `app_version` varchar(32) DEFAULT NULL,
  `recorder_source` varchar(64) DEFAULT NULL,
  `recording_auto_sync` tinyint(1) NOT NULL DEFAULT 1,
  `last_seen_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mobile_devices_user_id_device_uuid_unique` (`user_id`,`device_uuid`),
  CONSTRAINT `mobile_devices_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mobile_devices`
--

LOCK TABLES `mobile_devices` WRITE;
/*!40000 ALTER TABLE `mobile_devices` DISABLE KEYS */;
/*!40000 ALTER TABLE `mobile_devices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `type` varchar(255) NOT NULL DEFAULT 'freelancer_registered',
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `related_id` bigint(20) unsigned DEFAULT NULL,
  `related_type` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_user_id_foreign` (`user_id`),
  CONSTRAINT `notifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (1,1,'realtor_registered','New Freelancer Registered','Rohan poredi (poredirohan@gmail.com) has registered as a new freelancer.',4,'user',1,'2026-06-05 11:33:17','2026-01-31 08:52:06','2026-06-05 11:33:17'),(2,1,'realtor_registered','New Freelancer Registered','Yogesh Thakre (yogeshdt086@gmail.com) has registered as a new freelancer.',5,'user',1,'2026-06-05 11:33:17','2026-02-06 17:34:18','2026-06-05 11:33:17'),(3,1,'realtor_registered','New Freelancer Registered','omka naik (onkar.naik@iconicpages.com) has registered as a new freelancer.',6,'user',1,'2026-06-05 11:33:17','2026-04-20 09:56:13','2026-06-05 11:33:17'),(4,1,'realtor_registered','New Freelancer Registered','Amit Rajput (rocks3183@gmail.com) has registered as a new freelancer.',7,'user',1,'2026-06-05 11:33:17','2026-05-08 07:57:26','2026-06-05 11:33:17'),(5,1,'realtor_registered','New Freelancer Registered','Tanvi Mirase (tanvimirase2000@gmail.com) has registered as a new freelancer.',8,'user',1,'2026-06-05 11:33:17','2026-06-02 07:57:58','2026-06-05 11:33:17'),(6,1,'realtor_registered','New Freelancer Registered','Rumu AI CRM (rumuaicrm@gmail.com) has registered as a new freelancer.',9,'user',1,'2026-06-05 11:33:17','2026-06-05 10:59:46','2026-06-05 11:33:17'),(7,1,'realtor_registered','New Freelancer Registered','Atharva (assawant51@gmail.com) has registered as a new freelancer.',10,'user',0,NULL,'2026-06-05 14:07:59','2026-06-05 14:07:59'),(8,1,'realtor_registered','New Freelancer Registered','Omi naik (omiiiiinike@gmail.com) has registered as a new freelancer.',11,'user',0,NULL,'2026-06-05 20:32:20','2026-06-05 20:32:20'),(9,1,'realtor_registered','New Freelancer Registered','ICPG Technologies Pvt. Ltd. (teamiconicpages@gmail.com) has registered as a new freelancer.',12,'user',0,NULL,'2026-06-13 20:28:47','2026-06-13 20:28:47'),(10,1,'realtor_registered','New Freelancer Registered','Atharva Sawant (assawnat100@gmail.com) has registered as a new freelancer.',13,'user',0,NULL,'2026-07-07 10:58:56','2026-07-07 10:58:56'),(11,1,'realtor_registered','New Freelancer Registered','Atharva Sawant (assawnat100@gmail.com) has registered as a new freelancer.',14,'user',0,NULL,'2026-07-08 06:23:02','2026-07-08 06:23:02'),(12,1,'realtor_registered','New Realtor Registered','Atharva (assawant51@gmail.com) has registered as a new realtor.',16,'user',0,NULL,'2026-07-10 07:39:42','2026-07-10 07:39:42'),(13,1,'realtor_registered','New Realtor Registered','Atharva Sawant (assawanat100@gmail.com) has registered as a new realtor.',17,'user',0,NULL,'2026-07-27 08:24:16','2026-07-27 08:24:16'),(14,1,'realtor_registered','New Realtor Registered','Atharva Sawant (assawant020@gmail.com) has registered as a new realtor.',18,'user',0,NULL,'2026-07-28 03:46:43','2026-07-28 03:46:43'),(15,1,'realtor_registered','New Realtor Registered','Atharva Sawant (atharvasawant020@gmail.com) has registered as a new realtor.',19,'user',0,NULL,'2026-07-29 07:08:30','2026-07-29 07:08:30');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_token`
--

DROP TABLE IF EXISTS `password_reset_token`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_reset_token` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_token`
--

LOCK TABLES `password_reset_token` WRITE;
/*!40000 ALTER TABLE `password_reset_token` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_token` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
INSERT INTO `password_reset_tokens` VALUES ('atharvasawant020@gmail.com','$2y$12$GtkxgTP.Toz5.myAMXjKkOUzkJxZbMCSX0G2boidJ5.InHySgy9wa','2026-11-04 05:39:17');
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `subscription_id` bigint(20) unsigned NOT NULL,
  `payment_method` varchar(255) DEFAULT NULL,
  `transaction_id` varchar(255) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'USD',
  `gateway` varchar(255) DEFAULT NULL,
  `gateway_payment_id` varchar(255) DEFAULT NULL,
  `gateway_order_id` varchar(255) DEFAULT NULL,
  `gateway_signature` varchar(255) DEFAULT NULL,
  `webhook_payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`webhook_payload`)),
  `payment_status` varchar(255) NOT NULL DEFAULT 'pending',
  `payment_date` timestamp NULL DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `coupon_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payments_subscription_id_foreign` (`subscription_id`),
  KEY `payments_coupon_id_foreign` (`coupon_id`),
  KEY `payments_gateway_payment_id_index` (`gateway_payment_id`),
  KEY `payments_payment_status_index` (`payment_status`),
  KEY `payments_transaction_id_index` (`transaction_id`),
  CONSTRAINT `payments_coupon_id_foreign` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE SET NULL,
  CONSTRAINT `payments_subscription_id_foreign` FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
INSERT INTO `payments` VALUES (3,3,'online',NULL,1500.00,'INR',NULL,NULL,NULL,NULL,NULL,'failed',NULL,NULL,'2026-07-27 10:06:34','2026-07-27 10:25:25',NULL),(4,4,'online',NULL,1500.00,'INR',NULL,NULL,NULL,NULL,NULL,'pending',NULL,NULL,'2026-07-27 10:06:38','2026-07-27 10:06:38',NULL),(5,5,'online',NULL,1500.00,'INR',NULL,NULL,NULL,NULL,NULL,'pending',NULL,NULL,'2026-07-27 10:08:14','2026-07-27 10:08:14',NULL),(6,6,'online',NULL,1500.00,'INR',NULL,NULL,NULL,NULL,NULL,'pending',NULL,NULL,'2026-07-27 10:09:32','2026-07-27 10:09:32',NULL),(7,7,'online',NULL,1500.00,'INR',NULL,NULL,NULL,NULL,NULL,'pending',NULL,NULL,'2026-07-27 10:10:39','2026-07-27 10:10:39',NULL),(8,8,'online',NULL,1500.00,'INR',NULL,NULL,NULL,NULL,NULL,'pending',NULL,NULL,'2026-07-27 10:19:38','2026-07-27 10:19:38',NULL),(9,9,'online',NULL,1500.00,'INR',NULL,NULL,NULL,NULL,NULL,'pending',NULL,NULL,'2026-07-27 10:21:09','2026-07-27 10:21:09',NULL),(10,10,'online',NULL,1500.00,'INR',NULL,NULL,NULL,NULL,NULL,'pending',NULL,NULL,'2026-07-27 10:21:15','2026-07-27 10:21:15',NULL),(11,11,'online',NULL,1500.00,'INR',NULL,NULL,NULL,NULL,NULL,'pending',NULL,NULL,'2026-07-27 10:25:28','2026-07-27 10:25:28',NULL),(12,12,'online',NULL,1500.00,'INR',NULL,NULL,NULL,NULL,NULL,'pending',NULL,NULL,'2026-07-27 10:27:03','2026-07-27 10:27:03',NULL),(13,13,'online',NULL,1500.00,'INR',NULL,NULL,NULL,NULL,NULL,'pending',NULL,NULL,'2026-07-27 10:28:35','2026-07-27 10:28:35',NULL),(14,14,'online',NULL,1500.00,'INR','razorpay','pay_TIaLFtYRU4AFni','order_TIaL8SeDQ7XrK3','45d2b615d0fd224b81173c1f051616186a283b0617cce529369f222dc647c601',NULL,'completed',NULL,'2026-07-27 10:30:32','2026-07-27 10:30:09','2026-07-27 10:30:32',NULL),(15,15,'online',NULL,1500.00,'INR','razorpay','pay_TIs2B6gf1cMvr5','order_TIs1vWGBRJExkX','946d1f78537e752b931f2e425a147f6027dcd8135d6a14da5349cac0809b240f',NULL,'completed','2026-07-28 03:49:00','2026-07-28 03:49:00','2026-07-28 03:48:28','2026-07-28 03:49:00',NULL),(16,16,'online',NULL,2360.00,'INR','razorpay','pay_TJFjBxIReBCTYj','order_TJFirN2oxfHUeN','9c49648c2a7a40fb2a5caf3f78a0c59d527ebf6a170f593af1d496537ff0de20',NULL,'completed','2026-07-29 02:59:48','2026-07-29 02:59:48','2026-07-29 02:59:03','2026-07-29 02:59:48',NULL),(17,17,'online',NULL,1770.00,'INR','razorpay','pay_TJJeyYckWP8mTq','order_TJJeiYxG2UICIW','05a4f1a565f44afe120126f1e11b9cf376ddcd8938c6deace009903a5ca975ea',NULL,'completed','2026-07-29 06:50:30','2026-07-29 06:50:30','2026-07-29 06:49:54','2026-07-29 06:50:30',NULL),(18,18,'online',NULL,1770.00,'INR','razorpay','pay_TJJobdJMMzp8rf','order_TJJoTQRiIHSm5u','f7cb0c6ea7738fab5bc48f0841b3efe5be1a1de3fd8bf03c8f862f2e4ec8b629',NULL,'pending',NULL,NULL,'2026-07-29 06:59:09','2026-07-29 06:59:39',NULL),(19,19,'online',NULL,1770.00,'INR','razorpay','pay_TJJuuJLgmumX1Z','order_TJJulWCcTI0HLQ','c8f6f8b3b68b50b40815f86131853931c2d4e8f90a66c7f9d566c3b2ddd1fea9',NULL,'completed','2026-07-29 07:06:32','2026-07-29 07:06:32','2026-07-29 07:05:06','2026-07-29 07:06:32',NULL),(20,20,'online',NULL,1770.00,'INR','razorpay','pay_TJJygjYmVHzUCF','order_TJJyZ4OOWkbiD9','314b86885150d3386c5299727f7747c67a8062362e35ee5b3993a1d28e9610ed',NULL,'completed','2026-07-29 07:09:13','2026-07-29 07:09:13','2026-07-29 07:08:42','2026-07-29 07:09:13',NULL),(21,21,'online',NULL,2360.00,'INR','razorpay','pay_TJKR0HMEMiYbIg','order_TJKQqMzXvNWSP0','c530964197681c05b383de859032749092926c79cf8d293284589acfc443b6d7',NULL,'completed','2026-07-29 07:35:57','2026-07-29 07:35:57','2026-07-29 07:35:28','2026-07-29 07:35:57',NULL),(22,22,'online',NULL,1770.00,'INR','razorpay','pay_TJKb49rg4pMjWP','order_TJKavYC76fRJbx','579aa62b5a3bb1ce27f0c2b0214b1d5d5a6ff08071c788a4a72e5793abec16a1',NULL,'completed','2026-07-29 07:45:26','2026-07-29 07:45:26','2026-07-29 07:45:01','2026-07-29 07:45:26',NULL),(23,23,'online',NULL,2360.00,'INR','razorpay',NULL,'order_TJKemEdgr0Qzne',NULL,NULL,'pending',NULL,NULL,'2026-07-29 07:48:40','2026-07-29 07:48:41',NULL),(24,24,'online',NULL,2360.00,'INR','razorpay',NULL,'order_TJKfrYhGZ9SJPl',NULL,NULL,'pending',NULL,NULL,'2026-07-29 07:49:42','2026-07-29 07:49:42',NULL),(25,25,'online',NULL,2360.00,'INR','razorpay','pay_TJKg4DtjDysHNb','order_TJKfwyewDnwG6B','d3cfc585cd0e29205377593dd6f9dfddf26a3c88f23ff1bdbdf3700f43915525',NULL,'completed','2026-07-29 07:50:11','2026-07-29 07:50:11','2026-07-29 07:49:47','2026-07-29 07:50:11',NULL),(26,26,'online',NULL,1770.00,'INR',NULL,NULL,NULL,NULL,NULL,'pending',NULL,NULL,'2026-07-29 09:08:57','2026-07-29 09:08:57',NULL),(27,27,'online',NULL,1770.00,'INR','razorpay','pay_TJMA2iJF7qe792','order_TJM9q3thsNgR97','f5b182c402d45c4dbedb7dfab596cde456034d789fd37c0e529f3707b70f58b3',NULL,'completed','2026-07-29 09:17:14','2026-07-29 09:17:14','2026-07-29 09:16:46','2026-07-29 09:17:14',NULL),(28,28,'online',NULL,1770.00,'INR','razorpay',NULL,'order_TJpYYpxiPdKqEI',NULL,NULL,'pending',NULL,NULL,'2026-07-30 14:02:16','2026-07-30 14:02:18',NULL),(29,29,'online',NULL,1770.00,'INR','razorpay','pay_TJpc0XVG2r065Y','order_TJpbnxqAPjO9px','1177b12a27a4b80d2f58ad0388495408c0f2fa4651d2fcbc001245539b5ab42c',NULL,'completed','2026-07-30 14:05:59','2026-07-30 14:05:59','2026-07-30 14:05:21','2026-07-30 14:05:59',NULL),(30,30,'online',NULL,1770.00,'INR','razorpay','pay_TJpj3ofQyiJPep','order_TJpit9i7fantO4','7ad691b8e2c43baa5b51141edc63afd9e508534beca407b6f0b295c450e26fea',NULL,'completed','2026-07-30 14:12:30','2026-07-30 14:12:30','2026-07-30 14:12:02','2026-07-30 14:12:30',NULL),(31,31,'online',NULL,1770.00,'INR','razorpay',NULL,'order_TKCAQRuJsQ7w4W',NULL,NULL,'pending',NULL,NULL,'2026-07-31 12:09:22','2026-07-31 12:09:24',NULL);
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) unsigned NOT NULL,
  `name` text NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
INSERT INTO `personal_access_tokens` VALUES (1,'App\\Models\\User',1,'spa','e353c79c591b50f0ef9464940d9ab960496de8cd1c6f09c5308cd141970c5980','[\"*\"]',NULL,NULL,'2026-10-31 02:59:45','2026-10-31 02:59:45'),(4,'App\\Models\\User',16,'spa','0e4c907abe9ad169ddbba137d819fcff9f9f4b076d17e1d774692ebef00c7db6','[\"*\"]','2026-11-09 03:36:56',NULL,'2026-11-04 05:41:11','2026-11-09 03:36:56'),(5,'App\\Models\\User',19,'codex-mcp','5d924e2e5e9709e64ee5ee97f750b0ca4c2f72f29eeaa1b1c1d1476f8eb57bbe','[\"*\"]','2026-11-09 00:47:52',NULL,'2026-11-09 00:15:16','2026-11-09 00:47:52'),(6,'App\\Models\\User',20,'mcp:mcp_qvcqs2nwPgBxpbc6jTfnLVZr461oNIx5','91fc476ebc18a4aafef21f11babe485b4996be45816ddc31183441882eb3ac32','[\"mcp\"]','2026-11-09 00:28:19','2026-12-09 00:28:18','2026-11-09 00:28:18','2026-11-09 00:28:19');
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `plan_upgrade_requests`
--

DROP TABLE IF EXISTS `plan_upgrade_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `plan_upgrade_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `current_plan_id` bigint(20) unsigned NOT NULL,
  `requested_plan_id` bigint(20) unsigned NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `plan_upgrade_requests_user_id_foreign` (`user_id`),
  KEY `plan_upgrade_requests_current_plan_id_foreign` (`current_plan_id`),
  KEY `plan_upgrade_requests_requested_plan_id_foreign` (`requested_plan_id`),
  KEY `plan_upgrade_requests_approved_by_foreign` (`approved_by`),
  CONSTRAINT `plan_upgrade_requests_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`),
  CONSTRAINT `plan_upgrade_requests_current_plan_id_foreign` FOREIGN KEY (`current_plan_id`) REFERENCES `plans` (`id`),
  CONSTRAINT `plan_upgrade_requests_requested_plan_id_foreign` FOREIGN KEY (`requested_plan_id`) REFERENCES `plans` (`id`),
  CONSTRAINT `plan_upgrade_requests_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `plan_upgrade_requests`
--

LOCK TABLES `plan_upgrade_requests` WRITE;
/*!40000 ALTER TABLE `plan_upgrade_requests` DISABLE KEYS */;
INSERT INTO `plan_upgrade_requests` VALUES (1,3,1,2,'approved',NULL,'2026-01-29 14:52:35',NULL,1,'2026-01-29 14:52:22','2026-01-29 14:52:35'),(2,3,1,2,'approved',NULL,'2026-01-29 14:59:11',NULL,1,'2026-01-29 14:58:57','2026-01-29 14:59:11'),(3,3,1,2,'approved',NULL,'2026-01-29 15:19:21',NULL,1,'2026-01-29 15:15:33','2026-01-29 15:19:21'),(4,2,1,2,'approved','Plan expired after 30 days and reverted to Basic','2026-01-30 08:19:44','2026-03-01 08:19:44',1,'2026-01-30 08:19:37','2026-03-10 10:19:33'),(5,2,1,2,'approved','Plan expired after 30 days and reverted to Basic','2026-05-08 08:11:08','2026-06-07 08:11:08',1,'2026-04-09 11:45:49','2026-06-08 18:10:54'),(6,6,1,2,'rejected','Payment not received',NULL,NULL,1,'2026-04-20 09:56:49','2026-05-08 08:11:23'),(7,7,1,2,'approved',NULL,'2026-05-08 08:01:22','2026-06-07 08:01:22',1,'2026-05-08 07:59:46','2026-05-08 08:01:22'),(8,2,1,2,'approved',NULL,'2026-06-08 18:11:20','2026-07-08 18:11:20',1,'2026-06-08 18:11:05','2026-06-08 18:11:20');
/*!40000 ALTER TABLE `plan_upgrade_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `plans`
--

DROP TABLE IF EXISTS `plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `plans` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `audience` varchar(255) NOT NULL DEFAULT 'realtor',
  `max_campaigns` int(11) DEFAULT NULL,
  `max_leads_per_campaign` int(11) DEFAULT NULL,
  `max_managers` int(10) unsigned DEFAULT NULL,
  `max_employees` int(10) unsigned DEFAULT NULL,
  `max_teams` int(10) unsigned DEFAULT NULL,
  `max_monthly_leads` int(10) unsigned DEFAULT NULL,
  `max_inventory_units` int(10) unsigned DEFAULT NULL,
  `advanced_reports` tinyint(1) NOT NULL DEFAULT 0,
  `meta_integrations` tinyint(1) NOT NULL DEFAULT 0,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `price` decimal(10,2) DEFAULT NULL,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  `header_color` varchar(7) NOT NULL DEFAULT '#6b7280',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `plans_name_unique` (`name`),
  UNIQUE KEY `plans_key_unique` (`key`),
  KEY `plans_audience_index` (`audience`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `plans`
--

LOCK TABLES `plans` WRITE;
/*!40000 ALTER TABLE `plans` DISABLE KEYS */;
INSERT INTO `plans` VALUES (1,'basic','Basic','realtor',5,100,NULL,NULL,NULL,NULL,NULL,0,0,1,1,0.00,1,'#475569','2026-01-19 03:44:14','2026-01-19 03:44:14'),(2,'pro','Pro','realtor',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,0,1,1500.00,2,'#9333ea','2026-01-19 03:44:14','2026-01-29 15:14:57'),(3,'preminum','Preminum','realtor',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,0,1,2000.00,3,'#6b7280','2026-07-28 04:24:07','2026-07-28 04:24:07');
/*!40000 ALTER TABLE `plans` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `platform_audit_logs`
--

DROP TABLE IF EXISTS `platform_audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `platform_audit_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `admin_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `subject_type` varchar(255) DEFAULT NULL,
  `subject_id` bigint(20) unsigned DEFAULT NULL,
  `description` text DEFAULT NULL,
  `meta` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `platform_audit_logs_admin_id_foreign` (`admin_id`),
  KEY `platform_audit_logs_subject_type_subject_id_index` (`subject_type`,`subject_id`),
  KEY `platform_audit_logs_created_at_index` (`created_at`),
  KEY `platform_audit_logs_action_index` (`action`),
  CONSTRAINT `platform_audit_logs_admin_id_foreign` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `platform_audit_logs`
--

LOCK TABLES `platform_audit_logs` WRITE;
/*!40000 ALTER TABLE `platform_audit_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `platform_audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `realtor_site_visits`
--

DROP TABLE IF EXISTS `realtor_site_visits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `realtor_site_visits` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `lead_id` bigint(20) unsigned DEFAULT NULL,
  `inventory_project_id` bigint(20) unsigned DEFAULT NULL,
  `inventory_unit_id` bigint(20) unsigned DEFAULT NULL,
  `scheduled_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` varchar(255) NOT NULL DEFAULT 'scheduled',
  `customer_attended` tinyint(1) DEFAULT NULL,
  `outcome` text DEFAULT NULL,
  `feedback` text DEFAULT NULL,
  `next_action` text DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `cancellation_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `realtor_site_visits_lead_id_foreign` (`lead_id`),
  KEY `realtor_site_visits_inventory_project_id_foreign` (`inventory_project_id`),
  KEY `realtor_site_visits_inventory_unit_id_foreign` (`inventory_unit_id`),
  KEY `realtor_site_visits_user_id_scheduled_at_index` (`user_id`,`scheduled_at`),
  KEY `realtor_site_visits_user_id_status_index` (`user_id`,`status`),
  KEY `realtor_site_visits_status_index` (`status`),
  CONSTRAINT `realtor_site_visits_inventory_project_id_foreign` FOREIGN KEY (`inventory_project_id`) REFERENCES `inventory_projects` (`id`) ON DELETE SET NULL,
  CONSTRAINT `realtor_site_visits_inventory_unit_id_foreign` FOREIGN KEY (`inventory_unit_id`) REFERENCES `inventory_units` (`id`) ON DELETE SET NULL,
  CONSTRAINT `realtor_site_visits_lead_id_foreign` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE SET NULL,
  CONSTRAINT `realtor_site_visits_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `realtor_site_visits`
--

LOCK TABLES `realtor_site_visits` WRITE;
/*!40000 ALTER TABLE `realtor_site_visits` DISABLE KEYS */;
/*!40000 ALTER TABLE `realtor_site_visits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `realtor_tasks`
--

DROP TABLE IF EXISTS `realtor_tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `realtor_tasks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `lead_id` bigint(20) unsigned DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `priority` varchar(255) NOT NULL DEFAULT 'medium',
  `due_at` timestamp NULL DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `completion_notes` text DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `realtor_tasks_lead_id_foreign` (`lead_id`),
  KEY `realtor_tasks_user_id_status_index` (`user_id`,`status`),
  KEY `realtor_tasks_user_id_due_at_index` (`user_id`,`due_at`),
  KEY `realtor_tasks_status_index` (`status`),
  CONSTRAINT `realtor_tasks_lead_id_foreign` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE SET NULL,
  CONSTRAINT `realtor_tasks_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `realtor_tasks`
--

LOCK TABLES `realtor_tasks` WRITE;
/*!40000 ALTER TABLE `realtor_tasks` DISABLE KEYS */;
/*!40000 ALTER TABLE `realtor_tasks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sales_targets`
--

DROP TABLE IF EXISTS `sales_targets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sales_targets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `enterprise_id` bigint(20) unsigned NOT NULL,
  `team_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `period_month` date NOT NULL,
  `metric` varchar(255) NOT NULL,
  `target_value` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sales_targets_team_id_foreign` (`team_id`),
  KEY `sales_targets_user_id_foreign` (`user_id`),
  KEY `sales_targets_created_by_foreign` (`created_by`),
  KEY `sales_targets_enterprise_id_period_month_metric_index` (`enterprise_id`,`period_month`,`metric`),
  KEY `sales_targets_enterprise_team_month_index` (`enterprise_id`,`team_id`,`period_month`),
  KEY `sales_targets_enterprise_user_month_index` (`enterprise_id`,`user_id`,`period_month`),
  CONSTRAINT `sales_targets_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sales_targets_enterprise_id_foreign` FOREIGN KEY (`enterprise_id`) REFERENCES `enterprises` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sales_targets_team_id_foreign` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sales_targets_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales_targets`
--

LOCK TABLES `sales_targets` WRITE;
/*!40000 ALTER TABLE `sales_targets` DISABLE KEYS */;
/*!40000 ALTER TABLE `sales_targets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `settings` (
  `key` varchar(255) NOT NULL,
  `value` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `site_visits`
--

DROP TABLE IF EXISTS `site_visits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `site_visits` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `enterprise_id` bigint(20) unsigned NOT NULL,
  `team_id` bigint(20) unsigned DEFAULT NULL,
  `lead_id` bigint(20) unsigned DEFAULT NULL,
  `inventory_project_id` bigint(20) unsigned DEFAULT NULL,
  `inventory_unit_id` bigint(20) unsigned DEFAULT NULL,
  `assigned_to` bigint(20) unsigned DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `scheduled_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` varchar(255) NOT NULL DEFAULT 'scheduled',
  `customer_attended` tinyint(1) DEFAULT NULL,
  `outcome` text DEFAULT NULL,
  `feedback` text DEFAULT NULL,
  `next_action` text DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `cancellation_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `site_visits_team_id_foreign` (`team_id`),
  KEY `site_visits_lead_id_foreign` (`lead_id`),
  KEY `site_visits_inventory_project_id_foreign` (`inventory_project_id`),
  KEY `site_visits_inventory_unit_id_foreign` (`inventory_unit_id`),
  KEY `site_visits_assigned_to_foreign` (`assigned_to`),
  KEY `site_visits_created_by_foreign` (`created_by`),
  KEY `site_visits_enterprise_id_scheduled_at_index` (`enterprise_id`,`scheduled_at`),
  KEY `site_visits_enterprise_id_assigned_to_status_index` (`enterprise_id`,`assigned_to`,`status`),
  KEY `site_visits_status_index` (`status`),
  CONSTRAINT `site_visits_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `site_visits_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `site_visits_enterprise_id_foreign` FOREIGN KEY (`enterprise_id`) REFERENCES `enterprises` (`id`) ON DELETE CASCADE,
  CONSTRAINT `site_visits_inventory_project_id_foreign` FOREIGN KEY (`inventory_project_id`) REFERENCES `inventory_projects` (`id`) ON DELETE SET NULL,
  CONSTRAINT `site_visits_inventory_unit_id_foreign` FOREIGN KEY (`inventory_unit_id`) REFERENCES `inventory_units` (`id`) ON DELETE SET NULL,
  CONSTRAINT `site_visits_lead_id_foreign` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE SET NULL,
  CONSTRAINT `site_visits_team_id_foreign` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `site_visits`
--

LOCK TABLES `site_visits` WRITE;
/*!40000 ALTER TABLE `site_visits` DISABLE KEYS */;
/*!40000 ALTER TABLE `site_visits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subscriptions`
--

DROP TABLE IF EXISTS `subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `subscriptions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `plan_name` varchar(255) NOT NULL,
  `duration_months` int(11) NOT NULL,
  `start_date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `expiry_date` timestamp NULL DEFAULT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `gst` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `activated_at` timestamp NULL DEFAULT NULL,
  `internal_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `subscriptions_user_id_foreign` (`user_id`),
  KEY `subscriptions_status_index` (`status`),
  CONSTRAINT `subscriptions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subscriptions`
--

LOCK TABLES `subscriptions` WRITE;
/*!40000 ALTER TABLE `subscriptions` DISABLE KEYS */;
INSERT INTO `subscriptions` VALUES (3,17,'Pro',12,'2026-07-27 10:06:34',NULL,1500.00,0.00,0.00,'pending',NULL,NULL,'2026-07-27 10:06:34','2026-07-27 10:06:34'),(4,17,'Pro',12,'2026-07-27 10:06:38',NULL,1500.00,0.00,0.00,'pending',NULL,NULL,'2026-07-27 10:06:38','2026-07-27 10:06:38'),(5,17,'Pro',12,'2026-07-27 10:08:14',NULL,1500.00,0.00,0.00,'pending',NULL,NULL,'2026-07-27 10:08:14','2026-07-27 10:08:14'),(6,17,'Pro',12,'2026-07-27 10:09:32',NULL,1500.00,0.00,0.00,'pending',NULL,NULL,'2026-07-27 10:09:32','2026-07-27 10:09:32'),(7,17,'Pro',12,'2026-07-27 10:10:39',NULL,1500.00,0.00,0.00,'pending',NULL,NULL,'2026-07-27 10:10:39','2026-07-27 10:10:39'),(8,17,'Pro',12,'2026-07-27 10:19:38',NULL,1500.00,0.00,0.00,'pending',NULL,NULL,'2026-07-27 10:19:38','2026-07-27 10:19:38'),(9,17,'Pro',12,'2026-07-27 10:21:09',NULL,1500.00,0.00,0.00,'pending',NULL,NULL,'2026-07-27 10:21:09','2026-07-27 10:21:09'),(10,17,'Pro',12,'2026-07-27 10:21:15',NULL,1500.00,0.00,0.00,'pending',NULL,NULL,'2026-07-27 10:21:15','2026-07-27 10:21:15'),(11,17,'Pro',12,'2026-07-27 10:25:28',NULL,1500.00,0.00,0.00,'pending',NULL,NULL,'2026-07-27 10:25:28','2026-07-27 10:25:28'),(12,17,'Pro',12,'2026-07-27 10:27:03',NULL,1500.00,0.00,0.00,'pending',NULL,NULL,'2026-07-27 10:27:03','2026-07-27 10:27:03'),(13,17,'Pro',12,'2026-07-27 10:28:35',NULL,1500.00,0.00,0.00,'pending',NULL,NULL,'2026-07-27 10:28:35','2026-07-27 10:28:35'),(14,17,'Pro',12,'2026-07-27 10:30:32','2027-07-27 10:30:32',1500.00,0.00,0.00,'active',NULL,NULL,'2026-07-27 10:30:09','2026-07-27 10:30:32'),(15,18,'Pro',12,'2026-07-28 03:49:00','2027-07-28 03:49:00',1500.00,0.00,0.00,'active',NULL,NULL,'2026-07-28 03:48:28','2026-07-28 03:49:00'),(16,18,'Preminum',12,'2027-07-28 03:49:00','2028-07-28 03:49:00',2000.00,0.00,0.00,'active',NULL,NULL,'2026-07-29 02:59:03','2026-07-29 02:59:48'),(17,18,'Pro',12,'2028-07-28 03:49:00','2029-07-28 03:49:00',1500.00,0.00,0.00,'active',NULL,NULL,'2026-07-29 06:49:54','2026-07-29 06:50:30'),(18,18,'Pro',12,'2026-07-29 06:59:09',NULL,1500.00,0.00,0.00,'pending',NULL,NULL,'2026-07-29 06:59:09','2026-07-29 06:59:09'),(19,18,'Pro',12,'2029-07-28 03:49:00','2030-07-28 03:49:00',1500.00,0.00,0.00,'active',NULL,NULL,'2026-07-29 07:05:06','2026-07-29 07:06:32'),(20,19,'Pro',12,'2026-07-29 07:09:13','2027-07-29 07:09:13',1500.00,0.00,0.00,'active',NULL,NULL,'2026-07-29 07:08:42','2026-07-29 07:09:13'),(21,19,'Preminum',12,'2027-07-29 07:09:13','2028-07-29 07:09:13',2000.00,0.00,0.00,'active',NULL,NULL,'2026-07-29 07:35:28','2026-07-29 07:35:57'),(22,19,'Pro',12,'2028-07-29 07:09:13','2029-07-29 07:09:13',1500.00,0.00,0.00,'active',NULL,NULL,'2026-07-29 07:45:01','2026-07-29 07:45:26'),(23,19,'Preminum',12,'2026-07-29 07:48:40',NULL,2000.00,0.00,0.00,'pending',NULL,NULL,'2026-07-29 07:48:40','2026-07-29 07:48:40'),(24,19,'Preminum',12,'2026-07-29 07:49:42',NULL,2000.00,0.00,0.00,'pending',NULL,NULL,'2026-07-29 07:49:42','2026-07-29 07:49:42'),(25,19,'Preminum',12,'2029-07-29 07:09:13','2030-07-29 07:09:13',2000.00,0.00,0.00,'active',NULL,NULL,'2026-07-29 07:49:47','2026-07-29 07:50:11'),(26,19,'Pro',12,'2026-07-30 15:17:18',NULL,1500.00,0.00,0.00,'active',NULL,NULL,'2026-07-29 09:08:56','2026-07-30 09:47:18'),(27,19,'Pro',12,'2030-07-29 07:09:13','2031-07-29 07:09:13',1500.00,0.00,0.00,'active',NULL,NULL,'2026-07-29 09:16:46','2026-07-29 09:17:14'),(28,19,'Pro',12,'2026-07-30 14:02:16',NULL,1500.00,0.00,0.00,'pending',NULL,NULL,'2026-07-30 14:02:16','2026-07-30 14:02:16'),(29,19,'Pro',12,'2031-07-29 07:09:13','2032-07-29 07:09:13',1500.00,0.00,0.00,'active',NULL,NULL,'2026-07-30 14:05:21','2026-07-30 14:05:59'),(30,19,'Pro',12,'2032-07-29 07:09:13','2033-07-29 07:09:13',1500.00,0.00,0.00,'active',NULL,NULL,'2026-07-30 14:12:02','2026-07-30 14:12:30'),(31,19,'Pro',12,'2026-07-31 12:09:22',NULL,1500.00,0.00,0.00,'pending',NULL,NULL,'2026-07-31 12:09:22','2026-07-31 12:09:22');
/*!40000 ALTER TABLE `subscriptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tasks`
--

DROP TABLE IF EXISTS `tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tasks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `enterprise_id` bigint(20) unsigned NOT NULL,
  `team_id` bigint(20) unsigned DEFAULT NULL,
  `lead_id` bigint(20) unsigned DEFAULT NULL,
  `assigned_to` bigint(20) unsigned DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `priority` varchar(255) NOT NULL DEFAULT 'medium',
  `due_at` timestamp NULL DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `completion_notes` text DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tasks_team_id_foreign` (`team_id`),
  KEY `tasks_lead_id_foreign` (`lead_id`),
  KEY `tasks_assigned_to_foreign` (`assigned_to`),
  KEY `tasks_created_by_foreign` (`created_by`),
  KEY `tasks_enterprise_id_assigned_to_status_index` (`enterprise_id`,`assigned_to`,`status`),
  KEY `tasks_enterprise_id_due_at_index` (`enterprise_id`,`due_at`),
  KEY `tasks_status_index` (`status`),
  CONSTRAINT `tasks_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tasks_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tasks_enterprise_id_foreign` FOREIGN KEY (`enterprise_id`) REFERENCES `enterprises` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tasks_lead_id_foreign` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tasks_team_id_foreign` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tasks`
--

LOCK TABLES `tasks` WRITE;
/*!40000 ALTER TABLE `tasks` DISABLE KEYS */;
/*!40000 ALTER TABLE `tasks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `team_memberships`
--

DROP TABLE IF EXISTS `team_memberships`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `team_memberships` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `enterprise_id` bigint(20) unsigned NOT NULL,
  `team_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 1,
  `last_assigned_at` timestamp NULL DEFAULT NULL,
  `joined_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `team_memberships_team_id_user_id_unique` (`team_id`,`user_id`),
  UNIQUE KEY `team_memberships_enterprise_id_user_id_unique` (`enterprise_id`,`user_id`),
  KEY `team_memberships_user_id_foreign` (`user_id`),
  KEY `team_memberships_enterprise_id_team_id_index` (`enterprise_id`,`team_id`),
  CONSTRAINT `team_memberships_enterprise_id_foreign` FOREIGN KEY (`enterprise_id`) REFERENCES `enterprises` (`id`) ON DELETE CASCADE,
  CONSTRAINT `team_memberships_team_id_foreign` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE,
  CONSTRAINT `team_memberships_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `team_memberships`
--

LOCK TABLES `team_memberships` WRITE;
/*!40000 ALTER TABLE `team_memberships` DISABLE KEYS */;
/*!40000 ALTER TABLE `team_memberships` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `teams`
--

DROP TABLE IF EXISTS `teams`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `teams` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `enterprise_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `manager_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `lead_distribution_mode` varchar(255) NOT NULL DEFAULT 'manual',
  `settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`settings`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `teams_enterprise_id_name_unique` (`enterprise_id`,`name`),
  KEY `teams_manager_id_foreign` (`manager_id`),
  KEY `teams_status_index` (`status`),
  CONSTRAINT `teams_enterprise_id_foreign` FOREIGN KEY (`enterprise_id`) REFERENCES `enterprises` (`id`) ON DELETE CASCADE,
  CONSTRAINT `teams_manager_id_foreign` FOREIGN KEY (`manager_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `teams`
--

LOCK TABLES `teams` WRITE;
/*!40000 ALTER TABLE `teams` DISABLE KEYS */;
/*!40000 ALTER TABLE `teams` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_call_profiles`
--

DROP TABLE IF EXISTS `user_call_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_call_profiles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `agent_number` varchar(32) DEFAULT NULL,
  `auto_record` tinyint(1) NOT NULL DEFAULT 1,
  `consent_notice` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_call_profiles_user_id_unique` (`user_id`),
  CONSTRAINT `user_call_profiles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_call_profiles`
--

LOCK TABLES `user_call_profiles` WRITE;
/*!40000 ALTER TABLE `user_call_profiles` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_call_profiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `google_id` varchar(255) DEFAULT NULL,
  `google_avatar` text DEFAULT NULL,
  `facebook_id` varchar(255) DEFAULT NULL,
  `facebook_avatar` text DEFAULT NULL,
  `facebook_page_id` varchar(255) DEFAULT NULL,
  `facebook_page_name` varchar(255) DEFAULT NULL,
  `facebook_page_access_token` text DEFAULT NULL,
  `facebook_token_expired` tinyint(1) NOT NULL DEFAULT 0,
  `phone` varchar(255) DEFAULT NULL,
  `company` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `email_notifications` tinyint(1) NOT NULL DEFAULT 1,
  `marketing_emails` tinyint(1) NOT NULL DEFAULT 0,
  `public_profile` tinyint(1) NOT NULL DEFAULT 0,
  `theme` varchar(255) NOT NULL DEFAULT 'light',
  `language` varchar(255) NOT NULL DEFAULT 'en',
  `timezone` varchar(255) NOT NULL DEFAULT 'Asia/Kolkata',
  `role` enum('admin','realtor','enterprise') NOT NULL DEFAULT 'realtor',
  `plan_id` bigint(20) unsigned DEFAULT NULL,
  `subscription_suspended_at` timestamp NULL DEFAULT NULL,
  `suspended_plan_id` bigint(20) unsigned DEFAULT NULL,
  `subscription_suspend_reason` varchar(500) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_plan_id_foreign` (`plan_id`),
  KEY `users_google_id_index` (`google_id`),
  KEY `users_facebook_id_index` (`facebook_id`),
  KEY `users_suspended_plan_id_foreign` (`suspended_plan_id`),
  CONSTRAINT `users_plan_id_foreign` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`) ON DELETE SET NULL,
  CONSTRAINT `users_suspended_plan_id_foreign` FOREIGN KEY (`suspended_plan_id`) REFERENCES `plans` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Admin','admin@rumu.in',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,1,0,0,'auto','en','Asia/Kolkata','admin',2,NULL,NULL,NULL,NULL,'$2y$12$9kRLswJFqpKrIO8G93Ka9eHu2tWysyAUpiRZAhtAvW9nuex2G5ZfS',NULL,'2026-01-19 03:44:17','2026-06-16 23:51:09'),(2,'Hitesh Manoj Bhagwat','hiteshbhagwat97@gmail.com','104080773963437801273','https://lh3.googleusercontent.com/a/ACg8ocKWWYtErmIHagz47vQgqe3GJDFGUsNcGsT3gnwOt3MLryEfughPUw=s96-c',NULL,NULL,NULL,NULL,NULL,0,'7038214873','Iconicpages','I m content Marketing Agency','avatars/Ya2sCzgBUUN3qlrdCM5111VtWkvut4AGHRhyjiJA.png',1,0,0,'auto','en','Asia/Kolkata','realtor',2,NULL,NULL,NULL,'2026-06-05 11:22:26','$2y$12$Iu/p.Beu5mryNzZarIhWF.rotockqGGy0T7N3jrsXFjGcnITjaMDm','0l1ocKnaYZWygqav3DCcW5z6n32mLnBnpDcmJpQzKVYQTxzrpQRk3Gys0bqr','2026-01-19 14:53:54','2026-06-17 16:12:34'),(3,'Tanvi Mirase','tanvi.mirase@iconicpages.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'+918087744220','Iconicpages Pvt Ltd',NULL,'avatars/ogBoKCf1Qhiy4swyvzFoTYryfN39kERMijbvCrxd.jpg',1,1,0,'auto','en','Asia/Kolkata','realtor',2,NULL,NULL,NULL,NULL,'$2y$12$dWwjJ6rlWFRipd6MB7ttu.7Q1CQezhtt.jtlFeG10m5TPrR045xla',NULL,'2026-01-29 13:21:48','2026-01-29 15:45:34'),(4,'Rohan poredi','poredirohan@gmail.com','114309882698060487221','https://lh3.googleusercontent.com/a/ACg8ocL88xJfkU6B5BhIyUkZRTpzOLETaVFEMhl0sNtmoQolpJ9hvdTb6g=s96-c',NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,1,0,0,'light','en','Asia/Kolkata','realtor',1,NULL,NULL,NULL,'2026-06-11 11:55:19','$2y$12$zaTZ6.rSXod26GDaIj0TJuijikiQLHzGa0/86odB/RazFuCdTjfzW','eN86mixbgiA4jRNSzcDP1sLbudRcfoprs0npqY63jtQ9oVoK6QqVt0EfAji4','2025-07-18 11:31:18','2026-06-11 11:55:19'),(5,'Yogesh Thakre','yogeshdt086@gmail.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,1,0,0,'light','en','Asia/Kolkata','realtor',1,NULL,NULL,NULL,NULL,'$2y$12$bLuLDFt68Ku1zQOFKTDFSeF3GB6yZ7QP5E5wjmO7pRDJQ4/p/Ga0G',NULL,'2026-02-06 17:34:18','2026-02-06 17:34:18'),(6,'omka naik','onkar.naik@iconicpages.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,'avatars/lnTJzqNU0Gpz7GGJvFDtVmc35AXaTFfJgI53zBhq.png',1,0,0,'dark','en','Asia/Kolkata','realtor',1,NULL,NULL,NULL,NULL,'$2y$12$xdP9p4KTW0mLiD7ITE39gulsYjqle6Qn0lgKuutD8rkVk.MHmkgB.',NULL,'2026-04-20 09:56:13','2026-06-02 08:43:20'),(7,'Amit Rajput','rocks3183@gmail.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,1,0,0,'light','en','Asia/Kolkata','realtor',2,NULL,NULL,NULL,NULL,'$2y$12$QHaTzO68K8110w37.B6hYewEYah0RqRykb36LXmEt3J71ccNGgvRi',NULL,'2026-05-08 07:57:26','2026-05-08 08:01:22'),(8,'Tanvi Mirase','tanvimirase2000@gmail.com','114488395122353394491','https://lh3.googleusercontent.com/a/ACg8ocKFHgKofc1v9SwaWGs43c1UYPXhNtkCniuK3QYBgsEYqJWZESuRKg=s96-c',NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,1,0,0,'light','en','Asia/Kolkata','realtor',1,NULL,NULL,NULL,'2026-06-06 19:26:15','$2y$12$KF8mLholwdygrdEXbto8O.oyhsorHuFEZ.6yXVJt9.8nBP2SMuK9u','oLQOG0bW2qUDK2TMUllVpM7Awpdik8YGr0OBnoPCNWCq3ItoRrFZ2l4AoLCM','2026-06-02 07:57:58','2026-06-06 19:26:15'),(9,'Rumu AI CRM','rumuaicrm@gmail.com','104672452547015497525','https://lh3.googleusercontent.com/a/ACg8ocIaIbUORndgFrb51jvTNhU583zsF1UzdS-NmJDEZWrIMOVvWXQ=s96-c',NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,1,0,0,'light','en','Asia/Kolkata','realtor',1,NULL,NULL,NULL,'2026-06-05 11:32:03','$2y$12$Bl6qMYpADGboBK0.Zc02Vukhw44jTVpXFDYxLSnzsgrZdI9lCjhom','8S7c9eQAfiwwGqMMX1Hl00KFvmkWDA00GazNA572ktTEBqWMPWdygzwgS0em','2026-06-05 10:59:46','2026-06-05 11:32:03'),(11,'Omi naik','omiiiiinike@gmail.com','104639572215049138895','https://lh3.googleusercontent.com/a/ACg8ocIKOGpl_8Mh-wMUXt4S6lEvYvYiUFd5SxQ2cMuoPPHkvjou_gxB=s96-c',NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,1,0,0,'light','en','Asia/Kolkata','realtor',1,NULL,NULL,NULL,NULL,'$2y$12$XWkXcghwVbxVzMCCwlxdiuE5dZhZEKS2W2dSeWAUlXSt.3OOyqKZi','LFr16C9RHotKj1dUM06HabYpEJVfzgRRzgn4R02vVRh7S6b0QAPPDc9cPO7q','2026-06-05 20:32:20','2026-06-05 20:32:20'),(14,'Atharva Sawant','assawnat100@gmail.com',NULL,NULL,'122243804882268468','https://platform-lookaside.fbsbx.com/platform/profilepic/?asid=122243804882268468&width=1920&ext=1786103582&hash=AftWmYc7N_nhEvpjTuiy02UR',NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,1,0,0,'light','en','Asia/Kolkata','realtor',1,NULL,NULL,NULL,NULL,'$2y$12$ULYYBlGAJAT.Wf2PPwRsa.aTsUmVrGJEDCCdg3PuPEQcxqB2ma.hG','nDyY2B0CaL6iI7pzNbCUutGOIWGw7iuD7kaKSYr29iKCR7egUAYaXdzhODhQ','2026-07-08 06:23:02','2026-07-08 06:23:02'),(15,'Hitesh Bhagwat','teamiconicpages@gmail.com',NULL,NULL,'878707245306699','https://platform-lookaside.fbsbx.com/platform/profilepic/?asid=876457162198374&width=1920&ext=1786277828&hash=AfvZ1C5IQAlN57Sv6QHvNNMr','110913518764214','Iconicpages','eyJpdiI6IjVKVGt3TDdMMk5oZjUyTXZFSlduVkE9PSIsInZhbHVlIjoiZmJvKy9NL2J1WC9NRXcwWiswQ255dmhuN1BOMW1NaWRyTHVSdlR0eTFocHUreGRnb2MxVzF2OE04eWxqb21PdWtad2V1MlYzWWJNMFNJUXpPaW0vZUpZS2ZFQUg3dEhOSHNUOEhyNHFxYVoxMHlEbHhsRjRJQzlIUzhuTm9RTk9kV0Rtc1VTSjN4YTM1b3U1dC83cVRQSEVkUU1HUDQwYS9HT1FaSEZEblkrakUwdHVKd0dWOVZTWGVBWXRyaWJqN2VQWFdBYVE3V2Jzb1g5Sk5DWUtnN3ZaQ3VTNWV5cDFGNXJCa3EyMGtXMFJtSUUrb0lDaWRXdUpvSzU5bUVJSVFFZWg3K0lELzRxRkFWWTYvUkdyV2ZMR1pBL2pabEtPaHYzVFNSWVc5SE09IiwibWFjIjoiMGFjODcxMDIxZDY2OWNjZmRlZjFkMjhiNjkxMzM1NTVlMTQ0ZjFlNzVlNDcyM2I4NWM2MDYyNGFhNjYwOGQ3ZiIsInRhZyI6IiJ9',0,NULL,NULL,NULL,NULL,1,0,0,'light','en','Asia/Kolkata','realtor',1,NULL,NULL,NULL,NULL,'$2y$12$oYtGVbLzFb2D45ewMeNYmuTY1fC8ZkTHwY64tAu8OYIJuUR0z8Piu','RVrq6dm8Q1CnHlFdKxx3Hd5lr45XL1b4tlP2uFC0LoqqKvwcTjysEgWqy3X7','2026-07-10 06:47:09','2026-07-18 04:53:36'),(16,'Atharva','assawant51@gmail.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,1,0,0,'light','en','Asia/Kolkata','realtor',1,NULL,NULL,NULL,NULL,'$2y$12$3z9eT36d8FtI3NROFtDVXOFEEP2CJ1FpU0oR91f9NLVj/SFJm8xNi',NULL,'2026-07-10 07:39:42','2026-11-04 05:40:58'),(17,'Atharva Sawant','assawanat100@gmail.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,1,0,0,'light','en','Asia/Kolkata','realtor',1,'2026-07-30 09:48:31',2,NULL,NULL,'$2y$12$udWeT8DM3kRaY58x9HjS0.9P4DQP0at6WSCkW3HtgsXn6/XnNXYgC',NULL,'2026-07-27 08:24:16','2026-07-30 09:48:31'),(18,'Atharva Sawant','assawant020@gmail.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,1,0,0,'light','en','Asia/Kolkata','realtor',2,NULL,NULL,NULL,NULL,'$2y$12$ghCeeUaHLxk1SVnQ41.PBONl7Kn9SYJQsC37Zp6jzBMA7.kUEAy/G',NULL,'2026-07-28 03:46:43','2026-07-29 07:06:32'),(19,'Atharva Sawant','atharvasawant020@gmail.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,1,0,0,'light','en','Asia/Kolkata','realtor',2,NULL,NULL,NULL,NULL,'$2y$12$wcU9nZ14MFcfM0hcJ5TCcurlpI0fVrxm0YBhQT/fBJ5BhnRBzxKYe',NULL,'2026-07-29 07:08:30','2026-07-29 09:17:14'),(20,'MCP Smoke User','mcp-smoke@rumu.in',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,1,0,0,'light','en','Asia/Kolkata','realtor',NULL,NULL,NULL,NULL,NULL,'$2y$12$3Rajqv8eTRYU9Jr80lqL1u3b.jCfB2EB6J2rYRSAYBw3isrm5IWvC',NULL,'2026-11-09 00:28:08','2026-11-09 00:28:08');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-09 14:47:58



-- ==============================================================
-- RUMU SOCIAL INTEGRATION TABLES (Added Nov 2026)
-- Includes Facebook & Instagram Direct, Pages, Comments, & Avatars
-- ==============================================================

SET FOREIGN_KEY_CHECKS=0;

-- 1. social_accounts
DROP TABLE IF EXISTS social_accounts;
CREATE TABLE social_accounts (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  company_id bigint(20) unsigned DEFAULT NULL,
  user_id bigint(20) unsigned NOT NULL,
  platform varchar(30) NOT NULL DEFAULT 'facebook',
  external_id varchar(191) NOT NULL,
  
ame varchar(255) DEFAULT NULL,
  username varchar(255) DEFAULT NULL,
  profile_picture_url text DEFAULT NULL,
  ccess_token text NOT NULL,
  	oken_expires_at timestamp NULL DEFAULT NULL,
  permissions longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(permissions)),
  status varchar(30) NOT NULL DEFAULT 'connected',
  last_synced_at timestamp NULL DEFAULT NULL,
  created_at timestamp NULL DEFAULT NULL,
  updated_at timestamp NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY social_accounts_company_id_platform_index (company_id,platform),
  KEY social_accounts_user_id_platform_index (user_id,platform),
  KEY social_accounts_external_id_index (external_id),
  CONSTRAINT social_accounts_company_id_foreign FOREIGN KEY (company_id) REFERENCES enterprises (id) ON DELETE CASCADE,
  CONSTRAINT social_accounts_user_id_foreign FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. social_pages
DROP TABLE IF EXISTS social_pages;
CREATE TABLE social_pages (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  company_id bigint(20) unsigned DEFAULT NULL,
  user_id bigint(20) unsigned NOT NULL,
  social_account_id bigint(20) unsigned NOT NULL,
  platform varchar(30) NOT NULL DEFAULT 'facebook',
  external_id varchar(191) NOT NULL,
  
ame varchar(255) NOT NULL,
  username varchar(255) DEFAULT NULL,
  category varchar(255) DEFAULT NULL,
  instagram_business_account_id varchar(191) DEFAULT NULL,
  ccess_token text NOT NULL,
  is_active tinyint(1) NOT NULL DEFAULT 1,
  last_synced_at timestamp NULL DEFAULT NULL,
  created_at timestamp NULL DEFAULT NULL,
  updated_at timestamp NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY social_pages_company_id_platform_index (company_id,platform),
  KEY social_pages_user_id_platform_index (user_id,platform),
  KEY social_pages_external_id_index (external_id),
  KEY social_pages_instagram_business_account_id_index (instagram_business_account_id),
  KEY social_pages_social_account_id_foreign (social_account_id),
  CONSTRAINT social_pages_company_id_foreign FOREIGN KEY (company_id) REFERENCES enterprises (id) ON DELETE CASCADE,
  CONSTRAINT social_pages_social_account_id_foreign FOREIGN KEY (social_account_id) REFERENCES social_accounts (id) ON DELETE CASCADE,
  CONSTRAINT social_pages_user_id_foreign FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. social_posts
DROP TABLE IF EXISTS social_posts;
CREATE TABLE social_posts (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  company_id bigint(20) unsigned DEFAULT NULL,
  user_id bigint(20) unsigned NOT NULL,
  social_page_id bigint(20) unsigned NOT NULL,
  platform varchar(30) NOT NULL,
  external_id varchar(191) NOT NULL,
  permalink text DEFAULT NULL,
  message text DEFAULT NULL,
  media_type varchar(50) DEFAULT NULL,
  media_url text DEFAULT NULL,
  	humbnail_url text DEFAULT NULL,
  likes_count int(10) unsigned NOT NULL DEFAULT 0,
  comments_count int(10) unsigned NOT NULL DEFAULT 0,
  shares_count int(10) unsigned NOT NULL DEFAULT 0,
  published_at timestamp NULL DEFAULT NULL,
  
aw_data longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(
aw_data)),
  created_at timestamp NULL DEFAULT NULL,
  updated_at timestamp NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY social_posts_social_page_id_external_id_unique (social_page_id,external_id),
  KEY social_posts_company_id_platform_index (company_id,platform),
  KEY social_posts_user_id_platform_index (user_id,platform),
  KEY social_posts_published_at_index (published_at),
  CONSTRAINT social_posts_company_id_foreign FOREIGN KEY (company_id) REFERENCES enterprises (id) ON DELETE CASCADE,
  CONSTRAINT social_posts_social_page_id_foreign FOREIGN KEY (social_page_id) REFERENCES social_pages (id) ON DELETE CASCADE,
  CONSTRAINT social_posts_user_id_foreign FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. social_comments
DROP TABLE IF EXISTS social_comments;
CREATE TABLE social_comments (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  company_id bigint(20) unsigned DEFAULT NULL,
  user_id bigint(20) unsigned NOT NULL,
  social_post_id bigint(20) unsigned NOT NULL,
  parent_comment_id bigint(20) unsigned DEFAULT NULL,
  platform varchar(30) NOT NULL,
  external_id varchar(191) NOT NULL,
  rom_id varchar(191) DEFAULT NULL,
  rom_name varchar(255) DEFAULT NULL,
  message text NOT NULL,
  is_hidden tinyint(1) NOT NULL DEFAULT 0,
  is_deleted tinyint(1) NOT NULL DEFAULT 0,
  created_time timestamp NULL DEFAULT NULL,
  
aw_data longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(
aw_data)),
  created_at timestamp NULL DEFAULT NULL,
  updated_at timestamp NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY social_comments_social_post_id_external_id_unique (social_post_id,external_id),
  KEY social_comments_company_id_platform_index (company_id,platform),
  KEY social_comments_user_id_platform_index (user_id,platform),
  KEY social_comments_parent_comment_id_index (parent_comment_id),
  KEY social_comments_created_time_index (created_time),
  CONSTRAINT social_comments_company_id_foreign FOREIGN KEY (company_id) REFERENCES enterprises (id) ON DELETE CASCADE,
  CONSTRAINT social_comments_parent_comment_id_foreign FOREIGN KEY (parent_comment_id) REFERENCES social_comments (id) ON DELETE CASCADE,
  CONSTRAINT social_comments_social_post_id_foreign FOREIGN KEY (social_post_id) REFERENCES social_posts (id) ON DELETE CASCADE,
  CONSTRAINT social_comments_user_id_foreign FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. social_conversations (Includes participant_avatar & VARCHAR(191) IDs)
DROP TABLE IF EXISTS social_conversations;
CREATE TABLE social_conversations (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  company_id bigint(20) unsigned DEFAULT NULL,
  user_id bigint(20) unsigned NOT NULL,
  social_page_id bigint(20) unsigned NOT NULL,
  platform varchar(30) NOT NULL,
  external_id varchar(191) NOT NULL,
  participant_id varchar(191) NOT NULL,
  participant_name varchar(255) DEFAULT NULL,
  participant_username varchar(255) DEFAULT NULL,
  participant_avatar text DEFAULT NULL,
  unread_count int(10) unsigned NOT NULL DEFAULT 0,
  last_message_text text DEFAULT NULL,
  last_message_at timestamp NULL DEFAULT NULL,
  is_assigned_to bigint(20) unsigned DEFAULT NULL,
  internal_notes text DEFAULT NULL,
  created_at timestamp NULL DEFAULT NULL,
  updated_at timestamp NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY social_conversations_social_page_id_external_id_unique (social_page_id,external_id),
  KEY social_conversations_company_id_platform_index (company_id,platform),
  KEY social_conversations_user_id_platform_index (user_id,platform),
  KEY social_conversations_participant_id_index (participant_id),
  KEY social_conversations_last_message_at_index (last_message_at),
  KEY social_conversations_is_assigned_to_index (is_assigned_to),
  CONSTRAINT social_conversations_company_id_foreign FOREIGN KEY (company_id) REFERENCES enterprises (id) ON DELETE CASCADE,
  CONSTRAINT social_conversations_is_assigned_to_foreign FOREIGN KEY (is_assigned_to) REFERENCES users (id) ON DELETE SET NULL,
  CONSTRAINT social_conversations_social_page_id_foreign FOREIGN KEY (social_page_id) REFERENCES social_pages (id) ON DELETE CASCADE,
  CONSTRAINT social_conversations_user_id_foreign FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. social_messages
DROP TABLE IF EXISTS social_messages;
CREATE TABLE social_messages (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  company_id bigint(20) unsigned DEFAULT NULL,
  user_id bigint(20) unsigned NOT NULL,
  social_conversation_id bigint(20) unsigned NOT NULL,
  platform varchar(30) NOT NULL,
  external_id varchar(191) DEFAULT NULL,
  sender_id varchar(191) NOT NULL,
  sender_name varchar(255) DEFAULT NULL,
  
ecipient_id varchar(191) NOT NULL,
  message_text text DEFAULT NULL,
  ttachments longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(ttachments)),
  is_from_page tinyint(1) NOT NULL DEFAULT 0,
  sent_at timestamp NULL DEFAULT NULL,
  created_at timestamp NULL DEFAULT NULL,
  updated_at timestamp NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY social_messages_company_id_platform_index (company_id,platform),
  KEY social_messages_user_id_platform_index (user_id,platform),
  KEY social_messages_external_id_index (external_id),
  KEY social_messages_sent_at_index (sent_at),
  KEY social_messages_social_conversation_id_foreign (social_conversation_id),
  CONSTRAINT social_messages_company_id_foreign FOREIGN KEY (company_id) REFERENCES enterprises (id) ON DELETE CASCADE,
  CONSTRAINT social_messages_social_conversation_id_foreign FOREIGN KEY (social_conversation_id) REFERENCES social_conversations (id) ON DELETE CASCADE,
  CONSTRAINT social_messages_user_id_foreign FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. social_mentions
DROP TABLE IF EXISTS social_mentions;
CREATE TABLE social_mentions (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  company_id bigint(20) unsigned DEFAULT NULL,
  user_id bigint(20) unsigned NOT NULL,
  social_page_id bigint(20) unsigned NOT NULL,
  platform varchar(30) NOT NULL,
  external_id varchar(191) NOT NULL,
  media_id varchar(191) DEFAULT NULL,
  comment_id varchar(191) DEFAULT NULL,
  mention_type varchar(30) NOT NULL DEFAULT 'post',
  media_url text DEFAULT NULL,
  caption text DEFAULT NULL,
  uthor_username varchar(255) DEFAULT NULL,
  mentioned_at timestamp NULL DEFAULT NULL,
  
aw_data longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(
aw_data)),
  created_at timestamp NULL DEFAULT NULL,
  updated_at timestamp NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY social_mentions_company_id_platform_index (company_id,platform),
  KEY social_mentions_user_id_platform_index (user_id,platform),
  KEY social_mentions_external_id_index (external_id),
  KEY social_mentions_mentioned_at_index (mentioned_at),
  KEY social_mentions_social_page_id_foreign (social_page_id),
  CONSTRAINT social_mentions_company_id_foreign FOREIGN KEY (company_id) REFERENCES enterprises (id) ON DELETE CASCADE,
  CONSTRAINT social_mentions_social_page_id_foreign FOREIGN KEY (social_page_id) REFERENCES social_pages (id) ON DELETE CASCADE,
  CONSTRAINT social_mentions_user_id_foreign FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. social_insights
DROP TABLE IF EXISTS social_insights;
CREATE TABLE social_insights (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  company_id bigint(20) unsigned DEFAULT NULL,
  user_id bigint(20) unsigned NOT NULL,
  social_page_id bigint(20) unsigned NOT NULL,
  platform varchar(30) NOT NULL,
  metric_type varchar(50) NOT NULL,
  period varchar(30) NOT NULL DEFAULT 'day',
  metrics longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(metrics)),
  
ecorded_date date NOT NULL,
  created_at timestamp NULL DEFAULT NULL,
  updated_at timestamp NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY social_insights_company_id_platform_index (company_id,platform),
  KEY social_insights_user_id_platform_index (user_id,platform),
  KEY social_insights_social_page_id_metric_type_recorded_date_index (social_page_id,metric_type,
ecorded_date),
  CONSTRAINT social_insights_company_id_foreign FOREIGN KEY (company_id) REFERENCES enterprises (id) ON DELETE CASCADE,
  CONSTRAINT social_insights_social_page_id_foreign FOREIGN KEY (social_page_id) REFERENCES social_pages (id) ON DELETE CASCADE,
  CONSTRAINT social_insights_user_id_foreign FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. social_webhook_events
DROP TABLE IF EXISTS social_webhook_events;
CREATE TABLE social_webhook_events (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  event_id varchar(191) NOT NULL,
  platform varchar(30) NOT NULL DEFAULT 'meta',
  event_type varchar(100) NOT NULL,
  payload longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(payload)),
  processed tinyint(1) NOT NULL DEFAULT 0,
  processed_at timestamp NULL DEFAULT NULL,
  error text DEFAULT NULL,
  created_at timestamp NULL DEFAULT NULL,
  updated_at timestamp NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY social_webhook_events_event_id_unique (event_id),
  KEY social_webhook_events_processed_index (processed),
  KEY social_webhook_events_event_type_index (event_type),
  KEY social_webhook_events_created_at_index (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. social_sync_logs
DROP TABLE IF EXISTS social_sync_logs;
CREATE TABLE social_sync_logs (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  company_id bigint(20) unsigned DEFAULT NULL,
  user_id bigint(20) unsigned NOT NULL,
  social_page_id bigint(20) unsigned DEFAULT NULL,
  platform varchar(30) NOT NULL,
  sync_type varchar(50) NOT NULL,
  status varchar(30) NOT NULL DEFAULT 'pending',
  items_synced int(10) unsigned NOT NULL DEFAULT 0,
  error_message text DEFAULT NULL,
  started_at timestamp NULL DEFAULT NULL,
  completed_at timestamp NULL DEFAULT NULL,
  created_at timestamp NULL DEFAULT NULL,
  updated_at timestamp NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY social_sync_logs_company_id_platform_index (company_id,platform),
  KEY social_sync_logs_user_id_platform_index (user_id,platform),
  KEY social_sync_logs_status_index (status),
  KEY social_sync_logs_social_page_id_foreign (social_page_id),
  CONSTRAINT social_sync_logs_company_id_foreign FOREIGN KEY (company_id) REFERENCES enterprises (id) ON DELETE CASCADE,
  CONSTRAINT social_sync_logs_social_page_id_foreign FOREIGN KEY (social_page_id) REFERENCES social_pages (id) ON DELETE CASCADE,
  CONSTRAINT social_sync_logs_user_id_foreign FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. social_oauth_states
DROP TABLE IF EXISTS social_oauth_states;
CREATE TABLE social_oauth_states (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  company_id bigint(20) unsigned DEFAULT NULL,
  user_id bigint(20) unsigned NOT NULL,
  state varchar(128) NOT NULL,
  
edirect_uri text DEFAULT NULL,
  expires_at timestamp NOT NULL,
  created_at timestamp NULL DEFAULT NULL,
  updated_at timestamp NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY social_oauth_states_state_unique (state),
  KEY social_oauth_states_company_id_user_id_index (company_id,user_id),
  KEY social_oauth_states_expires_at_index (expires_at),
  CONSTRAINT social_oauth_states_company_id_foreign FOREIGN KEY (company_id) REFERENCES enterprises (id) ON DELETE CASCADE,
  CONSTRAINT social_oauth_states_user_id_foreign FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS=1;



-- ==============================================================
-- RUMU WHATSAPP MODULE TABLES (Added Nov 2026)
-- WhatsApp Business Cloud API Integration
-- ==============================================================

SET FOREIGN_KEY_CHECKS=0;

-- 1. whatsapp_accounts
DROP TABLE IF EXISTS whatsapp_accounts;
CREATE TABLE whatsapp_accounts (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  company_id bigint(20) unsigned NOT NULL,
  user_id bigint(20) unsigned DEFAULT NULL,
  waba_id varchar(100) DEFAULT NULL,
  phone_number_id varchar(100) NOT NULL,
  display_phone_number varchar(50) NOT NULL,
  erified_name varchar(255) DEFAULT NULL,
  code_verification_status varchar(50) NOT NULL DEFAULT 'NOT_VERIFIED',
  quality_rating varchar(50) NOT NULL DEFAULT 'GREEN',
  status varchar(30) NOT NULL DEFAULT 'connected',
  ccess_token text NOT NULL,
  pp_secret text DEFAULT NULL,
  webhook_verify_token varchar(128) DEFAULT NULL,
  pi_version varchar(20) NOT NULL DEFAULT 'v19.0',
  is_default tinyint(1) NOT NULL DEFAULT 0,
  settings longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(settings)),
  last_synced_at timestamp NULL DEFAULT NULL,
  created_at timestamp NULL DEFAULT NULL,
  updated_at timestamp NULL DEFAULT NULL,
  deleted_at timestamp NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY whatsapp_accounts_company_id_status_index (company_id,status),
  KEY whatsapp_accounts_phone_number_id_index (phone_number_id),
  KEY whatsapp_accounts_waba_id_index (waba_id),
  KEY whatsapp_accounts_user_id_foreign (user_id),
  CONSTRAINT whatsapp_accounts_company_id_foreign FOREIGN KEY (company_id) REFERENCES enterprises (id) ON DELETE CASCADE,
  CONSTRAINT whatsapp_accounts_user_id_foreign FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. whatsapp_contacts
DROP TABLE IF EXISTS whatsapp_contacts;
CREATE TABLE whatsapp_contacts (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  company_id bigint(20) unsigned NOT NULL,
  lead_id bigint(20) unsigned DEFAULT NULL,
  wa_id varchar(50) NOT NULL,
  phone_number varchar(50) NOT NULL,
  
ame varchar(255) DEFAULT NULL,
  profile_picture_url text DEFAULT NULL,
  profile_data longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(profile_data)),
  last_active_at timestamp NULL DEFAULT NULL,
  created_at timestamp NULL DEFAULT NULL,
  updated_at timestamp NULL DEFAULT NULL,
  deleted_at timestamp NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY whatsapp_contacts_company_id_wa_id_unique (company_id,wa_id),
  KEY whatsapp_contacts_company_id_phone_number_index (company_id,phone_number),
  KEY whatsapp_contacts_lead_id_index (lead_id),
  CONSTRAINT whatsapp_contacts_company_id_foreign FOREIGN KEY (company_id) REFERENCES enterprises (id) ON DELETE CASCADE,
  CONSTRAINT whatsapp_contacts_lead_id_foreign FOREIGN KEY (lead_id) REFERENCES leads (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. whatsapp_conversations
DROP TABLE IF EXISTS whatsapp_conversations;
CREATE TABLE whatsapp_conversations (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  company_id bigint(20) unsigned NOT NULL,
  whatsapp_account_id bigint(20) unsigned NOT NULL,
  whatsapp_contact_id bigint(20) unsigned NOT NULL,
  ssigned_to bigint(20) unsigned DEFAULT NULL,
  status varchar(30) NOT NULL DEFAULT 'open',
  session_expires_at timestamp NULL DEFAULT NULL,
  unread_count int(10) unsigned NOT NULL DEFAULT 0,
  last_message_text text DEFAULT NULL,
  last_message_at timestamp NULL DEFAULT NULL,
  last_incoming_at timestamp NULL DEFAULT NULL,
  last_outgoing_at timestamp NULL DEFAULT NULL,
  metadata longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(metadata)),
  created_at timestamp NULL DEFAULT NULL,
  updated_at timestamp NULL DEFAULT NULL,
  deleted_at timestamp NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY whatsapp_conversations_account_contact_unique (whatsapp_account_id,whatsapp_contact_id),
  KEY whatsapp_conversations_company_id_status_index (company_id,status),
  KEY whatsapp_conversations_assigned_to_index (ssigned_to),
  KEY whatsapp_conversations_last_message_at_index (last_message_at),
  KEY whatsapp_conversations_whatsapp_contact_id_foreign (whatsapp_contact_id),
  CONSTRAINT whatsapp_conversations_company_id_foreign FOREIGN KEY (company_id) REFERENCES enterprises (id) ON DELETE CASCADE,
  CONSTRAINT whatsapp_conversations_whatsapp_account_id_foreign FOREIGN KEY (whatsapp_account_id) REFERENCES whatsapp_accounts (id) ON DELETE CASCADE,
  CONSTRAINT whatsapp_conversations_whatsapp_contact_id_foreign FOREIGN KEY (whatsapp_contact_id) REFERENCES whatsapp_contacts (id) ON DELETE CASCADE,
  CONSTRAINT whatsapp_conversations_assigned_to_foreign FOREIGN KEY (ssigned_to) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. whatsapp_messages
DROP TABLE IF EXISTS whatsapp_messages;
CREATE TABLE whatsapp_messages (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  company_id bigint(20) unsigned NOT NULL,
  whatsapp_conversation_id bigint(20) unsigned NOT NULL,
  meta_message_id varchar(191) DEFAULT NULL,
  direction varchar(20) NOT NULL,
  sender_id varchar(100) NOT NULL,
  
ecipient_id varchar(100) NOT NULL,
  	ype varchar(30) NOT NULL DEFAULT 'text',
  content text DEFAULT NULL,
  media_url text DEFAULT NULL,
  media_mime_type varchar(100) DEFAULT NULL,
  
aw_payload longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(
aw_payload)),
  status varchar(30) NOT NULL DEFAULT 'pending',
  error_code varchar(50) DEFAULT NULL,
  error_message text DEFAULT NULL,
  sent_at timestamp NULL DEFAULT NULL,
  delivered_at timestamp NULL DEFAULT NULL,
  
ead_at timestamp NULL DEFAULT NULL,
  ailed_at timestamp NULL DEFAULT NULL,
  created_at timestamp NULL DEFAULT NULL,
  updated_at timestamp NULL DEFAULT NULL,
  deleted_at timestamp NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY whatsapp_messages_meta_message_id_unique (meta_message_id),
  KEY whatsapp_messages_company_id_status_index (company_id,status),
  KEY whatsapp_messages_conversation_created_index (whatsapp_conversation_id,created_at),
  KEY whatsapp_messages_direction_index (direction),
  CONSTRAINT whatsapp_messages_company_id_foreign FOREIGN KEY (company_id) REFERENCES enterprises (id) ON DELETE CASCADE,
  CONSTRAINT whatsapp_messages_conversation_id_foreign FOREIGN KEY (whatsapp_conversation_id) REFERENCES whatsapp_conversations (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. whatsapp_templates
DROP TABLE IF EXISTS whatsapp_templates;
CREATE TABLE whatsapp_templates (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  company_id bigint(20) unsigned NOT NULL,
  whatsapp_account_id bigint(20) unsigned NOT NULL,
  meta_template_id varchar(100) DEFAULT NULL,
  
ame varchar(100) NOT NULL,
  language varchar(20) NOT NULL DEFAULT 'en_US',
  category varchar(50) NOT NULL DEFAULT 'UTILITY',
  status varchar(30) NOT NULL DEFAULT 'APPROVED',
  components longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(components)),
  sample_values longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(sample_values)),
  
ejected_reason text DEFAULT NULL,
  last_synced_at timestamp NULL DEFAULT NULL,
  created_at timestamp NULL DEFAULT NULL,
  updated_at timestamp NULL DEFAULT NULL,
  deleted_at timestamp NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY whatsapp_templates_account_name_lang_unique (whatsapp_account_id,
ame,language),
  KEY whatsapp_templates_company_id_status_index (company_id,status),
  KEY whatsapp_templates_category_index (category),
  CONSTRAINT whatsapp_templates_company_id_foreign FOREIGN KEY (company_id) REFERENCES enterprises (id) ON DELETE CASCADE,
  CONSTRAINT whatsapp_templates_account_id_foreign FOREIGN KEY (whatsapp_account_id) REFERENCES whatsapp_accounts (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. whatsapp_webhook_events
DROP TABLE IF EXISTS whatsapp_webhook_events;
CREATE TABLE whatsapp_webhook_events (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  event_id varchar(191) NOT NULL,
  event_type varchar(100) NOT NULL,
  payload longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  status varchar(30) NOT NULL DEFAULT 'pending',
  error text DEFAULT NULL,
  
eceived_at timestamp NOT NULL DEFAULT current_timestamp(),
  processed_at timestamp NULL DEFAULT NULL,
  created_at timestamp NULL DEFAULT NULL,
  updated_at timestamp NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY whatsapp_webhook_events_event_id_unique (event_id),
  KEY whatsapp_webhook_events_status_index (status),
  KEY whatsapp_webhook_events_event_type_index (event_type),
  KEY whatsapp_webhook_events_created_at_index (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. whatsapp_message_logs
DROP TABLE IF EXISTS whatsapp_message_logs;
CREATE TABLE whatsapp_message_logs (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  company_id bigint(20) unsigned DEFAULT NULL,
  whatsapp_message_id bigint(20) unsigned DEFAULT NULL,
  pi_endpoint varchar(255) NOT NULL,
  http_method varchar(10) NOT NULL DEFAULT 'POST',
  http_status int(11) DEFAULT NULL,
  
equest_payload longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(
equest_payload)),
  
esponse_payload longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  error_details text DEFAULT NULL,
  duration_ms int(11) DEFAULT NULL,
  created_at timestamp NULL DEFAULT NULL,
  updated_at timestamp NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY whatsapp_message_logs_company_status_index (company_id,http_status),
  KEY whatsapp_message_logs_whatsapp_message_id_foreign (whatsapp_message_id),
  KEY whatsapp_message_logs_created_at_index (created_at),
  CONSTRAINT whatsapp_message_logs_company_id_foreign FOREIGN KEY (company_id) REFERENCES enterprises (id) ON DELETE CASCADE,
  CONSTRAINT whatsapp_message_logs_message_id_foreign FOREIGN KEY (whatsapp_message_id) REFERENCES whatsapp_messages (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS=1;
