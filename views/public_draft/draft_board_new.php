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
    <div id="draft-board" style="width: <?php echo TOTAL_WIDTH; ?>px;">
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
            TODO: Update draft + player tables to carry draft counter fields
            TODO: Update draft + player services to properly handle an increment draft counter (picks, trades, edits)
            TODO: Begin writing the JS that grabs this data from the server, and spins through it and puts it on the screen
            TODO: Re-write server logic JS hits to combine both update check + new data retrieval into one request
            -->
            <div class="pick <?php echo $pick->position; ?>" data-pick-number="<?php echo $pick->player_pick; ?>">
              <span class="pick-number"><?php echo $pick->player_pick; ?></span>
              <?php if (isset($pick->pick_time)) { ?>
                <span class="first-name"><?php echo $pick->first_name; ?></span>
                <span class="last-name"><?php echo $pick->last_name; ?></span>
                <span class="manager"><?php echo $pick->manager_name; ?></span>
                <span class="position"><?php echo $pick->position; ?></span>
                <span class="team"><?php echo $pick->team; ?></span>
              <?php } ?>
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

  <script type="text/javascript">
    var poll_time = 1000 * <?php echo BOARD_RELOAD; ?>,
            our_pick = <?php echo $DRAFT->draft_current_pick; ?>,
            last_pick = <?php echo $DRAFT->draft_rounds * NUMBER_OF_MANAGERS; ?>,
            intervalID;

    $(document).ready(function() {

    });
  </script>
</html>