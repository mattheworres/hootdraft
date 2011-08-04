<table id="draft_table" width="<?php echo TOTAL_WIDTH;?>">
	<tr><th class="left_col">Rd.</th><th class="left_col" colspan="<?php echo NUMBER_OF_MANAGERS;?>"><?php echo $DRAFT->draft_name;?> - Draft Board</th></tr>
	<?php
	for($i = 1; $i <= $DRAFT->draft_rounds; ++$i) {
		$picks_row = $ALL_PICKS[$i - 1];
		?><tr>
			<td class="left_col" width="10"><?php echo $i;?></td>
			<?php
			foreach($picks_row as $pick) {
				if(isset($pick->pick_time) && strlen($pick->pick_time) > 0) {
					?><td width="<?php echo COL_WIDTH;?>" style="background-color:<?php echo $DRAFT->sports_colors[$pick->position];?>;"># <?php echo $pick->player_pick;?><br />
						<strong><?php echo $pick->first_name . "<br />" . $pick->last_name;?></strong><br />
																		(<?php echo $pick->position . " - " . $pick->team;?>)<br />
						<?php echo $pick->manager_name;?></td>
				<?php } else {?>
					<td width="<?php echo COL_WIDTH;?>"># <?php echo $pick->player_pick;?><br />
						&nbsp;<br />
						&nbsp;<br />
						&nbsp;<br />
						<?php echo $pick->manager_name;?></td>
					<?php
				}
			}
			?>
		</tr>
	<?php }?>
</table>