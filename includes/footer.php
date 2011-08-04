<div id="footer">
	<!--<a href="#">Link One</a> | <a href="#">Link Two</a> | <a href="#">Link Three</a> | <a href="#">Link Four</a> | <a href="#">Link Five</a>--><br />
	PHPDraft | <a href="http://gplv3.fsf.org/" target="_blank">GNU GPL3 License</a> | 2011 <a href="http://www.mattheworres.com" target="_blank">Matthew Orres</a>
</div>

<div id="loadingDialog">
	<img src="images/loading.gif" alt="Loading..."/>Loading...
</div>

<div id="informationDialog"></div>

<script type="text/javascript" src="js/jquery-1.6.2.min.js"></script>
<script type="text/javascript" src="js/jquery-ui-1.8.14.custom.min.js"></script>
<script type="text/javascript">
	$('#loadingDialog').dialog({
		autoOpen: false,
		title: "Loading...",
		modal: true,
		draggable: false,
		resizable: false
	});
	
	$('#informationDialog').dialog({
		autoOpen: false,
		resizable: false,
		show: 'drop',
		hide: 'drop',
		buttons: [
			{
				text: "Ok",
				click: function() { $(this).dialog("close"); }
			}
		]
	})
</script>