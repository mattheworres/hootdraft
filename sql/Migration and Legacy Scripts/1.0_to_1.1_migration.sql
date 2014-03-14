-- PHPDraft Migration Script
-- Only use if you have a pre-existing PHPDraft database (from version 1.0)
-- *Schema change associated with the addition of the trades table
-- *Schema change associated with the addition of the pro_players table (autocomplete)
-- *Value updates to draft.draft_sport
-- *Bug Fix - NBA position 'Shooting Guard' incorrectly abbreviated 'SH'
--
-- *NOTE: 1.1 adds autocomplete-assisted pick entry, in order to use this feature the
-- pro_players table must be populated! Use the 'Update Pro Players' tool
-- (control_panel.php?action=updateProPlayers) and the provided CSV files for each
-- league (resources/ProPlayers_NFL.csv, etc.) to enable autocomplete.
--
-- No deprecated columns result from this migration.

USE phpdraft;

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

-- --------------------------------------------------------

--
-- Table structure for table `pro_players`
--
CREATE TABLE IF NOT EXISTS `pro_players` (
  `pro_player_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique ID of the player',
  `league` text NOT NULL COMMENT 'Three character abbreviation of league player belongs to.',
  `first_name` text NOT NULL COMMENT 'First name of the player',
  `last_name` text NOT NULL COMMENT 'Last name of the player',
  `position` text NOT NULL COMMENT 'Abbreviation of the position the player plays',
  `team` text NOT NULL COMMENT 'Abbreviation of the city of the team the player plays for',
  PRIMARY KEY (`pro_player_id`),
  KEY `league_idx` (`league`(4)),
  FULLTEXT KEY `firstname_idx` (`first_name`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Pro players used for auto-complete for pick entry' AUTO_INCREMENT=1 ;

--
-- Update draft_sport value to new values
--
UPDATE `draft`
SET `draft_sport` = 'NFL'
WHERE `draft_sport` = 'football';

UPDATE `draft`
SET `draft_sport` = 'NHL'
WHERE `draft_sport` = 'hockey';

UPDATE `draft`
SET `draft_sport` = 'MLB'
WHERE `draft_sport` = 'baseball';

UPDATE `draft`
SET `draft_sport` = 'NBA'
WHERE `draft_sport` = 'basketball';

--
-- Update players with correct position
--
UPDATE `players`
SET `position` = 'SG'
WHERE `position` = 'SH';