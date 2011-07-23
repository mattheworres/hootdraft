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