<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<?php require('meta.php');?>
		<link href="css/public_draft.css" type="text/css" rel="stylesheet" />
	</head>
	<body>
		<div id="page_wrapper">
			<?php require('header.php');?>

			<?php require('/views/shared/public_draft_menu.php');?>
			<div id="content">
				<h3>Search Draft Picks - <?php echo $DRAFT->draft_name;?></h3>
				<fieldset class="public_form">
					<legend>Search Criteria</legend>
					<p>Use the search box below to enter the first name, or last name, or both names together of a player or players that have already been drafted.</p>
					<p>
						<label for="search_terms">Name(s): </label> <input type="text" name="search_terms" id="search_terms" class="large" maxlength="60" /><br />
					</p>
					<p>
						<label for="team">Team:</label>
						<select name="team" id="team" tabindex="2">
							<option value="" selected="selected">(all teams)</option>
							<?php foreach($DRAFT->sports_teams as $abbr => $sports_team_name) {
								?><option value="<?php echo $abbr;?>"><?php echo $sports_team_name;?></option>
							<?php }?>
						</select>
					</p>
					<p>
						<label for="position">Position*:</label>
						<select name="position" id="position" tabindex="3">
							<option value="" selected="selected">(all positions)</option>
							<?php foreach($DRAFT->sports_positions as $abbr => $sports_position) {
								?><option style="background-color: <?php echo $DRAFT->sports_colors[$abbr];?>" value="<?php echo $abbr;?>"><?php echo $sports_position;?></option>
							<?php }?>
						</select>
					</p>
					<input type="button" class="button" value="Search" id="search" />
				</fieldset>
				<div id="search_results"></div>
				<br/><br/>
			</div>
			<?php require('footer.php');?>
			<script type="text/javascript">
				function load_search() {
					var search_terms = $("#search_terms").val(),
					$loadingDialog = $('#loadingDialog'),
					selectedTeam = $('#team').val(),
					selectedPosition = $('#position').val();
					
					//$("#search_results").load('public_draft.php?action=searchResults&did=<?php echo DRAFT_ID;?>&search='+search_terms);
					
					$loadingDialog.dialog('open');
					$.ajax({
						type: 'GET',
						async: false,
						data: { keywords: search_terms, team: selectedTeam, position: selectedPosition },
						url: 'public_draft.php?action=searchResults&did=<?php echo DRAFT_ID;?>',
						success: function(data) {
							$loadingDialog.dialog('close');
							$('#search_results').html(data);
						},
						error: function(data) {
							$loadingDialog.dialog('close');
						}
					})
				};

				$(document).ready(function() {
					$("#search").keydown(function(e) {
						if(e.keyCode == 13) {
							load_search();
						}
					});

					$("#search").click(function() {
						load_search();
					});
				});
			</script>
		</div>
	</body>
</html>