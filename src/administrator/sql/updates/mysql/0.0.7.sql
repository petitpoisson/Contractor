-- Update for Contractor version 0.0.7
ALTER TABLE `#__ctr_client` CHANGE `person_name` `client_name` VARCHAR(255) NOT NULL DEFAULT '0';