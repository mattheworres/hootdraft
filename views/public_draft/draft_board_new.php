<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">

    <link href="css/style.css" type="text/css" rel="stylesheet" />
    <link href="css/jquery-ui-1.10.3.min.css" type="text/css" rel="stylesheet" />
    <link href="css/draft_board_dynamic_styles.php" type="text/css" rel="stylesheet" />
    <link href="css/draft_Board.css" type="text/css" rel="stylesheet" />

    <title>PHPDraft - <?php echo $DRAFT->draft_name; ?> - Draft Board</title>
  </head>
  <body>
    <div id="draft-board" style="width: <?php echo TOTAL_WIDTH; ?>px;" 
         data-draft-reload="<?php echo BOARD_RELOAD; ?>" 
         data-draft-id="<?php echo $DRAFT->draft_id;?>"
         data-draft-counter="0">
      <?php
      for ($i = 1; $i <= $DRAFT->draft_rounds; ++$i) {
        $picks_row = $ALL_PICKS[$i - 1];
        ?>
        <div class="row">
          <div class="round-number">
            <?php echo $i; ?>
          </div>
          <?php foreach ($picks_row as $pick) { ?>
            <!--
            TODO: Pull this structure out into an Underscore template
            TODO: Begin writing the JS that grabs this data from the server, and spins through it and puts it on the screen
            TODO: Re-write server logic JS hits to combine both update check + new data retrieval into one request
            -->
            <div class="pick" data-pick-number="<?php echo $pick->player_pick; ?>">
              <span class="pick-number"><?php echo $pick->player_pick; ?></span>
            </div>
          <?php } ?>
        </div>
      <?php } ?>
    </div>
  </body>
  <script type="text/javascript" src="js/jquery-1.10.2.min.js"></script><script src="http://ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
  <script>window.jQuery || document.write('<script src="js/jquery-1.10.2.min.js">\x3C/script>');</script>
  <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.10.3/jquery-ui.js"></script>
  <script>window.jQuery.ui || document.write('<script src="js/jquery-ui-1.10.3.min.js">\x3C/script>');</script>
  <script type="text/javascript" src="js/underscore-min.js"></script>
<!-- TODO: Move to separate JS file -->
  <script type="text/javascript">
    var poll_time = 1000 * <?php echo BOARD_RELOAD; ?>,
        our_pick = <?php echo $DRAFT->draft_current_pick; ?>,
        last_pick = <?php echo $DRAFT->draft_rounds * NUMBER_OF_MANAGERS; ?>;

    intervalID = 0,
    checkIsRunning = false;

    $(document).ready(function() {
      var reloadMs = 1000*parseInt($('#draft-board').data('draft-reload'), 10);
      
      _.templateSettings.variable = "pick";
      
      pickTemplate = _.template(
          $("#pickTemplate").html()
      );
    
      refreshBoard();
              
      intervalID = setInterval(function() {
        refreshBoard();
      }, reloadMs);
    });
    
    function refreshBoard() {
      var currentCounter = parseInt($('#draft-board').data('draft-counter'), 10),
          draftId = parseInt($('#draft-board').data('draft-id'), 10),
          refreshUrl = 'public_draft.php?action=refreshBoard&did=' + draftId + '&currentCounter=' + currentCounter;
  
      if(checkIsRunning) {
        console.log('No man, too quick!!');   
        return;
      }
      
      checkIsRunning = true;
      
      $.ajax({
        type: 'GET',
        url: refreshUrl,
        dataType: 'json',
        complete: function() {
          checkIsRunning = false;
        },
        success: function(response) {
          switch(response.Status) {
            case "draft-complete":
              //Do something to stop the interval.
              break;
              
            case "up-to-date":
              //do nothing right now
              break;

            case "out-of-date":
              $.each(response.Players, function() {
                var player = this,
                    $pickCell = $('div.pick[data-pick-number="' + player.player_pick + '"]');
                    
                    console.log('This: ' + 'div.pick[data-pick-number="' + player.player_pick + '"]');
                    
                $pickCell.html(pickTemplate(player)).addClass(player.position);
              });
              
              $('#draft-board').data('draft-counter', parseInt(response.CurrentCounter, 10));
              break;
          }
        },
        error: function() {
          //TODO: No.
          alert('There was an issue brah.');
        }
      });
    }
  </script>
  
  <script type="text/template" id="pickTemplate">
    <span class="pick-number"><%- pick.player_pick %></span>
    <span class="first-name"><%- pick.first_name %></span>
    <span class="last-name"><%- pick.last_name %></span>
    <span class="manager"><%- pick.manager_name %></span>
    <span class="position"><%- pick.position %></span>
    <span class="team"><%- pick.team %></span> 
  </script>
</html>