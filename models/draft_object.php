<?php
/**
 * Represents a PHPDraft "draft" object, which is the parent object.
 *
 * A draft has many managers, and managers have many players (picks).
 */
class draft_object {
    public $draft_id;
    public $draft_name;
    public $draft_status;
    public $visibility;
    public $draft_sport;
    public $draft_style;
    public $draft_rounds;

    public function  __construct(array $properties = array()) {
        foreach($properties as $property => $value)
            if(property_exists('draft_object', $property))
                $this->$property = $value;
    }

    /**
     * Check the validity of parent draft object and return array of error descriptions if invalid.
     * @return array/string errors
     */
    public function getValidity() {
        $errors = array();

        if(empty($this->draft_name))
            $errors[] = "Draft Name is empty.";
        if(empty($this->draft_sport))
            $errors[] = "Draft Sport is empty.";
        if(empty($this->draft_style))
            $errors[] = "Draft Style is empty.";

        if($this->draft_rounds < 1)
            $errors[] = "Draft rounds must be at least 1 or more.";

        $name_count = mysql_num_rows(mysql_query("SELECT draft_id FROM draft WHERE draft_name = '" . $this->draft_name . "' AND draft_sport = '" . $this->draft_sport . "'"));

        if($name_count > 0)
            $errors[] = "Draft already found with that name and sport.";

        return $errors;
    }

    /**
     * Adds a new instance of this draft to the database
     * @return boolean success, whether or not the MySQL transaction succeeded.
     */
    public function saveDraft() {
        $sql = "INSERT INTO draft "
        . "(draft_id, draft_name, draft_sport, draft_status, draft_style, draft_rounds) "
        . "VALUES "
        . "(NULL, '" . $this->draft_name . "', '" . $this->draft_sport . "', 'undrafted', '" . $this->draft_style . "', " . $this->draft_rounds . ")";

        if(!mysql_query($sql))
            return false;

        $this->draft_id = mysql_insert_id();

        return true;
    }
}
?>
