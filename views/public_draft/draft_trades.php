<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<?php require('includes/meta.php');?>
		<link href="css/public_draft.css" type="text/css" rel="stylesheet" />
		<link href="css/public_trades.css" type="text/css" rel="stylesheet" />
	</head>
	<body>
		<div id="page_wrapper">
			<?php require('includes/header.php');?>

			<?php require('views/shared/public_draft_menu.php');?>
			<div id="content">
				<h3><?php echo $DRAFT->draft_name; ?> - Draft Trades</h3>
				<p>Here you can see all of the trades that have occurred in the draft up to this point.</p>
				<?php if(NUMBER_OF_TRADES == 0) { ?>
				<h2>No trades have been made yet.</h2>
				<?php } else {
					foreach($DRAFT_TRADES as $trade) {/* @var $trade trade_object */
						$trade_date = php_draft_library::parseObjectDate($trade->trade_time);
						$manager1_assets = $TRADE_SERVICE->getTradeManagerAssets($trade->manager1->manager_id, $trade);
						$manager2_assets = $TRADE_SERVICE->getTradeManagerAssets($trade->manager2->manager_id, $trade);
					?>
				<fieldset>
					<legend><?php echo $trade->manager1->manager_name; ?> and <?php echo $trade->manager2->manager_name; ?> (<?php echo $trade_date; ?>) </legend>
					<div class="trade-details">
						<div class="manager-details manager1-assets">
							<h3><?php echo $trade->manager1->manager_name; ?> received:</h3>
							<?php foreach($manager1_assets as $asset) {/* @var $asset trade_asset_object */ ?>
								<p style="background-color: <?php if($asset->WasDrafted()) { echo $DRAFT->sports_colors[$asset->player->position]; } else { echo "#AAAAAA"; } ?>">
									<span class="player-name"><?php if($asset->WasDrafted()) { echo $asset->player->casualName(); } else { echo "Round #" . $asset->player->player_round . " Pick (#" . $asset->player->player_pick . ")"; } ?></span>
									<?php if($asset->WasDrafted()) { echo "(Pick #" . $asset->player->player_pick . ", " . $asset->player->team . " - " . $asset->player->position . ")<br/>"; }?>
								</p>
							<?php } ?>
						</div>
						<div class="manager-details manager2-assets">
							<h3><?php echo $trade->manager2->manager_name; ?> received:</h3>
							<?php foreach($manager2_assets as $asset) {/* @var $asset trade_asset_object */ ?>
								<p style="background-color: <?php if($asset->WasDrafted()) { echo $DRAFT->sports_colors[$asset->player->position]; } else { echo "#AAAAAA"; } ?>">
									<span class="player-name"><?php if($asset->WasDrafted()) { echo $asset->player->casualName(); } else { echo "Round #" . $asset->player->player_round . " Pick (#" . $asset->player->player_pick . ")"; } ?></span>
									<?php if($asset->WasDrafted()) { echo "(Pick #" . $asset->player->player_pick . ", " . $asset->player->team . " - " . $asset->player->position . ")<br/>"; }?>
								</p>
							<?php } ?>
						</div>
					</div>
				</fieldset>
				<?php } ?>
				<?php } ?>
			</div>
			<?php require('includes/footer.php');?>
			<script type="text/javascript">
				$(document).ready(function() {
					
				});
			</script>
		</div>
	</body>
</html>