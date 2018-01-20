-- Change all tables charsets to UTF-8 & collations to utf8_general_ci

ALTER TABLE users CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE draft CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE managers CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE players CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE trades CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE trade_assets CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE pro_players CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE round_times CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE draft_stats CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE depth_chart_positions CHARACTER SET utf8 COLLATE utf8_general_ci;

-- Change individual column charsets to UTF-8 & collations to utf8_general_ci

ALTER TABLE draft
  MODIFY draft_name TEXT CHARACTER SET utf8 COLLATE utf8_general_ci,
  MODIFY draft_sport TEXT CHARACTER SET utf8 COLLATE utf8_general_ci,
  MODIFY draft_status TEXT CHARACTER SET utf8 COLLATE utf8_general_ci,
  MODIFY draft_style TEXT CHARACTER SET utf8 COLLATE utf8_general_ci,
  MODIFY draft_password TEXT CHARACTER SET utf8 COLLATE utf8_general_ci;

ALTER TABLE depth_chart_positions MODIFY position VARCHAR(6) CHARACTER SET utf8 COLLATE utf8_general_ci;

ALTER TABLE draft_stats
  MODIFY longest_avg_pick_manager_name TEXT CHARACTER SET utf8 COLLATE utf8_general_ci,
  MODIFY shortest_avg_pick_manager_name TEXT CHARACTER SET utf8 COLLATE utf8_general_ci,
  MODIFY longest_single_pick_manager_name TEXT CHARACTER SET utf8 COLLATE utf8_general_ci,
  MODIFY shortest_single_pick_manager_name TEXT CHARACTER SET utf8 COLLATE utf8_general_ci,
  MODIFY most_drafted_team TEXT CHARACTER SET utf8 COLLATE utf8_general_ci,
  MODIFY least_drafted_team TEXT CHARACTER SET utf8 COLLATE utf8_general_ci,
  MODIFY most_drafted_position TEXT CHARACTER SET utf8 COLLATE utf8_general_ci,
  MODIFY least_drafted_position TEXT CHARACTER SET utf8 COLLATE utf8_general_ci;

ALTER TABLE managers MODIFY manager_name TEXT CHARACTER SET utf8 COLLATE utf8_general_ci;

ALTER TABLE players
  MODIFY first_name TEXT CHARACTER SET utf8 COLLATE utf8_general_ci,
  MODIFY last_name TEXT CHARACTER SET utf8 COLLATE utf8_general_ci,
  MODIFY team CHAR(3) CHARACTER SET utf8 COLLATE utf8_general_ci,
  MODIFY position VARCHAR(4) CHARACTER SET utf8 COLLATE utf8_general_ci,
  MODIFY position_eligibility VARCHAR(24) CHARACTER SET utf8 COLLATE utf8_general_ci;

ALTER TABLE pro_players
  MODIFY league TEXT CHARACTER SET utf8 COLLATE utf8_general_ci,
  MODIFY first_name TEXT CHARACTER SET utf8 COLLATE utf8_general_ci,
  MODIFY last_name TEXT CHARACTER SET utf8 COLLATE utf8_general_ci,
  MODIFY position TEXT CHARACTER SET utf8 COLLATE utf8_general_ci,
  MODIFY team TEXT CHARACTER SET utf8 COLLATE utf8_general_ci;

-- Create phinxlog table
CREATE TABLE `phinxlog` (
  `version` BIGINT(20) NOT NULL,
  migration_name VARCHAR(100) DEFAULT NULL,
  start_time TIMESTAMP NULL DEFAULT NULL,
  end_time TIMESTAMP NULL DEFAULT NULL,
  breakpoint TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY  (`version`)
) ENGINE=`InnoDB` DEFAULT CHARSET=`utf8`;

-- Insert rows into phinxlog table to denote the upgrade occurred (manually)
INSERT INTO `phinxlog` (`version`, `migration_name`, `start_time`, `end_time`, `breakpoint`)
VALUES
  (20170324024149, 'CreateUsers', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, 0),
  (20170324024150, 'CreateDrafts', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, 0),
  (20170324024151, 'CreateManagers', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, 0),
  (20170324024152, 'CreatePlayers', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, 0),
  (20170324024153, 'CreateTrades', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, 0),
  (20170324024154, 'CreateTradeAssets', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, 0),
  (20170324024155, 'CreateProPlayers', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, 0),
  (20170324024156, 'CreateRoundTimes', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, 0),
  (20170324024157, 'CreateDraftStats', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, 0),
  (20170324024158, 'CreateDepthChartPositions', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, 0),
  (20170324024159, 'FixUsersVerificationColumn', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, 0);
