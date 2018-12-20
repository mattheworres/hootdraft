class AddPickController {
  constructor($scope,
    $rootScope,
    $routeParams,
    $q,
    $location,
    $loading,
    $timeout,
    subscriptionKeys,
    workingModalService,
    api,
    messageService,
    authenticationService,
    draftService,
    pathHelperService,
    errorService) {
    this.$scope = $scope;
    this.$rootScope = $rootScope;
    this.$routeParams = $routeParams;
    this.$q = $q;
    this.$location = $location;
    this.$loading = $loading;
    this.$timeout = $timeout;
    this.subscriptionKeys = subscriptionKeys;
    this.workingModalService = workingModalService;
    this.api = api;
    this.messageService = messageService;
    this.authenticationService = authenticationService;
    this.draftService = draftService;
    this.pathHelperService = pathHelperService;
    this.errorService = errorService;

    this._savePick = this._savePick.bind(this);
  }

  $onInit() {
    this.currentPick = {};
    this.addInProgress = false;

    this.draftService.getDraft().then(draft => {
      this.draft = draft;
      this.draftStatus = this.draftService.getStatus();

      if ((draft !== null) && ((draft.setting_up === true) || (draft.complete === true))) {
        if (draft.setting_up) {
          this.messageService.showWarning('Unable to add picks for draft: draft has not been started yet.');
        } else if (draft.complete) {
          this.messageService.showWarning('Unable to add picks for draft: draft is already completed');
        }

        this.pathHelperService.sendToPreviousPath();
        this.draftError = true;
      }

      this._loadCurrentPick(this.draft.draft_id);
    }, () => {
      this.messageService.showError('Unable to load draft');
    });
  }

  _loadCurrentPick(draftId) {
    this.$loading.start('load_current');
    this.currentLoading = true;

    const currentPickSuccess = data => {
      this.$loading.finish('load_current');
      this.currentLoading = false;
      this.currentPick = data.pick;
      this.pristineCurrentPick = data.pick;
      this.teams = data.teams;
      this.positions = data.positions;
      this.last5Picks = data.last_5_picks;
      this.next5Picks = data.next_5_picks;
      this.isLastPick = this.next5Picks.length === 1;
      this.$scope.$broadcast(this.subscriptionKeys.focusPlayerAutocomplete);
    };

    const errorHandler = () => {
      this.$loading.finish('load_current');
      this.currentLoading = false;
      this.currentError = true;
      this.messageService.showError('Unable to get current pick');
    };

    this.currentError = false;
    this.api.Pick.getCurrent({draft_id: draftId}, currentPickSuccess, errorHandler);
  }

  _savePick() {
    this.messageService.closeToasts();
    this.workingModalService.openModal();

    const addSuccessHandler = () => {
      this.addInProgress = false;
      this.workingModalService.closeModal();

      this.messageService.showSuccess(`${this.currentPick.first_name} ${this.currentPick.last_name} drafted`);

      if (this.isLastPick) {
        this.workingModalService.closeModal();
        this.workingModalService.openModal();
        //Draft has been completed - ensure commish user *thinks* something big happened, even though this is all instant
        this.$timeout(() => {
          this.$location.path(`/draft/${this.$routeParams.draft_id}`);

          this.workingModalService.closeModal();

          this.messageService.showSuccess('Congrats! Your draft has been completed.', 'That\'s a Wrap!');
        }, 1500);
      } else {
        this._loadCurrentPick(this.draft.draft_id);
      }
    };

    const addFailureHandler = response => {
      this.workingModalService.closeModal();
      this.addInProgress = false;
      const addError = this.errorService.parseValidationErrorsFromResponse(response);

      this.messageService.showError(`${addError}`, 'Unable to enter pick');
    };

    this.api.Pick.add(this.currentPick, addSuccessHandler, addFailureHandler);
  }
}

AddPickController.$inject = [
  '$scope',
  '$rootScope',
  '$routeParams',
  '$q',
  '$location',
  '$loading',
  '$timeout',
  'subscriptionKeys',
  'workingModalService',
  'api',
  'messageService',
  'authenticationService',
  'draftService',
  'pathHelperService',
  'errorService',
];

angular.module('phpdraft.pick').component('phpdAddPick', {
  controller: AddPickController,
  templateUrl: 'app/features/pick/add/addPick.component.html',
});
