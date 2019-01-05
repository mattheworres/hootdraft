class EditPickController {
  constructor($scope,
    $location,
    workingModalService,
    api,
    messageService,
    authenticationService,
    pathHelperService,
    errorService,
    draftService) {
    this.$scope = $scope;
    this.$location = $location;
    this.workingModalService = workingModalService;
    this.api = api;
    this.messageService = messageService;
    this.authenticationService = authenticationService;
    this.pathHelperService = pathHelperService;
    this.errorService = errorService;
    this.draftService = draftService;
  }

  $onInit() {
    this.selectedDraftRound = 1;
    this.currentPick = {};
    this.pristineCurrentPick = {};
    this.showPickSelection = true;
    this.editInProgress = false;
    this.pagerItemTally = 0;

    this.draftService.getDraft().then(draft => {
      this.draft = draft;
      this.draftStatus = this.draftService.getStatus();

      if (draft.setting_up || draft.complete) {
        if (draft.setting_up) {
          this.messageService.showWarning('Unable to edit picks for draft: draft has not been started yet.');
        } else if (draft.complete) {
          this.messageService.showWarning('Unable to edit picks for draft: draft is already completed');
        }
      } else if (draft.in_progress) {
        this.pagerItemTally = draft.draft_rounds * 10;
        this.selectedDraftRound = draft.draft_current_round;

        this.deregisterSelectedDraftRoundWatcher = this.$scope.$watch('$ctrl.selectedDraftRound', () => {
          this._loadRoundData(this.draft.draft_id);
        });
      }
    });
  }

  $onDestroy() {
    if (angular.isFunction(this.deregisterSelectedDraftRoundWatcher)) {
      this.deregisterSelectedDraftRoundWatcher();
    }
  }

  _loadRoundData(draft_id, onPageLoad) {
    const roundSuccess = data => {
      this.roundPicks = data;
      this.roundHasPicks = data.length > 0;
      this.roundPicksLoading = false;
    };

    const errorHandler = () => {
      this.picksError = true;
      this.roundPicksLoading = false;
      this.messageService.showError('Unable to load picks');
    };

    if (this.draftStatus.valid && !this.draftStatus.locked) {
      this.picksError = false;
      this.roundPicksLoading = onPageLoad;
      this.api.Pick.getSelectedByRound({draft_id, round: this.selectedDraftRound, sort_ascending: true}, roundSuccess, errorHandler);
    }
  }

  selectEditPick(pick) {
    this.currentPick = pick;
    this.pristineCurrentPick = pick;
    this.showPickSelection = false;
  }

  _savePick() {
    this.messageService.closeToasts();

    const editSuccessHandler = () => {
      this.editInProgress = false;

      this.messageService.showSuccess(`${this.currentPick.first_name} ${this.currentPick.last_name} updated`);
      this.pathHelperService.sendToPreviousPath();
    };

    const editFailureHandler = response => {
      this.editInProgress = false;

      if ((angular.isDefined(response) ? response.status : 0) === 401) {
        this.messageService.showError('Unauthorized: please log in.');
        this.authenticationService.uncacheSession();
        this.$location.path('/login');
        return;
      }

      const editError = this.errorService.parseValidationErrorsFromResponse(response);
      this.messageService.showError(`${editError}`, 'Unable to edit pick');
      return;
    };

    this.api.Pick.update(this.currentPick, editSuccessHandler, editFailureHandler);
  }
}

EditPickController.$inject = [
  '$scope',
  '$location',
  'workingModalService',
  'api',
  'messageService',
  'authenticationService',
  'pathHelperService',
  'errorService',
  'draftService',
];

angular.module('phpdraft.pick').component('phpdEditPick', {
  controller: EditPickController,
  templateUrl: 'app/features/pick/edit/editPick.component.html',
});
