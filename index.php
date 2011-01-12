<?php session_start();?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
	<?php require('meta.php'); ?>
    </head>
    <body>
	<div id="page_wrapper">
	    <?php require('header.php'); ?>

	    <?php require('menu.php');
	    require_once('dbconn.php');
	    set_conn();

	    $draft_result = mysql_query("SELECT * FROM draft WHERE draft_status != 'undrafted' ORDER by draft_status DESC");
	    $number_of_drafts = mysql_num_rows($draft_result);
	    ?>
	    <div id="content">
		<h3>Welcome to PHPDraft!</h3>
		<div class='featurebox_center'>PHPDraft has moved to a private beta phase!  This private beta phase is an on-going process aimed at improving PHPDraft all around, and is focused on the user experience and ease of use.  All of PHPDraft's core functions have been completed, just some feature/UI tweaking is left!  Contact Matt if you'd like to get involved.</div>
		<p>PHPDraft is a browser-based web application that your commissioner has decided to use to run your fantasy sports draft!  With PHPDraft, you can watch in real time as your commissioner enters draft picks, see breakdowns of the positions most taken, see who in your league is the slowest drafter, and more!  And at the end, everyone can print out their own PDF document or Excel spreadsheet of their team for their records!</p>
		<p>To get started, go ahead and click on the name of a draft below.  If a lock icon shows up beside a draft, that means you'll have to get the password from your commissioner*</p>
		<table width="100%">
		    <tr>
			<th width="16">&nbsp;</th>
			<th>Draft Name</th>
			<th>Sport</th>
			<th>Draft Status</th>
		    </tr>
		    <?php if($number_of_drafts == 0) {
			?><tr><td colspan="4"><h2>There are currently no drafts available.</h2></td></tr><?php
		    }else {
			while($draft_row = mysql_fetch_array($draft_result)) {
			    $visibility = ($draft_row['draft_password'] != '' ? "locked" : "unlocked");

			    if($draft_row['draft_status'] == "undrafted")
				$draft_status = "Setting Up";
			    elseif($draft_row['draft_status'] == "in_progress")
				$draft_status = "Currently Drafting";
			    elseif($draft_row['draft_status'] == "complete")
				$draft_status = "Draft Complete";
			    ?><tr>
			<td><img src="images/icons/<?php echo $visibility;?>.png" border="0" alt="<?php echo $visibility;?>" /></td>
			<td><a href="draft_main.php?draft_id=<?php echo $draft_row['draft_id'];?>"><?php echo $draft_row['draft_name'];?></a></td>
			<td><?php echo $draft_row['draft_sport'];?></td>
			<td><?php echo $draft_status;?></td>
		    </tr>
			    <?php }
		    }?>
		</table>
		<p style="font-size: 80%;">*Cookies must be enabled</p>
	    </div>
	    <?php require('footer.php'); ?>
	</div>
    </body>
</html>