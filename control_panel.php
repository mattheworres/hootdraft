<?php
require("includes/global_setup.php");
require("includes/check_login.php");

DEFINE("ACTIVE_TAB", "CONTROL_PANEL");
DEFINE("ACTION", isset($_GET['action']) ? $_GET['action'] : "");

switch(ACTION) {
	case 'createDraft':
		//<editor-fold defaultstate="collapsed" desc="createDraft Logic">
		$draft = new draft_object();
		DEFINE("CONTROL_PANEL_ACTION", "CREATE");
		require_once("views/control_panel/create_draft.php");
		//</editor-fold>
		break;

	case 'addDraft':
		//<editor-fold defaultstate="collapsed" desc="addDraft Logic">
		$ERRORS = array();
		
		$draft_name = isset($_POST['draft_name']) ? trim($_POST['draft_name']) : "";
		$draft_sport = isset($_POST['draft_sport']) ? trim($_POST['draft_sport']) : "";
		$draft_style = isset($_POST['draft_style']) ? trim($_POST['draft_style']) : "";
		$draft_rounds = isset($_POST['draft_rounds']) ? (int)$_POST['draft_rounds'] : "";

		$draft = new draft_object();
		$draft->draft_name = $draft_name;
		$draft->draft_sport = $draft_sport;
		$draft->draft_style = $draft_style;
		$draft->draft_rounds = $draft_rounds;

		$object_errors = $draft->getValidity();

		if(count($object_errors) > 0) {
			$ERRORS = $object_errors;
			DEFINE("CONTROL_PANEL_ACTION", "ADD");
			require_once("views/control_panel/create_draft.php");
			exit(1);
		}

		if($draft->saveDraft() == false) {
			$ERRORS[] = "Draft could not be saved, please try again.";
			DEFINE("CONTROL_PANEL_ACTION", "ADD");
			require_once("views/control_panel/create_draft.php");
			exit(1);
		}

		define("PAGE_HEADER", "Draft Successfully Created");
		define("P_CLASS", "success");
		define("PAGE_CONTENT", "Your draft, <em>" . $draft->draft_name . "</em> has been successfully created.  <a href=\"draft.php?did=" . $draft->draft_id . "\">Click here</a> to manage your new draft.<br/><br/>REMEMBER: Your next step should be to add all managers before you begin drafting players.");
		require_once("views/shared/generic_result_view.php");
		//</editor-fold>
		break;
		
	case 'manageDrafts':
		// <editor-fold defaultstate="collapsed" desc="manageDrafts Logic">
		$DRAFTS = draft_object::getAllDrafts();
		DEFINE("CONTROL_PANEL_ACTION", "MANAGE");
		require_once('views/control_panel/manage_draft.php');
		// </editor-fold>
		break;
		
	case 'manageProfile':
		// <editor-fold defaultstate="collapsed" desc="manageProfile Logic">
		DEFINE("CONTROL_PANEL_ACTION", "MANAGE");
		require_once("models/user_edit_model.php");
		
		$loggedInUser = new user_object();
		$loggedInUser->getCurrentlyLoggedInUser();
		$user_view_model = new user_edit_model($loggedInUser);
		
		require_once("views/control_panel/manage_profile.php");
		// </editor-fold>
		break;
		
	case 'saveProfile':
		// <editor-fold defaultstate="collapsed" desc="saveProfile Logic">
		require_once("models/user_edit_model.php");
		
		$ERRORS = array();
		
		$loggedInUser = new user_object();
		$loggedInUser->getCurrentlyLoggedInUser();
		$user_view_model = new user_edit_model($loggedInUser);
		
		$user_view_model->getFormValues();
		
		$object_errors = $user_view_model->getValidity();
		
		if(count($object_errors) > 0) {
			$ERRORS = $object_errors;
			require_once("views/control_panel/manage_profile.php");
			break;
		}
		
		if(!$user_view_model->saveUser()) {
			$ERRORS[] = "An error has occurred and your profile could not be updated. Please try again.";
			require_once("views/control_panel/manage_profile.php");
			break;
		}
		
		define("PAGE_HEADER", "Profile Successfully Updated");
		define("P_CLASS", "success");
		define("PAGE_CONTENT", "Your user account has been successfully updated. <a href=\"control_panel.php?action=manageProfile\">Click here</a> to change your profile again, or <a href=\"control_panel.php\">click here</a> to be taken back to the control panel.");
		require_once("views/shared/generic_result_view.php");
		// </editor-fold>
		break;
		
	case 'updateProPlayers':
		require_once("views/control_panel/update_pro_players.php");
		exit(0);
		break;
	
	case 'uploadProPlayers':
		header('Cache-Control: no-cache, must-revalidate');
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
		header('Content-type: application/json');
		
		if(!isset($_POST['sport'])) {
			$obj = array();
			$obj["Success"] = "false";
			$obj["Message"] = "Must provide a sport.";
			echo json_encode($obj);
			exit(1);
		}
			
		$sport = $_POST['sport'];
		
		if($sport != "NFL" && $sport != "MLB" && $sport != "NHL" && $sport != "NBA") {
			$obj = array();
			$obj["Success"] = "false";
			$obj["Message"] = "Invalid value for sport.";
			echo json_encode($obj);
			exit(1);
		}
		
		if(!isset($_FILES['csv_file'])) {
			$obj = array();
			$obj["Success"] = "false";
			$obj["Message"] = "Must upload a CSV file";
			echo json_encode($obj);
			exit(1);
		}
		
		if($_FILES['csv_file']['error'] > 0) {
			$obj = array();
			$obj["Success"] = "false";
			$obj["Message"] = "Upload error - " . $_FILES['csv_file']['error'];
			echo json_encode($obj);
			exit(1);
		}
		
		$extension = $ext = strtolower(end(explode('.', $_FILES['csv_file']['name'])));
		
		if($extension != "csv") {
			$obj = array();
			$obj["Success"] = "false";
			$obj["Message"] = "File uploaded must be a CSV!";
			echo json_encode($obj);
			exit(1);
		}
		
		$tempName = $_FILES['csv_file']['tmp_name'];
		$players = array();
		
		if(($handle = fopen($tempName, 'r')) !== FALSE) {
			global $PHPD; /** @var @PHPD PHPDRAFT */
			
			if($PHPD->useCsvTimeout())
				set_time_limit(0);
			
			while(($data = fgetcsv($handle, 1000, ';')) !== FALSE) {
				if($data[0] == "Player")
					continue;
				
				$new_player = new pro_player_object();
				
				$new_player->league = $sport;
				$name_column = explode(",", $data[0]);
				
				if(count($name_column) == 2) {
					$new_player->last_name = trim($name_column[0]);
					$new_player->first_name = trim($name_column[1]);
				} else {
					$new_player->last_name = "Player";
					$new_player->first_name = "Unknown";
				}
				
				$new_player->position = isset($data[1]) ? trim($data[1]) : '';
				$new_player->team = isset($data[2]) ? trim($data[2]) : '';

				$players[] = $new_player;
			}
			
			fclose($handle);
			
			if(!pro_player_object::savePlayers($sport, $players)) {
				$obj = array();
				$obj["Success"] = "false";
				$obj["Message"] = "Error encountered when updating new players to database.";
				echo json_encode($obj);
				exit(1);
			}
			
			$obj = array();
			$obj["Success"] = "true";
			$obj["Message"] = "";
			echo json_encode($obj);
			exit(0);
		} else {
			$obj = array();
			$obj["Success"] = "false";
			$obj["Message"] = "Files permission issue: unable to open CSV on server.";
			echo json_encode($obj);
			exit(1);
		}
		
		break;

	case '':
	case 'home':
	default:
		$DRAFTS = draft_object::getAllDrafts();
		require_once('views/control_panel/index.php');
		break;
}

?>