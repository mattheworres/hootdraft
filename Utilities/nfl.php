<?php
    set_time_limit(0);
    $con = mysql_connect('localhost','root','') or die ("<p class ='error'> Error: ". mysql_error() ." </p>");
    mysql_select_db("phpdraft", $con) or die ("<p class='error'>Error: ". mysql_error() . "</p>");
    
    $nfl_roster = fopen("NFL_roster.csv","r") or exit("Unable to open file!");
    $teams = array();

    
    while(!feof($nfl_roster))
    {
        $player = explode(',', fgets($nfl_roster));


        $sql_player = "INSERT INTO pro_players (last,first,position,team,league) VALUES ('" . mysql_real_escape_string($player[0]) . "', '" . mysql_real_escape_string($player[1]) . "', '" . mysql_real_escape_string($player[2]) . "', '" . mysql_real_escape_string($player[3]) . "', 'NFL')";
        mysql_query($sql_player) or die ("<p class ='error'> Error: ". mysql_error() ." </p>");

        if(!in_array($player[3],$teams))
        {
            array_push($teams,$player[3]);
        }
    }

    for($i=0; $i<count($teams); $i++)
    {
        mysql_query("INSERT INTO pro_players (last,position,league) VALUES ('" .mysql_real_escape_string($teams[$i]). "', 'DEF', 'NFL')")
        or die ("<p class ='error'> Error: ". mysql_error() ." </p>");
    }
    
    fclose($nfl_roster);
?>
