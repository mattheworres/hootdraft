# This is a fix for InnoDB in MySQL >= 4.1.x
# It "suspends judgement" for fkey relationships until are tables are set.
SET FOREIGN_KEY_CHECKS = 0;

use phpdraft;

DROP TABLE IF EXISTS `depth_chart_positions`;

ALTER TABLE `draft` DROP COLUMN `using_depth_charts`;

DROP INDEX `depth_chart_idx` ON `players`;
ALTER TABLE `players` DROP COLUMN `depth_chart_position_id`;
ALTER TABLE `players` DROP COLUMN `position_eligibility`;

# This restores the fkey checks, after having unset them earlier
SET FOREIGN_KEY_CHECKS = 1;