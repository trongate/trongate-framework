-- =============================================
-- Trongate Framework Setup SQL
-- Matches the official Trongate table structure
-- with the addition of an 'email' column for
-- admin account creation during setup.
-- =============================================

CREATE TABLE IF NOT EXISTS `trongate_user_levels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `level_title` varchar(125) DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `trongate_user_levels` (`id`, `level_title`) VALUES
(1, 'admin');

CREATE TABLE IF NOT EXISTS `trongate_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(32) DEFAULT NULL,
  `user_level_id` int(11) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `trongate_administrators` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(65) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(60) DEFAULT NULL,
  `trongate_user_id` int(11) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 0,
  `failed_login_attempts` int(11) NOT NULL DEFAULT 0,
  `last_failed_attempt` int(11) NOT NULL DEFAULT 0,
  `login_blocked_until` int(11) NOT NULL DEFAULT 0,
  `failed_login_ip` varchar(45) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `trongate_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `token` varchar(125) DEFAULT NULL,
  `user_id` int(11) DEFAULT 0,
  `expiry_date` int(11) DEFAULT NULL,
  `code` varchar(3) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `target_table` varchar(255) NOT NULL,
  `identifier` varchar(255) NOT NULL,
  `ip_address` varchar(45) NOT NULL DEFAULT '',
  `attempted_at` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `target_table` (`target_table`(191)),
  KEY `identifier` (`identifier`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `target_table` varchar(255) NOT NULL,
  `identifier` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expiry_date` int(11) NOT NULL,
  `used` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `token` (`token`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;