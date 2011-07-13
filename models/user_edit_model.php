<?php

/**
 * View model for the "Manage Profile" portion of the Commissioner control panel
 */
class user_edit_model {
    public $username;
    public $oldPassword;
    public $newPassword;
    public $newVerifiedPassword;
    public $public_name;
    
    public function getFormValues() {
        $this->username = $_REQUEST['username'];
        $this->oldPassword = $_REQUEST['old_password'];
        $this->newPassword = $_REQUEST['new_password'];
        $this->newVerifiedPassword = $_REQUEST['verify_password'];
        $this->public_name = $_REQUEST['name'];
    }
    
    /**
     * Check the validity of user object and return array of error descriptions if invalid.
     * @return array errors
     */
    public function getValidity() {
        $errors = array();

        if(empty($this->username))
            $errors[] = "User Name is empty.";
        if(empty($this->public_name))
            $errors[] = "Public Name is empty.";
        
        if(strlen($this->oldPassword) > 0) {
            if(!isset($this->newPassword) || strlen(trim($this->newPassword)) || !isset($this->newVerifiedPassword) || strlen(trim($this->newVerifiedPassword)))
                    $errors[] = "If you are changing your password, you must provide a new password (and verify it) in order to proceed.";
            else if($this->newPassword != $this->newVerifiedPassword)
                    $errors[] = "Your new passwords do not match. Please check for errors and try again.";
        }

        return $errors;
    }
    
    /**
     * Updates the current commissioner user account
     * @return boolean success whether or not the MySQL transaction succeeded.
     */
    public function saveUser() {
        //TODO: Make this function a transform - take this view model and turn it into an entity model as best we can, so we can turn things over to the entity for saving. Don't want to delegate to view models for those features.
    }
}
?>
