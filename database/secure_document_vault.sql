CREATE DATABASE IF NOT EXISTS secure_document_vault
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE secure_document_vault;

DROP TABLE IF EXISTS documents;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(180) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NULL,
  role ENUM('admin','manager','user') NOT NULL DEFAULT 'user',
  oauth_provider VARCHAR(50) NULL,
  oauth_id VARCHAR(120) NULL,
  two_factor_secret VARCHAR(80) NULL,
  two_factor_enabled TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_users_role (role),
  UNIQUE KEY uniq_oauth (oauth_provider, oauth_id)
) ENGINE=InnoDB;

CREATE TABLE documents (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  owner_id INT UNSIGNED NOT NULL,
  original_name VARCHAR(255) NOT NULL,
  stored_name VARCHAR(255) NOT NULL UNIQUE,
  mime_type VARCHAR(160) NOT NULL,
  size_bytes INT UNSIGNED NOT NULL,
  sha256_hash CHAR(64) NOT NULL,
  signature TEXT NOT NULL,
  public_key MEDIUMTEXT NOT NULL,
  iv VARCHAR(80) NOT NULL,
  auth_tag VARCHAR(80) NOT NULL,
  encryption_alg VARCHAR(80) NOT NULL DEFAULT 'AES-256-GCM',
  status ENUM('pending','verified','rejected') NOT NULL DEFAULT 'pending',
  last_verification_status ENUM('not_checked','valid','failed') NOT NULL DEFAULT 'not_checked',
  uploaded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  verified_at TIMESTAMP NULL,
  verified_by INT UNSIGNED NULL,
  CONSTRAINT fk_documents_owner FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_documents_verifier FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_documents_owner (owner_id),
  INDEX idx_documents_status (status)
) ENGINE=InnoDB;

INSERT INTO users (name, email, password_hash, role, two_factor_secret, two_factor_enabled) VALUES
('System Admin', 'admin@vault.local', '$2y$12$Q67MdVz553s6.yOjD0b25egfwZWTAk.Vw5NvGTRkLH.2gBXabr6fm', 'admin', 'JBSWY3DPEHPK3PXP', 0),
('Document Manager', 'manager@vault.local', '$2y$12$tddQkTFFTrASghENag9rwumjWsDOeOLet/6oHWvKal.Kzh2yZKg3e', 'manager', 'JBSWY3DPEHPK3PXQ', 0),
('Demo User', 'user@vault.local', '$2y$12$gBGycVu/L6IeKEP5IcfMmOI1.xrKN6WUnQDF69iA5aYyTJz0lHhsy', 'user', 'JBSWY3DPEHPK3PXR', 0);
