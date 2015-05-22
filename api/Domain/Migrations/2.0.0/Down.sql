# This is a fix for InnoDB in MySQL >= 4.1.x
# It "suspends judgement" for fkey relationships until are tables are set.
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `users`;

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

ALTER TABLE `managers` ADD COLUMN `manager_email` TEXT;