<?php
global $draft_id;		//Use the globally-defined draft_id, which is passed in as a $_REQUEST var
if(!empty($draft_id)) {
    include_once('models/draft_model.php');
    require_once("login_fcns.php");
    ?><div id="right_side">
    <h3>Navigation</h3>
     <?php if($currentFile != "draft_main.php") {?><h4><a href="draft_main.php?draft_id=<?php echo $draft_id;?>">Draft Home Page</a></h4><?php } ?>
    <p><a href="draft_board.php?draft_id=<?php echo $draft_id;?>"><img src="images/icons/draft_board_small.png" class="small_link" alt="View Full Draft Board" />&nbsp;Full Draft Board</a></p>
    <p><a href="draft_teams.php?draft_id=<?php echo $draft_id;?>"><img src="images/icons/team_picks_small.png" class="small_link" alt="View Picks by Team" />&nbsp;Picks per Team</a></p>
    <p><a href="draft_rounds.php?draft_id=<?php echo $draft_id;?>"><img src="images/icons/round_picks_small.png" class="small_link" alt="View Picks by Round" />&nbsp;Picks per Round</a></p>
    <br />
    <h4><a href="draft_stats.php?draft_id=<?php echo $draft_id;?>"><img src="images/icons/stats_small.png" class="small_link" alt="View Draft Statistics" />&nbsp;Draft Statistics</a></h4>
    <br />
    <h4><a href="draft_search.php?draft_id=<?php echo $draft_id;?>"><img src="images/icons/search_small.png" class="small_link" alt="Search the Draft" />&nbsp;Search</a></h4>
    <br />
    <br />
    <br />
</div><?php }//This will hide the menu if we're given a bogus/bad draft ID?>