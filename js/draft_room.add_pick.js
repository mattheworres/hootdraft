$(function() {
    var draft_id = $('#draft_id').val(),
        autocompleteOptions = {
            source: function(request, response) {
                $.ajax({
                    type: 'GET',
                    url: 'draft.php',
                    dataType: 'json',
                    data: { action: 'searchProPlayers',
                        did: draft_id,
                        league: $('#league').val(),
                        first: $('#first_name').val(),
                        last: $('#last_name').val(),
                        team: $('#team').val(),
                        position: $('#position').val()
                    },
                    success: function(data) {
                        response($.map(data, function(item) {
                            return {
                                first_name: item.first_name,
                                last_name: item.last_name,
                                team: item.team,
                                position: item.position,
                                label: item.first_name + " " + item.last_name + " (" + item.position + ", " + item.team + ")"
                            }
                        }));
                    }
                });
            },
            minLength: 2,
            select: function(event, ui) {
                var $firstName = $('#first_name'),
                    $lastName = $('#last_name'),
                    $team = $('#team'),
                    $position = $('#position');

                setTimeout(function() {
                    $firstName.val(ui.item.first_name);
                    $lastName.val(ui.item.last_name);
                    $team.val(ui.item.team);
                    $position.val(ui.item.position);
                }, 50);
            }
        },
        matchTemplate = _.template($("#matchingPickTemplate").html()),
        blockForMatches = true;

    $(document).ready(function() {
        var $addPickForm = $('#addPickForm');

        $("#first_name").focus();

        if($addPickForm.data('has-autocomplete') == '1') {
           $('#first_name').autocomplete(autocompleteOptions);
           $('#last_name').autocomplete(autocompleteOptions);
        }

        $addPickForm.validate();

        $addPickForm.on('submit', interceptFormSubmit);

        $('#alreadyPickedDialog').dialog({
            autoOpen: false,
            title: "Possible Matching Picks",
            modal: true,
            width: 700,
            resizable: false,
            draggable: false,
            buttons: [
                {
                    text: "Yes, I am sure.",
                    click: okAlreadyDraftedButtonHandler
                },
                {
                    text: "No / Cancel",
                    click: function() {
                        $(this).dialog('close');
                    }
                }
            ]
        });

        $(document).on('click', '#clearButton', clearButtonHandler);
    });

    function interceptFormSubmit(event) {
        if(!blockForMatches) {
            return true;
        }

        event.preventDefault();

        var first_name = $('#first_name').val(),
            last_name = $('#last_name').val(),
            draft_id = $('#draft_id').val(),
            $pickForm = $('#addPickForm'),
            $matchesList = $('#alreadyPickedDialog .matchingPicks');

        if(!$pickForm.valid()) {
            return false;
        }

        $('#loadingDialog').dialog('open');
        $.ajax({
            url: 'draft_room.php',
            type: 'GET',
            data: {
                action: 'checkAlreadyDrafted',
                did: draft_id,
                first_name: first_name,
                last_name: last_name},
            dataType: 'json',
            complete: function() {
                $('#loadingDialog').dialog('close');
            },
            success: function(response) {
                if(response.PossibleMatchExists == true) {
                    $matchesList.empty();

                    $.each(response.Matches, function() {
                        var player = this;

                        player.positionClass = player.position;

                        switch(player.position) {
                            case '1B':
                            case '2B':
                            case '3B':
                                player.positionClass = 'x' + player.position;
                                break;
                        }

                        $matchesList.append(matchTemplate({player: player}));
                    });

                    $('#alreadyPickedDialog').find('.pickName').html(first_name + ' ' + last_name);

                    $('#alreadyPickedDialog').dialog('open');

                    return false;
                }

                blockForMatches = false;
                $('#addPickButton').click();

                return true;
            },
            error: function() {
                $('#informationDialog').html('Unable to check status of picks, please ensure you have a working internet connection.').dialog('open');
            }
        });
    }

    function okAlreadyDraftedButtonHandler() {
        $('#alreadyPickedDialog').dialog('close');
        $('#loadingDialog').dialog('open');

        blockForMatches = false;

        $('#addPickButton').click();
    }

    function clearButtonHandler() {
        var $first = $('#first_name'),
            $last = $('#last_name'),
            $team = $('#team'),
            $position = $('#position');

        $first.val('');
        $last.val('');
        $team.val('');
        $position.val('');
    }
});