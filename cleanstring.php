<?php
# Example usage: broken_tags("<b>This is a string</u>") returns TRUE, broken_tags("<b>This is a string</b>") returns FALSE.
function broken_tags($str) {
	preg_match_all("/(<\w+)(?:.){0,}?>/", $str, $v1);
	preg_match_all("/<\/\w+>/", $str, $v2);
	$open = array_map('strtolower', $v1[1]);
	$closed = array_map('strtolower', $v2[0]);
	foreach ($open as $tag) {
	$end_tag = preg_replace("/<(.*)/", "</$1>", $tag);
	if (!in_array($end_tag, $closed)) return true;
	unset($closed[array_search($end_tag, $closed)]);
	}
	return false;
} 


/*
Usage:
Allowable attributes can be comma seperated or array
Example:
<?php strip_tags_attributes($string,'<strong><em><a>','href,rel'); ?>
*/
function strip_tags_attributes($string,$allowtags=NULL,$allowattributes=NULL) {
	$string = strip_tags($string,$allowtags);
	if (!is_null($allowattributes)) {
	if(!is_array($allowattributes))
		$allowattributes = explode(",",$allowattributes);
	if(is_array($allowattributes))
		$allowattributes = implode(")(?<!",$allowattributes);
	if (strlen($allowattributes) > 0)
		$allowattributes = "(?<!".$allowattributes.")";
	$string = preg_replace_callback("/<[^>]*>/i",create_function(
		'$matches',
		'return preg_replace("/ [^ =]*'.$allowattributes.'=(\"[^\"]*\"|\'[^\']*\')/i", "", $matches[0]);'
		),$string);
	}
	return $string;
} 

/*
This is the original CleanString fcn, and is intended for once-only execution.

Cleans user input, and then you can HTML-encode it for database storage and simply output
it if it's being output to a browser.  If it's being output to a form, then use br2nl
to change breaks to newlines.
*/
function CleanString($mystring) {	//Cleans given string from being malicious.
	//$mystring = str_replace(";","&#59;",$mystring);
	$mystring = str_replace("& ","&amp; ",$mystring);
	$mystring = str_replace("<?php","&lt;&#63;php",$mystring);
	$mystring = str_replace("?>","&#63;&gt;",$mystring);
	$mystring = str_replace("<%","[",$mystring);
	$mystring = str_replace("'","&#39;",$mystring);
	$mystring = str_replace("\"","&quot;",$mystring);
	$mystring = strip_tags($mystring, "<i><b><u><br />");

	return $mystring;
}

/*
This is an updated version of CleanString that makes the assumption that the user
will be adding and removing slashes from characters that may be malicious, such as
single and double quotes, but are necessary for such tag attributes as HREF, SRC and
CLASS.

USAGE:  Use it to clean a string, but it will leave all single and double quotes.  To make
it safe, you must ADD SLASHES when you're ready to store it in the database (i.e. use it in
a mysql_query string), and then REMOVE SLASHES when it's being read out of the database for
either browser usage or form viewing.

1. For ADDING SLASHES: use mysql_real_escape_string()*
*CAVEAT: mysql_real_escape_string() requires an active MYSQL connection, because the second
parameter in the function is a MySQL Link Identifier to an active connection.  If there is no
active connection, it will spit out an error.  If for whatever reason you need to add slashes
without having an active database connection, use PHP's addslashes(), which presents some obscure
yet possible SQL attacks involving certain character sets that mysql_real_escape_string() prevents.

2. For REMOVING SLASHES: stripslashes()
*/
function CleanString2($mystring) {	//Cleans given string from being malicious.
	$mystring = str_replace("& ","&amp; ",$mystring);
	$mystring = str_replace("<?php","&lt;&#63;php",$mystring);
	$mystring = str_replace("?>","&#63;&gt;",$mystring);
	$mystring = str_replace("<%","[",$mystring);
	$mystring = strip_tags_attributes($mystring, "<br /><br><em><strong><i><b><u><p><ol><ul><li><h2><h3><img><a>", "href,src,class,alt,title");

	return $mystring;
}

function br2nl($mystring) {//Converts HTML linebreaks with newline characters, used to translate from database into form data
	$mystring = str_replace("<br />","\n",$mystring);
	return $mystring;
}

function ncymca_nl2br($mystring) {//Converts newline characters into HTML linebreaks, used to translate form data into database data
	$mystring = str_replace("\n","<br />",$mystring);
	$mystring = str_replace("<br>","<br />",$mystring);
	$mystring = str_replace("\r","",$mystring);
	return $mystring;
}

//Function that removes ALL whitespace, regardless of place (good for filename fields):
function trim_whitespace($mystring) {
	$mystring = str_replace(array("\n", "\r", "\t", " ", "\o", "\xOB"), '', $mystring);
	return $mystring;
}?>