<?php

$colors = array(
	"light_orange" => "#FFCC66",
	"light_blue" => "#CCFFFF",
	"light_yellow" => "#FFFF99",
	"light_red" => "#99CC99",
	"dark_green" => "#99CC99",
	"light_purple" => "#CCCCFF",
	"light_green" => "#CCFFCC",
	"light_pink" => "#FFCCFF",
	"taupe" => "#99CCCC"
);

$position_colors = Array(
   //Hockey/NHL
	"LW" => $colors['light_orange'],	//LT ORANGE		*
	"C" => $colors['light_blue'],	//LT BLUE		*
	"RW" => $colors['light_yellow'],	//LT YELLOW	*
	"D" => $colors['light_red'],	//LT RED		*
	"G" => $colors['dark_green'],	//DK GREEN		*

	//Football/NFL
	"QB" => $colors['light_blue'], //LT BLUE
	"RB" => $colors['light_orange'], //LT ORANGE
	"WR" => $colors['light_yellow'], //LT YELLOW
	"TE" => $colors['light_red'], //LT RED
	"DEF" => $colors['dark_green'], //DK GREEN
	"K" => $colors['light_purple'], //LT PURPLE		*

	//Baseball/MLB
	//CATCHER ALREADY DONE: LT BLUE
	"1B" => $colors['light_orange'], //LT ORANGE
	"2B" => $colors['light_yellow'], //LT YELLOW
	"3B" => $colors['light_red'], //LT RED
	"SS" => $colors['dark_green'], //DK GREEN
	"OF" => $colors['light_green'], //LT GREEN		*
	"UTIL" => $colors['light_pink'], //LT PINK		*
	"SP" => $colors['light_purple'], //LT PURPLE
	"RP" => $colors['taupe'], //TAUPE?				*

	//Basketball/NBA
	"PG" => $colors['light_orange'],	//LT ORANGE
	"SH" => $colors['dark_green'],	//DK GREEN
	"SF" => $colors['light_yellow'],	//LT YELLOW
	"PF" => $colors['light_red'],	//LT RED
	//CENTER ALREADY DONE: LT BLUE
);

$nhl_teams = Array(
	"ANA" => "Anaheim Ducks",
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
	"WAS" => "Washington Capitals",
	"WPG" => "Winnipeg Jets"
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
	/*"DL" => "Defensive Lineman",
	"LB" => "Linebacker",
	"DB" => "Defensive Back",
	"OL" => "Offensive Lineman"*/
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
