<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<?php require('includes/meta.php');?>
	</head>
	<body>
		<div id="page_wrapper">
			<?php require('includes/header.php'); ?>
			<div id="content-wide">
				<h3>Update Pro Players</h3>
				<p><strong>Note:</strong> If none of what's below makes sense to you - worry not! This is an <strong>optional</strong> feature to help those who wish to update this feature.</p>
				<p>As the commissioner, when entering draft picks PHPDraft will try to save you some time and offer suggestions for likely players to enter
					&mdash; typing two or more letters in the &quot;first name&quot; box will pop up a suggestion box. The box that pops up uses a database table in
					PHPDraft that has been pre-populated with 700+ players for each pro sports league (NFL, NHL, MLB, NBA).</p>
				<p>But what happens when one season ends and another begins? Some players retire, others are traded or sign elsewhere &mdash; a fact of modern
					professional sports. Keep your install of PHPDraft up-to-date by updating that database!</p>
				<p>Here you can upload a specially formated CSV ("comma separated values") file that contains all players from a particular sports league. 
					There are a few instances in which you would want to use this form:</p>
				<ul>
					<li>If for some reason the current database is out of date (has data from the last season)</li>
					<li>You have made corrections or amendments to the data that comes default with PHPDraft</li>
				</ul>
				<p>In order for PHPDraft to know how to handle your CSV, you must follow a few guidelines:</p>
				<ol>
					<li>In the format: <strong>"LAST, FIRST";"POSITION";"TEAM"(newline at end of line)</strong></li>
					<li>Player name is comma separated</li>
					<li>Position be a proper capitalized abbreviation<strong>*</strong></li>
					<li>Team be a proper capitalized abbreviation<strong>*</strong></li>
				</ol>
				<p><strong>*</strong>see libraries/sports_values_library.php for the full list of valid values</p>
				<p>Updating values for a particular sport will erase currently existing values, but there are valid working backup CSVs for each sport in the /resources folder.</p>

				<fieldset>
					<form id="uploadForm" action="control_panel.php?action=uploadProPlayers" method="post">
						<p>
							<label for="sport">Sports League:</label>
							<select id="sport" name="sport">
								<option value="NFL">National Football League (NFL)</option>
								<option value="MLB">Major League Baseball (MLB)</option>
								<option value="NHL">National Hockey League (NHL)</option>
								<option value="NBA">National Basketball Association (NBA)</option>
							</select>
						</p>
						<p>
							<label for="csv_file">Choose CSV to Upload:</label>
							<input type="file" id="csv_file" name="csv_file" />
						</p>
						<p><input type="submit" name="submit_button" class="button" value="Update Pro Players" /></p>
					</form>
				</fieldset>
			</div>
			<?php require('includes/footer.php');?>
			<script type="text/javascript" src="js/jquery.form.js"></script>
			<script type="text/javascript">
				$(document).ready(function() {
					$('#uploadForm').submit(function(e) {
						//TODO: Use Jquery.Validate to validate this client-side, such as ensuring its a CSV!
						var $form = $(this),
							$sport = $('#sport').val(),
							$csv_file = $('#csv_file').val(),
							$loading = $('#loadingDialog'),
							$message = $('#informationDialog');
							
						$message.html('').dialog('close');
						$loading.dialog('open');
							
						e.preventDefault();
						
						if($sport.length < 3 || $csv_file.length == 0) {
							$loading.dialog('close');
							$message.html('You must choose a sport and select a CSV file to upload.').dialog('open');
						}
						
						$form.ajaxSubmit({
							iframe: true,
							async: false,
							dataType: 'json',
							success: function(data) {
								$loading.dialog('close');
								if(data.Success == "true") {
									$message.html('Pro players updated successfully for ' + $sport + "!").dialog('open');
								} else {
									$message.html('Could not upload CSV file: ' + data.Message).dialog('open');
								}
							},
							error: function() {
								$loading.dialog('close');
								$message.html('An error has occurred while uploading the CSV file.').dialog('open');
							}
						});
						
						return false;
					});
				});
			</script>
		</div>
	</body>
</html>