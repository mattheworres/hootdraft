# This is a fix for InnoDB in MySQL >= 4.1.x
# It "suspends judgement" for fkey relationships until are tables are set.
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `draft_stats`;

CREATE TABLE `user_login`
(
    `UserID` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `Username` TEXT NOT NULL,
    `Password` TEXT NOT NULL,
    `Name` VARCHAR(15) DEFAULT '' NOT NULL,
    PRIMARY KEY (`UserID`),
    UNIQUE INDEX `UserID` (`UserID`)
) ENGINE=MyISAM;

# This restores the fkey checks, after having unset them earlier
SET FOREIGN_KEY_CHECKS = 1;

# Convert timezones back to being locally set
# NOTE: This should only be run once as it's relative: you will
# mangle data if you run it multiple times, hence why it's block-commented out.
# Change 'EST' to whatever timezone you set for PHPDraft <1.3.x

/*
UPDATE `draft`
SET `draft_start_time` = CONVERT_TZ(`draft_start_time`, 'GMT', 'EST')
WHERE `draft_start_time` IS NOT NULL;

UPDATE `draft`
SET `draft_end_time` = CONVERT_TZ(`draft_end_time`, 'GMT', 'EST')
WHERE `draft_end_time` IS NOT NULL;

UPDATE `trades`
SET `trade_time` = CONVERT_TZ(`trade_time`, 'GMT', 'EST')
WHERE `trade_time` IS NOT NULL;
*/

ALTER TABLE `managers` ADD COLUMN `manager_email` TEXT;

ALTER TABLE `draft` DROP COLUMN `commish_id`;
ALTER TABLE `trades` DROP COLUMN `trade_round`;