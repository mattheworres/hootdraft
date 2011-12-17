<?php
/**
 * This is meant to be a app-wide library file containing data and functions that are universal in nature.
 * 
 * Every effort should be made to keep this slim and small. If this gets large/unwieldy, that can't be a best practice.
 */

class php_draft_library {
	public static function secondsToWords($seconds) {
		$words = "";

		//Years
		$years = (int)intval($seconds / 31536000);
		if($years > 0) {
			$seconds -= ($years * 31536000);
			$words .= $years ." years, ";
		}

		//Weeks
		$weeks = (int)intval($seconds / 604800);
		if($weeks > 0) {
			$seconds -= ($weeks * 604800);
			$words .= $weeks ." weeks, ";
		}

		//Days
		$days = (int)intval($seconds / 86400);
		if($days > 0) {
			$seconds -= ($days * 86400);
			$words .= $days ." days, ";
		}

		//Hours
		$hours = (int)intval($seconds / 3600);
		if($hours > 0){
			$words .= $hours ." hours, ";
		}
		//Minutes
		$minutes = bcmod(((int)$seconds / 60),60);
		if($hours > 0 || $minutes > 0){
			$words .= $minutes ." minutes, ";
		}

		//Seconds
		$seconds = bcmod((int)$seconds,60);
		$words .= $seconds ." seconds";

		return $words;
	}

	/**
	 * Get the Unix timestamp right now according to mktime.
	 * @return time PHP Time string 
	 */
	public static function getNowUnixTimestamp() {
		//mktime($hour,$min,$sec,$mon,$day,$year);
		return mktime(date('H'), date('i'), date('s'), date('m'), date('d'), date('Y'));
	}
	
	/**
	 * Returns a standardized PHP date string in this format: Y-m-d H:i:s
	 * @return string $date Formatted date for now 
	 */
	public static function getNowPhpTime() {
		return date("Y-m-d H:i:s");
	}
	
	public static function getNowRefreshTime() {
		return date("h:i:s A");
	}
	
	/**
	 * Format a date from a string stored in the DB in a readable way.
	 * @param type $date_string 
	 */
	public static function parseObjectDate($date_string) {
		$time = strtotime($date_string);
		return date("M j Y \a\\t g:i a", $time);
	}
}
?>
