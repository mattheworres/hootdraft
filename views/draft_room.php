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

		<?php require('comm_draft_room_menu.php'); ?>
		<div id="content">
		<h3><?php echo $title; ?></h3>
		<fieldset>
			<legend>Draft Room Functions</legend>
			<h3><a href="comm_draft_picks.php?action=add&draft_id=<?php echo $draft_id; ?>"><img src="images/icons/add.png" alt="Add Pick" border="0" />&nbsp;Make a Pick</a></h3><br />
			<h3><a href="comm_draft_picks.php?action=select_edit&draft_id=<?php echo $draft_id; ?>"><img src="images/icons/edit.png" alt="Edit Pick" border="0" />&nbsp;Edit a Pick</a></h3><br />
			<h3><a href="comm_manage_draft.php?did=<?php echo $draft_id; ?>"><img src="images/icons/back_blue.png" alt="Back to Manage Page" border="0" />&nbsp;Back to Manage Page</a></h3><br />
			<h3><a href="draft_board.php?draft_id=<?php echo $draft_id; ?>"><img src="images/icons/draft_board.png" alt="View Public Draft Board" border="0" />&nbsp;View Public Draft Board</a></h3>
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
				for($i=0; $i < 10; $i++) {
				$picks_row = mysql_fetch_array($picks_result); ?>
			<tr>
				<td><?php echo $picks_row['player_round']; ?></td>
				<td><?php echo $picks_row['player_pick']; ?></td>
				<td><?php echo $picks_row['manager_name']; ?></td>
				<td><?php if($picks_row['last_name'] != '') { echo $picks_row['last_name'] . ", " . $picks_row['first_name']; }else{ echo "&nbsp;";}?></td>
				<td><?php echo $picks_row['position']; ?></td>
				<td><?php echo $picks_row['team']; ?></td>
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