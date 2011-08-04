<?php
require("/includes/global_setup.php");
require_once("/includes/check_login.php");

DEFINE("ACTIVE_TAB", "CONTROL_PANEL");

switch($_GET['action']) {
	case 'createDraft':
		//<editor-fold defaultstate="collapsed" desc="createDraft Logic">
		$draft = new draft_object();
		DEFINE("CONTROL_PANEL_ACTION", "CREATE");
		require_once("/views/control_panel/create_draft.php");
		//</editor-fold>
		break;

	case 'addDraft':
		//<editor-fold defaultstate="collapsed" desc="addDraft Logic">
		$ERRORS = array();

		$draft = new draft_object();
		$draft->draft_name = trim($_POST['draft_name']);
		$draft->draft_sport = trim($_POST['draft_sport']);
		$draft->draft_style = trim($_POST['draft_style']);
		$draft->draft_rounds = (int)$_POST['draft_rounds'];

		$object_errors = $draft->getValidity();

		if(count($object_errors) > 0) {
			$ERRORS = $object_errors;
			DEFINE("CONTROL_PANEL_ACTION", "ADD");
			require_once("/views/control_panel/create_draft.php");
			exit(1);
		}

		if($draft->saveDraft() == false) {
			$ERRORS[] = "Draft could not be saved, please try again.";
			DEFINE("CONTROL_PANEL_ACTION", "ADD");
			require_once("/views/control_panel/create_draft.php");
			exit(1);
		}

		define("PAGE_HEADER", "Draft Successfully Created");
		define("P_CLASS", "success");
		define("PAGE_CONTENT", "Your draft, <em>" . $draft->draft_name . "</em> has been successfully created.  <a href=\"control_panel.php?action=manageDraft&did=" . $draft->draft_id . "\">Click here</a> to manage your new draft.<br/><br/>REMEMBER: Your next step should be to add all managers before you begin drafting players.");
		require_once("/views/shared/generic_result_view.php");
		//</editor-fold>
		break;
		
	case 'manageDrafts':
		// <editor-fold defaultstate="collapsed" desc="manageDrafts Logic">
		$DRAFTS = draft_object::getAllDrafts();
		DEFINE("CONTROL_PANEL_ACTION", "MANAGE");
		require_once('/views/control_panel/manage_draft.php');
		// </editor-fold>
		break;
		
	case 'manageProfile':
		// <editor-fold defaultstate="collapsed" desc="manageProfile Logic">
		DEFINE("CONTROL_PANEL_ACTION", "MANAGE");
		require_once("/models/user_edit_model.php");
		
		$loggedInUser = new user_object();
		$loggedInUser->getCurrentlyLoggedInUser();
		$user_view_model = new user_edit_model($loggedInUser);
		
		require_once("/views/control_panel/manage_profile.php");
		// </editor-fold>
		break;
		
	case 'saveProfile':
		// <editor-fold defaultstate="collapsed" desc="saveProfile Logic">
		require_once("/models/user_edit_model.php");
		
		$ERRORS = array();
		
		$loggedInUser = new user_object();
		$loggedInUser->getCurrentlyLoggedInUser();
		$user_view_model = new user_edit_model($loggedInUser);
		
		$user_view_model->getFormValues();
		
		$object_errors = $user_view_model->getValidity();
		
		if(count($object_errors) > 0) {
			$ERRORS = $object_errors;
			require_once("/views/control_panel/manage_profile.php");
			break;
		}
		
		if(!$user_view_model->saveUser()) {
			$ERRORS[] = "An error has occurred and your profile could not be updated. Please try again.";
			require_once("/views/control_panel/manage_profile.php");
			break;
		}
		
		define("PAGE_HEADER", "Profile Successfully Updated");
		define("P_CLASS", "success");
		define("PAGE_CONTENT", "Your user account has been successfully updated. <a href=\"control_panel.php?action=manageProfile\">Click here</a> to change your profile again, or <a href=\"control_panel.php\">click here</a> to be taken back to the control panel.");
		require_once("/views/shared/generic_result_view.php");
		// </editor-fold>
		break;

	case '':
	case 'home':
	default:
		$DRAFTS = draft_object::getAllDrafts();
		require_once('/views/control_panel/index.php');
		break;
}

?>