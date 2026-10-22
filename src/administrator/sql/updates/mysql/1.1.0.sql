-- Create pivot table for clients
CREATE TABLE IF NOT EXISTS `#__ctr_client_tags` (
  `client_id` int(10) UNSIGNED NOT NULL,
  `tag_id` int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`client_id`, `tag_id`),
  KEY `idx_tag_id` (`tag_id`),
  CONSTRAINT `#__ctr_client_tags_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `#__ctr_client` (`id`) ON DELETE CASCADE,
  CONSTRAINT `#__ctr_client_tags_ibfk_2` FOREIGN KEY (`tag_id`) REFERENCES `#__ctr_tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Migrate existing client tags (preserves current data)
INSERT IGNORE INTO `#__ctr_client_tags` (`client_id`, `tag_id`)
SELECT `id`, `tag_id` FROM `#__ctr_client` WHERE `tag_id` IS NOT NULL AND `tag_id` > 0;

-- Drop foreign key constraint and old column from clients
ALTER TABLE `#__ctr_client` DROP FOREIGN KEY `#__ctr_client_ibfk_1`;
ALTER TABLE `#__ctr_client` DROP COLUMN `tag_id`;

-- Create pivot table for contracts
CREATE TABLE IF NOT EXISTS `#__ctr_contract_tags` (
  `contract_id` int(10) UNSIGNED NOT NULL,
  `tag_id` int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`contract_id`, `tag_id`),
  KEY `idx_tag_id` (`tag_id`),
  CONSTRAINT `#__ctr_contract_tags_ibfk_1` FOREIGN KEY (`contract_id`) REFERENCES `#__ctr_contract` (`id`) ON DELETE CASCADE,
  CONSTRAINT `#__ctr_contract_tags_ibfk_2` FOREIGN KEY (`tag_id`) REFERENCES `#__ctr_tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Migrate existing contract tags (preserves current data)
INSERT IGNORE INTO `#__ctr_contract_tags` (`contract_id`, `tag_id`)
SELECT `id`, `tag_id` FROM `#__ctr_contract` WHERE `tag_id` IS NOT NULL AND `tag_id` > 0;

-- Drop foreign key constraint and old column from contracts
ALTER TABLE `#__ctr_contract` DROP FOREIGN KEY `#__ctr_contract_ibfk_2`;
ALTER TABLE `#__ctr_contract` DROP COLUMN `tag_id`;