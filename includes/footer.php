<div id="footer">
	<br />
	PHPDraft 1.1.1 | <a href="http://gplv3.fsf.org/" target="_blank">GNU GPL3 License</a> | 2012 <a href="http://www.mattheworres.com" target="_blank">Matthew Orres</a>
</div>

<div id="loadingDialog">
	<img src="images/loading.gif" alt="Loading..."/>Loading...
</div>

<div id="informationDialog"></div>

<script type="text/javascript" src="js/jquery-1.10.2.min.js"></script>
<script type="text/javascript" src="js/jquery-ui-1.10.3.min.js"></script>
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