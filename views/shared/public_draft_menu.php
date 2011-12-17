<?php
if(DRAFT_ID > 0) {
	?><div id="right_side">
	<h3>Navigation</h3>
	<h4><a href="public_draft.php?did=<?php echo DRAFT_ID; ?>"><span class="phpdraft-icon ui-icon ui-icon-home"></span>Draft Home Page</a></h4>
	<p><a href="public_draft.php?action=draftBoard&did=<?php echo DRAFT_ID; ?>"><span class="phpdraft-icon ui-icon ui-icon-calculator"></span>&nbsp;Full Draft Board</a></p>
	<p><a href="public_draft.php?action=picksPerManager&did=<?php echo DRAFT_ID; ?>"><span class="phpdraft-icon ui-icon ui-icon-bullet"></span>&nbsp;Picks per Manager</a></p>
	<p><a href="public_draft.php?action=picksPerRound&did=<?php echo DRAFT_ID; ?>"><span class="phpdraft-icon ui-icon ui-icon-bullet"></span>&nbsp;Picks per Round</a></p>
	<p><a href="public_draft.php?action=viewTrades&did=<?php echo DRAFT_ID; ?>"><span class="phpdraft-icon ui-icon ui-icon-transferthick-e-w"></span>&nbsp;View Trades</a></p>
	<br />
	<h4><a href="public_draft.php?action=draftStats&did=<?php echo DRAFT_ID; ?>"><span class="phpdraft-icon ui-icon ui-icon-signal"></span>&nbsp;Draft Statistics</a></h4>
	<br />
	<h4><a href="public_draft.php?action=searchDraft&did=<?php echo DRAFT_ID; ?>"><span class="phpdraft-icon ui-icon ui-icon-search"></span>&nbsp;Search</a></h4>
	<br />
	<br />
	<br />
</div><?php }?>