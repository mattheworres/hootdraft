<?php

function get_last_ten_picks($draft_id) {
    //Get the entire pick row for a draft for the last ten picks
    //Returns resultset
    $sql = "SELECT *, m.manager_name ".
	    "FROM players ".
	    "LEFT OUTER JOIN managers m ".
	    "ON m.manager_id = players.manager_id ".
	    "WHERE players.draft_id = '".$draft_id."' ".
	    "AND pick_time IS NOT NULL ".
	    "AND pick_duration IS NOT NULL ".
	    "ORDER BY player_pick DESC LIMIT 10";
    
    $picks_result = mysql_query($sql);

    return $picks_result;
}

function get_current_pick($draft_id) {
    $draft_row = mysql_fetch_array(mysql_query("SELECT draft_current_round, draft_current_pick FROM draft WHERE draft_id = ".$draft_id));
    
    $sql = "SELECT *, m.* ".
	    "FROM players ".
	    "LEFT OUTER JOIN managers m ".
	    "ON m.manager_id = players.manager_id ".
	    "WHERE players.draft_id = '".$draft_id."' ".
	    "AND player_round = '".$draft_row['draft_current_round']."' ".
	    "AND player_pick = '".$draft_row['draft_current_pick']."' ".
	    "LIMIT 1";

    $current_pick = mysql_fetch_array(mysql_query($sql));

    return $current_pick;
}

function get_next_pick($draft_id, $current_pick) {
    $sql = "SELECT player_round, player_pick FROM players ".
	    "WHERE draft_id = '".$draft_id."' ".
	    "AND player_pick = '".($current_pick+1)."' LIMIT 1";
    $next_pick = mysql_query($sql);
    if(mysql_num_rows($next_pick) == 0)
	return false;
    else
	$next_pick = mysql_fetch_array($next_pick);

    return $next_pick;
}

function get_next_picks($draft_id, $current_pick) {
    $sql = "SELECT p.*, m.manager_name ".
	    "FROM players p ".
	    "LEFT OUTER JOIN managers m ".
	    "ON m.manager_id = p.manager_id ".
	    "WHERE p.draft_id = '".$draft_id."' ".
	    "AND p.player_pick > '".$current_pick."'".
	    "ORDER BY p.player_pick ASC ".
	    "LIMIT 5";
    $result = mysql_query($sql);

    return $result;
}

function search_draft($draft_id, $search_terms) {
    $sql = "SELECT p.*, m.manager_name,  MATCH (p.first_name, p.last_name) AGAINST ('".$search_terms."') as score ".
	    "FROM players p ".
	    "LEFT OUTER JOIN managers m ".
	    "ON m.manager_id = p.manager_id ".
	    "WHERE MATCH (p.first_name, p.last_name) AGAINST ('".$search_terms."') ".
	    "AND p.draft_id = '".$draft_id."' ".
	    "ORDER BY score ASC, p.player_pick DESC";

    $result = mysql_query($sql);

    return $result;
}

function search_draft_basic($draft_id, $search_terms) {
    $sql = "SELECT p.*, m.manager_name ".
	    "FROM players p ".
	    "LEFT OUTER JOIN managers m ".
	    "ON m.manager_id = p.manager_id ".
	    "WHERE p.draft_id = '".$draft_id."' ".
	    "AND (p.first_name LIKE '%".$search_terms."%'".
	    "OR p.last_name LIKE '%".$search_terms."%')".
	    "ORDER BY p.player_pick DESC";

    $result = mysql_query($sql);

    return $result;

}

function get_last_picks($draft_id) {
    $sql = "SELECT p.*, m.manager_name ".
	    "FROM players p ".
	    "LEFT OUTER JOIN managers m ".
	    "ON m.manager_id = p.manager_id ".
	    "WHERE p.draft_id = '".$draft_id."' ".
	    "AND p.pick_time IS NOT NULL ".
	    "ORDER BY p.player_pick DESC ".
	    "LIMIT 5";
    $result = mysql_query($sql);

    return $result;
}

function get_managers($draft_id) {
    $sql = "SELECT * ".
	    "FROM managers ".
	    "WHERE draft_id = '".$draft_id."' ".
	    "ORDER BY draft_order";
    $manager_result = mysql_query($sql);

    return $manager_result;
}

