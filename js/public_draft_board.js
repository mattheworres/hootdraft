$(function() {
  intervalID = 0,
  checkIsRunning = false;

  $(document).ready(function() {
    initDialog();
    
    var reloadMs = 1000 * parseInt($('#draft-board').data('draft-reload'), 10);

    _.templateSettings.variable = "pick";

    pickTemplate = _.template(
        $("#pickTemplate").html()
    );

    refreshBoard(function() {
      $('#loadingDialog').dialog('close');
    });

    intervalID = setInterval(function() {
      refreshBoard();
    }, reloadMs);
  });
  
  function initDialog() {
    $('#loadingDialog').dialog({
    autoOpen: true,
    title: "Loading...",
    modal: true,
    draggable: false,
    resizable: false
  });
  }

  function refreshBoard(callback) {
    var currentCounter = parseInt($('#draft-board').data('draft-counter'), 10),
        draftId = parseInt($('#draft-board').data('draft-id'), 10),
        refreshUrl = 'public_draft.php?action=refreshBoard&did=' + draftId + '&currentCounter=' + currentCounter;

    if(checkIsRunning) {
      return;
    }

    checkIsRunning = true;

    $.ajax({
      type: 'GET',
      url: refreshUrl,
      dataType: 'json',
      complete: function() {
        checkIsRunning = false;
        
        if(typeof(callback) === "function") {
          callback();
        }
      },
      success: function(response) {
        switch(response.Status) {
          case "draft-complete":
            clearInterval(intervalID);
            break;

          case "up-to-date":
            //do nothing right now
            break;

          case "out-of-date":
            $.each(response.Players, function() {
              var player = this,
                  $pickCell = $('div.pick[data-pick-number="' + player.player_pick + '"]');

              $pickCell.html(pickTemplate(player)).removeClass().addClass('pick').addClass(player.position);
            });

            $('#draft-board').data('draft-counter', parseInt(response.CurrentCounter, 10));
            break;
        }
      },
      error: function() {
        $('#informationDialog').html('There was a connection issue while attempting to gather new picks - if the issue persists contact your administrator.').dialog('open');
      }
    });
  }
});