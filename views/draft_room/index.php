<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
	<?php require('includes/meta.php'); ?>
	</head>
	<body>
	<div id="page_wrapper">
		<?php require('includes/header.php'); ?>

		<?php require('views/shared/draft_room_menu.php'); ?>
		<div id="content">
		<h3><?php echo $DRAFT->draft_name; ?></h3>
		<fieldset>
			<legend>Draft Room Functions</legend>
			<h4><a href="draft_room.php?action=addScreen&did=<?php echo DRAFT_ID; ?>"><span class="phpdraft-icon ui-icon ui-icon-plusthick"></span>&nbsp;Make a Pick</a></h4><br />
			<h4><a href="draft_room.php?action=selectPickToEdit&did=<?php echo DRAFT_ID; ?>"><span class="phpdraft-icon ui-icon ui-icon-pencil"></span>&nbsp;Edit a Pick</a></h4><br />
			<h4><a href="trades.php?did=<?php echo DRAFT_ID; ?>"><span class="phpdraft-icon ui-icon ui-icon-transferthick-e-w"></span>&nbsp;Enter a Trade</a></h4><br />
			<h4><a href="draft.php?did=<?php echo DRAFT_ID; ?>"><span class="phpdraft-icon ui-icon ui-icon-arrowreturnthick-1-w"></span>&nbsp;Back to Manage Page</a></h4><br />
			<h4><a href="public_draft.php?action=draftBoard&did=<?php echo DRAFT_ID; ?>"><span class="phpdraft-icon ui-icon ui-icon-calculator"></span>&nbsp;View Public Draft Board</a></h4>
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
			<?php if(count($LAST_TEN_PICKS) == 0) {
				?><td colspan="5"><h2>No picks have been made yet.</h2></td><?php
			}else {
				foreach($LAST_TEN_PICKS as $player) { ?>
			<tr style="background-color:<?php echo $DRAFT->sports_colors[$player->position]; ?>;">
				<td><?php echo $player->player_round; ?></td>
				<td><?php echo $player->player_pick; ?></td>
				<td><?php echo $player->manager_name; ?></td>
				<td><?php if($player->hasName()) { echo $player->properName(); } else { echo "&nbsp;";}?></td>
				<td><?php echo $player->position; ?></td>
				<td><?php echo $player->team; ?></td>
			</tr>
				<?php }
			}?>
			</table>
		</fieldset>
		</div>
		<?php require('includes/footer.php'); ?>
	</div>
	</body>
</html>