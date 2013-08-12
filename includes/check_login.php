<?php

/*
  Used to ensure commissioner access to the parent file that includes it.
 */

if (!$LOGGED_IN_USER->userAuthenticated()) {
  header('Location: login.php');
  define("PAGE_HEADER", "Access Restricted");
  define("P_CLASS", "error");
  define("PAGE_CONTENT", "In order to access this portion of the site, you must be the commissioner. <a href=\"login.php\">Click here</a> to go to the login page.");
  require_once("views/shared/generic_result_view.php");
  exit(1);
}

//If we have gotten to this point, the user is properly logged in and we can continue.
?>