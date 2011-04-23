<?php require('check_login.php');?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
	<?php require('meta.php'); ?>
    </head>
    <body>
        <div id="page_wrapper">
	    <?php require('header.php'); ?>

	    <?php
	    require_once('cleanstring.php');
	    require_once('models/draft_model.php');

            $draft_id = CleanString(trim($_REQUEST['did']));
	    if(empty($draft_id))
		require('comm_menu.php');
	    else
		require('comm_draft_menu.php');?>
            <div id="content">
		<h3>Select a Draft</h3>
                <p>To begin managing a draft (either draft details, or editing managers, or editing players), select a draft below by clicking on its name.</p>
                <table width="700">
                    <tr>
                        <th>Draft Name</th>
                        <th>Draft Sport</th>
                        <th># Managers</th>
                        <th>Status</th>
                    </tr>
			<?php
			$alt_row = true;
			//while($draft_row = mysql_fetch_array($draft_result)) {
                        foreach(DRAFTS as $draft) {
                            $manager_num = manager_object::getCountOfManagersByDraftId($draft->draft_id);
			    ?><tr<?php echo ($alt_row ? " background-color=\"#cccccc\"" : "");?>>
                        <td><a href="comm_manage_draft.php?did=<?php echo $draft->draft_id;?>"><?php echo $draft->draft_name;?></a></td>
                        <td><?php echo $draft->draft_sport;?></td>
                        <td><?php echo $manager_num;?></td>
                        <td><?php echo $draft->draft_status;?></td>
                    </tr>
			    <?php
			    if($alt_row)
				$alt_row = false;
			    else
				$alt_row = true;
			}//foreach
			?>
                </table>
		    <?php
		}elseif(!empty($_REQUEST['did']) && $_REQUEST['did'] > 0) {//If we've been given a draft ID, then we need to show a list of options for this particular draft
		    $draft_id = CleanString($_REQUEST['did']);
		    $draft_result = mysql_query("SELECT * FROM draft WHERE draft_id = '" . $draft_id . "'");
		    if($draft_row = mysql_fetch_array($draft_result)) {//If we were able to successfully find the draft with the ID they handed us
			$manager_result = mysql_query("SELECT * FROM managers WHERE draft_id = '" . $draft_id . "' ORDER BY draft_order");
			$manager_num = mysql_num_rows($manager_result);

			$draft_order_result = mysql_query("SELECT draft_order FROM managers WHERE draft_id = '".$draft_id."' ORDER BY draft_order DESC LIMIT 1");
			$draft_order_row = mysql_fetch_array($draft_order_result);
			$lowest_order = $draft_order_row['draft_order'];

			if($draft_row['draft_status'] == "complete") {
			    $start_time = strtotime($draft_row['draft_start_time']);
			    $end_time = strtotime($draft_row['draft_end_time']);
			    $elapsed_time = $end_time - $start_time;
			    $elapsed_time = seconds_to_words($elapsed_time);
			}
			?><h3>Manage <?php echo $draft_row['draft_name'] . " (" . $draft_row['draft_sport'] . ")";?></h3>
                <p>Select your option below to begin managing this draft, or to begin/continue the draft process, enter the Draft Room now!</p>
                <fieldset>
                    <legend><?php echo $draft_row['draft_name'];?> - Current Status</legend>
                    <div style="width: 70%; float:left;">
			<p><strong>Sport: </strong> <?php echo $draft_row['draft_sport'];?></p>
			<p><strong>Drafting Style: </strong> <?php echo $draft_row['draft_style'];?></p>
			<p><strong># of Rounds: </strong> <?php echo $draft_row['draft_rounds'];?></p>
			<p><strong>Status: </strong> <?php echo $draft_row['draft_status'];?> </p>
			<?php if($draft_row['draft_status'] == "complete") {?><p><strong>Total Draft Duration: </strong><?php echo $elapsed_time . "</p>";}?>
			<p><strong>Draft Visibility: </strong> <?php if(!empty($draft_row['draft_password'])) {
					echo "Private<br /><strong>Draft Password:</strong> " . $draft_row['draft_password'];
				    }else {
					echo "Public";
				    }?></p>
		    </div>
		    <div style="width: 30%; float:right; text-align: right;">
			<p><img src="images/icons/<?php echo $draft_row['draft_status'];?>.png" alt="<?php echo $draft_row['draft_status'];?>" title="<?php echo $draft_row['draft_status'];?>"/></p>
		    </div>
			    <?php if($manager_num == 0) {
				echo "<p class=\"error\">*Before you can start your draft, you must <a href=\"comm_add_mgrs.php?did=".$draft_id."\">add managers</a>.</p>\n";
	}else { ?>

                    <table width="100%">
                        <tr>
					<?php if($draft_row['draft_status'] == "undrafted") {?><th width="100">&nbsp;</th>
		<?php } ?><th>Manager Name</th>
                            <th>Manager Team</th>
			    <th width="85">Draft Order</th>
                        </tr>
				    <?php
				    while($manager_row = mysql_fetch_array($manager_result)) {
					$uparrow_on = true;
					$downarrow_on = true;
					if($manager_row['draft_order'] == 1)
					    $uparrow_on = false;
					if($manager_row['draft_order'] == $lowest_order)
					    $downarrow_on = false;
		?>
                        <tr>
		<?php if($draft_row['draft_status'] == "undrafted") {?><td><a href="comm_edit_mgr.php?did=<?php echo $draft_id;?>&mid=<?php echo $manager_row['manager_id'];?>">Edit</a> |
                                <a href="comm_delete_mgr.php?did=<?php echo $draft_id;?>&mid=<?php echo $manager_row['manager_id'];?>">Delete</a></td>
		    <?php } ?><td><?php echo $manager_row['manager_name'];?></td>
                            <td><?php echo $manager_row['team_name'];?></td>
			    <td>
						<?php echo $manager_row['draft_order'] . "&nbsp;&nbsp;";?>
		<?php if($uparrow_on && $draft_row['draft_status'] == "undrafted") {?>
				<a href="comm_draft_order.php?action=up&did=<?php echo $draft_id;?>&mid=<?php echo $manager_row['manager_id'];?>"><img src="images/icons/ArrowUp.png" alt="Move Up" border="0" /></a>
		    <?php }else {?>
				<img src="images/icons/ArrowUp_off.png" alt="Move Up"  border="0"/>
						    <?php }
						echo "&nbsp;";
		if($downarrow_on && $draft_row['draft_status'] == "undrafted") {?>
				<a href="comm_draft_order.php?action=down&did=<?php echo $draft_id;?>&mid=<?php echo $manager_row['manager_id'];?>"><img src="images/icons/ArrowDown.png" alt="Move Up"  border="0"/></a>
		    <?php }else {?>
				<img src="images/icons/ArrowDown_off.png" alt="Move Up"  border="0"/>
		    <?php }?>
			    </td>
                        </tr>
					<?php
				} ?></table>
	    <?php } ?>
                </fieldset>
		<fieldset>
		    <legend><?php echo $draft_row['draft_name'];?> - Functions</legend>
			    <?php if($draft_row['draft_status'] == "undrafted") {?><p><strong><a href="comm_add_mgrs.php?did=<?php echo $draft_id;?>">Add Manager(s)</a></strong></p>
				<?php } ?><p><strong><a href="comm_edit_draft_pass.php?did=<?php echo $draft_id;?>">Change Draft Visibility</a></strong></p>
	<?php if($draft_row['draft_status'] != "complete" && $manager_num > 0) {?><p><strong><a href="comm_edit_draft_status.php?did=<?php echo $draft_id;?>">Change Draft Status</a></strong></p><?php } ?>
		</fieldset>
			<?php
		    }else {//Were not able to find the draft with the ID handed to us
	?><h3>Draft Not Found</h3>
                <p class="error">The draft you have attempted to manage could not be found.  Please hit your BACK button and try again.</p>
			<?php
		    }
		}//elseif(!empty($_RE...
?>
            </div>
<?php require('footer.php'); ?>
        </div>
    </body>
</html>