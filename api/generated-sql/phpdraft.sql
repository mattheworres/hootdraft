
# This is a fix for InnoDB in MySQL >= 4.1.x
# It "suspends judgement" for fkey relationships until are tables are set.
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- draft
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `draft`;

CREATE TABLE `draft`
(
    `draft_id` INTEGER NOT NULL AUTO_INCREMENT,
    `draft_create_time` DATETIME NOT NULL,
    `draft_name` TEXT NOT NULL,
    `draft_sport` TEXT NOT NULL,
    `draft_status` TEXT NOT NULL,
    `draft_counter` INTEGER DEFAULT 0 NOT NULL,
    `draft_style` TEXT NOT NULL,
    `draft_rounds` int(2) unsigned DEFAULT 0 NOT NULL,
    `draft_password` TEXT,
    `draft_start_time` DATETIME,
    `draft_end_time` DATETIME,
    `draft_current_round` int(5) unsigned DEFAULT 1 NOT NULL,
    `draft_current_pick` int(5) unsigned DEFAULT 1 NOT NULL,
    PRIMARY KEY (`draft_id`)
) ENGINE=MyISAM;

-- ---------------------------------------------------------------------
-- managers
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `managers`;

CREATE TABLE `managers`
(
    `manager_id` INTEGER NOT NULL AUTO_INCREMENT,
    `draft_id` INTEGER DEFAULT 0 NOT NULL,
    `manager_name` TEXT NOT NULL,
    `manager_email` TEXT,
    `draft_order` tinyint(3) unsigned DEFAULT 0 NOT NULL,
    PRIMARY KEY (`manager_id`),
    INDEX `draft_idx` (`draft_id`),
    INDEX `manager_idx` (`manager_id`)
) ENGINE=MyISAM;

-- ---------------------------------------------------------------------
-- players
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `players`;

CREATE TABLE `players`
(
    `player_id` INTEGER NOT NULL AUTO_INCREMENT,
    `manager_id` INTEGER DEFAULT 0 NOT NULL,
    `first_name` TEXT,
    `last_name` TEXT,
    `team` CHAR(3),
    `position` VARCHAR(4),
    `pick_time` DATETIME,
    `pick_duration` INTEGER(10),
    `player_counter` INTEGER,
    `draft_id` int(11) unsigned DEFAULT 0 NOT NULL,
    `player_round` INTEGER DEFAULT 0 NOT NULL,
    `player_pick` INTEGER DEFAULT 0 NOT NULL,
    PRIMARY KEY (`player_id`),
    INDEX `manager_idx` (`manager_id`),
    INDEX `draft_idx` (`draft_id`)
) ENGINE=MyISAM;

-- ---------------------------------------------------------------------
-- pro_players
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `pro_players`;

CREATE TABLE `pro_players`
(
    `pro_player_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `league` TEXT NOT NULL,
    `first_name` TEXT NOT NULL,
    `last_name` TEXT NOT NULL,
    `position` TEXT NOT NULL,
    `team` TEXT NOT NULL,
    PRIMARY KEY (`pro_player_id`),
    INDEX `league_idx` (`league`(4))
) ENGINE=MyISAM;

-- ---------------------------------------------------------------------
-- trade_assets
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `trade_assets`;

CREATE TABLE `trade_assets`
(
    `tradeasset_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `trade_id` int(11) unsigned NOT NULL,
    `player_id` int(11) unsigned NOT NULL,
    `oldmanager_id` int(11) unsigned NOT NULL,
    `newmanager_id` int(11) unsigned NOT NULL,
    `was_drafted` TINYINT(1) NOT NULL,
    PRIMARY KEY (`tradeasset_id`),
    INDEX `trade_id` (`trade_id`)
) ENGINE=MyISAM;

-- ---------------------------------------------------------------------
-- trades
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `trades`;

CREATE TABLE `trades`
(
    `trade_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `draft_id` int(11) unsigned NOT NULL,
    `manager1_id` int(11) unsigned NOT NULL,
    `manager2_id` int(11) unsigned NOT NULL,
    `trade_time` DATETIME,
    PRIMARY KEY (`trade_id`),
    INDEX `manager1_idx` (`manager1_id`),
    INDEX `manager2_idx` (`manager2_id`),
    INDEX `draft_idx` (`draft_id`)
) ENGINE=MyISAM;

-- ---------------------------------------------------------------------
-- user_login
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `user_login`;

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