function get_summary_stats($draft_id) {
    /*
     * $stats is an array that holds all of the mysql arrays of query information
     * Stat columns that are found in this array at the end of this function:
     * 'Average_Time' -> Average time taken for a pick
     * 'Average_Time_High' -> Highest average pick duration
     * 'Average_Time_Low' -> Lowest average pick duration
     * 'Round_Time_High' -> Highest single round duration (summation of all pick durations)
     * 'Round_Time_Low' -> Lowest single round duration (summation of all pick durations)
     * 'High_Time' -> Highest single pick duration
     * 'Low_Time' -> Lowest single pick duration
     * 'High_Team' -> Most drafted pro team
     * 'Low_Team' -> Least drafted pro team
     * 'High_Position' -> Most drafted player position
     * 'Low_Position' -> Least drafted player position
     */
    $stats = Array();
    $sql = "SELECT avg(pick_duration) as pick_average
	    FROM players p
	    WHERE p.draft_id = ".$draft_id."
	    LIMIT 1";

    $row = mysql_fetch_array(mysql_query($sql));
    $stats['Average_Time'] = $row;

    $sql = "SELECT p.pick_duration, m.manager_name, avg(pick_duration) as pick_average
	    FROM players p
	    LEFT OUTER JOIN managers m
	    ON m.manager_id = p.manager_id
	    WHERE p.draft_id = ".$draft_id."
	    GROUP BY m.manager_name 
	    ORDER BY pick_average DESC
	    LIMIT 1";

    $row = mysql_fetch_array(mysql_query($sql));
    $stats['Average_Time_High'] = $row;

    $sql = "SELECT p.pick_duration, m.manager_name, avg(pick_duration) as pick_average
	    FROM players p
	    LEFT OUTER JOIN managers m
	    ON m.manager_id = p.manager_id
	    WHERE p.draft_id = ".$draft_id."
	    GROUP BY m.manager_name
	    ORDER BY pick_average ASC
	    LIMIT 1";

    $row = mysql_fetch_array(mysql_query($sql));
    $stats['Average_Time_Low'] = $row;

    $sql = "SELECT DISTINCT p.player_round, sum( p.pick_duration ) AS round_time
	    FROM players p
	    WHERE p.draft_id = ".$draft_id."
	    AND p.pick_duration IS NOT NULL
	    GROUP BY player_round
	    ORDER BY round_time DESC
	    LIMIT 1";

    $row = mysql_fetch_array(mysql_query($sql));
    $stats['Round_Time_High'] = $row;

    $sql = "SELECT DISTINCT p.player_round, sum( p.pick_duration ) AS round_time
	    FROM players p
	    WHERE p.draft_id = ".$draft_id."
	    AND p.pick_duration IS NOT NULL
	    GROUP BY player_round
	    ORDER BY round_time ASC
	    LIMIT 1";

    $row = mysql_fetch_array(mysql_query($sql));
    $stats['Round_Time_Low'] = $row;

    $sql = "SELECT p.pick_duration, p.player_pick, m.manager_name, max(pick_duration) as pick_max
	    FROM players p
	    LEFT OUTER JOIN managers m
	    ON m.manager_id = p.manager_id
	    WHERE p.draft_id = ".$draft_id."
	    GROUP BY m.manager_name
	    ORDER BY pick_max DESC
	    LIMIT 1";

    $row = mysql_fetch_array(mysql_query($sql));
    $stats['High_Time'] = $row;

    $sql = "SELECT p.pick_duration, p.player_pick, m.manager_name, min(pick_duration) as pick_min
	    FROM players p
	    LEFT OUTER JOIN managers m
	    ON m.manager_id = p.manager_id
	    WHERE p.draft_id = ".$draft_id."
	    GROUP BY m.manager_name
	    ORDER BY pick_min ASC
	    LIMIT 1";

    $row = mysql_fetch_array(mysql_query($sql));
    $stats['Low_Time'] = $row;

     $sql = "SELECT DISTINCT p.team, count(team) as team_occurences
	    FROM players p
	    WHERE p.draft_id = ".$draft_id."
	    AND p.team IS NOT NULL
	    GROUP BY team
	    ORDER BY team_occurences DESC
	    LIMIT 1";

    $row = mysql_fetch_array(mysql_query($sql));
    $stats['High_Team'] = $row;

    $sql = "SELECT DISTINCT p.team, count(team) as team_occurences
	    FROM players p
	    WHERE p.draft_id = ".$draft_id."
	    AND p.team IS NOT NULL
	    GROUP BY team
	    ORDER BY team_occurences ASC
	    LIMIT 1";

    $row = mysql_fetch_array(mysql_query($sql));
    $stats['Low_Team'] = $row;

    $sql = "SELECT DISTINCT p.position, count(position) as position_occurences
	    FROM players p
	    WHERE p.draft_id = ".$draft_id."
	    AND p.position IS NOT NULL
	    GROUP BY position
	    ORDER BY position_occurences DESC
	    LIMIT 1";

    $row = mysql_fetch_array(mysql_query($sql));
    $stats['High_Position'] = $row;

    $sql = "SELECT DISTINCT p.position, count(position) as position_occurences
	    FROM players p
	    WHERE p.draft_id = ".$draft_id."
	    AND p.position IS NOT NULL
	    GROUP BY position
	    ORDER BY position_occurences ASC
	    LIMIT 1";

    $row = mysql_fetch_array(mysql_query($sql));
    $stats['Low_Position'] = $row;

    return $stats;
}

