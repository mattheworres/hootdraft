<p class="success"><strong>Last searched:</strong>  <?php echo $NOW;?></p>
<p class="centered-success">Total Players Found: <?php echo $SEARCHER->search_count; ?></p>
<table width="100%">
	<tr>
		<th width="140">Manager</th>
		<th width="40">Pick</th>
		<th>Player</th>
		<th width="110">Position</th>
		<th width="70">Team</th>
	</tr>
	<?php if($SEARCHER->search_count == 0) {?>
	<tr>
		<td colspan="5"><h3>No Picks Found</h3></td>
	</tr>

	<?php } else {
		foreach($SEARCHER->player_results as $pick) {?>
			<tr style="background-color: <?php echo $DRAFT->sports_colors[$pick->position];?>">
				<td><?php echo $pick->manager_name;?></td>
				<td><?php echo $pick->player_pick;?></td>
				<td><span class="player-name"><?php echo $pick->casualName();?></span></td>
				<td><?php echo $pick->position;?></td>
				<td><?php echo $pick->team;?></td>
			</tr>
			<?php
		}
	}
	?>
</table>