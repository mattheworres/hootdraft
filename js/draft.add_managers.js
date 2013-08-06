$(document).ready(function() {
	$('#addManagers').button();
	
	$(document).on('keypress', 'input.error', keypressHandler);

	$(document).on('click', '#addManagerButton', addManagerButtonHandler);
	
	$(document).on('click', 'span.removeManagerButton', removeManagerHandler);
	
	$(document).on('click', '#addManagers', addManagerHandler);
});

function keypressHandler() {
	$(this).removeClass('error');
}

function addManagerButtonHandler() {
	var $newRow = $('#add-managers-table tr.data-row:first').clone();

	$newRow.find('input').val('').removeClass('error');
	$newRow.insertBefore('#last-row');
}

function removeManagerHandler() {
	var $row = $(this).parents('tr:first'),
	rowCount = $('#add-managers-table tr.data-row').length;

	if(rowCount > 1)
		$row.remove();
	else
		alert('You can\'t remove the last row for a manager, silly!');
}

function addManagerHandler() {
	var $informationDialog = $('#informationDialog'),
		$loadingDialog = $('#loadingDialog');
						
	$('p.errorDescription').hide();

	if(!validateManagers()) {
		$('p.errorDescription').html('One or more of the managers are invalid. Please fix highlighted fields to continue.').show();
		return;
	}

	//Build array of JSON objects
	var managers = [];

	$.each($('#add-managers-table tr.data-row'), function() {
		var name = $(this).find('input.manager_name').val();
		var email = $(this).find('input.manager_email').val();
		managers.push({
			manager_name: name, 
			manager_email: email
		});
	});

	$loadingDialog.dialog('open');

	$.ajax({
		type: 'POST',
		data: {
			did: $('#draft_id').val(), 
			action: 'saveManagers', 
			managers: managers
		},
		url: 'draft.php?action=saveManagers',
		success: function(data) {
			$loadingDialog.dialog('close');

			if(data == "SUCCESS") {
				$informationDialog.html('Congratulations! Your new managers have been added to the draft.').dialog('open');
				removeAllExtraRows();
				updateCurrentTable(managers);
			}else {
				$('p.errorDescription').html('A server-side error has occurred. Please try again.').show();
			}
		},
		error: function() {
			$loadingDialog.dialog('close');
			$('p.errorDescription').html('A server-side error has occurred. Please try again.').show();
		}
	});
}
				
function validateManagers() {
	$('input.error').removeClass('error');
	
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
	$('#add-managers-table input.manager_info').val('');
					
	while($('#add-managers-table tr.data-row').length > 1)
		$('#add-managers-table tr.data-row:first').remove();
}
				
function updateCurrentTable(new_managers) {
	var needToAddNewRow = $('#current-managers-table tr').length == 1;
	$.each(new_managers, function() {
		if(needToAddNewRow)  {
			var $brandNewRow = $('<tr></tr>').addClass('current-row');
			$('<td></td>').text(' ').appendTo($brandNewRow);
			$('<td></td>').text(this.manager_name).addClass('current-manager-name').appendTo($brandNewRow);
			$('<td></td>').text(this.manager_email).addClass('current-manager-email').appendTo($brandNewRow);
			$('#current-managers-table').append($brandNewRow);
			needToAddNewRow = false;
		}else {
			var $newRow = $('#current-managers-table tr.current-row:first').clone();

			$newRow.show();
			$newRow.find('.current-manager-name').html(this.manager_name);
			$newRow.find('.current-manager-email').html(this.manager_email);
			$newRow.insertAfter('#current-managers-table tr.current-row:last');
		}
	});
}
				
function validateEmail(email) {
	var re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/ 
	return email.match(re) 
}