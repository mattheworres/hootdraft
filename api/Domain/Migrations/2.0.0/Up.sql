# This is a fix for InnoDB in MySQL >= 4.1.x
# It "suspends judgement" for fkey relationships until are tables are set.
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `user_login`;

CREATE TABLE `users`
(
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `enabled` BIT(1) NOT NULL DEFAULT b'0',
    `email` VARCHAR(255) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `salt` VARCHAR(16) NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `roles` VARCHAR(255) NOT NULL,
    `verificationKey` VARCHAR(16) NULL,
    `creationTime` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    PRIMARY KEY (`id`),
    UNIQUE INDEX `id` (`id`),
    UNIQUE INDEX `UNIQUE_USER` (`email`, `name`)
) ENGINE=MyISAM;
ALTER TABLE `users` ADD UNIQUE `UNIQUE_USER` (`email`, `name`);

ALTER TABLE `managers` DROP COLUMN `manager_email`;

ALTER TABLE `draft` ADD `commish_id` INT(11) NOT NULL AFTER `draft_id`;

#Add flag to draft for extended rosters.
#NOTE: If any existing drafts used extended rosters, you will need to manually set the flag to 1 for said drafts.
ALTER TABLE `draft` ADD `nfl_extended` BIT(1) NOT NULL DEFAULT b'0' AFTER `draft_current_pick`;

# Run this for existing drafts once you create your admin user, then enter that user ID below:
UPDATE `draft` SET `commish_id` = 16 WHERE `commish_id` = 0;



# This restores the fkey checks, after having unset them earlier
SET FOREIGN_KEY_CHECKS = 1;