-- phpMyAdmin SQL Dump
-- version 2.11.10
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Aug 07, 2010 at 09:20 AM
-- Server version: 4.1.22
-- PHP Version: 5.2.6

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Database: `scsports_phpdraft`
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

--
-- Dumping data for table `draft`
--


-- --------------------------------------------------------

--
-- Table structure for table `managers`
--

CREATE TABLE IF NOT EXISTS `managers` (
  `manager_id` int(11) NOT NULL auto_increment COMMENT 'Unique ID for this manager',
  `draft_id` int(11) NOT NULL default '0' COMMENT 'Foreign key for the draft that this manager belongs to',
  `manager_name` text NOT NULL COMMENT 'Name of this manager (i.e. Bill)',
  `team_name` text COMMENT 'Name of the team this manager manages (i.e. Bill''s Buffalos)',
  `draft_order` tinyint(3) unsigned NOT NULL default '0' COMMENT 'The order in which managers make picks in the draft',
  PRIMARY KEY  (`manager_id`),
  KEY `draft_idx` (`draft_id`),
  FULLTEXT KEY `manager_idx` (`manager_name`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `managers`
--


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

--
-- Dumping data for table `players`
--


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
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COMMENT='Will store logins for users of the PHPDraft system' AUTO_INCREMENT=100001 ;

--
-- Dumping data for table `user_login`
--

INSERT INTO `user_login` (`UserID`, `Username`, `Password`, `Name`) VALUES
(99999, 'admin_commish', '5baa61e4c9b93f3f0682250b6cf8331b7ee68fd8', 'Matthew Orres');
