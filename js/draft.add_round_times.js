$(document).ready(function() {
    $(document).on('change', '#round_times_enabled', roundTimesEnabledChangeHandler);
    $(document).on('change', '#is_static_time', isStaticTimeChangeHandler);
    $(document).on('click', '#saveRoundTimesButton', saveRoundTimesHandler);
    fillDynamicPicksOnLoad(dynamicRoundTimers, staticRoundTimer);
    isStaticTimeChangeHandler();
    roundTimesEnabledChangeHandler();
    setupUIElements();
});

function customRoundTimesValid() {
    var visibleRows = $('table.times-table:visible tr.data-row'),
        valid = true;

    if(visibleRows.length == 0) {
        return true;
    }

    $.each(visibleRows, function() {
        var $row = $(this),
            minutes = parseInt($row.find('input.minutes').val(), 10),
            seconds = parseInt($row.find('input.seconds').val(), 10);

        if(minutes == 0 && seconds == 0) {
            valid = false;
        }
    });

    return valid;
}

function roundTimesEnabledChangeHandler() {
    var roundTimesEnabled = $('#round_times_enabled').is(':checked'),
        $isStaticTimeCheckbox = $('#is_static_time'),
        isStaticTime = $isStaticTimeCheckbox.is(':checked'),
        $staticTable = $('#static-round-time-table'),
        $dynamicTable = $('#dynamic-round-time-table');

    if(roundTimesEnabled) {
        $isStaticTimeCheckbox.removeProp('disabled');

        if(isStaticTime) {
            $staticTable.show();
        } else {
            $dynamicTable.show();
        }
    } else {
        $staticTable.hide();
        $dynamicTable.hide();
        $isStaticTimeCheckbox.prop('disabled', 'disabled');
    }
}

function setupUIElements() {
    var spinnerIcons = {
        up: "ui-icon-plus",
        down: "ui-icon-minus"
    };

    $('input.minutes').spinner({
        min: 0,
        max: 59,
        icons: spinnerIcons
    });

    $('input.seconds').spinner({
        min: 0,
        max: 59,
        icons: spinnerIcons
    })

    $('#saveRoundTimesButton').button();
}

function isStaticTimeChangeHandler() {
    var $isStaticTimeCheckbox = $('#is_static_time'),
        $isStaticTime = $isStaticTimeCheckbox.is(':checked'),
        $isEnabled = $isStaticTimeCheckbox.prop('disabled') == 'disabled',
        $staticTable = $('#static-round-time-table'),
        $dynamicTable = $('#dynamic-round-time-table');

    if($isEnabled) {
        return;
    }

    if($isStaticTime) {
        $staticTable.show();
        $dynamicTable.hide();
    } else {
        $staticTable.hide();
        $dynamicTable.show();
    }
}

function fillDynamicPicksOnLoad(dynamicPicksData, staticPickData) {
    var timerTotalSeconds = staticPickData.round_time_seconds;

    $.each(dynamicPicksData, function() {
       var roundTimer = this,
           timerRound = roundTimer.draft_round,
           timerTotalSeconds = roundTimer.round_time_seconds,
           timerMinutes = Math.floor(timerTotalSeconds / 60),
           timerSeconds = timerTotalSeconds - (timerMinutes * 60),
           $minutesControl = $('#dynamic-round-time-table').find('input.minutes[data-round="' + timerRound + '"]'),
           $secondsControl = $('#dynamic-round-time-table').find('input.seconds[data-round="' + timerRound + '"]');

        $minutesControl.val(timerMinutes);
        $secondsControl.val(timerSeconds);
    });

    if(staticPickData.round_time_seconds == 0) {
        return;
    }

    var timerMinutes = Math.floor(staticPickData.round_time_seconds / 60),
        timerSeconds = timerTotalSeconds - (timerMinutes * 60),
        $minutesControl = $('#static-round-time-table input.minutes'),
        $secondsControl = $('#static-round-time-table input.seconds');

    $minutesControl.val(timerMinutes);
    $secondsControl.val(timerSeconds);
}

function saveRoundTimesHandler() {
    var $informationDialog = $('#informationDialog'),
        $loadingDialog = $('#loadingDialog'),
        $errorDescriptor = $('p.errorDescription');

    $errorDescriptor.hide();

    if (!customRoundTimesValid()) {
        $errorDescriptor.html('One or more round times are invalid. Please fix highlighted fields to continue.').show();
        return;
    }

    //Build array of JSON objects
    var roundTimes = [],
        isRoundTimesEnabled = $('#round_times_enabled').is(':checked'),
        isStaticTime = isRoundTimesEnabled && $('#is_static_time').is(':checked'),
        draft_id = $('#draft_id').val();

    if(!isRoundTimesEnabled) {

    } else if(isStaticTime) {
        var $row = $('#static-round-time-table'),
            minutes = $row.find('input.minutes').val(),
            seconds = $row.find('input.seconds').val(),
            round = 0,
            totalSeconds = (minutes * 60) + seconds;

        roundTimes.push(makeNewRoundTime($('#static-round-time-table'), draft_id, true));
    } else {
        $.each($('#dynamic-round-time-table tr.data-row'), function() {
            roundTimes.push(makeNewRoundTime($(this), draft_id, false));
        });
    }

    $loadingDialog.dialog('open');

    $.ajax({
        type: 'POST',
        data: {
            did: draft_id,
            roundTimes: roundTimes,
            isRoundTimesEnabled: isRoundTimesEnabled
        },
        url: 'draft.php?action=saveRoundTimes',
        success: function(data) {
            $loadingDialog.dialog('close');

            if (data == "SUCCESS") {
                $informationDialog.html('Congratulations! Your round times have been updated.').dialog('open');
            } else {
                $errorDescriptor.html('A server-side error has occurred. Please try again.').show();
            }
        },
        error: function() {
            $loadingDialog.dialog('close');
            $errorDescriptor.html('A server-side error has occurred. Please try again.').show();
        }
    });
}

function makeNewRoundTime($selector, draft_id, is_static_time) {
    var minutes = parseInt($selector.find('input.minutes').val(), 10),
        seconds = parseInt($selector.find('input.seconds').val(), 10),
        round = parseInt($selector.find('input.minutes').data('round'), 10),
        totalSeconds = (minutes * 60) + seconds;

    return{
        draft_id: draft_id,
        is_static_time: is_static_time,
        draft_round: round,
        round_time_seconds: totalSeconds
    };
}