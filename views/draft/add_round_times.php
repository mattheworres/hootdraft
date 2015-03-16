<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<?php require('includes/meta.php');?>
		<link href="css/draft.css" type="text/css" rel="stylesheet" />
	</head>
	<body>
		<div id="page_wrapper">
			<?php require('includes/header.php');
			require('views/shared/draft_menu.php');?>
			<div id="content">
				<input type="hidden" id="draft_id" value="<?php echo DRAFT_ID;?>" />
				<fieldset>
          <form id="round_times_form" method="POST">
            <legend>Edit Round Pick Timers</legend>
            <p>If you'd like to enable the round pick timer, which shows up on the public draft board in the form of a popup countdown timer to let managers know how much time they have to pick, specify how much time you'd like to use below.</p>
            <p>You can either use a single standard pick time that is the same for every round, or specify a particular time for each round.</p>
            <p>
              <input type="checkbox" name="round_times_enabled" id="round_times_enabled" <?php if($ROUND_TIMES_ENABLED) { echo " checked=\"checked\""; } ?> /> <label for="round_times_enabled">Enable pick timers</label>
            </p>
            <p><input type="checkbox" name="is_static_time" id="is_static_time" <?php if($IS_STATIC_TIME) { echo ' checked="checked"'; } ?> /> <label for="is_static_time">Use a single pick time for all rounds (uncheck to specify pick times per each round)</label></p>
            <table id="static-round-time-table" class="times-table" width="100%">
              <th width="50">Round</th>
              <th>&nbsp;</th>
              <tr class="data-row">
                <td class="center">(all)</td>
                <td>
                  picks will be
                  <input class="minutes" size="1" validate="required: 'input[name=round_times_enabled]:checked'" value="0" /> <label>minutes</label> and
                  <input class="seconds" size="1" validate="required: 'input[name=round_times_enabled]:checked'" value="0" /> <label>seconds</label> long (at most)
                </td>
              </tr>
            </table>
            <table id="dynamic-round-time-table" class="times-table" width="100%">
              <th width="50">Round</th>
              <th>&nbsp;</th>
              <?php for($i = 1; $i < $DRAFT_ROUNDS + 1; ++$i) { ?>
              <tr class="data-row">
                <td class="center">
                  <strong><?php echo $i;?></strong>
                </td>
                <td>
                  picks will be
                  <input class="minutes" data-round="<?php echo $i;?>" size="1" validate="required: 'input[name=is_static_time]:checked'" value="0" /> <label>minutes</label> and
                  <input class="seconds" data-round="<?php echo $i;?>" size="1" validate="required: 'input[name=is_static_time]:checked'" value="0" /> <label>seconds</label> long (at most)
                </td>
              </tr>
              <?php } ?>
            </table>
            <p><input type="button" class="button" id="saveRoundTimesButton" value="Save Round Pick Timers"  tabindex="5"/></p>
            <p class="errorDescription error">One or more of the fields above have errors in them. Please correct the highlighted fields and try again.</p>
          </form>
				</fieldset>
			</div>
			<?php require('includes/footer.php');?>
      <script>
        dynamicRoundTimers = <?php echo json_encode($DYNAMIC_ROUND_TIMES); ?>;
        staticRoundTimer = <?php echo json_encode($STATIC_ROUND_TIME); ?>;
      </script>
			<script src="js/draft.add_round_times.js" type="text/javascript"></script>
		</div>
	</body>
</html>