-- Table structure for table `#__ctr_tags`
CREATE TABLE IF NOT EXISTS `#__ctr_tags` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `value` varchar(255) DEFAULT NULL,
  `color` varchar(7) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `#__ctr_tags`
INSERT IGNORE INTO `#__ctr_tags` (`id`, `value`, `color`) VALUES
(1, 'Special', '#ff0000'),
(2, 'Marked', '#586a7a');

-- Table structure for table `#__ctr_client`
CREATE TABLE IF NOT EXISTS `#__ctr_client` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `published` tinyint(1) NOT NULL DEFAULT 0,
  `date_crea` date NOT NULL DEFAULT current_timestamp(),
  `joomla_user_id` int(11) UNSIGNED DEFAULT NULL,
  `client_name` varchar(255) NOT NULL DEFAULT '0',
  `company_name` varchar(1024) DEFAULT NULL,
  `email` varchar(255) NOT NULL DEFAULT '0',
  `phone` varchar(64) DEFAULT NULL,
  `tag_id` int(10) UNSIGNED NULL DEFAULT NULL,
  `mailtext` text NOT NULL DEFAULT '-',
  PRIMARY KEY (`id`),
  CONSTRAINT `#__ctr_client_ibfk_1` FOREIGN KEY (`tag_id`) REFERENCES `#__ctr_tags` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `#__ctr_client`
INSERT IGNORE INTO `#__ctr_client` (`id`, `date_crea`, `joomla_user_id`, `client_name`, `company_name`, `email`) VALUES
(1, '2026-06-13 15:02:15', NULL, 'Test user', 'Test company', 'test@test.com');

-- Table structure for table `#__ctr_config`
CREATE TABLE IF NOT EXISTS `#__ctr_config` (
  `item` varchar(255) NOT NULL DEFAULT '0',
  `value` varchar(255) NOT NULL DEFAULT '0',
  PRIMARY KEY (`item`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Configuration values for Contractor';


-- Dumping data for table `#__ctr_config`
REPLACE INTO `#__ctr_config` (`item`, `value`) VALUES
('currency', 'EUR'),
('font_framework', '0'),
('front_color1', '#c7d1e1'),
('front_color2', '#c7d8a9'),
('front_loadfw', '0'),
('mailengine', '0'),
('mailfrom', 'info@petitpoisson.be'),
('mailfromname', 'Contractor at petitpoisson.be'),
('mailreply', 'no-reply@petitpoisson.be'),
('mailsubject', 'Subscription renewal'),
('signature', '<p>Best regards<br><strong>The Petitpoisson team</strong></p>'),
('smtp_auth', 'login'),
('smtp_host', 'localhost'),
('smtp_password', '0'),
('smtp_port', '25'),
('smtp_security', 'none'),
('smtp_username', '0'),
('warn_days', '30'),
('warn_mail', 'xavier.spirlet@gmail.com'),
('warn_when', '1');

-- Table structure for table `#__ctr_contract`
CREATE TABLE IF NOT EXISTS `#__ctr_contract` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `published` tinyint(1) NOT NULL DEFAULT 0,
  `client_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `name` varchar(255) NOT NULL DEFAULT '0',
  `date_start` date NOT NULL DEFAULT current_timestamp(),
  `valid_thru` date NOT NULL DEFAULT current_timestamp(),
  `rem` varchar(1024) DEFAULT NULL,
  `xrem` VARCHAR(1024) NULL DEFAULT NULL,
  `tag_id` int(10) UNSIGNED NULL DEFAULT NULL,
  `lastmsg` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`),
  CONSTRAINT `#__ctr_contract_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `#__ctr_client` (`id`),
  CONSTRAINT `#__ctr_contract_ibfk_2` FOREIGN KEY (`tag_id`) REFERENCES `#__ctr_tags` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `#__ctr_contract`
INSERT IGNORE INTO `#__ctr_contract` (`id`, `client_id`, `published`, `name`, `date_start`, `valid_thru`, `rem`) VALUES
(1, 1, 1, 'Test contract', '2026-06-13 15:05:57', '2026-06-13 15:05:57', 'Update prices on renewal');

-- Table structure for table `#__ctr_contract_line`
CREATE TABLE IF NOT EXISTS `#__ctr_contract_line` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `contract_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `description` varchar(255) NOT NULL DEFAULT '0',
  `price` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `contract_id` (`contract_id`),
  CONSTRAINT `#__ctr_contract_line_ibfk_1` FOREIGN KEY (`contract_id`) REFERENCES `#__ctr_contract` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `#__ctr_contract_line`
INSERT IGNORE INTO `#__ctr_contract_line` (`id`, `contract_id`, `description`, `price`) VALUES
(1, 1, 'Line 1', 100),
(2, 1, 'Other line', 9999);

-- Table structure for table `#__ctr_invoices`
CREATE TABLE IF NOT EXISTS `#__ctr_invoices` (
  `id` int(10) UNSIGNED NOT NULL,
  `invoicedate` date NOT NULL DEFAULT current_timestamp(),
  `reference` varchar(255) NOT NULL DEFAULT '0',
  `pdf_link` varchar(255) NOT NULL DEFAULT '0',
  `client_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`),
  CONSTRAINT `#__ctr_invoices_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `#__ctr_client` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for table `#__ctr_log`
CREATE TABLE IF NOT EXISTS `#__ctr_log` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `timedate` datetime NOT NULL DEFAULT current_timestamp(),
  `client_id` int(10) UNSIGNED DEFAULT NULL,
  `level` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `description` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `#__ctr_log` (`client_id`, `level`, `description`) VALUES 
(NULL,0,'Contractor installed')