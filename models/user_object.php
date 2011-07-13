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
    
    public function getHashedPassword() {
        return sha1($this->password);
    }
    
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
    
    public function saveUser() {
        //This is where the user object should be saved.
    }
}
?>
