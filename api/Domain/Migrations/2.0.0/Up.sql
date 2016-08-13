# This is a fix for InnoDB in MySQL >= 4.1.x
# It "suspends judgement" for fkey relationships until are tables are set.
SET FOREIGN_KEY_CHECKS = 0;

use phpdraft;

DROP TABLE IF EXISTS `user_login`;

CREATE TABLE `users` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `enabled` TINYINT(1) NOT NULL DEFAULT b'0',
    `email` VARCHAR(255) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `salt` VARCHAR(16) NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `roles` VARCHAR(255) NOT NULL,
    `verificationKey` VARCHAR(16) NULL,
    `creationTime` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE `UNIQUE_ID` (`id`)
) ENGINE=InnoDB;

# Switch MySQL engine from MyISAM to InnoDB for most tables (except pro_players)
ALTER TABLE `trade_assets` ENGINE=InnoDB;
ALTER TABLE `trades` ENGINE=InnoDB;
ALTER TABLE `round_times` ENGINE=InnoDB;
ALTER TABLE `players` ENGINE=InnoDB;
ALTER TABLE `managers` ENGINE=InnoDB;
ALTER TABLE `draft` ENGINE=InnoDB;

ALTER TABLE `managers` DROP COLUMN `manager_email`;

ALTER TABLE `draft` ADD `commish_id` INT(11) NOT NULL AFTER `draft_id`;
ALTER TABLE `draft` ADD `draft_stats_generated` datetime default NULL AFTER `draft_end_time`;

#NOTE: To migrate PHPDraft <1.3.x NFL drafts that use extended rosters, you will have to manually update their
#draft_sport to "NFLE".

ALTER TABLE `trades` ADD `trade_round` INT(5) NOT NULL AFTER `trade_time`;

# Run this for existing drafts once you create your admin user, then enter that user ID below:
UPDATE `draft` SET `commish_id` = 16 WHERE `commish_id` = 0;

# Add Draft Stats computed table
CREATE TABLE `draft_stats` (
  `draft_stat_id` int(11) NOT NULL auto_increment,
  `draft_id` int(11) NOT NULL,
  `drafting_time_seconds` int(11) NOT NULL,
  `longest_avg_pick_manager_name` text NOT NULL,
  `longest_avg_pick_seconds` INT(11) NOT NULL,
  `shortest_avg_pick_manager_name` text NOT NULL,
  `shortest_avg_pick_seconds` INT(11) NOT NULL,
  `longest_single_pick_manager_name` text NOT NULL,
  `longest_single_pick_seconds` INT(11) NOT NULL,
  `shortest_single_pick_manager_name` text NOT NULL,
  `shortest_single_pick_seconds` INT(11) NOT NULL,
  `average_pick_seconds` INT(11) NOT NULL,
  `longest_round` INT(11) NOT NULL,
  `longest_round_seconds` INT(11) NOT NULL,
  `shortest_round` INT(11) NOT NULL,
  `shortest_round_seconds` INT(11) NOT NULL,
  `average_round_seconds` INT(11) NOT NULL,
  `most_drafted_team` text NOT NULL,
  `most_drafted_team_count` int(11) NOT NULL,
  `least_drafted_team` text NOT NULL,
  `least_drafted_team_count` int(11) NOT NULL,
  `most_drafted_position` text NOT NULL,
  `most_drafted_position_count` int(11) NOT NULL,
  `least_drafted_position` text NOT NULL,
  `least_drafted_position_count` int(11) NOT NULL,
  PRIMARY KEY  (`draft_stat_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

ALTER TABLE `draft` ADD INDEX `commish_id` (`commish_id`);

ALTER TABLE `managers` DROP INDEX `draft_idx`;
ALTER TABLE `managers` ADD INDEX `draft_idx` (`draft_id`);

ALTER TABLE `players` DROP INDEX `manager_idx`;
ALTER TABLE `players` DROP INDEX `draft_idx`;
ALTER TABLE `players` ADD INDEX `manager_idx` (`manager_id`);
ALTER TABLE `players` ADD INDEX `draft_idx` (`draft_id`);
ALTER TABLE `players` ADD INDEX `counter_idx` (`player_counter`);

ALTER TABLE `trades` DROP INDEX `manager1_idx`;
ALTER TABLE `trades` DROP INDEX `manager2_idx`;
ALTER TABLE `trades` DROP INDEX `draft_idx`;
ALTER TABLE `trades` ADD INDEX `manager1_idx` (`manager1_id`);
ALTER TABLE `trades` ADD INDEX `manager2_idx` (`manager2_id`);
ALTER TABLE `trades` ADD INDEX `draft_idx` (`draft_id`);

ALTER TABLE `pro_players` ADD FULLTEXT KEY `lastname_idx` (`last_name`);

ALTER TABLE `round_times` DROP INDEX `draft_idx`;
ALTER TABLE `round_times` DROP INDEX `round_idx`;
ALTER TABLE `round_times` ADD INDEX `draft_idx` (`draft_id`);
ALTER TABLE `round_times` ADD INDEX `round_idx` (`draft_round`);

# Convert timezones from locally-set via PHP (PHPDraft <v1.3.x), to UTC based times (v2.0+):
# NOTE: This should only be run once as it's relative: you will
# mangle data if you run it multiple times, hence why it's block-commented out.
# Change 'EST' to whatever timezone you set for PHPDraft <1.3.x
# First, use the query below to ensure the conversion will work *VERY IMPORTANT*:

/*
SET @localTimezone = 'EST';
SET @utcTimezone = 'GMT';

SELECT 
  draft_id,
  CONVERT_TZ(`draft_start_time`, @localTimezone, @utcTimezone) AS 'NEW_DRAFT_START_TIME',
  CONVERT_TZ(`draft_end_time` , @localTimezone, @utcTimezone) AS 'NEW_DRAFT_END_TIME'
FROM `draft`
WHERE `draft_start_time` IS NOT NULL
OR `draft_end_time` IS NOT NULL;


# If you need to check trades, here's the corresponding query:

SET @localTimezone = 'EST';
SET @utcTimezone = 'GMT';

SELECT
  `trade_id`,
  CONVERT_TZ(`trade_time`, @localTimezone, @utcTimezone) AS 'NEW_TRADE_TIME'
FROM `trades`
WHERE `trade_time` IS NOT NULL;
*/

# If you get all NULL values back, the MySQL installation does not have timezone data loaded,
# and you will have to run the query below by replacing the named timezones with their offsets.
# So for instance, 'EST' becomes '-05:00' and GMT becomes '+00:00'
# Use the query above to figure out what works so you don't bork your data with the queries below!

/*
SET @localTimezone = 'EST';
SET @utcTimezone = 'GMT';

UPDATE `draft`
SET `draft_start_time` = CONVERT_TZ(`draft_start_time`, @localTimezone, @utcTimezone)
WHERE `draft_start_time` IS NOT NULL;

UPDATE `draft`
SET `draft_end_time` = CONVERT_TZ(`draft_end_time`, @localTimezone, @utcTimezone)
WHERE `draft_end_time` IS NOT NULL;

UPDATE `trades`
SET `trade_time` = CONVERT_TZ(`trade_time`, @localTimezone, @utcTimezone)
WHERE `trade_time` IS NOT NULL;
*/

# This restores the fkey checks, after having unset them earlier
SET FOREIGN_KEY_CHECKS = 1;