<?php
require_once("check_login.php");
require_once("models/draft_object.php");

DEFINE("ACTIVE_TAB", "CONTROL_PANEL");

switch($_REQUEST['action']) {
    case 'createDraft':
        $draft = new draft_object();
        require_once("/views/control_panel_create_draft_view.php");
        break;

    case 'addDraft':
        $ERRORS = array();

        $draft = new draft_object(array(
            'draft_name' => CleanString(trim($_POST['draft_name'])),
            'draft_sport' => CleanString(trim($_POST['draft_sport'])),
            'draft_style' => CleanString(trim($_POST['draft_style'])),
            'draft_rounds' => intval($_POST['draft_rounds'])
        ));

        $object_errors = $draft->getValidity();

        if(count($object_errors) > 0) {
            $ERRORS = $object_errors;
            require_once("/views/control_panel_create_draft_view.php");
            break;
        }

        if($draft->saveDraft() == false) {
            $ERRORS[] = "Draft could not be saved, please try again.";
            require_once("/views/control_panel_create_draft_view.php");
            break;
        }

        define("PAGE_TITLE", "Draft Successfully Created");
        define("PAGE_CONTENT", "<p class=\"success\">Your draft, <em>" . $draft->draft_name . "</em> has been successfully created.  <a href=\"control_panel.php?action=manageDraft&draftId=" . $draft->draft_id . "\">Click here</a> to manage your new draft.</p><p>REMEMBER: Your next step should be to add all managers before you begin drafting players.</p>");
        require_once("/views/generic_success_view.php");

        break;

    case 'manageDraft':
        echo "You are managing your draft.";
        //require_once('/views/CHANGE_ME___.php');
        break;

    case 'manageProfile':
        echo "You are managing your profile.";
        //require_once('/views/CHANGE_ME___.php');
        break;

    case '':
    case 'home':
    default:
        require_once('/views/control_panel_view.php');
        break;
}

?>