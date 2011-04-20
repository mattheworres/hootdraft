<?php
require_once("check_login.php");
require_once("models/draft_object.php");

DEFINE("ACTIVE_TAB", "CONTROL_PANEL");

switch($_REQUEST['action']) {
    case 'createDraft':
        //<editor-fold defaultstate="collapsed" desc="createDraft Logic">
        $draft = new draft_object();
        DEFINE("CONTROL_PANEL_ACTION", "CREATE");
        require_once("/views/control_panel_create_draft_view.php");
        break;
        //</editor-fold>

    case 'addDraft':
        //<editor-fold defaultstate="collapsed" desc="addDraft Logic">

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
            DEFINE("CONTROL_PANEL_ACTION", "ADD");
            require_once("/views/control_panel_create_draft_view.php");
            break;
        }

        if($draft->saveDraft() == false) {
            $ERRORS[] = "Draft could not be saved, please try again.";
            DEFINE("CONTROL_PANEL_ACTION", "ADD");
            require_once("/views/control_panel_create_draft_view.php");
            break;
        }

        define("PAGE_HEADER", "Draft Successfully Created");
        define("PAGE_CONTENT", "<p class=\"success\">Your draft, <em>" . $draft->draft_name . "</em> has been successfully created.  <a href=\"control_panel.php?action=manageDraft&draftId=" . $draft->draft_id . "\">Click here</a> to manage your new draft.</p><p>REMEMBER: Your next step should be to add all managers before you begin drafting players.</p>");
        require_once("/views/generic_success_view.php");
        //</editor-fold>
        break;

    case 'manageDraft':
        // <editor-fold desc="manageDraft Logic">
        //TODO: Look to old comm_manage_draft.php for logic to put here; still need to clean
        //control_panel_manage_draft_view.php into an acceptable view.
        break;
        // </editor-fold>
    case 'manageProfile':
        
        //require_once('/views/CHANGE_ME___.php');
        break;

    case '':
    case 'home':
    default:
        require_once('/views/control_panel_view.php');
        break;
}

?>