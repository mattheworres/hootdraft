$(document).ready(function() {
	resetArrows();

	$('#managers-table span.manager-move-link').live('click', function() {
		var $row = $(this).parents('tr:first'),
			manager_id = $row.attr('data-manager-id'),
			$loadingDialog = $('#loadingDialog'),
			isMoveUp = $(this).is('.move-up')
			isOff = $(this).is('.down-off') || $(this).is('.up-off');

		if(isOff)
			return;

		$loadingDialog.dialog('open');

		$.ajax({
			type: 'POST',
			url: 'manager.php?action=moveManager',
			data: { mid: manager_id, direction: isMoveUp ? 'up' : 'down'},
			success: function(data) {
				$loadingDialog.dialog('close');
				if(data == "SUCCESS") {
					if(isMoveUp) {
						$row.insertBefore($row.prev());
						resetArrows();
					}else {
						$row.insertAfter($row.next());
						resetArrows();
					}
				}else{
					alert('Sorry - an error has occurred and the manager\'s order could not be changed.');
				}
			},
			error: function() {
				$loadingDialog.dialog('close');
				alert('Sorry - an error has occurred and the manager\'s order could not be changed.');
			}
		})
	});
	
	$('#managers-table .manager-delete-link').live('click', function() {
		var $row = $(this).parents('tr:first'),
			manager_id = $row.attr('data-manager-id'),
			$loadingDialog = $('#loadingDialog');

		$loadingDialog.dialog('open');

		$.ajax({
			type: 'POST',
			url: 'manager.php?action=deleteManager',
			data: { mid: manager_id },
			success: function(data) {
				$loadingDialog.dialog('close');
				if(data == "SUCCESS") {
					$row.remove();
					resetArrows();
					checkForOtherManagers();
				} else {
					alert('Sorry - an error has occurred and the manager could not be deleted.');
				}
			},
			error: function() {
				$loadingDialog.dialog('close');
				alert('Sorry - an error has occurred and the manager could not be deleted.');
			}
		});
	});
	
	$('#visibilityDialog').dialog({
		title: 'Change Draft Visibility',
		autoOpen: false,
		modal: true,
		width: 650,
		buttons: [
			{
				text: "Save",
				click: function() { 
					var success = updateDraftVisibility(); 
					if(success === true) {
						$(this).dialog("close");
						$('#informationDialog').html('Draft\'s visibility updated!').dialog('open');
					}
				}
			},
			{
				text: "Cancel",
				click: function() { $(this).dialog("close"); }
			}
		]
	});
	
	$('#changeVisibility').live('click', function() {
		$('#visibilityDialog').dialog('open');
	})
	
	$('#draft_status').live('change', function() {
		var value = $(this).val(),
			$passwordBox = $('#passwordBox');
		if(value == 1)
			$passwordBox.show();
		else
			$passwordBox.hide();
	});
	
	$('#draft_password, #draft_password_confirm').live('blur', function() {
		checkMatchingPasswords($('#draft_status'), $('#draft_password'), $('#draft_password_confirm'));
	});

	$('#draft_password, #draft_password_confirm').live('keyup', function(e) {
		checkMatchingPasswords($('#draft_status'), $('#draft_password'), $('#draft_password_confirm'));
	});
});

function checkForOtherManagers() {
	var number_of_managers = $('#managers-table tr').length - 1;	//Account for table header
	
	if(number_of_managers == 0) {
		$('#managers-table').hide();
		$('#no-managers-msg').show();
		$('#draft-status-link').hide();
		alert('You\'ve just deleted the last manager this draft had - in order to start this draft you must first add managers again.');
	}
}

function resetArrows() {
	var rows = $('#managers-table > tbody > tr'),
		i = 1,
		tableLength = rows.length;

	$.each(rows, function() {
		if(i == 1) {
			$(this).children().children('span.move-up').removeClass('up-on').addClass('up-off');
			if(tableLength > 1)
				$(this).children().children('span.move-down').removeClass('down-off').addClass('down-on');
		}else if(i == tableLength) {
			$(this).children().children('span.move-down').removeClass('down-on').addClass('down-off');
			if(tableLength > 1)
				$(this).children().children('span.move-up').removeClass('up-off').addClass('up-on');
		}else {
			$(this).children().children('span.move-down').removeClass('down-off').addClass('down-on');
			$(this).children().children('span.move-up').removeClass('up-off').addClass('up-on');
		}

		++i;
	});
}

function updateDraftVisibility() {
	var status = parseInt($('#draft_status').val(), 10),
		$password = $('#draft_password'),
		$confirmPassword = $('#draft_password_confirm'),
		$visibilityError = $('#visibilityError'),
		$draftVisibility = $('#draft_visibility'),
		password = $.trim($('#draft_password').val()),
		confirmPassword = $.trim($('#draft_password_confirm').val());
		
	$password.removeClass('error');
	$confirmPassword.removeClass('error');
	$visibilityError.hide();
		
	if(status == 0) {
		var updateSuccess = savePassword('');
		if(updateSuccess === true) {
			$password.val('');
			$confirmPassword.val('');
			$draftVisibility.html('Public');
			return true;
		}else {
			$visibilityError.html('There was a server-side error, please try again.').show();
			return false;
		}
	}else {
		if(password.length == 0 || confirmPassword.length == 0 || password != confirmPassword) {
			$visibilityError.html('To make the draft private, you must provide a password and confirm that password!').show();
			$password.addClass('error');
			$confirmPassword.addClass('error');
			return false;
		}
		
		var success = savePassword(password);
		
		if(success === true) {
			$('#visibilityDialog').dialog('close');
			$draftVisibility.html('Private<br/><br/><strong>Draft Password:</strong> ' + password);
			return true;
		}else {
			$visibilityError.html('There was a server-side error, please try again.').show();
			return false;
		}
	}
}

function savePassword(draft_pass) {
	var draft_id = parseInt($('#draft_id').val(), 10),
		$loadingDialog = $('#loadingDialog');
	
	$loadingDialog.dialog('open');
	
	result = false;
	
	$.ajax({
		async: false,
		type: 'POST',
		data: {action: 'updateVisibility', did: draft_id, password: draft_pass},
		url: 'draft.php',
		success: function(data) {
			$loadingDialog.dialog('close');
			if(data == "SUCCESS") 
				result = true;
			else
				result = false;
		},
		error: function() {
			$loadingDialog.dialog('close');
			result =  false;
		}
	});
	
	return result;
}

function checkMatchingPasswords($statusSelect, $password, $confirmPassword) {
	var $visibilityError = $('#visibilityError');
	$visibilityError.hide();
	
	if($statusSelect.val() == 0)
		return;
	
	console.log('Password len: ' + $password.val().length + ' ; Confirm length: ' + $confirmPassword.val().length);
	if($password.val().length > 0 && $confirmPassword.val().length > 0)
		if($password.val() != $confirmPassword.val())
			$visibilityError.html('Passwords entered do not match!').show();
		else
			$visibilityError.hide();
	else
		$visibilityError.hide();
}