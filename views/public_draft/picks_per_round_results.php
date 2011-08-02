<p class="success"><strong>Last refreshed:</strong>  <?php echo $NOW;?></p>
<p><strong>Round: </strong><?php echo $ROUND;?></p>
<table width="100%">
	<tr>
		<th width="140">Manager</th>
		<th width="40">Pick</th>
		<th>Player</th>
		<th width="110">Position</th>
		<th width="70">Team</th>
	</tr>
	<?php foreach($ROUND_PICKS as $pick) {?>
		<tr style="background-color: <?php echo $DRAFT->sports_colors[$pick->position];?>">
			<td><?php echo $pick->manager_name;?></td>
			<td><?php echo $pick->player_pick;?></td>
			<td><span class="player-name"><?php echo $pick->casualName();?></span></td>
			<td><?php echo $pick->position;?></td>
			<td><?php echo $pick->team;?></td>
		</tr>
	<?php }?>
</table>