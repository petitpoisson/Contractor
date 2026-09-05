-- Disable foreign key checks to avoid dependency issues during drop
SET FOREIGN_KEY_CHECKS=0;

-- --------------------------------------------------------
-- Drop all tables related to the Contractor component
-- --------------------------------------------------------

DROP TABLE IF EXISTS `#__ctr_contract_line`;
DROP TABLE IF EXISTS `#__ctr_invoices`;
DROP TABLE IF EXISTS `#__ctr_log`;
DROP TABLE IF EXISTS `#__ctr_contract`;
DROP TABLE IF EXISTS `#__ctr_tags`;
DROP TABLE IF EXISTS `#__ctr_config`;
DROP TABLE IF EXISTS `#__ctr_client`;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS=1;