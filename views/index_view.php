<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
	<?php require('meta.php'); ?>
    </head>
    <body>
	<div id="page_wrapper">
	    <?php require('header.php'); ?>

	    <?php require('menu.php'); ?>
	    <div id="content">
		<h3>Welcome to PHPDraft!</h3>
                <div class='featurebox_center'><strong>PHPDraft is now officially in a public beta phase</strong>!  Private Beta finished with no hiccups or burps, so a public beta was deemed best!  All of the source code that runs this site is publicly available for free at <a href="http://www.sourceforge.net">SourceForge.net</a>, where the project has <a href="http://sourceforge.net/projects/phpdraft">its own project page</a>, complete with source control integration, bug tracking, and forums for feedback and help!  So please, if you have input, need help, or would like to help, please contact Matt on the project page!</div>
		<p>PHPDraft is a browser-based web application that your commissioner has decided to use to run your fantasy sports draft!  With PHPDraft, you can watch in real time as your commissioner enters draft picks, see breakdowns of the positions most taken, see who in your league is the slowest drafter, and more!  And at the end, everyone can print out their own PDF document or Excel spreadsheet of their team for their records!</p>
		<p><strong>To get started, go ahead and click on the name of a draft below.</strong>  If a lock icon shows up beside a draft, that means you'll have to get the password from your commissioner*</p>
		<table width="100%">
		    <tr>
			<th width="16">&nbsp;</th>
			<th>Draft Name</th>
			<th>Sport</th>
			<th>Draft Status</th>
		    </tr>
		    <?php if($drafts->number_of_drafts == 0) { ?><tr><td colspan="4"><h2>There are currently no drafts available.</h2></td></tr><?php
		    }else {
			foreach($drafts->draft_objects as $draft) {?><tr>
			<td><img src="images/icons/<?php echo $draft->visibility;?>.png" border="0" alt="<?php echo $draft->visibility;?>" /></td>
			<td><a href="draft_main.php?draft_id=<?php echo $draft->draft_id;?>"><?php echo $draft->draft_name;?></a></td>
			<td><?php echo $draft->draft_sport;?></td>
			<td><?php echo $draft->draft_status;?></td>
		    </tr>
			    <?php }
		    }?>
		</table>
		<p style="font-size: 80%;">*Cookies must be enabled</p>
	    </div>
	    <?php require('footer.php'); ?>
	</div>
    </body>
</html>
