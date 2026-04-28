-- Caribbean Gospel Radio HD — Database Schema
-- Run this once on your MySQL server:
-- mysql -u root -p < setup.sql

CREATE DATABASE IF NOT EXISTS crhd CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE crhd;

-- ── STATION (single row) ──────────────────────────────
CREATE TABLE IF NOT EXISTS station (
  id           INT PRIMARY KEY AUTO_INCREMENT,
  name         VARCHAR(255) NOT NULL DEFAULT 'Caribbean Gospel Radio HD',
  stream_url   VARCHAR(500) DEFAULT '',
  fallback_url VARCHAR(500) DEFAULT '',
  metadata_url VARCHAR(500) DEFAULT '',
  bitrate      VARCHAR(50)  DEFAULT '',
  timezone     VARCHAR(100) DEFAULT 'America/New_York',
  logo_url     VARCHAR(500) DEFAULT '',
  logo_data    LONGTEXT     DEFAULT NULL,  -- base64 for uploaded images
  cbox_id      VARCHAR(100) DEFAULT '',
  cbox_tag     VARCHAR(100) DEFAULT '',
  cbox_embed   VARCHAR(1000) DEFAULT '',
  updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
INSERT IGNORE INTO station (id, name, stream_url, timezone)
  VALUES (1, 'Caribbean Gospel Radio HD', 'https://aud1.sjamz.com:8008/stream', 'America/New_York');

-- ── HERO ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS hero (
  id              INT PRIMARY KEY AUTO_INCREMENT,
  bg_type         ENUM('gradient','image','video') DEFAULT 'gradient',
  bg_src          VARCHAR(1000) DEFAULT '',
  bg_data         LONGTEXT DEFAULT NULL,
  overlay_opacity DECIMAL(3,2)  DEFAULT 0.55,
  headline1       VARCHAR(255)  DEFAULT 'Praise',
  headline2       VARCHAR(255)  DEFAULT 'Worship',
  headline3       VARCHAR(255)  DEFAULT 'Word',
  tagline         VARCHAR(500)  DEFAULT 'Broadcasting 24/7 Across the Caribbean & Beyond',
  subline         VARCHAR(500)  DEFAULT 'The Sound of the Islands · Gospel · Worship · Inspiration',
  updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
INSERT IGNORE INTO hero (id) VALUES (1);

-- ── COLORS ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS colors (
  id          INT PRIMARY KEY AUTO_INCREMENT,
  primary_col VARCHAR(50) DEFAULT '#FFB700',
  accent_col  VARCHAR(50) DEFAULT '#CC0000',
  dark_col    VARCHAR(50) DEFAULT '#0a0a0a',
  nav_bg      VARCHAR(100) DEFAULT 'rgba(0,0,0,0.92)',
  player_bg   VARCHAR(50) DEFAULT '#111111',
  section_bg  VARCHAR(50) DEFAULT '#111111',
  footer_bg   VARCHAR(50) DEFAULT '#0d0d0d',
  text_col    VARCHAR(50) DEFAULT '#ffffff',
  updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
INSERT IGNORE INTO colors (id) VALUES (1);

-- ── DJs ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS djs (
  id         INT PRIMARY KEY AUTO_INCREMENT,
  name       VARCHAR(255) NOT NULL,
  bio        TEXT         DEFAULT '',
  photo_url  VARCHAR(1000) DEFAULT '',
  photo_data LONGTEXT      DEFAULT NULL,
  sort_order INT           DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ── SCHEDULE ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS schedule (
  id            INT PRIMARY KEY AUTO_INCREMENT,
  show_name     VARCHAR(255) NOT NULL,
  day_pattern   VARCHAR(100) NOT NULL,  -- e.g. "Mon – Fri", "Sunday", "Daily"
  time_start    VARCHAR(20)  NOT NULL,  -- e.g. "10:00 PM"
  time_end      VARCHAR(20)  NOT NULL,  -- e.g. "12:00 AM"
  dj_id         INT          DEFAULT NULL,
  live_override TINYINT(1)   DEFAULT 0,
  sort_order    INT          DEFAULT 0,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (dj_id) REFERENCES djs(id) ON DELETE SET NULL
);

-- ── DOWNLOADS ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS downloads (
  id         INT PRIMARY KEY AUTO_INCREMENT,
  platform   VARCHAR(255) NOT NULL,
  sub_label  VARCHAR(255) DEFAULT '',
  url        VARCHAR(1000) DEFAULT '#',
  icon_key   VARCHAR(50)  DEFAULT '',
  icon_color VARCHAR(50)  DEFAULT '#222',
  img_url    VARCHAR(1000) DEFAULT '',
  img_data   LONGTEXT      DEFAULT NULL,
  sort_order INT           DEFAULT 0
);
INSERT IGNORE INTO downloads (id, platform, sub_label, icon_key, icon_color) VALUES
  (1, 'App Store',    'Download on the', 'apple',  '#1c1c1e'),
  (2, 'Google Play',  'Get it on',       'google', '#0d3320'),
  (3, 'Amazon Alexa', 'Enable skill on', 'alexa',  '#1a2e4a');

-- ── SOCIALS ──────────────────────────────────────────
CREATE TABLE IF NOT EXISTS socials (
  id         INT PRIMARY KEY AUTO_INCREMENT,
  platform   VARCHAR(255) NOT NULL,
  url        VARCHAR(1000) DEFAULT '#',
  icon_key   VARCHAR(50)  DEFAULT '',
  icon_color VARCHAR(50)  DEFAULT '#333',
  img_url    VARCHAR(1000) DEFAULT '',
  img_data   LONGTEXT      DEFAULT NULL,
  sort_order INT           DEFAULT 0
);
INSERT IGNORE INTO socials (id, platform, icon_key, icon_color) VALUES
  (1, 'Facebook',  'fb', '#1877F2'),
  (2, 'X/Twitter', 'x',  '#000000'),
  (3, 'Instagram', 'ig', 'gradient'),
  (4, 'YouTube',   'yt', '#FF0000'),
  (5, 'WhatsApp',  'wa', '#25D366'),
  (6, 'TikTok',    'tt', '#000000');

-- ── FLAGS ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS flags (
  id         INT PRIMARY KEY AUTO_INCREMENT,
  name       VARCHAR(100) NOT NULL,
  code       VARCHAR(10)  NOT NULL,
  sort_order INT DEFAULT 0
);
INSERT IGNORE INTO flags (id, name, code, sort_order) VALUES
  (1,'Jamaica','jm',1),(2,'Trinidad','tt',2),(3,'Barbados','bb',3),
  (4,'Guyana','gy',4),(5,'Antigua','ag',5),(6,'Grenada','gd',6),
  (7,'St Lucia','lc',7),(8,'St Vincent','vc',8),(9,'Dominica','dm',9),
  (10,'St Kitts','kn',10),(11,'Belize','bz',11),(12,'Haiti','ht',12),
  (13,'Dom. Rep.','do',13),(14,'Cuba','cu',14),(15,'Puerto Rico','pr',15),
  (16,'Suriname','sr',16),(17,'Bahamas','bs',17),(18,'Cayman Islands','ky',18);

-- ── ADMIN USERS ──────────────────────────────────────
CREATE TABLE IF NOT EXISTS admin_users (
  id            INT PRIMARY KEY AUTO_INCREMENT,
  username      VARCHAR(100) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
-- Default: admin / crhd2024! (change immediately after setup)
INSERT IGNORE INTO admin_users (id, username, password_hash)
  VALUES (1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
