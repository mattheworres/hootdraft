-- PHPDraft Migration Script
-- Only use if you have a pre-existing PHPDraft database (from version 1.0)
-- This migration script only handles the schema change associated with the addition of the
-- trades feature, which requires two new tables.
--
-- No deprecated columns result from this migration.

--
-- Table structure for table `trades`
--
CREATE TABLE IF NOT EXISTS `trades` (
  `trade_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique ID for a trade',
  `draft_id` int(11) unsigned NOT NULL COMMENT 'FK to the draft',
  `manager1_id` int(11) unsigned NOT NULL COMMENT 'FK to manager 1',
  `manager2_id` int(11) unsigned NOT NULL COMMENT 'FK to manager 2',
  `trade_time` datetime DEFAULT NULL COMMENT 'The timestamp that the pick occurred',
  PRIMARY KEY (`trade_id`),
  KEY `manager1_idx` (`manager1_id`),
  KEY `manager2_idx` (`manager2_id`),
  KEY `draft_idx` (`draft_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Table to track trades that occur in a draft.' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `trade_assets`
--
CREATE TABLE IF NOT EXISTS `trade_assets` (
`tradeasset_id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY COMMENT 'Unique ID for the asset',
`trade_id` INT( 11 ) UNSIGNED NOT NULL COMMENT 'FK for the trade this asset was involved in',
`player_id` INT( 11 ) UNSIGNED NOT NULL COMMENT 'FK to the pick or player this asset is related to',
`oldmanager_id` INT( 11 ) UNSIGNED NOT NULL COMMENT 'FK to the manager this asset used to belong to',
`newmanager_id` INT( 11 ) UNSIGNED NOT NULL COMMENT 'FK to the manager this asset belongs to after trade',
`was_drafted` TINYINT( 1 ) NOT NULL COMMENT 'Boolean whether the asset was a drafted player or not at the time of trade',
INDEX ( `trade_id` , `player_id` , `oldmanager_id` , `newmanager_id` )
) ENGINE = MYISAM DEFAULT CHARSET=latin1 COMMENT = 'Table that tracks the assets that exchanged hands in a trade.' AUTO_INCREMENT=1;