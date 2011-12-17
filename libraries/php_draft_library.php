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
	 * Parse a string (from getNowPhpTime) into a date object
	 * @return php_draft_date_object $date_object
	 */
	public static function parseStringDate($date_string) {
		$parsed_date = date_parse($date_string);
		
		$date_object = new php_draft_date_object();
		$date_object->year = $parsed_date['year'];
		$date_object->month = $parsed_date['month'];
		$date_object->day = $parsed_date['day'];
		$date_object->hour = $parsed_date['hour'];
		$date_object->minute = $parsed_date['minute'];
		$date_object->second = $parsed_date['second'];
		
		return $date_object;
	}
}

/**
 * Class to contain data about a parsed date
 */
class php_draft_date_object {
	/**
	 * @var int
	 */
	public $year;
	/**
	 * @var int
	 */
	public $month;
	/**
	 * @var int
	 */
	public $day;
	/**
	 * @var int
	 */
	public $hour;
	/**
	 * @var int
	 */
	public $minute;
	/**
	 * @var int
	 */
	public $second;
}
?>
