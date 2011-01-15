<?php
/*
 * view File for Draft Room
 *
*/
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
	<?php require('meta.php'); ?>
    </head>
    <body>
	<div id="page_wrapper">
	    <?php require('header.php'); ?>

	    <?php require('draft_menu.php'); ?>
	    <div id="content">
		<h3><?php echo $title;?></h3>
		<p>This is the main page for this draft.  Below is some summary information for the draft. Use the links to the right for more functionality.</p>
		<fieldset>
		    <legend><?php echo $draft_row['draft_name'];?> - Links</legend>
		    <h3><a href="draft_board.php?draft_id=<?php echo $draft_id;?>"><img src="images/icons/draft_board.png" alt="View Full Draft Board" class="small_link" />&nbsp;View Full Draft Board</a></h3>
		</fieldset>
		<fieldset>
		    <legend><?php echo $draft_row['draft_name'];?> - Current Status</legend>
                    <div style="width: 70%; float:left;">
			<p><strong>Sport: </strong> <?php echo $draft_row['draft_sport'];?></p>
			<p><strong>Drafting Style: </strong> <?php echo $draft_row['draft_style'];?></p>
			<p><strong># of Rounds: </strong> <?php echo $draft_row['draft_rounds'];?></p>
			<p><strong>Status: </strong> <?php echo $draft_row['draft_status'];?> </p>
                        <?php if($draft_row['draft_status'] == 'in_progress' || $draft_row['draft_status'] == 'complete') {?><p><strong>Draft Start Time: </strong> <?php echo $draft_row['draft_start_time'];?></p><?php } ?>
                        <?php if($draft_row['draft_status'] == 'complete') {?><p><strong>Draft End Time: </strong> <?php echo $draft_row['draft_end_time'];?></p>
                        <p><strong>Time Spent Drafting: </strong> <?php echo $elapsed_time;?></p><?php } ?>
		    </div>
		    <div style="width: 30%; float:right; text-align: right;">
			<p><img src="images/icons/<?php echo $draft_row['draft_status'];?>.png" alt="<?php echo $draft_row['draft_status'];?>" title="<?php echo $draft_row['draft_status'];?>"/></p>
		    </div>
		</fieldset>
		<fieldset>
		    <legend>Recent Picks - Last 10</legend>
		    <table width="100%">
			<tr>
			    <th width="55">Rd #</th>
			    <th width="55">Pick #</th>
			    <th>Manager</th>
			    <th>Player</th>
			    <th width="55">Pos.</th>
			    <th width="55">Team</th>
			</tr>
			<?php if(mysql_num_rows($picks_result) == 0) {
			    ?><td colspan="5"><h2>No picks have been made yet.</h2></td><?php
			}else {
                            $rowbg = "#CCCCCC";
			    for($i=0; $i < 10; $i++) {
                                if($rowbg == "#FFFFFF")
                                    $rowbg = "#CCCCCC";
                                else
                                    $rowbg = "#FFFFFF";
                                
				$picks_row = mysql_fetch_array($picks_result); ?>
			<tr bgcolor="<?php echo $rowbg;?>">
			    <td><?php echo $picks_row['player_round'];?></td>
			    <td><?php echo $picks_row['player_pick'];?></td>
			    <td><?php echo $picks_row['manager_name'];?></td>
			    <td><?php if($picks_row['last_name'] != '') {
	    echo $picks_row['last_name'] . ", " . $picks_row['first_name'];
	}else {
	    echo "&nbsp;";
				}?></td>
			    <td><?php echo $picks_row['position'];?></td>
			    <td><?php echo $picks_row['team'];?></td>
			</tr>
	<?php }
	    }?>
		    </table>
		</fieldset>
	    </div>
<?php require('footer.php'); ?>
	</div>
    </body>
</html>