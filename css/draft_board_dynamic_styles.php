<?php
header('Content-Type: text/css');
//Dynamically create CSS file containing classed background colors for the draft board
require("../libraries/sports_values_library.php");
$sports_values = new sports_values_library();

foreach($sports_values->position_colors as $position => $hex_color_key) {
?>
#draft-board div.pick.<?php echo $position;?>, #alreadyPickedDialog strong.<?php echo $position; ?> {
  background-color: <?php echo $hex_color_key;?>;
}

<?php } ?>