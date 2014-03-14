$(function() {
  var pickTemplate,
      intervalID = 0,
      checkIsRunning = false;

  $(document).ready(function() {
    initDialogs();
    initializeEventHandlers();

    var reloadMs = 1000 * parseInt($('#draft-board').data('draft-reload'), 10);

    _.templateSettings.variable = "pick";

    pickTemplate = _.template($("#pickTemplate").html());

    refreshBoard(function() {
      $('#loadingDialog').dialog('close');
    });

    intervalID = setInterval(function() {
      refreshBoard();
    }, reloadMs);
  });

  function initDialogs() {
    $('#loadingDialog').dialog({
      autoOpen: true,
      title: "Loading...",
      modal: true,
      draggable: false,
      resizable: false
    });
    
    $('#informationDialog').dialog({
      autoOpen: false,
      modal: true,
      draggable: false,
      rezisable: false
    });
  }
  
  function initializeEventHandlers() {
    $(document).on('mouseenter', 'div.pick.undrafted', function() {
      pickClickHandler($(this), true);
    });
    
    $(document).on('mouseleave', 'div.pick.undrafted', function() {
      pickClickHandler($(this), false);
    });
    
    $(document).on('click', '#back-button', function() {
      var draft_id = parseInt($('#draft-board').data('draft-id'), 10);
      window.location.href = 'public_draft.php?did=' + draft_id;
    });
  }

  function refreshBoard(callback) {
    var currentCounter = parseInt($('#draft-board').data('draft-counter'), 10),
            draftId = parseInt($('#draft-board').data('draft-id'), 10),
            refreshUrl = 'public_draft.php?action=refreshBoard&did=' + draftId + '&currentCounter=' + currentCounter;

    if (checkIsRunning) {
      return;
    }

    checkIsRunning = true;

    $.ajax({
      type: 'GET',
      url: refreshUrl,
      dataType: 'json',
      complete: function() {
        checkIsRunning = false;

        if (typeof(callback) === "function") {
          callback();
        }
      },
      success: function(response) {
        //We may have data to process still, so doing this outside of switch makes sense:
        if(response.Status === "up-to-date") {
          return;
        }
        
        if(response.Status === "draft-complete") {
          clearInterval(intervalID);
        }
        
        if(response.Status === "draft-not-ready") {
          clearInterval(intervalID);
          $('#informationDialog').html('Your draft is not ready yet, sending you back to the draft main page for now, please wait...').dialog('open');
          var draft_id = parseInt($('#draft-board').data('draft-id'), 10);
          window.location.href = 'public_draft.php?did=' + draft_id;
        }
        
        if(response.Players !== undefined) {
          //To reduce slow client performance when we're processing 15 or more new picks, disable overlay:
          var showOverlay = parseInt(response.PlayersCount, 10) < 15;
          
          $.each(response.Players, function() {
            var player = this,
                $pickCell = $('div.pick[data-pick-number="' + player.player_pick + '"]'),
                isDrafted = player.last_name !== null && player.first_name !== null;

            if (isDrafted) {
              var positonClass = player.position;
              switch(player.position) {
                case '1B':
                case '2B':
                case '3B':
                  positonClass = 'x' + player.position;
                  break;
              }
              
              $pickCell.html(pickTemplate(player))
                      .removeClass()
                      .addClass('pick')
                      .addClass(positonClass)
                      .data('pick-duration', player.pick_duration);
            } else {
              $pickCell.find('span.manager').html(player.manager_name);
            }

            if(showOverlay) {
              $pickCell.find('div.overlay').show().fadeOut(1250);
            }
          });

          $('#draft-board').data('draft-counter', parseInt(response.CurrentCounter, 10));
        }
      },
      error: function() {
        $('#informationDialog').html('There was a connection issue while attempting to gather new picks - if the issue persists contact your administrator.').dialog('open');
      }
    });
  }

  function pickClickHandler($pickCell, mouseEnter) {
    if(mouseEnter) {
      $pickCell.find('span.manager').show();
      return;
    }
    
    $pickCell.find('span.manager').hide();
  }
  
  function secondsToWords(seconds) {
    var years,
        weeks,
        days,
        hours,
        minutes,
        seconds;

    years = parseInt(seconds / 31536000);
    
    if(years > 0) {
      return years + " year" + pluralUnitEnding(years);
    }
    
    weeks = parseInt(seconds / 604800);
    
    if(weeks > 0) {
      return weeks + " week" + pluralUnitEnding(weeks);
    }
    
    days = parseInt(seconds / 86400);
    
    if(days > 0) {
      return days + " day" + pluralUnitEnding(days);
    }
    
    hours = parseInt(seconds / 3600);
    
    if(hours > 0) {
      return hours + " week" + pluralUnitEnding(hours);
    }
    
    minutes = parseInt(seconds / 60);
    
    if(minutes > 0) {
      return minutes + " minute" + pluralUnitEnding(minutes);
    }
    
    return seconds + " second" + pluralUnitEnding(seconds);
  }
  
  function pluralUnitEnding(amount) {
    return amount > 1 ? "s" : "";
  }
});