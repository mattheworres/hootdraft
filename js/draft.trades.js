$(document).ready(function() {
	var draftId = parseInt($('#did').val(), 10),
		$informationDialog = $('#informationDialog'),
		$loadingDialog = $('#loadingDialog');
		
	$('#submit').button();
	
	$('select.manager_select').change(function() {
		var $managerSelect = $(this),
			selectedManagerId = parseInt($managerSelect.val(), 10),
			managerNumber = parseInt($managerSelect.attr('data-manager-id'), 10);
		
		disableOtherManagerOption($managerSelect);
		
		$managerSelect.find('option:first').attr('disabled', 'disabled');
		
		if(selectedManagerId == 0 || isNaN(selectedManagerId)) {
			$managerSelect.siblings('div.managerPlayers').html(' ');
			return;
		}
		
		$loadingDialog.dialog('open');
		
		$.ajax({
			data: { mid: selectedManagerId, did: draftId },
			type: 'GET',
			url: 'trades.php?action=getManagerPlayers',
			success: function(playersData) {
				if(playersData == "FAILURE") {
					handleAjaxError();
					return;
				}
				
				var $playersBox = $managerSelect.siblings('div.managerPlayers')
				
				$playersBox.html(' ');
				
				$loadingDialog.dialog('close');
				
				$.each(playersData, function(i, player) {
					var $newCheckbox = $('<input/>'),
						$newLabel = $('<label></label>'),
						$newBreak = $('<br/>'),
						seriesName = 'manager' + managerNumber + 'assets[]',
						uniqueName = 'manager' + managerNumber + 'assets[' + i + ']';
						
					$newCheckbox.attr('type', 'checkbox');
					$newCheckbox.attr('value', player.player_id);
					$newCheckbox.attr('name', seriesName);
					$newCheckbox.attr('id', uniqueName);
					$newCheckbox.addClass('playerCheck');
					
					$newLabel.attr('for', uniqueName);
					
					if(player.pick_time != null) {
						$newLabel.html(player.last_name + ", " + player.first_name + " (" + player.position + ", " + player.team + ")");
					}else {
						$newLabel.html("Round #" + player.player_round + " Pick (#" + player.player_pick + ")");
					}
					
					$newCheckbox.appendTo($playersBox);
					$newLabel.appendTo($playersBox);
					$newBreak.appendTo($playersBox);
				});
				
				checkTradeValidity();
			},
			error: function() {
				handleAjaxError();
			}
		})
	});
	
	$('input.playerCheck').live('click', function() {
		var $checkbox = $(this),
			$label = $checkbox.siblings('label[for*="' + $checkbox.attr('id') + '"]');
			
		if($checkbox.is(':checked'))
			$label.addClass('selected');
		else
			$label.removeClass('selected');
		
		checkTradeValidity();
	});
	
	$('#submit').live('click', function() {
		handleFormSubmit($loadingDialog, $informationDialog);
	});
});

function disableOtherManagerOption($currentManagerSelect) {
	var $otherManagerSelect = $currentManagerSelect.attr('id') == "manager1" ? $('#manager2') : $('#manager1'),
		$otherManagerOptions = $otherManagerSelect.find('option');
	
	$.each($otherManagerOptions, function() {
		$currentManager = $(this);
		if($currentManager.val() != "(choose a manager)" && $currentManager.attr('disabled') == 'disabled')
			$currentManager.removeAttr('disabled');
		
		if($currentManager.val() == $currentManagerSelect.val())
			$currentManager.attr('disabled', 'disabled');
	});
}

function checkTradeValidity() {
	var isValid = $('#manager1Players').find('input:checked').length > 0
		&& $('#manager2Players').find('input:checked').length > 0;
	
	if(isValid) {
		$('#submit').button("option", "disabled", false);
		return true;
	}else {
		$('#submit').button("option", "disabled", true);
		return false;
	}
}

function handleAjaxError() {
	$loadingDialog.dialog('close');
	$('p.errorDescription').html('There was an error attempting to load the players for this manager. Please try again.');
	checkTradeValidity();
}

function handleFormSubmit($loadingDialog, $informationDialog) {
	if(checkTradeValidity()){
		$loadingDialog.dialog('open');
		var $submitData = $('#trade_box').formSerialize();
		$.ajax({
			type: 'POST',
			data: $submitData,
			url: 'trades.php?action=submitTrade',
			success: function(data) {
				$loadingDialog.dialog('close');
				if(data != "SUCCESS") {
					try{
						var jsonObject = JSON.parse(data);
						$informationDialog.html('There were issues with your submission. More details:<br/><br/>');
						
						$.each(jsonObject, function() {
							$informationDialog.append(this + '<br/>');
						});
						
						$informationDialog.dialog('open');
					}catch(e) {
						$informationDialog.html('There was a server error with the submission. More details: Error L#135')
							.dialog('open');
					}
					
					return;
				}
				wipeForm();
				$informationDialog.html('Success! The trade has been entered. It is now reflected in the system. You can now go back to entering picks, or you can stay here and enter another trade :)')
					.dialog('open');
			},
			error: function(data) {
				$loadingDialog.dialog('close');
				$informationDialog.html('There was a server error with the submission, the trade was not submitted.')
					.dialog('open');
			}
		});
	}
}

function wipeForm() {
	$.each($('select.manager_select'), function() {
		$(this).children('option').removeAttr('disabled');
		$(this).val('');
	});
	
	$.each($('div.managerPlayers'), function() {
		$(this).html(' ');
	});
	
	checkTradeValidity();
}