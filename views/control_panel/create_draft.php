<?php require_once('check_login.php'); ?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
	<?php require('meta.php'); ?>
	</head>
	<body>
	<div id="page_wrapper">
		<?php require('header.php'); ?>

		<?php require('comm_menu.php'); ?>
		<div id="content">
		<h3>Create a New Draft</h3>
		<p>To create a draft, please enter a name for the draft to make it unique and identifiable (such as "Refridgerator Raiders" or "Yoohoo Yuppies"), and select the sport that this fantasy draft is for.  Once you're done, press "Create Draft" to create the draft.</p>
				<fieldset>
					<legend>Create Draft</legend>
					<form action="control_panel.php?action=addDraft" method="post">
							<p><label for="draft_name">Draft Name*:</label>
							<input type="text" name="draft_name" id="draft_name" value="<?php echo $draft->draft_name ?>" /></p>
							<p><label for="draft_sport">Draft Sport*:</label>
							<select name="draft_sport">
									<option value="football"<?php ($draft->draft_sport == "football" ? "selected=\"selected\"" : "") ?>>Football - NFL</option>
									<option value="baseball"<?php ($draft->draft_sport == "baseball" ? "selected=\"selected\"" : "") ?>>Baseball - MLB</option>
									<option value="hockey"<?php ($draft->draft_sport == "hockey" ? "selected=\"selected\"" : "") ?>>Hockey - NHL</option>
									<option value="basketball"<?php ($draft->draft_sport == "basketball" ? "selected=\"selected\"" : "") ?>>Basketball - NBA</option>
							</select></p>
							<p><label for="draft_style">Draft Style*:</label>
							<select name="draft_style">
									<option value="serpentine"<?php ($draft->draft_style == "serpentine" ? "selected=\"selected\"" : "") ?>>Serpentine Draft</option>
									<option value="standard"<?php ($draft->draft_style == "standard" ? "selected=\"selected\""  : "") ?>>Standard Draft</option>
							</select></p>
							<p><label for="draft_name"># of Rounds*:</label>
							<input type="text" name="draft_rounds" id="draft_rounds" size="2" maxlength="2" value="<?php echo $draft->draft_rounds ?>" /> (players per team)</p>
							<p><input type="submit" name="submit" class="button" value="Create Draft" /></p>
							<?php if(count($ERRORS) > 0) { ?>
							<?php foreach($ERRORS as $error) { ?>
							<p class="error">* <?php echo $error; ?></p>
							<?php } ?>
							<?php }else {?>
							<p class="error">*Required</p>
							<?php } ?>
					</form>
		</fieldset>
		</div>
		<?php require('footer.php'); ?>
	</div>
	</body>
</html>