$(document).ready(function() {
  resetArrows();
  setupUIElements();
  setupEventHandlers();
});

function setupEventHandlers() {
  $(document).on('click', '#managers-table span.manager-move-link', function() {
    moveManagerHandler($(this));
  });
  
  $(document).on('click', '#managers-table .manager-delete-link', function() {
    deleteManagerHandler($(this));
  });
  
  $(document).on('click', '#changeVisibility', function() {
    $('#visibilityDialog').dialog('open');
  });
  
  $(document).on('click', '#changeDraftStatus', function() {
    $('#draftStatusDialog').dialog('open');
  });

  $(document).on('change', '#draft_visibility', function() {
    changeDraftVisibilityHandler($(this));
  });

  $(document).on('blur', '#draft_password, #draft_password_confirm', function() {
    checkMatchingPasswords($('#draft_visibility'), $('#draft_password'), $('#draft_password_confirm'));
  });

  $(document).on('keyup', '#draft_password, #draft_password_confirm', function() {
    checkMatchingPasswords($('#draft_visibility'), $('#draft_password'), $('#draft_password_confirm'));
  });
  
  $(document).on('click', 'span.manager-edit-link', function() {
    var $managerRow = $(this).parents('tr.manager-row');
    
    managerEditClickHandler($managerRow);
  });
  
  $(document).on('click', 'span.manager-save-link', function() {
    var $managerRow = $(this).parents('tr.manager-row');
    
    managerSaveClickHandler($managerRow);
  });
  
  $(document).on('click', 'span.manager-cancel-link', function() {
    var $managerRow = $(this).parents('tr.manager-row');
    
    managerCancelClickHandler($managerRow);
  });
}

function setupUIElements() {
  $('#visibilityDialog').dialog({
    title: 'Change Draft Visibility',
    autoOpen: false,
    modal: true,
    width: 650,
    buttons: [
      {
        text: "Save",
        click: function() {
          var success = updateDraftVisibility();
          if (success === true) {
            $(this).dialog("close");
            $('#informationDialog').html('Draft\'s visibility updated!').dialog('open');
          }
        }
      },
      {
        text: "Cancel",
        click: function() {
          $(this).dialog("close");
        }
      }
    ]
  });
  
  $('#draftStatusDialog').dialog({
    title: "Update Draft Status",
    autoOpen: false,
    modal: true,
    width: 650,
    buttons: [
      {
        text: "Save",
        click: function() {
          updateDraftStatus(function() {
            $('#draftStatusDialog').dialog('close');
            $('#informationDialog').html('Draft\'s status updated! Refreshing page now...').dialog('open');
            location.reload(true);
          });
        }
      },
      {
        text: "Cancel",
        click: function() {
          $(this).dialog('close');
        }
      }
    ]
  });
  
  $('#draft_status').buttonset();
}

function managerEditClickHandler($managerRow) {
  $managerRow.find('span.manager-name, span.manager-email').hide();
  $managerRow.find('input.manager-name').val($managerRow.data('manager-name')).show();
  $managerRow.find('input.manager-email').val($managerRow.data('manager-email')).show();
  
  $managerRow.find('td.main-functions').hide();
  $managerRow.find('td.edit-functions').show();
  
  $managerRow.find('span.manager-move-link').hide();
}

function managerSaveClickHandler($managerRow) {
  var manager_name = $.trim($managerRow.find('input.manager-name').val()),
      manager_email = $.trim($managerRow.find('input.manager-email').val()),
      manager_id = parseInt($managerRow.data('manager-id'), 10),
      $managerNameDisplay = $managerRow.find('span.manager-name'),
      $managerEmailDisplay = $managerRow.find('span.manager-email');
      
  //Todo: If this gets more complicated, use Jquery validate. Otherwise, only 1 field...
  if(manager_name.length === 0) {
    $('#informationDialog').html('Unable to save: manager must have a non-empty name.').dialog('open');
    return;
  }
  
  $('#loadingDialog').dialog('open');
  
  $.ajax({
    type: 'POST',
    url: 'manager.php?action=updateManager&mid=' + manager_id,
    data: {'manager_name': manager_name, 'manager_email': manager_email},
    dataType: 'json',
    complete: function() {
      $('#loadingDialog').dialog('close');
    },
    success: function(response) {
      switch(response.Status) {
        case 'invalid-data':
          $('#informationDialog').html('Unable to save manager: ' + response.Errors).dialog('open');
          break;
          
        case 'unable-to-save':
          $('#informationDialog').html('Unable to save manager: unexpected error occurred').dialog('open');
          break;
          
        case 'save-successful':
          $managerNameDisplay.text(manager_name);
          $managerEmailDisplay.text(manager_email);
          $managerRow.data('manager-name', manager_name);
          $managerRow.data('manager-email', manager_email);
          managerCancelClickHandler($managerRow);
          break;
      }
    },
    error: function() {
      $('#informationDialog').html('An error has occurred and the manager could not be updated.');
    }
  });
}