function get_manager($draft_id, $manager_id) {
    $sql = "SELECT * ".
	    "FROM managers ".
	    "WHERE draft_id = '".$draft_id."' ".
	    "AND manager_id = '".$manager_id."' ".
	    "LIMIT 1";

    $manager_result = mysql_query($sql);

    return $manager_result;
}

function get_previous_time($draft_id, $pick) {
    $sql = "SELECT pick_time FROM players WHERE draft_id = '".$draft_id."' AND player_pick = '".($pick - 1)."' LIMIT 1";
    
    $result = mysql_query($sql);
    $row = mysql_fetch_array($result);
    return $row;
}

function get_round_picks($draft_id, $round, $sort = "DESC") {
    $sql = "SELECT p.*, m.manager_name
	FROM players p
	LEFT OUTER JOIN managers m
	ON m.manager_id = p.manager_id
	WHERE p.draft_id = '".$draft_id."'
	AND p.player_round = '".$round."'
	AND p.pick_time IS NOT NULL ";
    if($sort == "DESC")
        $sql .= "ORDER BY p.player_pick DESC";
    else
	$sql .= "ORDER BY p.player_pick ASC";

    $result = mysql_query($sql);

    return $result;
}

function get_all_round_picks($draft_id, $round, $sort = "ASC") {
    $sql = "SELECT p.*, m.manager_name
	FROM players p
	LEFT OUTER JOIN managers m
	ON m.manager_id = p.manager_id
	WHERE p.draft_id = '".$draft_id."'
	AND p.player_round = '".$round."' ";
    if($sort == "DESC")
	$sql .= "ORDER BY p.player_pick DESC";
    else
        $sql .= "ORDER BY p.player_pick ASC";

    $result = mysql_query($sql);

    return $result;
}

function get_team_picks($draft_id, $manager_id) {
    $sql = "SELECT p.*
	FROM players p
	WHERE p.draft_id = '".$draft_id."'
	AND p.manager_id = '".$manager_id."'
	AND p.pick_time IS NOT NULL
        ORDER BY p.player_pick ASC";

    $result = mysql_query($sql);

    return $result;
}

function seconds_to_words($seconds) {
    /*** return value ***/
    $words = "";

    /*** get the years ***/
    $years = intval(intval($seconds) / 31536000);
    if($years > 0) {
	$seconds -= ($years * 31536000);
	$words .= $years ." years, ";
    }

    /*** get the weeks ***/
    $weeks = intval(intval($seconds) / 604800);
    if($weeks > 0) {
	$seconds -= ($weeks * 604800);
	$words .= $weeks ." weeks, ";
    }

    /*** get the days ***/
    $days = intval(intval($seconds) / 86400);
    if($days > 0) {
	$seconds -= ($days * 86400);
	$words .= $days ." days, ";
    }

    /*** get the hours ***/
    $hours = intval(intval($seconds) / 3600);
    if($hours > 0){
        $words .= $hours ." hours, ";
    }
    /*** get the minutes ***/
    $minutes = bcmod((intval($seconds) / 60),60);
    if($hours > 0 || $minutes > 0){
        $words .= $minutes ." minutes, ";
    }

    /*** get the seconds ***/
    $seconds = bcmod(intval($seconds),60);
    $words .= $seconds ." seconds";

    return $words;
}

