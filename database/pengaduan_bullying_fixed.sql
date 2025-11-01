-- Database untuk Sistem Pengaduan Bullying Anonim
-- Import file ini ke phpMyAdmin
-- FIXED VERSION - Proper table ordering
-- Includes system_settings for customization

CREATE DATABASE IF NOT EXISTS `pengaduan_bullying` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `pengaduan_bullying`;

-- 1. Tabel kategori kasus (tidak ada foreign key dependency)
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert kategori default
INSERT INTO `categories` (`name`, `description`) VALUES
('Bullying Verbal', 'Ejekan, hinaan, ancaman verbal'),
('Bullying Fisik', 'Kekerasan fisik, pemukulan, dorongan'),
('Cyberbullying', 'Bullying melalui media sosial atau internet'),
('Bullying Sosial', 'Pengucilan, fitnah, merusak reputasi'),
('Pelecehan Seksual', 'Komentar atau tindakan tidak senonoh'),
('Diskriminasi', 'Perlakuan tidak adil berdasarkan SARA'),
('Lainnya', 'Kategori lain yang tidak termasuk di atas');

-- 2. Tabel admin users (harus dibuat sebelum reports karena foreign key)
CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('superadmin','staff_bk') DEFAULT 'staff_bk',
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default admin user (username: admin, password: Admin123!)
INSERT INTO admin_users (username, password_hash, email, full_name, role, is_active) VALUES
('admin', '$2y$10$5g/G2rcByq3Jf4vmXWI.M.Ds1XrgjaotSR5q8JhniXxWJQtV9VMVy', 'admin@school.com', 'Administrator', 'superadmin', 1);

-- 2b. Tabel system_settings (untuk kustomisasi sekolah)
CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`),
  KEY `idx_setting_key` (`setting_key`),
  KEY `updated_by` (`updated_by`),
  CONSTRAINT `system_settings_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default settings
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `updated_by`) VALUES
('school_name', 'Sistem Pengaduan Bullying', 1),
('school_tagline', 'Laporan Anda dijamin 100% ANONIM dan akan ditangani dengan serius oleh tim konseling sekolah', 1),
('school_logo', '', 1),
('school_background', '', 1);

-- 3. Tabel laporan utama
CREATE TABLE `reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tracking_code` varchar(20) NOT NULL,
  `pin_hash` varchar(255) NOT NULL,
  `category_id` int(11) NOT NULL,
  `title` varchar(200) DEFAULT NULL,
  `description` text NOT NULL,
  `location` varchar(200) DEFAULT NULL,
  `incident_date` date DEFAULT NULL,
  `incident_time` time DEFAULT NULL,
  `parties_involved` text DEFAULT NULL,
  `witnesses` text DEFAULT NULL,
  `urgency_level` enum('normal','high','emergency') DEFAULT 'normal',
  `status` enum('new','reviewed','escalated','resolved','closed') DEFAULT 'new',
  `assigned_to` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `tracking_code` (`tracking_code`),
  KEY `category_id` (`category_id`),
  KEY `status` (`status`),
  KEY `urgency_level` (`urgency_level`),
  KEY `assigned_to` (`assigned_to`),
  KEY `created_at` (`created_at`),
  KEY `status_urgency` (`status`, `urgency_level`),
  CONSTRAINT `fk_reports_category` FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`),
  CONSTRAINT `fk_reports_assigned` FOREIGN KEY (`assigned_to`) REFERENCES `admin_users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Tabel untuk file bukti
CREATE TABLE `report_attachments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `report_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_type` varchar(100) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `report_id` (`report_id`),
  CONSTRAINT `fk_attachments_report` FOREIGN KEY (`report_id`) REFERENCES `reports`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Tabel pesan anonim (chat dua arah)
CREATE TABLE `report_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `report_id` int(11) NOT NULL,
  `sender` enum('reporter','admin') NOT NULL,
  `sender_id` int(11) DEFAULT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `report_id` (`report_id`),
  KEY `sender_id` (`sender_id`),
  KEY `report_sender` (`report_id`, `sender`),
  CONSTRAINT `fk_messages_report` FOREIGN KEY (`report_id`) REFERENCES `reports`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_messages_admin` FOREIGN KEY (`sender_id`) REFERENCES `admin_users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Tabel audit log untuk aktivitas admin
CREATE TABLE `audit_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `target_type` varchar(50) DEFAULT NULL,
  `target_id` int(11) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `admin_id` (`admin_id`),
  KEY `created_at` (`created_at`),
  KEY `admin_date` (`admin_id`, `created_at`),
  CONSTRAINT `fk_audit_admin` FOREIGN KEY (`admin_id`) REFERENCES `admin_users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Tabel sessions untuk admin
CREATE TABLE `admin_sessions` (
  `id` varchar(128) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `last_activity` int(10) UNSIGNED NOT NULL,
  `data` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `admin_id` (`admin_id`),
  KEY `last_activity` (`last_activity`),
  CONSTRAINT `fk_sessions_admin` FOREIGN KEY (`admin_id`) REFERENCES `admin_users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. Tabel untuk rate limiting (anti spam) - TIDAK ADA FOREIGN KEY
CREATE TABLE `rate_limit` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `action` varchar(50) NOT NULL,
  `attempts` int(11) DEFAULT 1,
  `last_attempt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `ip_action` (`ip_address`, `action`),
  KEY `last_attempt` (`last_attempt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. Tabel notifikasi untuk admin
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) DEFAULT NULL,
  `type` varchar(50) NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text DEFAULT NULL,
  `related_id` int(11) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `admin_id` (`admin_id`),
  KEY `is_read` (`is_read`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `fk_notifications_admin` FOREIGN KEY (`admin_id`) REFERENCES `admin_users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
