
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
