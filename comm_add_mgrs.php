<?php require('check_login.php'); ?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
	<?php require('meta.php'); ?>
	</head>
	<body>
	<div id="page_wrapper">
		<?php require('header.php'); ?>

		<?php
		require_once('cleanstring.php');

		if(!empty($_REQUEST['draft_id']))
		$draft_id = intval($_REQUEST['draft_id']);
		else
		$draft_id = intval($_REQUEST['did']);

		if(empty($draft_id))
		require('comm_menu.php');
		else
		require('comm_draft_menu.php'); ?>

		<div id="content">
		<?php
		function print_add_managers($draft_id, $managers = array()) {
			echo "<form action=\"comm_add_mgrs.php\" method=\"POST\">
			<input type=\"hidden\" name=\"draft_id\" value=\"".$draft_id."\" />
			<fieldset>
			<legend>Add Managers</legend>
			<p><label for=\"mgr_1_name\">Manager 1 Name:</label>
			<input type=\"text\" name=\"mgr_1\" id=\"mgr_1\">".$managers[0]['name']."</input><br />
			<label for=\"mgr_1_team\">Manager 1 Team Name:</label>
			<input type=\"text\" name=\"mgr_1_team\" id=\"mgr_1_team\">".$managers[0]['team_name']."</input></p>

			<p><label for=\"mgr_1_name\">Manager 2 Name:</label>
			<input type=\"text\" name=\"mgr_2\" id=\"mgr_2\">".$managers[1]['name']."</input><br />
			<label for=\"mgr_1_team\">Manager 2 Team Name:</label>
			<input type=\"text\" name=\"mgr_2_team\" id=\"mgr_2_team\">".$managers[1]['team_name']."</input></p>

			<p><label for=\"mgr_1_name\">Manager 3 Name:</label>
			<input type=\"text\" name=\"mgr_3\" id=\"mgr_3\">".$managers[2]['name']."</input><br />
			<label for=\"mgr_1_team\">Manager 3 Team Name:</label>
			<input type=\"text\" name=\"mgr_3_team\" id=\"mgr_3_team\">".$managers[2]['team_name']."</input></p>

			<p><label for=\"mgr_1_name\">Manager 4 Name:</label>
			<input type=\"text\" name=\"mgr_4\" id=\"mgr_4\">".$managers[3]['name']."</input><br />
			<label for=\"mgr_1_team\">Manager 4 Team Name:</label>
			<input type=\"text\" name=\"mgr_4_team\" id=\"mgr_4_team\">".$managers[3]['team_name']."</input></p>

			<p><label for=\"mgr_5_name\">Manager 5 Name:</label>
			<input type=\"text\" name=\"mgr_5\" id=\"mgr_5\">".$managers[4]['name']."</input><br />
			<label for=\"mgr_5_team\">Manager 5 Team Name:</label>
			<input type=\"text\" name=\"mgr_5_team\" id=\"mgr_5_team\">".$managers[4]['team_name']."</input></p>

			<p><label for=\"mgr_6_name\">Manager 6 Name:</label>
			<input type=\"text\" name=\"mgr_6\" id=\"mgr_6\">".$managers[5]['name']."</input><br />
			<label for=\"mgr_6_team\">Manager 6 Team Name:</label>
			<input type=\"text\" name=\"mgr_6_team\" id=\"mgr_6_team\">".$managers[5]['team_name']."</input></p>

			<p><label for=\"mgr_7_name\">Manager 7 Name:</label>
			<input type=\"text\" name=\"mgr_7\" id=\"mgr_7\">".$managers[6]['name']."</input><br />
			<label for=\"mgr_7_team\">Manager 7 Team Name:</label>
			<input type=\"text\" name=\"mgr_7_team\" id=\"mgr_7_team\">".$managers[6]['team_name']."</input></p>

			<p><label for=\"mgr_8_name\">Manager 8 Name:</label>
			<input type=\"text\" name=\"mgr_8\" id=\"mgr_8\">".$managers[7]['name']."</input><br />
			<label for=\"mgr_8_team\">Manager 8 Team Name:</label>
			<input type=\"text\" name=\"mgr_8_team\" id=\"mgr_8_team\">".$managers[7]['team_name']."</input></p>

			<p><label for=\"mgr_9_name\">Manager 9 Name:</label>
			<input type=\"text\" name=\"mgr_9\" id=\"mgr_9\">".$managers[8]['name']."</input><br />
			<label for=\"mgr_9_team\">Manager 9 Team Name:</label>
			<input type=\"text\" name=\"mgr_9_team\" id=\"mgr_9_team\">".$managers[8]['team_name']."</input></p>

			<p><label for=\"mgr_10_name\">Manager 10 Name:</label>
			<input type=\"text\" name=\"mgr_10\" id=\"mgr_10\">".$managers[9]['name']."</input><br />
			<label for=\"mgr_10_team\">Manager 10 Team Name:</label>
			<input type=\"text\" name=\"mgr_10_team\" id=\"mgr_10_team\">".$managers[9]['team_name']."</input></p>
			<input type=\"submit\" value=\"Add Manager(s)\"></input>
			</fieldset>
		</form>";
		}
		
		$managers = array();
		$i = 0;
		while($i < 10) {
			$mgr_name = CleanString(trim($_REQUEST['mgr_'.$i]));
			$team_name = CleanString(trim($_REQUEST['mgr_'.$i.'_team']));
			if(!empty($mgr_name) && !empty($team_name)) {
			$temp_array = array($mgr_name, $team_name);
			array_push($managers, $temp_array);
			}
			$i++;
		}
		
		if(!empty($managers))
			$have_managers = true;
		else
			$have_managers = false;
		$garbage = "JASDJA";
		$draft_result = mysql_query("SELECT draft_id FROM draft WHERE draft_id = '" . $draft_id . "'");
		$draft_found = mysql_num_rows($draft_result);
		if(empty($draft_id) || $draft_found == 0) {
			?><h3>Draft Not Found</h3>
		<p class="error">An error has occurred and the draft you were attempting to add managers to was not found.  Please go back and try again.</p>
	<?php }elseif(!$have_managers && !empty($_REQUEST['draft_id'])) {
	?><h3>Add Managers</h3>
	<p class="error">You must enter at least one manager name! Re-enter your manager names below, then hit "Add Managers" to continue.</p>
	<?php print_add_managers($draft_id);
	}elseif($have_managers) {
	$manager_order_result = mysql_query("SELECT draft_order FROM managers WHERE draft_id = '".$draft_id."' ORDER BY draft_order DESC LIMIT 1");
	$manager_order_row = mysql_fetch_array($manager_order_result);

	$starting_order = intval($manager_order_row['draft_order']) + 1;	//Starting value to assign new managers for drafting order

	foreach($managers as $idx => $manager) {
		$manager_name = $manager[0];
		$team_name = $manager[1];
	   $sql = "INSERT INTO managers ".
		"(manager_id, draft_id, manager_name, team_name, draft_order) ".
		"VALUES ".
		"(NULL, '" . $draft_id . "', '" . $manager_name . "', '" . $team_name . "', ".$starting_order."); ";
	   $starting_order++;
	   $queries[$idx] = $sql;
	}
	
	$successful = true;
	foreach($queries as $idx => $query) {
		$query = trim($query);
		if(!empty($query)) {
		$success = mysql_query($query);
		if(!$success)
			$successful = false;
		}
	}
	
	if($successful) {
		?><h3>Managers Added Successfully!</h3>
		<p class="success">The following managers were added successfully:</p>
		<table width="100%">
		<tr>
			<th>Manager Name</th>
			<th>Team Name</th>
		</tr>
		<?php foreach($managers as $manager) { ?>
		<tr>
			<td><?php echo $manager[0]; ?></td>
			<td><?php echo $manager[1]; ?></td>
		</tr>
		<?php } ?>
		</table>
		<p class="success">Return to the <a href="comm_manage_draft.php?did=<?php echo $draft_id; ?>">draft's main page</a>.</p><?php
	}else {
		?><h3>Managers Not Added</h3>
		<p class="error">An error has occurred and the managers were not added. Please hit the back button on your browser and try again.</p><?php
	}
	
	}else { ?><h3>Add Managers</h3>
		<p>Add managers by entering their names (manager name + team name) below and then hit "Add Managers" to continue.  You can add up to 10 managers at a single time.</p>
	<?php 
	print_add_managers($draft_id);
	} ?>
		</div>
<?php require('footer.php'); ?>
	</div>
	</body>
</html>