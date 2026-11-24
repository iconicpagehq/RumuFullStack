
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
