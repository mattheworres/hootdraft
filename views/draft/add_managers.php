<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<?php require('meta.php');?>
		<link href="css/draft.css" type="text/css" rel="stylesheet" />
	</head>
	<body>
		<div id="page_wrapper">
			<?php require('header.php');
			require('/views/shared/draft_menu.php');?>
			<div id="content">
				<input type="hidden" name="draft_id" value="<?php echo DRAFT_ID;?>" />
				<fieldset>
					<legend>Add Managers</legend>
					<p>Add multiple managers to your draft using this form. To add more rows, click on the green plus icon at the bottom. To remove rows, click on the red X of that row.</p>
					<table id="add-managers-table" width="100%">
						<th class="center"><span id="addManagerButton"><img src="images/icons/add.png" alt="Add a Manager row"/></span></th>
						<th>Manager Name*</th>
						<th>Manager Email</th>
						<tr class="data-row">
							<td><span class="removeManagerButton">(&mdash;)</span></td>
							<td>
								<input type="text" class="manager_info manager_name" />
							</td>
							<td>
								<input type="text" class="manager_info manager_email" />
							</td>
						</tr>
						<tr id="last-row">
							<td>&nbsp;</td>
							<td>&nbsp;</td>
							<td><input type="button" value="Add Managers" id="addManagers"/></td>
						</tr>
					</table>
					<p class="errorDescription error">One or more of the fields above have errors in them. Please correct the highlighted fields and try again.</p>
				</fieldset>
			</div>
			<?php require('footer.php');?>

			<script type="text/javascript">
				$(document).ready(function() {
					$('input.error').live('keypress', function() {
						$(this).removeClass('error');
					});
					
					$('#addManagers').button();

					$('#addManagerButton').live('click', function() {
						var $newRow = $('#add-managers-table tr.data-row:first').clone();

						$newRow.find('input').val('').removeClass('error');
						$newRow.insertBefore('#last-row');
					});
				
					$('span.removeManagerButton').live('click', function() {
						var $row = $(this).parents('tr:first'),
							rowCount = $('#add-managers-table tr.data-row').length;
						
						if(rowCount > 1)
							$row.remove();
						else
							alert('You can\'t remove the last row for a manager, silly!');
					});
				
					$('#addManagers').live('click', function() {
						var $informationDialog = $('#informationDialog'),
							$loadingDialog = $('#loadingDialog');
						
						$('p.errorDescription').hide();
						
						if(!validateManagers())
							return;
						
						//Build array of JSON objects
						var managers = [];
						
						$.each($('#add-managers-table tr.data-row'), function() {
							var name = $(this).find('input.manager_name').val();
							var email = $(this).find('input.manager_email').val();
							managers.push({ manager_name: name, manager_email: email});
						});
						
						$loadingDialog.dialog('open');
						
						$.ajax({
							type: 'POST',
							data: managers,
							dataType: 'json',
							url: 'draft.php?saveManagers',
							success: function(data) {
								console.log(data);
								if(data == "SUCCESS") {
									$loadingDialog.dialog('close');
									$informationDialog.val('Congratulations! Your new managers have been added to the draft.').dialog('open');
									removeAllExtraRows();
								}else {
									$loadingDialog.dialog('close');
									$('p.errorDescription').html('A server-side error has occurred, please try again.').show();
								}
							},
							error: function() {
								$loadingDialog.dialog('close');
								$('p.errorDescription').html('A server-side error has occurred, please try again.').show();
							}
						});
					});
				});
				
				function validateManagers() {
					$.each($('input.manager_name'), function() {
						var name = $(this).val();
						if(name.length == 0)
							$(this).addClass('error');
					});
				
					$.each($('input.manager_email'), function() {
						var email = $(this).val();
						if(email.length > 1)
							if(!validateEmail(email))
								$(this).addClass('error');
					});
					
					if($('input.error').length > 0) {
						$('p.errorDescription').show();
						return false;
					}
					return true;
				}
				
				function removeAllExtraRows() {
					$('#add-managers-table tr.data-row').val('');
					
					while($('#add-managers-table tr.data-row').length > 1)
						$('#add-managers-table tr.data-row:first').remove();
				}
				
				function validateEmail(email) 
				{ 
					 var re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/ 
					 return email.match(re) 
				}
			</script>
		</div>
	</body>
</html>