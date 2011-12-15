$(document).ready(function() {
	var draftId = parseInt($('#did').val(), 10),
		$informationDialog = $('#informationDialog'),
		$loadingDialog = $('#loadingDialog');
	$('select.manager_select').change(function() {
		var $managerSelect = $(this),
			selectedManagerId = parseInt($managerSelect.val(), 10);
		
		if(selectedManagerId == 0 || isNaN(selectedManagerId)) {
			$managerSelect.siblings('div.managerPlayers').html(' ');
			return;
		}
		
		$loadingDialog.show();
		
		$.ajax({
			data: {mid: selectedManagerId, did: draftId},
			type: 'GET',
			url: 'trades.php?action=getManagerPlayers',
			success: function(playersData) {
				$loadingDialog.hide();
				$playersBox = $managerSelect.siblings('div.managerPlayers');
				$playersBox.html(' ');
				$.each(playersData, function(i, player) {
					if(player.pick_time != null) {
						$playersBox.append(player.last_name + ", " + player.first_name + " (" + player.position + ", " + player.team + ")<br/>");
					}else {
						$playersBox.append("Pick #" + player.player_pick + " (Round #" + player.player_round + ")<br/>");
					}
					
				});
			},
			error: function() {
				$loadingDialog.hide();
				$('p.errorDescription').html('There was an error attempting to load the players for this manager. Please try again.');
			}
		})
	});
});

function popError($informationDialog) {
	
}