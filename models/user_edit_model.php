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
	
	public function __construct(user_object $user_object = null) {
		if($user_object === null)
			return;
		
		$this->username = $user_object->Username;
		$this->public_name = $user_object->Name;
	}
	
	public function getFormValues() {
		if(isset($_POST['username'])) $this->username = $_POST['username'];
		if(isset($_POST['old_password'])) {
			$this->oldPassword = user_object::getHashedPassword($_POST['old_password']);
			if(isset($_POST['new_password'])) $this->newPassword = user_object::getHashedPassword($_POST['new_password']);
			if(isset($_POST['verify_password'])) $this->newVerifiedPassword = user_object::getHashedPassword($_POST['verify_password']);
		}
		if(isset($_POST['name'])) $this->public_name = $_POST['name'];
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
			$current_user = new user_object();
			$current_user->getCurrentlyLoggedInUser();
			
			if($current_user->Password != $this->oldPassword)
					$errors[] = "You have entered your old password incorrectly.";
			
			if(!isset($this->newPassword) || strlen(trim($this->newPassword)) == 0 || !isset($this->newVerifiedPassword) || strlen(trim($this->newVerifiedPassword)) == 0)
					$errors[] = "If you are changing your password, you must provide a new password (and verify it) in order to proceed.";
			
			if($this->newPassword != $this->newVerifiedPassword)
					$errors[] = "Your new passwords do not match. Please check for errors and try again.";
		}

		return $errors;
	}
	
	/**
	 * Converts this view model into a reasonable entity equivalent, and then performs the save function with that entity.
	 * @return boolean success whether or not the MySQL transaction succeeded.
	 */
	public function saveUser() {
		$user_entity_object = new user_object();
		$user_entity_object->Username = $this->username;
		$user_entity_object->Name = $this->public_name;
		
		if(isset($this->oldPassword) && isset($this->newPassword))
			$user_entity_object->Password = $this->newPassword;
		
		return $user_entity_object->saveUser();
	}
}
?>