function is_undrafted($draft_id) {
    //Checks to see if a draft is undrafted.
    $sql = "SELECT draft_status FROM draft WHERE draft_id = '".$draft_id."' LIMIT 1";
    $draft_result = mysql_query($sql);
    $draft_row = mysql_fetch_array($draft_result);

    if($draft_row['draft_status'] == "undrafted")
	return true;
    else
	return false;
}

//Constants
$position_colors = Array(
   "LW" => "#FFCC66",	//LT ORANGE	*
    "C" => "#CCFFFF",	//LT BLUE	*
    "RW" => "#FFFF99",	//LT YELLOW	*
    "D" => "#FF9999",	//LT RED	*
    "G" => "#99CC99",	//DK GREEN	*

    "QB" => "#CCFFFF", //LT BLUE
    "RB" => "#FFCC66", //LT ORANGE
    "WR" => "#FFFF99", //LT YELLOW
    "TE" => "#FF9999", //LT RED
    "DEF" => "#99CC99", //DK GREEN
    "K" => "#CCCCFF", //LT PURPLE	*

    //CATCHER ALREADY DONE: LT BLUE
    "1B" => "#FFCC66", //LT ORANGE
    "2B" => "#FFFF99", //LT YELLOW
    "3B" => "#FF9999", //LT RED
    "SS" => "#99CC99", //DK GREEN
    "OF" => "#CCFFCC", //LT GREEN	*
    "UTIL" => "#FFCCFF", //LT PINK	*
    "SP" => "#CCCCFF", //LT PURPLE
    "RP" => "#99CCCC", //TAUPE?		*

    "PG" => "#FFCC66",	//LT ORANGE
    "SH" => "#99CC99",	//DK GREEN
    "SF" => "#FFFF99",	//LT YELLOW
    "PF" => "#FF9999",	//LT RED
    //CENTER ALREADY DONE: LT BLUE
);

$nhl_teams = Array(
	"ANA" => "Anaheim Ducks",
	"ATL" => "Atlanta Thrashers",
	"BOS" => "Boston Bruins",
	"BUF" => "Buffalo Sabres",
	"CGY" => "Calgary Flames",
	"CAR" => "Carolina Hurricanes",
	"CHI" => "Chicago Blackhawks",
	"COL" => "Colorado Avalanche",
	"CBS" => "Columbus Bluejackets",
	"DAL" => "Dallas Stars",
	"DET" => "Detroit Red Wings",
	"EDM" => "Edmonton Oilers",
	"FLA" => "Florida Panthers",
	"LAK" => "Los Angeles Kings",
	"MIN" => "Minnesota Wild",
	"MTL" => "Montreal Canadiens",
	"NSH" => "Nashville Predators",
	"NJD" => "New Jersey Devils",
	"NYI" => "New York Islanders",
	"NYR" => "New York Rangers",
	"OTT" => "Ottawa Senators",
	"PHI" => "Philadelphia Flyers",
	"PHO" => "Phoenix Coyotes",
	"PIT" => "Pittsburgh Penguins",
	"SJS" => "San Jose Sharks",
	"STL" => "St Louis Blues",
	"TAM" => "Tampa Bay Lightning",
	"TOR" => "Toronto Maple Leafs",
	"VAN" => "Vancouver Canucks",
	"WAS" => "Washington Capitals"
);

$nhl_positions = Array(
  "LW" => "Left Wing",
    "RW" => "Right Wing",
    "C" => "Center",
    "D" => "Defenseman",
    "G" => "Goaltender"
);

$nfl_teams = Array(
    "ARI" => "Arizona Cardinals",
    "ATL" => "Atlanta Falcons",
    "BAL" => "Baltimore Ravens",
    "BUF" => "Buffalo Bills",
    "CAR" => "Carolina Panthers",
    "CHI" => "Chicago Bears",
    "CIN" => "Cincinatti Bengals",
    "CLE" => "Cleveland Browns",
    "DAL" => "Dallas Cowboys",
    "DEN" => "Denver Broncos",
    "DET" => "Detroit Lions",
    "GNB" => "Green Bay Packers",
    "HOU" => "Houston Texans",
    "IND" => "Indianapolis Colts",
    "JAC" => "Jacksonville Jaguars",
    "K.C" => "Kansas City Chiefs",
    "MIA" => "Miami Dolphins",
    "MIN" => "Minnesota Vikings",
    "NWE" => "New England Patriots",
    "NOR" => "New Orleans Saints",
    "NYG" => "New York Giants",
    "NYJ" => "New York Jets",
    "OAK" => "Oakland Raiders",
    "PHI" => "Philadelphia Eagles",
    "PIT" => "Pittsburgh Steelers",
    "SDG" => "San Diego Chargers",
    "SFO" => "San Francisco 49ers",
    "SEA" => "Seattle Seahawks",
    "STL" => "St Louis Rams",
    "TAM" => "Tampa Bay Buccaneers",
    "TEN" => "Tennessee Titans",
    "WAS" => "Washington Redskins"
);

