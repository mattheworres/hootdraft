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
    
    public function getHashedPassword() {
        return sha1($this->password);
    }
    
    //TODO: Change definition of password - assumption to be that user-object ALWAYS has the password that is hashed. getHashedPassword has to be modified, and we must provide the setter for that too.
    
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
        $update_sql = "UPDATE user_login SET Username = '" . $this->user_name . "' AND Password = '" . $this->password . "' AND Name = '" . $this->public_name . "'
            WHERE UserId = " . $this->user_id;
        
        return mysql_query($update_sql);
    }
}
?>