function managerCancelClickHandler($managerRow) {
  $managerRow.find('span.manager-name, span.manager-email').show();
  $managerRow.find('input.manager-name, input.manager-email').val('').hide();
  
  $managerRow.find('td.main-functions').show();
  $managerRow.find('td.edit-functions').hide();
  
  $managerRow.find('span.manager-move-link').show();
}

function moveManagerHandler($moveLink) {
  var $row = $moveLink.parents('tr:first'),
            manager_id = $row.attr('data-manager-id'),
            $loadingDialog = $('#loadingDialog'),
            isMoveUp = $moveLink.is('.move-up'),
            isOff = $moveLink.is('.down-off') || $moveLink.is('.up-off');

    if (isOff)
      return;

    $loadingDialog.dialog('open');

    $.ajax({
      type: 'POST',
      url: 'manager.php?action=moveManager',
      data: {mid: manager_id, direction: isMoveUp ? 'up' : 'down'},
      complete: function() {
        $loadingDialog.dialog('close');
      },
      success: function(data) {
        if (data === "SUCCESS") {
          if (isMoveUp) {
            $row.insertBefore($row.prev());
            resetArrows();
          } else {
            $row.insertAfter($row.next());
            resetArrows();
          }
        } else {
          alert('Sorry - an error has occurred and the manager\'s order could not be changed.');
        }
      },
      error: function() {
        alert('Sorry - an error has occurred and the manager\'s order could not be changed.');
      }
    });
}

function deleteManagerHandler($deleteLink) {
  var $row = $deleteLink.parents('tr:first'),
            manager_id = $row.attr('data-manager-id'),
            $loadingDialog = $('#loadingDialog');

    $loadingDialog.dialog('open');

    $.ajax({
      type: 'POST',
      url: 'manager.php?action=deleteManager',
      data: {mid: manager_id},
      complete: function() {
        $loadingDialog.dialog('close');
      },
      success: function(data) {
        if (data === "SUCCESS") {
          $row.remove();
          resetArrows();
          checkForOtherManagers();
        } else {
          alert('Sorry - an error has occurred and the manager could not be deleted.');
        }
      },
      error: function() {
        alert('Sorry - an error has occurred and the manager could not be deleted.');
      }
    });
}

function changeDraftVisibilityHandler($draftVisibility) {
  var value = $draftVisibility.val(),
      $passwordBox = $('#passwordBox');

  if (value === "1") {
    $passwordBox.show();
  } else {
    $passwordBox.val('').hide();
  }
}

function checkForOtherManagers() {
  var number_of_managers = $('#managers-table tr').length - 1;	//Account for table header

  if (number_of_managers === 0) {
    $('#managers-table').hide();
    $('#no-managers-msg').show();
    $('#draft-status-link').hide();
    alert('You\'ve just deleted the last manager this draft had - in order to start this draft you must first add managers again.');
  }
}

function resetArrows() {
  var rows = $('#managers-table > tbody > tr'),
          i = 1,
          tableLength = rows.length;

  $.each(rows, function() {
    var $tableRowChildren = $(this).children();

    if (i === 1) {
      $tableRowChildren.children('span.move-up').removeClass('up-on').addClass('up-off');

      if (tableLength > 1) {
        $tableRowChildren.children('span.move-down').removeClass('down-off').addClass('down-on');
      }
    } else if (i === tableLength) {
      $tableRowChildren.children('span.move-down').removeClass('down-on').addClass('down-off');

      if (tableLength > 1) {
        $tableRowChildren.children('span.move-up').removeClass('up-off').addClass('up-on');
      }
    } else {
      $tableRowChildren.children('span.move-down').removeClass('down-off').addClass('down-on');
      $tableRowChildren.children('span.move-up').removeClass('up-off').addClass('up-on');
    }

    ++i;
  });
}

