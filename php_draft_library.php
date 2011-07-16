<?php
/**
 * This is meant to be a app-wide library file containing data and functions that are universal in nature.
 * 
 * Every effort should be made to keep this slim and small. If this gets large/unwieldy, that can't be a best practice.
 */

function seconds_to_words($seconds) {
	$words = "";

	//Years
	$years = intval(intval($seconds) / 31536000);
	if($years > 0) {
		$seconds -= ($years * 31536000);
		$words .= $years ." years, ";
	}

	//Weeks
	$weeks = intval(intval($seconds) / 604800);
	if($weeks > 0) {
		$seconds -= ($weeks * 604800);
		$words .= $weeks ." weeks, ";
	}

	//Days
	$days = intval(intval($seconds) / 86400);
	if($days > 0) {
		$seconds -= ($days * 86400);
		$words .= $days ." days, ";
	}

	//Hours
	$hours = intval(intval($seconds) / 3600);
	if($hours > 0){
		$words .= $hours ." hours, ";
	}
	//Minutes
	$minutes = bcmod((intval($seconds) / 60),60);
	if($hours > 0 || $minutes > 0){
		$words .= $minutes ." minutes, ";
	}

	//Seconds
	$seconds = bcmod(intval($seconds),60);
	$words .= $seconds ." seconds";

	return $words;
}
?>
