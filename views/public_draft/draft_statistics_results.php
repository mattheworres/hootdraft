<p class="success">Last refreshed: <?php echo $NOW; ?></p>
<p><strong>Longest Average Pick Time (The &quot;Hooooold Oonnn&quot; Award)</strong><br /><?php echo $STATS->hold_on_manager_name;?> - <?php echo $STATS->hold_on_pick_time;?></p>
<p><strong>Shortest Average Pick Time (The Quickie Award)</strong><br /><?php echo $STATS->quickie_manager;?> - <?php echo $STATS->quickie_pick_time;?></p>
<p><strong>Longest Single Pick (The Slowpoke Rodriguez Award)</strong><br /><?php echo $STATS->slowpoke_manager_name;?> - <?php echo $STATS->slowpoke_pick_time;?></p>
<p><strong>Shortest Single Pick (The Speedy Gonzalez Award)</strong><br /><?php echo $STATS->speedy_manager_name;?> - <?php echo $STATS->speedy_pick_time;?></p>
<p><strong>Average Pick Time</strong><br /><?php echo $STATS->average_pick_time;?></p>
<p><strong>Longest Round Time</strong><br />Round #<?php echo $STATS->longest_round;?> - <?php echo $STATS->longest_round_time;?></p>
<p><strong>Shortest Round Time</strong><br />Round #<?php echo $STATS->shortest_round;?> - <?php echo $STATS->shortest_round_time;?></p>
<p><strong>Most Drafted Team</strong><br /><?php echo $STATS->most_drafted_team;?> - <?php echo $STATS->most_drafted_team_count;?> of their players drafted</p>
<p><strong>Least Drafted Team</strong><br /><?php echo $STATS->least_drafted_team;?> - <?php echo $STATS->least_drafted_team_count;?> of their players drafted</p>
<p><strong>Most Drafted Position</strong><br /><?php echo $STATS->most_drafted_position;?> - <?php echo $STATS->most_drafted_position_count;?> of them drafted</p>
<p><strong>Least Drafted Position</strong><br /><?php echo $STATS->least_drafted_position;?> - <?php echo $STATS->least_drafted_position_count;?> of them drafted</p>