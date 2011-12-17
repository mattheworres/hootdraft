<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<?php require('includes/meta.php');?>
	</head>
	<body>
		<div id="page_wrapper">
			<?php require('includes/header.php');
			require('views/shared/draft_menu.php');?>
			<div id="content">
				<fieldset>
					<legend>Edit Draft</legend>
					<p>To change your draft's details, use the form below.</p>
					<p class="error">NOTE: You will not be able to edit this draft's details once you begin the draft (change it's status to &quot;in progress&quot;)</p>
					<form action="draft.php?action=updateDraft" method="post">
						<input type="hidden" name="did" value="<?php echo DRAFT_ID;?>" />
						<p>
							<label for="draft_name">Draft Name*:</label>
							<input type="text" name="draft_name" id="draft_name" value="<?php echo $DRAFT->draft_name;?>" />
						</p>
						<p>
							<label for="draft_sport">Draft Sport*:</label>
							<select name="draft_sport">
								<option value="football"<?php echo $DRAFT->draft_sport == "football" ? " selected" : "";?>>Football</option>
								<option value="baseball"<?php echo $DRAFT->draft_sport == "baseball" ? " selected" : "";?>>Baseball</option>
								<option value="hockey"<?php echo $DRAFT->draft_sport == "hockey" ? " selected" : "";?>>Hockey</option>
							</select>
						</p>
						<p>
							<label for="draft_style">Draft Style*:</label>
							<select name="draft_style">
								<option value="serpentine"<?php echo $DRAFT->draft_style == "serpentine" ? " selected" : "";?>>Serpentine Draft</option>
								<option value="standard"<?php echo $DRAFT->draft_style == "standard" ? " selected" : "";?>>Standard Draft</option>
							</select>
						</p>
						<p>
							<label for="draft_rounds"># of Rounds*:</label>
							<input type="text" name="draft_rounds" id="draft_rounds" size="2" maxlength="2" value="<?php echo $DRAFT->draft_rounds;?>" /> (players per team)
						</p>
						<p><input type="submit" name="submit" class="button" value="Update Draft" /></p>
						<?php if(isset($ERRORS) && count($ERRORS) > 0) {?>
							<?php foreach($ERRORS as $error) {?>
								<p class="error">* <?php echo $error;?></p>
							<?php }?>
						<?php } else {?>
							<p class="error">*Required</p>
						<?php }?>
					</form>
				</fieldset>
			</div>
			<?php require('includes/footer.php');?>
		</div>
	</body>
</html>