function updateDraftVisibility() {
  var visibilityValue = parseInt($('#draft_visibility').val(), 10),
          $password = $('#draft_password'),
          $confirmPassword = $('#draft_password_confirm'),
          $visibilityError = $('#visibilityError'),
          $draftVisibilityStatus = $('#draft_visibility_status'),
          password = $.trim($('#draft_password').val()),
          confirmPassword = $.trim($('#draft_password_confirm').val());

  $password.removeClass('error');
  $confirmPassword.removeClass('error');
  $visibilityError.hide();

  if (visibilityValue === 0) {
    var updateSuccess = savePassword('');
    if (updateSuccess === true) {
      $password.val('');
      $confirmPassword.val('');
      $draftVisibilityStatus.html('Public');
      return true;
    } else {
      $visibilityError.html('There was a server-side error, please try again.').show();
      return false;
    }
  } else {
    if (password.length === 0 || confirmPassword.length === 0 || password !== confirmPassword) {
      $visibilityError.html('To make the draft private, you must provide a password and confirm that password!').show();
      $password.addClass('error');
      $confirmPassword.addClass('error');
      return false;
    }

    var success = savePassword(password);

    if (success === true) {
      $('#visibilityDialog').dialog('close');
      $draftVisibilityStatus.html('Private<br/><br/><strong>Draft Password:</strong> ' + password);
      return true;
    } else {
      $visibilityError.html('There was a server-side error, please try again.').show();
      return false;
    }
  }
}

function updateDraftStatus(successCallback) {
  var draft_id = parseInt($('#draft_id').val(), 10),
      statusValue = $('#draft_status input[name="draft_status"]:checked').val(),
      $loadingDialog = $('#loadingDialog');
      
  $loadingDialog.dialog('open');
  
  $.ajax({
    type: 'POST',
    url: 'draft.php?action=updateStatus&did=' + draft_id,
    dataType: 'json',
    data: {draft_status: statusValue},
    complete: function() {
      $loadingDialog.dialog('close');
    },
    success: function(response) {
      switch(response.Status) {
        case 'status-unchanged':
          $('#draftStatusDialog').dialog('close');
          $('#informationDialog').html('Your draft status has remain unchanged!').dialog('open');
          break;
        
        case 'invalid-status':
          $('#informationDialog').html('Draft status could not be updated: an invalid status was selected.').dialog('open');
          break;
          
        case 'unable-to-update':
          $('#informationDialog').html('An error has occurred and the draft status could not be updated: ' + response.Error).dialog('open');
          break;
          
        case 'status-updated':
          if(typeof(successCallback) === "function") {
            successCallback();
          }
          break;
      }
    },
    error: function() {
      $('#informationDialog').html('An error has occurred and the draft status was unable to be updated.').dialog('open');
    }
  });
}

function savePassword(draft_pass) {
  var draft_id = parseInt($('#draft_id').val(), 10),
          $loadingDialog = $('#loadingDialog');

  $loadingDialog.dialog('open');

  result = false;

  $.ajax({
    async: false,
    type: 'POST',
    data: {action: 'updateVisibility', did: draft_id, password: draft_pass},
    url: 'draft.php',
    complete: function() {
      $loadingDialog.dialog('close');
    },
    success: function(data) {
      if (data === "SUCCESS")
        result = true;
      else
        result = false;
    },
    error: function() {
      result = false;
    }
  });

  return result;
}

function checkMatchingPasswords($statusSelect, $password, $confirmPassword) {
  var $visibilityError = $('#visibilityError');
  $visibilityError.hide();

  if ($statusSelect.val() === 0)
    return;

  if ($password.val().length > 0 && $confirmPassword.val().length > 0)
    if ($password.val() !== $confirmPassword.val())
      $visibilityError.html('Passwords entered do not match!').show();
    else
      $visibilityError.hide();
  else
    $visibilityError.hide();
}