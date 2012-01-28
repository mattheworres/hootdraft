<?php

/**
 * Represents a PHPDraft "user" object, which currently there is only one: the Commish
 */
class user_object {
	/** @var int */
	public $UserId;
	/** @var string $Username The handle with which user logged in with */
	public $Username;
	/** @var string $Name The public-visible name for the Commissioner */
	public $Name;
	/** @var string $Password User's password */
	public $Password;
	
	public function __construct($user_id = 0) {
		global $DBH; /* @var $DBH PDO */
		$user_id = (int)$user_id;
		
		if($user_id == 0)
			return false;
		
		$stmt = $DBH->prepare("SELECT * FROM user_login WHERE UserId = ? LIMIT 1");
		$stmt->bindParam(1, $user_id);
		
		$stmt->setFetchMode(PDO::FETCH_INTO, $this);
		
		if(!$stmt->execute())
			return false;
		
		if(!$stmt->fetch())
			return false;
		
		return true;
	}
	
	/**
	 * Grab whatever information is available for the currently logged in user
	 * @return void 
	 */
	public function getCurrentlyLoggedInUser() {
		global $DBH; /* @var $DBH PDO */
		
		$this->UserId = isset($_SESSION['userid']) ? (int)$_SESSION['userid'] : 0;
		$this->Username = isset($_SESSION['username']) ? $_SESSION['username'] : "";
		$this->Password = isset($_SESSION['password']) ? $_SESSION['password'] : "";
		
		if($this->UserId == 0)
			return;
		
		$stmt = $DBH->prepare("SELECT Name FROM user_login WHERE UserID = ? AND Username = ? AND Password = ? LIMIT 1");
		$stmt->bindParam(1, $this->UserId);
		$stmt->bindParam(2, $this->Username);
		$stmt->bindParam(3, $this->Password);
		
		if(!$stmt->execute())
			return;
		
		$name_row = $stmt->fetch();
		
		$name = $name_row['Name'];
		
		$this->Name = strlen($name) > 0 ? $name : "The Commish";
	}
	
	/**
	 * Given the ID of a commissioner, grab and populate the object
	 * @param int $commish_id
	 * @return void 
	 */
	public function getDefaultCommissioner($commish_id) {
		global $DBH; /* @var $DBH PDO */
		$commish_id = (int)$commish_id;
		
		if($commish_id == 0)
			return;
		
		$stmt = $DBH->prepare("SELECT * FROM user_login WHERE UserId = ? LIMIT 1");
		$stmt->bindParam(1, $commish_id);
		
		$stmt->setFetchMode(PDO::FETCH_INTO, $this);
		
		if(!$stmt->execute())
			return;
		
		$stmt->fetch();
	}
	
	/**
	 * Given a plaintext password, return the hashed version according to hashing method specified
	 * @param string $rawPassword
	 * @return string Hashed password string 
	 */
	public static function getHashedPassword($rawPassword) {
		return sha1($rawPassword);
	}
	
	/**
	 * Given a raw plaintext password and use the specified hashing method to store it in the object
	 */
	public function setHashedPassword($rawPassword) {
		$this->Password = sha1($rawPassword);
	}
	
	/**
	 * Check to see if the user object is authentic or not
	 * @return bool If the user is authenticated or not
	 */
	public function userAuthenticated() {
		global $DBH; /* @var $DBH PDO */
		if($this->UserId == 0 
			|| !isset($this->Username) 
			|| strlen($this->Username) == 0 
			|| !isset($this->Password) 
			|| strlen($this->Password) == 0)
			return false;
		
		$stmt = $DBH->prepare("SELECT UserID FROM user_login WHERE UserID = ? AND UserName = ? AND Password = ?");
		$stmt->bindParam(1, $this->UserId);
		$stmt->bindParam(2, $this->Username);
		$stmt->bindParam(3, $this->Password);
		
		if(!$stmt->execute())
			return false;
		
		if($stmt->rowCount() == 0)
			return false;
		
		return true;
	}
	
	/**
	 * Updates the entity object in the database. For the time being, this only updates and does not account for the creation of new users.
	 * @return boolean success whether or not the MySQL transaction succeeded.
	 */
	public function saveUser() {
		global $DBH; /* @var $DBH PDO */
		$param_number = 2;
		//TODO: Remove this hack. This assumes a single user being edited by himself:
		$this->setLoggedInId();
		
		$update_sql = "UPDATE user_login SET Username = ?";
		
		if($this->hasPassword())
			$update_sql .= ",  Password = ?";
		
		$update_sql .= ",  Name = ? WHERE UserID = ? LIMIT 1";
		
		$stmt = $DBH->prepare($update_sql);
		$stmt->bindParam(1, $this->Username);
		
		if($this->hasPassword()) {
			$stmt->bindParam($param_number, $this->Password);
			$param_number++;
		}
		
		$stmt->bindParam($param_number, $this->Name);
		$param_number++;
		$stmt->bindParam($param_number, $this->UserId);
		$param_number++;
		
		if(!$stmt->execute())
			return false;
		
		if($stmt->rowCount() == 1) {
			$this->updateAuthentication();
			return true;
		} else
			return false;
	}
	
	/**
	 * Assuming user information is up-to-date, update the session variables accordingly.
	 */
	public function updateAuthentication() {
		if($this->UserId > 0) {
			$_SESSION['userid'] = (int)$this->UserId;
			$_SESSION['username'] = $this->Username;
			$_SESSION['password'] = $this->Password;
		}
	}
	
	/**
	 * Whether or not user has a password.
	 * @return type 
	 */
	private function hasPassword() {
		return isset($this->Password) && strlen($this->Password) > 0;
	}
	
	/**
	 * Set the current user's ID for the currently logged in user
	 */
	private function setLoggedInId() {
		$this->UserId = (int)$_SESSION['userid'];
	}
}
?>
