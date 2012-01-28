<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
	<?php require('includes/meta.php'); ?>
	</head>
	<body>
	<div id="page_wrapper">
		<?php require('includes/header.php'); ?>

		<?php require('views/shared/main_menu.php'); ?>
		<div id="content">
			<h3>PHPDraft Help</h3>
			<p id="summaryBox"></p>
			
			<div class="questionBlock">
				<a id="seeDraft" class="anchor"></a>
				<span class="highlighted">Q:</span>
				<div class="questionText">I dont see my draft. Where is it?</div>
			</div>
			<div class="answerBlock">
				<span class="highlighted">A:</span>
				<div class="answerText">
					Clicking on the <strong><a href="index.php?action=select">Draft Central</a></strong> tab above will get
					you started. From there, find the name of your draft and click on it to be taken to the draft's main
					page. There, you'll have everything you need to see the draft, like the Live Draft Board, see all
					picks per round or manager, see any trades that have occurred, search for players taken, and more.
				</div>
			</div>
			
			<div class="questionBlock">
				<a id="enterPicks" class="anchor"></a>
				<span class="highlighted">Q:</span>
				<div class="questionText">Where do I go to enter my picks?</div>
			</div>
			<div class="answerBlock">
				<span class="highlighted">A:</span>
				<div class="answerText">
					The short answer is &mdash; you don't! With PHPDraft, once you make a selection inform your
					commissioner, and then they will enter the pick for you. Once the pick has been made, it
					instantly shows up on the draft board on its own. All you need to do is sit back and watch
					as your championship team comes together.
				</div>
			</div>
			
			<div class="questionBlock">
				<a id="takenPlayers" class="anchor"></a>
				<span class="highlighted">Q:</span>
				<div class="questionText">Is [Fantasy Player] still available? How do I find out?</div>
			</div>
			<div class="answerBlock">
				<span class="highlighted">A:</span>
				<div class="answerText">
					In order to find out if a certain player is available, use the Search feature from the main
					draft page and search for a portion of the player's name. For an example, let's say you
					are looking to draft a quarterback and are not sure if Drew Brees has been taken. Type in
					&quot;Dre&quot; into the "First Name" box, and just to be sure we can narrow the search to
					look at only quarterbacks by selecting &quot;Quarterback&quot; from the "Position" dropdown.
					If the search doesn't find a player, this means Drew Brees is still available!<br/><br/>
					
					Depending on how far into the draft you are, you could also quickly scan the draft board to see
					if Brees has already been taken. Each position is color-coded to help you find only the position
					you're looking for.
				</div>
			</div>
			
			<div class="questionBlock">
				<a id="login" class="anchor"></a>
				<span class="highlighted">Q:</span>
				<div class="questionText">Do I have to login to see anything?</div>
			</div>
			<div class="answerBlock">
				<span class="highlighted">A:</span>
				<div class="answerText">
					The most likely answer is NO! The only person that must log in to use PHPDraft is your commissioner,
					who is tasked with running the draft. Your commish can choose to password-protect a draft to keep
					the results safe from prying eyes, at which point you will have to enter the password your commissioner
					gave you when prompted. Otherwise, you can view everything you need to for your draft without entering
					a password!
				</div>
			</div>
			
			<div class="questionBlock">
				<a id="pickError" class="anchor"></a>
				<span class="highlighted">Q:</span>
				<div class="questionText">One of the picks is wrong, how can I fix it?</div>
			</div>
			<div class="answerBlock">
				<span class="highlighted">A:</span>
				<div class="answerText">
					(Kindly) let your commissioner know about the error, and he can go back and correct the mistake on any
					previously made picks. Usually these mistakes will get updated on the draft board once a new pick shows
					up on the screen.
				</div>
			</div>
			
			<div class="questionBlock">
				<a id="cost" class="anchor"></a>
				<span class="highlighted">Q:</span>
				<div class="questionText">How much is PHPDraft? Is our commish scamming us?</div>
			</div>
			<div class="answerBlock">
				<span class="highlighted">A:</span>
				<div class="answerText">
					PHPDraft is free and open source software, which means not only do I allow you to use it free of charge,
					I also allow (and encourage) you to look at its guts (technically, &quot;source code&quot;) and make
					changes if you like, as long as you promise to let the community use any helpful changes you come up with.<br/><br/>
					
					(Oh, and, uh... Psst... Don't tell him I said this, but if your commissioner said you owed an extra $20
					because of a &quot;fancy new digital draft board&quot;, you might want to see about getting that Jackson back ;-)
				</div>
			</div>
			
		</div>
		<?php require('includes/footer.php'); ?>
		<script type="text/javascript">
			$(document).ready(function() {
				var $summaryBox = $('#summaryBox');
				
				$.each($('div.questionText'), function() {
					var text = $(this).text(),
						id = $(this).siblings('a.anchor').attr('id'),
						anchorLink = $('<a>').addClass('shortcutLink').attr('href', '#' + id).html('&bull;&nbsp;' + text);
						
					$summaryBox.append(anchorLink).append('<br/>');
				});
			});
		</script>
	</div>
	</body>
</html>