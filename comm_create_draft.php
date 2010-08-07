<?php require('check_login.php');?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
	<?php require('meta.php'); ?>
    </head>
    <body>
	<div id="page_wrapper">
	    <?php require('header.php'); ?>

	    <?php require('comm_menu.php'); ?>
	    <div id="content">
		<?php
		function print_add($draft_name = "", $draft_sport = "", $draft_style = "", $draft_rounds = 20) {
		    echo "<fieldset>
			<legend>Create Draft</legend>
			<form action=\"comm_create_draft.php\" method=\"post\">
				<p><label for=\"draft_name\">Draft Name*:</label>
				<input type=\"text\" name=\"draft_name\" id=\"draft_name\" value=\"" . $draft_name . "\" /></p>
				<p><label for=\"draft_sport\">Draft Sport*:</label>
				<select name=\"draft_sport\">
					<option value=\"football\"" . ($draft_sport == "football" ? " selected" : "") . ">Football - NFL</option>
					<option value=\"baseball\"" . ($draft_sport == "baseball" ? " selected" : "") . ">Baseball - MLB</option>
					<option value=\"hockey\"" . ($draft_sport == "hockey" ? " selected" : "") . ">Hockey - NHL</option>
					<option value=\"basketball\"" . ($draft_sport == "basketball" ? " selected" : "") . ">Basketball - NBA</option>
				</select></p>
				<p><label for=\"draft_style\">Draft Style*:</label>
				<select name=\"draft_style\">
					<option value=\"serpentine\"".($draft_style == "serpentine" ? " selected" : "") . ">Serpentine Draft</option>
					<option value=\"standard\"".($draft_style == "standard" ? " selected " : "").">Standard Draft</option>
				</select></p>
				<p><label for=\"draft_name\"># of Rounds*:</label>
				<input type=\"text\" name=\"draft_rounds\" id=\"draft_rounds\" size=\"2\" maxlength=\"2\" value=\"" . $draft_rounds . "\" /> (players per team)</p>
				<p><input type=\"submit\" name=\"submit\" class=\"button\" value=\"Create Draft\" /></p>
				<p class=\"error\">*Required</p>
			</form>
		</fieldset>";
		}

		if(empty($_POST)) {//If the form is clean and hasn't been submitted
		    ?>
		<h3>Create a New Draft</h3>
		<p>To create a draft, please enter a name for the draft to make it unique and identifiable (such as "Refridgerator Raiders" or "Yoohoo Yuppies"), and select the sport that this fantasy draft is for.  Once you're done, press "Create Draft" to create the draft.</p>
		    <?php print_add();
		}elseif(!empty($_POST)) {//There has been a form submitted, we must do error-checking and input-cleaning.
		    require_once('cleanstring.php');
		    require_once('dbconn.php');

		    $draft_name = CleanString(trim($_POST['draft_name']));
		    $draft_sport = CleanString(trim($_POST['draft_sport']));
		    $draft_style = CleanString(trim($_POST['draft_style']));
		    $draft_rounds = intval($_POST['draft_rounds']);

		    

		    $name_result = mysql_query("SELECT draft_id FROM draft WHERE draft_name = '" . $draft_name . "' AND draft_sport = '" . $draft_sport . "'");
		    $name_count = mysql_num_rows($name_result);

		    if(empty($draft_name) ||
			    empty($draft_sport) ||
			    empty($draft_style) ||
			    $draft_rounds < 1) {//If either of these are empty, inform the user and re-output the form
			?><h3>One or Both Fields Empty</h3>
		<p class="error">In order to add a draft, you must specify both a name and a sport.  Please make sure both are available below, and hit "Create Draft" once you're done.</p>
			<?php print_add($draft_name, $draft_sport, $draft_style, $draft_rounds);
		    }elseif($name_count != 0) {//If our earlier query resulted in any rows, this means another draft has the same name (must be unique)
			?><h3>Draft Name Already Exists</h3>
		<p class="error">The draft you attempted to add has the same name as another draft that already exists.  Please consider either changing this new drafts name to make it unique, OR not adding a new draft at all and editing the other draft.</p>
			<?php print_add($draft_name, $draft_sport, $draft_style, $draft_rounds);
		    }else {//We have values for both, they both seem legal, lets add to database.
			$sql = "INSERT INTO draft "
				. "(draft_id, draft_name, draft_sport, draft_status, draft_style, draft_rounds) "
				. "VALUES "
				. "(NULL, '" . $draft_name . "', '" . $draft_sport . "', 'undrafted', '".$draft_style."', ".$draft_rounds.")";

			if(!mysql_query($sql)) {//If the query fails, inform the user of this
			    ?><h3>Error: Could Not Add Draft</h3>
		<p class="error">An error occurred when attempting to add the draft. If you'd like to try again, please hit the "Create Draft" button below again.</p>
			    <?php print_add($draft_name, $draft_sport, $draft_style, $draft_rounds);
			}else {//MySQL successfully added the record for us.
			    $draft_result = mysql_query("SELECT draft_id FROM draft WHERE draft_name = '" . $draft_name . "' AND draft_sport = '" . $draft_sport . "'");
			    $draft_row = mysql_fetch_array($draft_result);

			    ?><h3>Draft Successfully Created</h3>
		<p class="success">Your draft, <em><?php echo $draft_name;?></em> has been successfully created.  <a href="comm_manage_draft.php?did=<?php echo $draft_row['draft_id'];?>">Click here</a> to manage your new draft.</p>
		<p>REMEMBER: Your next step should be to add all managers before you begin drafting players.</p>
			    <?php
			}
		    }

		}?>
	    </div>
	    <?php require('footer.php'); ?>
	</div>
    </body>
</html>