# This is a fix for InnoDB in MySQL >= 4.1.x
# It "suspends judgement" for fkey relationships until are tables are set.
SET FOREIGN_KEY_CHECKS = 0;

use phpdraft;

CREATE TABLE `depth_chart_positions` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `draft_id` int(11) NOT NULL,
    `position` varchar(6) NOT NULL,
    `slots` int(5) NOT NULL,
    `display_order` tinyint(3) unsigned NOT NULL default '0',
    PRIMARY KEY  (`id`),
    INDEX `draft_idx` (`draft_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

# This restores the fkey checks, after having unset them earlier
SET FOREIGN_KEY_CHECKS = 1;

ALTER TABLE `draft` ADD `using_depth_charts` TINYINT(1) NOT NULL DEFAULT '0' AFTER `nfl_extended`;

ALTER TABLE `players` ADD `depth_chart_position_id` INT(11) NULL AFTER `player_pick`;
ALTER TABLE `players` ADD `position_eligibility` VARCHAR(24) NULL AFTER `depth_chart_position_id`;
ALTER TABLE `players` ADD INDEX `depth_chart_idx` (`depth_chart_position_id`);

# This restores the fkey checks, after having unset them earlier
SET FOREIGN_KEY_CHECKS = 1;