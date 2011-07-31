<p class="success"><strong>Last refreshed:</strong>  <?php echo $NOW; ?></p>
<p><strong>Manager: </strong><?php echo $MANAGER->manager_name; ?></p>
<table width="100%">
	<tr>
		<th width="40">Rd</th>
		<th width="40">Pick</th>
		<th>Player</th>
		<th width="120">Position</th>
		<th width="160">Team</th>
	</tr>
	<?php foreach($MANAGER_PICKS as $pick) {?>
		<tr style="background-color: <?php echo $DRAFT->sports_colors[$pick->position];?>">
			<td><?php echo $pick->player_round;?></td>
			<td><?php echo $pick->player_pick;?></td>
			<td><span class="player-name"><?php echo $pick->casualName(); ?></span></td>
			<td><?php echo $pick->position;?></td>
			<td><?php echo $pick->team;?></td>
		</tr>
	<?php }?>
</table>