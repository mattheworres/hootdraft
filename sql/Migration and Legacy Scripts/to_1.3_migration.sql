-- PHPDraft Migration Script
-- ONLY USE IF you have a pre-existing PHPDraft database (from version before version 1.3.0)
-- *Add round_times table for draft board round times
-- No deprecated columns result from this migration.

USE phpdraft;

--
-- Add the new table for storing round times
--
CREATE TABLE IF NOT EXISTS `round_times` (
  `round_time_id` int(11) NOT NULL auto_increment,
  `draft_id` int(11) NOT NULL COMMENT 'The foreign key to the draft this time belongs to.',
  `is_static_time` tinyint(1) COMMENT 'A boolean determining if this round time is the same across all rounds (true) or is specified per each round. If true, draft round can be null.',
  `draft_round` int(2) COMMENT 'The round this time applies to.',
  `round_time_seconds` int(11) COMMENT 'The amount of time in seconds (total) that the round timer should be for.',
  PRIMARY KEY  (`round_time_id`),
  KEY `draft_idx` (`draft_id`),
  KEY `round_idx` (`draft_round`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Stores information for the amounts of time (in seconds) that timers should run for draft rounds' AUTO_INCREMENT=1 ;