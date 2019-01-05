class PickTimersController {
  constructor($routeParams,
    $q,
    $loading,
    subscriptionKeys,
    workingModalService,
    api,
    messageService,
    errorService,
    draftService,
    $window,
    pathHelperService) {
    this.$routeParams = $routeParams;
    this.$q = $q;
    this.$loading = $loading;
    this.subscriptionKeys = subscriptionKeys;
    this.workingModalService = workingModalService;
    this.api = api;
    this.messageService = messageService;
    this.errorService = errorService;
    this.draftService = draftService;
    this.$window = $window;
    this.pathHelperService = pathHelperService;
  }

  $onInit() {
    this.pickTimerDataLoading = true;

    this.draftService.getDraft().then(draft => {
      this.draft = draft;
      this.draftStatus = this.draftService.getStatus();

      if (draft !== null && (draft.setting_up || draft.in_progress)) {
        this._loadPickTimerData(draft.draft_id);
      } else if (draft !== null && draft.complete) {
        this.pickTimerDataError = true;
        this.pathHelperService.sendToPreviousPath();
        this.messageService.showWarning('Draft is still setting up');
      }
    }, () => {
      this.messageService.showError('Unable to load draft');
    });
  }

  _loadPickTimerData(draftId) {
    const pickTimerDataSuccess = data => {
      let totalManagerSeconds = 0;

      //Convert total seconds to split minutes/seconds for each round time
      data.forEach(timer => {
        timer.minutes = Math.floor(timer.round_time_seconds / 60);
        timer.seconds = timer.round_time_seconds - (timer.minutes * 60);
        totalManagerSeconds += timer.round_time_seconds;
      });

      this.pickTimers = data;
      this.useStaticTime = (this.pickTimers !== null) && (this.pickTimers[0].is_static_time === '1');
      this.roundTimesEnabled = totalManagerSeconds > 0;

      if (this.roundTimesEnabled === false) {
        this._setSuggestedRoundTimes();
      } else {
        this._calculateTotalDraftTime();
      }
    };

    const managersSuccess = data => {
      this.numberOfManagers = data.length;
    };

    const errorHandler = () => {
      this.pickTimerDataLoading = false;
      this.pickTimerDataError = true;
    };

    this.pickTimerDataError = false;

    if (this.draftStatus.valid && !this.draftStatus.locked) {
      this.$loading.start('load_timers');

      const timersPromise = this.api.Draft.getTimers({draft_id: draftId}, pickTimerDataSuccess, errorHandler);
      const managersPromise = this.api.Manager.getManagers({draft_id: draftId}, managersSuccess, errorHandler);

      this.$q.all([timersPromise, managersPromise]).then(() => {
        this.pickTimerDataLoading = false;

        this.$loading.finish('load_timers');
        if (this.roundTimesEnabled) {
          this._calculateTotalDraftTime();
        }
      });

    } else {
      this.pickTimerDataLoading = false;
      this.pickTimerDataError = true;
    }
  }

  submitClicked() {
    if (this.form.$valid) {
      this.saveTimers();
    }
  }

  cancelClicked() {
    this.$window.history.back();
  }

  onTimerChanged() {
    this._calculateTotalDraftTime();
  }

  timersFormIsInvalid() {
    return this.saveInProgress || !this.form.$valid;
  }

  saveTimers() {
    this.workingModalService.openModal();

    const timersToSave = [];

    if (this.roundTimesEnabled) {
      if (this.useStaticTime) {
        //Only grab first timer
        const firstTimer = this.pickTimers[0];
        firstTimer.is_static_time = true;
        firstTimer.round_time_seconds = (firstTimer.minutes * 60) + firstTimer.seconds;
        firstTimer.draft_id = this.$routeParams.draft_id;
        timersToSave.push(firstTimer);
      } else {
        //Grab 'em all
        this.pickTimers.forEach(timer => {
          timer.is_static_time = false;
          timer.round_time_seconds = (timer.minutes * 60) + timer.seconds;
          timer.draft_id = this.$routeParams.draft_id;
          timersToSave.push(timer);
        });
      }
    }

    const saveModel = {
      draft_id: this.$routeParams.draft_id,
      isRoundTimesEnabled: this.roundTimesEnabled,
      roundTimes: timersToSave,
    };

    this.saveInProgress = true;

    this.messageService.closeToasts();

    const saveSuccessHandler = () => {
      this.saveInProgress = false;
      this.workingModalService.closeModal();

      this.form.$setPristine();

      this.messageService.showInfo('Round timers saved!');
      this.pathHelperService.sendToPreviousPath();
    };

    const saveFailureHandler = response => {
      this.saveInProgress = false;
      this.workingModalService.closeModal();
      const saveError = this.errorService.parseValidationErrorsFromResponse(response);

      this.messageService.showError(`${saveError}`, 'Unable to save timers');
    };

    this.api.Draft.setTimers(saveModel, saveSuccessHandler, saveFailureHandler);
  }

  _setSuggestedRoundTimes() {
    this.pickTimers.forEach(roundTime => {
      switch (false) {
        case !(roundTime.draft_round <= 1):
          roundTime.minutes = 4;
          roundTime.seconds = 0;
          break;
        case !(roundTime.draft_round <= 3):
          roundTime.minutes = 3;
          roundTime.seconds = 30;
          break;
        case !(roundTime.draft_round <= 5):
          roundTime.minutes = 3;
          roundTime.seconds = 15;
          break;
        case !(roundTime.draft_round <= 7):
          roundTime.minutes = 3;
          roundTime.seconds = 0;
          break;
        case !(roundTime.draft_round <= 10):
          roundTime.minutes = 2;
          roundTime.seconds = 30;
          break;
        case !(roundTime.draft_round <= 13):
          roundTime.minutes = 1;
          roundTime.seconds = 45;
          break;
        case !(roundTime.draft_round <= 15):
          roundTime.minutes = 1;
          roundTime.seconds = 15;
          break;
        case !(roundTime.draft_round <= 20):
          roundTime.minutes = 1;
          roundTime.seconds = 0;
          break;
        case !(roundTime.draft_round <= 30):
          roundTime.minutes = 0;
          roundTime.seconds = 45;
          break;
        default:
          roundTime.minutes = 0;
          roundTime.seconds = 5;
      }
    });

    this._calculateTotalDraftTime();
  }

  _calculateTotalDraftTime() {
    let roundTime;

    if ((this.pickTimers === null) || angular.isUndefined(this.numberOfManagers)) {
      this.totalDraftingTime = 0;
      return;
    }

    let secondsPerManager = 0;

    if (this.useStaticTime) {
      roundTime = this.pickTimers[0];
      const secondsPerRound = (roundTime.minutes * 60) + roundTime.seconds;
      secondsPerManager = secondsPerRound * this.pickTimers.length;
    }

    this.pickTimers.forEach(roundTime => {
      const secondsForThisRound = (roundTime.minutes * 60) + roundTime.seconds;
      secondsPerManager += secondsForThisRound;
    });

    if (this.numberOfManagers === 0) {
      this.totalDraftingTime = 0;
    } else {
      this.totalDraftingTime = secondsPerManager * this.numberOfManagers;
    }
  }
}


PickTimersController.$inject = [
  '$routeParams',
  '$q',
  '$loading',
  'subscriptionKeys',
  'workingModalService',
  'api',
  'messageService',
  'errorService',
  'draftService',
  '$window',
  'pathHelperService',
];

angular.module('phpdraft.draft').component('phpdPickTimers', {
  controller: PickTimersController,
  templateUrl: 'app/features/draft/edit/pickTimers.component.html',
});
