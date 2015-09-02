<?php

class sports_values_library {

  public function getTeams($pro_league) {
    switch (strtolower($pro_league)) {
      case 'nhl':
      case'hockey':
        return $this->nhl_teams;
        break;
      case 'nfl':
      case 'football':
        return $this->nfl_teams;
        break;
      case 'mlb':
      case 'baseball':
        return $this->mlb_teams;
        break;
      case 'nba':
      case 'basketball':
        return $this->nba_teams;
        break;
    }
  }

  public function getPositions($pro_league) {
    switch (strtolower($pro_league)) {
      case 'nhl':
      case 'hockey':
        return $this->nhl_positions;
        break;
      case 'nfl':
      case 'football':
        return $this->nfl_positions;
        break;
      case 'mlb':
      case 'baseball':
        return $this->mlb_positions;
        break;
      case 'nba':
      case 'basketball':
        return $this->nba_positions;
        break;
    }
  }

  public function __construct() {
    global $PHPD; /* @var PHPD PHPDRAFT */

    $this->colors = array(
        "light_orange" => "#FFCC66",
        "light_blue" => "#CCFFFF",
        "light_yellow" => "#FFFF99",
        "light_red" => "#FF9999",
        "dark_green" => "#99CC99",
        "light_purple" => "#CCCCFF",
        "light_green" => "#CCFFCC",
        "light_pink" => "#FFCCFF",
        "seafoam" => "#99CCCC",
        "switchgrass" => "#DAFF7F",
        "gray" => "#99CCCC"
    );

    // <editor-fold defaultstate="collapsed" desc="Position Colors">
    $this->position_colors = array(
        //Hockey/NHL
        "LW" => $this->colors['light_orange'], //LT ORANGE		*
        "C" => $this->colors['light_blue'], //LT BLUE		*
        "RW" => $this->colors['light_yellow'], //LT YELLOW	*
        "D" => $this->colors['light_red'], //LT RED		*
        "G" => $this->colors['dark_green'], //DK GREEN		*
        //Football/NFL
        "QB" => $this->colors['light_blue'], //LT BLUE
        "RB" => $this->colors['light_orange'], //LT ORANGE
        "WR" => $this->colors['light_yellow'], //LT YELLOW
        "TE" => $this->colors['light_red'], //LT RED
        "DEF" => $this->colors['dark_green'], //DK GREEN
        "K" => $this->colors['light_purple'], //LT PURPLE		*
        "DL" => $this->colors['light_green'],
        "LB" => $this->colors['light_pink'],
        "DB" => $this->colors['seafoam'],
        "OL" => $this->colors['switchgrass'],
        //Baseball/MLB
        //CATCHER ALREADY DONE: LT BLUE
        "1B" => $this->colors['light_orange'], //LT ORANGE
        "2B" => $this->colors['light_yellow'], //LT YELLOW
        "3B" => $this->colors['light_red'], //LT RED
        "SS" => $this->colors['dark_green'], //DK GREEN
        "OF" => $this->colors['light_green'], //LT GREEN		*
        "UTIL" => $this->colors['light_pink'], //LT PINK		*
        "SP" => $this->colors['light_purple'], //LT PURPLE
        "RP" => $this->colors['seafoam'], //SEAFOAM				*
        //Basketball/NBA
        "PG" => $this->colors['light_orange'], //LT ORANGE
        "SG" => $this->colors['dark_green'], //DK GREEN
        "SF" => $this->colors['light_yellow'], //LT YELLOW
        "PF" => $this->colors['light_red'], //LT RED
            //CENTER ALREADY DONE: LT BLUE
    );
    
    //PHPD-70: FIX 3 INFIELD ISSUES WITH CSS CLASS NAMES:
    $this->position_colors = array_merge($this->position_colors, array(
      "x1B" => $this->position_colors['1B'],
      "x2B" => $this->position_colors['2B'],
      "x3B" => $this->position_colors['3B'],
    ));
    
    // </editor-fold>
    // <editor-fold defaultstate="collapsed" desc="NHL Teams Positions">
    $this->nhl_teams = Array(
        "ANA" => "Anaheim Ducks",
        "BOS" => "Boston Bruins",
        "BUF" => "Buffalo Sabres",
        "CGY" => "Calgary Flames",
        "CAR" => "Carolina Hurricanes",
        "CHI" => "Chicago Blackhawks",
        "COL" => "Colorado Avalanche",
        "CBS" => "Columbus Blue Jackets",
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
        "ARI" => "Arizona Coyotes",
        "PIT" => "Pittsburgh Penguins",
        "SJS" => "San Jose Sharks",
        "STL" => "St Louis Blues",
        "TAM" => "Tampa Bay Lightning",
        "TOR" => "Toronto Maple Leafs",
        "VAN" => "Vancouver Canucks",
        "WAS" => "Washington Capitals",
        "WPG" => "Winnipeg Jets"
    );

    $this->nhl_positions = array(
        "LW" => "Left Wing",
        "RW" => "Right Wing",
        "C" => "Center",
        "D" => "Defenseman",
        "G" => "Goaltender"
    );
    // </editor-fold>
    // <editor-fold defaultstate="collapsed" desc="NFL Teams Positions">
    $this->nfl_teams = Array(
        "ARI" => "Arizona Cardinals",
        "ATL" => "Atlanta Falcons",
        "BAL" => "Baltimore Ravens",
        "BUF" => "Buffalo Bills",
        "CAR" => "Carolina Panthers",
        "CHI" => "Chicago Bears",
        "CIN" => "Cincinnati Bengals",
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
        "STL" => "St. Louis Rams",
        "TAM" => "Tampa Bay Buccaneers",
        "TEN" => "Tennessee Titans",
        "WAS" => "Washington Redskins"
    );

    $this->nfl_positions = array(
        "QB" => "Quarterback",
        "RB" => "Runningback",
        "WR" => "Wide Receiver",
        "TE" => "Tight End",
        "DEF" => "Defense",
        "K" => "Kicker"
    );

    if (isset($PHPD) && $PHPD->useNFLExtended()) {
      $this->nfl_positions = array_merge($this->nfl_positions, array(
          "DL" => "Defensive Lineman",
          "LB" => "Linebacker",
          "DB" => "Defensive Back",
          "OL" => "Offensive Lineman"
      ));
    }
    // </editor-fold>		
    // <editor-fold defaultstate="collapsed" desc="MLB Teams Positions">
    $this->mlb_teams = Array(
        "ARI" => "Arizona Diamondbacks",
        "ATL" => "Atlanta Braves",
        "BAL" => "Baltimore Orioles",
        "BOS" => "Boston Red Sox",
        "CHC" => "Chicago Cubs",
        "CWS" => "Chicago White Sox",
        "CIN" => "Cincinnati Reds",
        "CLE" => "Cleveland Indians",
        "COL" => "Colorado Rockies",
        "DET" => "Detroit Tigers",
        "HOU" => "Houston Astros",
        "K.C" => "Kansas City Royals",
        "LAA" => "Los Angeles Angels",
        "LAD" => "Los Angeles Dodgers",
        "MIA" => "Miami Marlins",
        "MIL" => "Milwaukee Brewers",
        "MIN" => "Minnesota Twins",
        "NYM" => "New York Mets",
        "NYY" => "New York Yankees",
        "OAK" => "Oakland Athletics",
        "PHI" => "Philadelphia Phillies",
        "PIT" => "Pittsburgh Pirates",
        "SDG" => "San Diego Padres",
        "SFO" => "San Francisco Giants",
        "SEA" => "Seattle Mariners",
        "STL" => "St Louis Cardinals",
        "TAM" => "Tampa Bay Rays",
        "TEX" => "Texas Rangers",
        "TOR" => "Toronto Blue Jays",
        "WAS" => "Washington Nationals"
    );

    $this->mlb_positions = array(
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
    // </editor-fold>
    // <editor-fold defaultstate="collapsed" desc="NBA Teams Positions">
    $this->nba_teams = array(
        "ATL" => "Atlanta Hawks",
        "BKN" => "Brooklyn Nets",
        "BOS" => "Boston Celtics",
        "CHI" => "Chicago Bulls",
        "CHA" => "Charlotte Hornets",
        "CLE" => "Cleveland Cavaliers",
        "DAL" => "Dallas Mavericks",
        "DEN" => "Denver Nuggets",
        "DET" => "Detroit Pistons",
        "GSW" => "Golden State Warriors",
        "HOU" => "Houston Rockets",
        "IND" => "Indiana Pacers",
        "LAC" => "Los Angeles Clippers",
        "LAL" => "Los Angeles Lakers",
        "MIL" => "Milwaukee Bucks",
        "NYK" => "New York Knicks",
        "PHI" => "Philadelphia 76ers",
        "PHO" => "Phoenix Suns",
        "POR" => "Portland Trail Blazers",
        "SAC" => "Sacramento Kings",
        "SAS" => "San Antonio Spurs",
        "OKC" => "Oklahoma City Thunder",
        "UTH" => "Utah Jazz",
        "WAS" => "Washington Wizards",
        "NOR" => "New Orleans Pelicans",
        "MIA" => "Miami Heat",
        "MIN" => "Minnesota Timberwolves",
        "ORL" => "Orlando Magic",
        "TOR" => "Toronto Raptors",
        "MEM" => "Memphis Grizzlies"
    );

    $this->nba_positions = array(
        "PG" => "Point Guard",
        "SG" => "Shooting Guard",
        "SF" => "Small Forward",
        "PF" => "Power Forward",
        "C" => "Center"
    );
    // </editor-fold>
  }

  private $colors;
  public $position_colors;
  private $nhl_teams;
  private $nhl_positions;
  private $nfl_teams;
  private $nfl_positions;
  private $mlb_teams;
  private $mlb_positions;
  private $nba_teams;
  private $nba_positions;

}

?>
