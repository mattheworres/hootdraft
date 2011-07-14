<?php

/**
 * Represents a PHPDraft "user" object, which currently there is only one: the Commish
 * 
 * @property int $user_id The unique identifier for this user
 * @property string $user_name The handle with which user logged in with
 * @property string $public_name The public-visible name for the Commissioner
 * @property string $password User's password
 * 
 * @method void getCurrentlyLoggedInUser() Grab whatever information is available for the currently logged in user
 */
class user_object {
    public $user_id;
    public $user_name;
    public $public_name;
    public $password;
    
    public function __construct(array $properties = array()) {
        foreach($properties as $property => $value)
            if(property_exists('user_object', $property))
                    $this->$property = $value;
    }
    
    public function getCurrentlyLoggedInUser() {
        $this->user_id = intval($_SESSION['userid']);
        $this->user_name = $_SESSION['username'];
        $this->password = $_SESSION['password'];
        
        if($this->user_id == 0)
            return;
        
        $nameResult = mysql_fetch_array(mysql_query("SELECT Name FROM user_login WHERE UserID = " . $this->user_id . " AND Username = '" . $this->user_name . "' AND Password = '" . $this->password . "' LIMIT 1"));
        
        $this->public_name = strlen($nameResult['Name']) > 0 ? $nameResult['Name'] : "The Commish";
    }
    
    /**
     * Given the ID of a commissioner, grab and populate the object
     * @param int $commish_id
     * @return void 
     */
    public function getDefaultCommissioner($commish_id) {
        $id_int = intval($commish_id);
        
        if($id_int == 0)
            return;
        
        $commish_sql = "SELECT * FROM user_login WHERE UserId = " . $id_int . " LIMIT 1";
        $commish_row = mysql_fetch_array(mysql_query($commish_sql));
        
        $this->user_id = $commish_row['UserId'];
        $this->user_name = $commish_row['Username'];
        $this->public_name = $commish_row['Name'];
        $this->password = $commish_row['Password'];
    }
    
    /**
     * To set the current user's ID for the currently logged in user
     */
    private function getLoggedInId() {
        $this->user_id = intval($_SESSION['userid']);
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
     * Given a raw plaintext password and use the specified hashing method and store it in the object
     */
    public function setHashedPassword($rawPassword) {
        $this->password = sha1($rawPassword);
    }
    
    /**
     * Check to see if the user object is authentic or not
     * @return bool If the user is authenticated or not
     */
    public function userAuthenticated() {
        if($this->user_id == 0 
            || !isset($this->user_name) 
            || strlen($this->user_name) == 0 
            || !isset($this->password) 
            || strlen($this->password) == 0)
            return false;
        
        $user_result = mysql_query("SELECT UserID
                                    FROM user_login 
                                    WHERE 
                                    UserID = '" . $this->user_id . "' AND
                                    UserName = '" . $this->user_name . "' AND
                                    Password = '" . $this->password . "'
                               ");
        
        if(!$user_row = mysql_fetch_array($user_result))
            return false;
       
        return true;
    }
    
    /**
     * Updates the entity object in the database. For the time being, this only updates and does not account for the creation of new users.
     * @return boolean success whether or not the MySQL transaction succeeded.
     */
    public function saveUser() {
        //TODO: Remove this hack. This assumes a single user being edited by himself.
        $this->getLoggedInId();
        
        $update_sql = "UPDATE user_login SET Username = '" . $this->user_name . "'";
        
        if(isset($this->password) && strlen($this->password) > 0)
                $update_sql .= ",  Password = '" . $this->password . "'";
        
        $update_sql .= ",  Name = '" . $this->public_name . "' WHERE UserID = " . $this->user_id;
        
        $success = mysql_query($update_sql);
        
        if($success)
            $this->updateAuthentication();
        
        return $success;
    }
    
    /**
     * Assuming user information is up-to-date, update the session variables accordingly.
     */
    public function updateAuthentication() {
        if($this->user_id > 0) $_SESSION['userid'] = $this->user_id;
        $_SESSION['username'] = $this->user_name;
        $_SESSION['password'] = $this->password;
    }
}
?>
