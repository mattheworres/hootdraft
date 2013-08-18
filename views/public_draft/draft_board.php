<!DOCTYPE html>
<html lang="en">
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
            <div class="pick undrafted" data-pick-number="<?php echo $pick->player_pick; ?>">
              <div class="overlay"></div>
              <span class="pick-number"><?php echo $pick->player_pick; ?></span>
              <span class="manager"><?php echo $pick->manager_name; ?></span>
            </div>
          <?php } ?>
        </div>
      <?php } ?>
    </div>
    <div id="loadingDialog">
      <img src="images/loading.gif" alt="Loading..."/>Loading...
    </div>
    <div id="informationDialog"></div>
  </body>
  <script type="text/javascript" src="js/jquery-1.10.2.min.js"></script><script src="http://ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
  <script>window.jQuery || document.write('<script src="js/jquery-1.10.2.min.js">\x3C/script>');</script>
  <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.10.3/jquery-ui.js"></script>
  <script>window.jQuery.ui || document.write('<script src="js/jquery-ui-1.10.3.min.js">\x3C/script>');</script>
  <script type="text/javascript" src="js/underscore-min.js"></script>
  <script type="text/javascript" src="js/public_draft_board.js"></script>
  
  <script type="text/template" id="pickTemplate">
    <div class="overlay"></div>
    <span class="pick-number"><%- pick.player_pick %></span>
    <span class="first-name"><%- pick.first_name %></span>
    <span class="last-name"><%- pick.last_name %></span>
    <span class="manager"><%- pick.manager_name %></span>
    <span class="position"><%- pick.position %></span>
    <span class="team"><%- pick.team %></span> 
  </script>
</html>