$nfl_positions = Array(
    "QB" => "Quarterback",
    "RB" => "Runningback",
    "WR" => "Wide Receiver",
    "TE" => "Tight End",
    "DEF" => "Defense",
    "K" => "Kicker"
);

$mlb_teams = Array(
    "ARI" => "Arizona Diamondbacks",
    "ATL" => "Atlanta Braves",
    "BAL" => "Baltimore Orioles",
    "BOS" => "Boston Redsox",
    "CHC" => "Chicago Cubs",
    "CWS" => "Chicago White Sox",
    "CIN" => "Cincinatti Reds",
    "CLE" => "Cleveland Indians",
    "COL" => "Colorado Rockies",
    "DET" => "Detroit Tigers",
    "FLA" => "Florida Marlins",
    "HOU" => "Houston Astros",
    "K.C" => "Kansas City Royals",
    "LAA" => "Los Angeles Angels",
    "LAD" => "Los Angeles Dodgers",
    "MIL" => "Milwaukee Brewers",
    "MIN" => "Minnesota Twins",
    "NYM" => "New York Mets",
    "NYY" => "New York Yankees",
    "OAK" => "Oakland Athletics",
    "PHI" => "Philadelphia Phillies",
    "PIT" => "Pittsburgh Pirates",
    "SDG" => "San Diego Chargers",
    "SFO" => "San Francisco Giants",
    "SEA" => "Seattle Mariners",
    "STL" => "St Louis Cardinals",
    "TAM" => "Tampa Bay Rays",
    "TEX" => "Texas Rangers",
    "TOR" => "Toronto Bluejays",
    "WAS" => "Washington Nationals"
);

$mlb_positions = Array(
  "C" => "Catcher",
    "1B" => "1st Base",
    "2B" => "2nd Base",
    "3B" => "3rd Base",
    "SS" => "Shortstop",
    "OF" => "Outfielder",
    "UTIL" => "Utility",
    "SP" => "Starting Pitcher",
    "RP" => "Relief Pitcher"
);

$nba_teams = Array(
    "ATL" => "Atlanta Hawks",
    "BOS" => "Boston Celtics",
    "CHI" => "Chicago Bulls",
    "CHA" => "Charlotte Bobcats",
    "CLE" => "Cleveland Cavaliers",
    "DAL" => "Dallas Mavericks",
    "DEN" => "Denver Nuggets",
    "GSW" => "Golden State Warriors",
    "HOU" => "Houston Rockets",
    "IND" => "Indiana Pacers",
    "LAC" => "Los Angeles Clippers",
    "LAL" => "Los Angeles Lakers",
    "MIL" => "Milwaukee Bucks",
    "NJN" => "New Jersey Nets",
    "NYK" => "New York Knicks",
    "PHI" => "Philadelphia Sixers",
    "PHO" => "Phoenix Suns",
    "POR" => "Portland Blazers",
    "SAC" => "Sacramento Kings",
    "SAS" => "San Antonio Spurs",
    "OKC" => "Oklahoma City Thunder",
    "UTH" => "Utah Jazz",
    "WAS" => "Washington Wizards",
    "NOR" => "New Orleans Hornets",
    "MIA" => "Miami Heat",
    "MIN" => "Minnesota Wolves",
    "ORL" => "Orlando Magic",
    "TOR" => "Toronto Raptors",
    "MEM" => "Memphis Grizzlies"
);

$nba_positions = Array(
  "PG" => "Point Guard",
    "SH" => "Shooting Guard",
    "SF" => "Small Forward",
    "PF" => "Power Forward",
    "C" => "Center"
);

?>
