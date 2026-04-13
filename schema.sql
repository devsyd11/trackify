-- Run this in MySQL (phpMyAdmin or mysql CLI) before using sign-in / sign-up.
CREATE DATABASE IF NOT EXISTS trackify_auth
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE trackify_auth;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  full_name VARCHAR(191) NOT NULL,
  email VARCHAR(191) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tracker_tokens (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  token CHAR(64) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_tracker_tokens_token (token),
  KEY idx_tracker_tokens_user (user_id),
  CONSTRAINT fk_tracker_tokens_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS phone_scan_history (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  phone_number VARCHAR(64) NOT NULL,
  url_count INT UNSIGNED NOT NULL DEFAULT 0,
  urls_json MEDIUMTEXT NOT NULL,
  created_at DATETIME NOT NULL,
  INDEX idx_user_created (user_id, created_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS facebook_monitor (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id         INT UNSIGNED NOT NULL,
  profile_url     VARCHAR(512) NOT NULL,
  label           VARCHAR(191) NOT NULL DEFAULT '',
  last_status     ENUM('unknown','active','inactive','unavailable') NOT NULL DEFAULT 'unknown',
  last_checked_at DATETIME NULL,
  last_changed_at DATETIME NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_fb_user (user_id),
  INDEX idx_fb_user_url (user_id, profile_url(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS delete_watch_monitor (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id         INT UNSIGNED NOT NULL,
  profile_url     VARCHAR(512) NOT NULL,
  label           VARCHAR(191) NOT NULL DEFAULT '',
  last_status     ENUM('unknown','active','inactive','unavailable') NOT NULL DEFAULT 'unknown',
  last_checked_at DATETIME NULL,
  last_changed_at DATETIME NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_dw_user (user_id),
  INDEX idx_dw_user_url (user_id, profile_url(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
