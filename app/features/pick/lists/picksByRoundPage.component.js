class ByRoundController {
  constructor($scope,
    $routeParams,
    $loading,
    subscriptionKeys,
    messageService,
    api,
    draftService,
    pathHelperService) {
    this.$scope = $scope;
    this.$routeParams = $routeParams;
    this.$loading = $loading;
    this.subscriptionKeys = subscriptionKeys;
    this.messageService = messageService;
    this.api = api;
    this.draftService = draftService;
    this.pathHelperService = pathHelperService;
    this._loadRoundData = this._loadRoundData.bind(this);
    this._loadInProgressData = this._loadInProgressData.bind(this);
  }

  $onInit() {
    this.selectedDraftRound = 1;
    this.currentDraftCounter = 0;

    this.draftService.getDraft().then(draft => {
      this.draftStatus = this.draftService.getStatus();
      this.currentDraftCounter = draft.draft_counter;
      this.draft = draft;

      if (draft !== null && draft.setting_up) {
        this.pageError = true;
        this.pathHelperService.sendToPreviousPath();
        this.messageService.showWarning('Draft is still setting up');
      } else if (draft.in_progress || draft.complete) {
        this.pagerItemTally = draft.draft_rounds * 10;

        if (draft.in_progress) {
          this.selectedDraftRound = draft.draft_current_round;

          this.deregister = this.$scope.$on(this.subscriptionKeys.draftCounterHasChanged, (event, args) => {
            const {draft, status} = args;

            this.onDraftCounterChanged(draft, status);
          }).bind(this);

          this._loadInProgressData(draft.draft_id, true);
        }

        this.deregisterRoundWatcher = this.$scope.$watch('$ctrl.selectedDraftRound', () => {
          this._loadRoundData(this.draft.draft_id, false);
        });

        this._loadRoundData(draft.draft_id, true);
      }
    }, () => {
      this.messageService.showError('Unable to load draft');
    });
  }

  $onDestroy() {
    if (angular.isFunction(this.deregister)) {
      this.deregister();
    }

    if (angular.isFunction(this.deregisterRoundWatcher)) {
      this.deregisterRoundWatcher();
    }
  }

  onDraftCounterChanged(draft, status) {
    if (angular.isDefined(this.draft)) {
      this.lodash.merge(this.draft, draft);
      this.currentDraftCounter = this.draft.draft_counter;
    } else {
      this.draft = draft;
    }

    this.lodash.merge(this.draftStatus, status);

    this._loadInProgressData(draft.draft_id, false);
    this._loadRoundData(draft.draft_id, false);
  }

  _loadRoundData(draft_id, onPageLoad) {
    const roundSuccess = data => {
      this.roundPicks = data;
      this.roundPicksLoading = false;
      this.$loading.finish('roundPicksLoading');
    };

    const errorHandler = () => {
      this.picksError = true;
      this.roundPicksLoading = false;
      this.$loading.finish('roundPicksLoading');
      this.messageService.showError('Unable to load picks');
    };

    if (this.draftStatus.valid && !this.draftStatus.locked) {
      this.picksError = false;
      this.roundPicksLoading = onPageLoad;
      this.$loading.start('roundPicksLoading');

      this.api.Pick.getAllByRound({draft_id, round: this.selectedDraftRound, sort_ascending: true}, roundSuccess, errorHandler);
    }
  }

  _loadInProgressData(draft_id, onPageLoad) {
    const nextSuccess = data => {
      this.nextLoading = false;
      this.nextFivePicks = data;
      this.$loading.finish('load_next_picks');
    };

    const nextErrorHandler = () => {
      this.nextLoading = false;
      this.nextError = true;
      this.$loading.finish('load_next_picks');
    };

    this.nextLoading = onPageLoad;
    this.nextError = false;

    if (onPageLoad) {
      this.$loading.start('load_next_picks');
    }

    this.api.Pick.getNext({draft_id, amount: 5}, nextSuccess, nextErrorHandler);
  }
}

ByRoundController.$inject = [
  '$scope',
  '$routeParams',
  '$loading',
  'subscriptionKeys',
  'messageService',
  'api',
  'draftService',
  'pathHelperService',
];

angular.module('phpdraft.pick').component('phpdPicksByRoundPage', {
  controller: ByRoundController,
  templateUrl: 'app/features/pick/lists/picksByRoundPage.component.html',
});


