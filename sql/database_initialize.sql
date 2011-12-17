-- PHPDraft Database Initialization Script
-- This script requires an empty database to be initialized, and then replace the name below with your database's name.
-- NOTE: Change this to whatever your database is. Make note if your host requires a prefixed name, like ACCOUNT_phpdraft (common on shared hosting)
USE phpdraft;

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Database: `phpdraft`
--

-- --------------------------------------------------------

--
-- Table structure for table `draft`
--

CREATE TABLE IF NOT EXISTS `draft` (
  `draft_id` int(11) NOT NULL auto_increment,
  `draft_name` text NOT NULL,
  `draft_sport` text NOT NULL,
  `draft_status` text NOT NULL,
  `draft_style` text NOT NULL COMMENT 'The style of draft that will take place, either serpentine or cyclical',
  `draft_rounds` int(2) unsigned NOT NULL default '0' COMMENT 'The number of rounds (players per team) to be drafted',
  `draft_password` text COMMENT 'Optional password to make public functions of the draft (view-only) private via password protection.',
  `draft_start_time` datetime default NULL COMMENT 'The timestamp of when the draft begins',
  `draft_end_time` datetime default NULL COMMENT 'The time when the draft was completed.',
  `draft_current_round` int(5) unsigned NOT NULL default '1' COMMENT 'The current round the draft is in',
  `draft_current_pick` int(5) unsigned NOT NULL default '1' COMMENT 'The current pick the draft is on',
  PRIMARY KEY  (`draft_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Stores information for individual drafts, which will have ma' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `managers`
--

CREATE TABLE IF NOT EXISTS `managers` (
  `manager_id` int(11) NOT NULL auto_increment COMMENT 'Unique ID for this manager',
  `draft_id` int(11) NOT NULL default '0' COMMENT 'Foreign key for the draft that this manager belongs to',
  `manager_name` text NOT NULL COMMENT 'Name of this manager (i.e. Bill)',
  `manager_email` text COMMENT 'Email address of this manager',
  `draft_order` tinyint(3) unsigned NOT NULL default '0' COMMENT 'The order in which managers make picks in the draft',
  PRIMARY KEY  (`manager_id`),
  KEY `draft_idx` (`draft_id`),
  FULLTEXT KEY `manager_idx` (`manager_name`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `players`
--

CREATE TABLE IF NOT EXISTS `players` (
  `player_id` int(11) NOT NULL auto_increment COMMENT 'Unique ID for this particular player',
  `manager_id` int(11) NOT NULL default '0' COMMENT 'Foreign key to reference the manager to which this player belongs',
  `first_name` text COMMENT 'The first name of this player',
  `last_name` text COMMENT 'The last name of this player',
  `team` char(3) default NULL COMMENT 'the team abbreviation of this player that is being drafted (three-letter abbreviation)',
  `position` varchar(4) default NULL COMMENT 'The position this player plays',
  `pick_time` datetime default NULL COMMENT 'The timestamp that the pick occurred',
  `pick_duration` int(10) default NULL COMMENT 'The number of seconds that the pick took',
  `draft_id` int(11) unsigned NOT NULL default '0',
  `player_round` int(11) NOT NULL default '0' COMMENT 'The round that this player was drafted in',
  `player_pick` int(11) NOT NULL default '0' COMMENT 'The particular draft pick that this player was taken in.',
  PRIMARY KEY  (`player_id`),
  KEY `manager_idx` (`manager_id`),
  KEY `draft_idx` (`draft_id`),
  FULLTEXT KEY `player_idx` (`first_name`,`last_name`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `user_login`
--
CREATE TABLE IF NOT EXISTS `user_login` (
  `UserID` int(10) unsigned NOT NULL auto_increment COMMENT 'Unique user ID for each user',
  `Username` text NOT NULL COMMENT 'Username for login',
  `Password` text NOT NULL COMMENT 'Password for username',
  `Name` varchar(15) NOT NULL default '' COMMENT 'The plaintext name of the site owner',
  PRIMARY KEY  (`UserID`),
  UNIQUE KEY `UserID` (`UserID`)
) ENGINE=MyISAM  
DEFAULT CHARSET=latin1 
COMMENT='Will store logins for users of the PHPDraft system' 
AUTO_INCREMENT=100001 ;

--
-- Dumping data for table `user_login`
--
INSERT INTO `user_login` (`UserID`, `Username`, `Password`, `Name`) VALUES
(99999, 'admin_commish', '5baa61e4c9b93f3f0682250b6cf8331b7ee68fd8', 'Matthew Orres');

-- --------------------------------------------------------

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