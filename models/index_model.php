<?php

require_once("models/draft_object.php");

class indexObject {
    public $number_of_drafts;
    public $draft_objects = array();

    public function set_drafts() {
        $draft_result = mysql_query("SELECT * FROM draft ORDER by draft_status DESC");
        $this->number_of_drafts = mysql_num_rows($draft_result);

        while($draft_row = mysql_fetch_array($draft_result)) {
            $draft_object = new draft_object();
            $draft_object->draft_id = $draft_row['draft_id'];
            $draft_object->draft_name = $draft_row['draft_name'];
            $draft_object->draft_status = $draft_row['draft_status'];
            $draft_object->draft_sport = $draft_row['draft_sport'];

            $draft_object->visibility = ($draft_row['draft_password'] != '' ? "locked" : "unlocked");

            if($draft_row['draft_status'] == "undrafted")
                $draft_object->draft_status = "Setting Up";
            elseif($draft_row['draft_status'] == "in_progress")
                $draft_object->draft_status = "Currently Drafting";
            elseif($draft_row['draft_status'] == "complete")
                $draft_object->draft_status = "Draft Complete";

            $this->draft_objects[] = $draft_object;
        }
    }
}

?>
