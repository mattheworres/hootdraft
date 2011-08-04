-- PHPDraft Migration Script
-- Only use if you have a pre-existing PHPDraft database (from beta version 0.9)
-- This migration script only handles one schema change, where team_name (a string) for a manager
-- has been replaced by their email. team_name was never used, and manager_email isn't right now,
-- but it's surely more useful in the future. team_name proved confusing anyways.
--
-- This will simply leave team_name intact, and just change the comment to denote that it has been deprecated.
-- If you have all data you need, feel free to delete team_name as it's not used at all.

ALTER TABLE `managers` ADD COLUMN `manager_email` text COMMENT 'Email address of this manager' AFTER `manager_name`;

ALTER TABLE `managers`  CHANGE COLUMN `team_name` `team_name` TEXT NULL COMMENT 'DEPRECATED' AFTER `manager_email`;