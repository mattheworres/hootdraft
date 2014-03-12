<div id="footer">
  <br /> 
  PHPDraft 1.2.1 | <a href="http://gplv3.fsf.org/" target="_blank">GNU GPL3 License</a> | 2014 <a href="http://www.mattheworres.com" target="_blank">Matthew Orres</a>
</div>

<div id="loadingDialog">
  <img src="images/loading.gif" alt="Loading..."/>Loading...
</div>

<div id="informationDialog"></div>

<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
<script>window.jQuery || document.write('<script src="js/jquery-1.10.2.min.js">\x3C/script>');</script>
<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.10.3/jquery-ui.js"></script>
<script>window.jQuery.ui || document.write('<script src="js/jquery-ui-1.10.3.min.js">\x3C/script>');</script>
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
        click: function() {
          $(this).dialog("close");
        }
      }
    ]
  });
</script>