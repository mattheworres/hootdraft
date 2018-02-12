class DraftIndexController {
  constructor($scope, $rootScope, $routeParams, $loading,
    $timeout, subscriptionKeys, api, messageService, draftService) {
    this.$scope = $scope;
    this.$rootScope = $rootScope;
    this.$routeParams = $routeParams;
    this.$loading = $loading;
    this.$timeout = $timeout;
    this.subscriptionKeys = subscriptionKeys;
    this.api = api;
    this.messageService = messageService;
    this.draftService = draftService;
  }

  $onInit() {
    this.settingUpLoaded = false;
    this.inProgressLoaded = false;
    this.completedLoaded = false;
    this.$scope.selectedDraftRound = 1;
    this.currentDraftCounter = 0;

    this.status = this.draftService.getStatus();
    this.draftService.getDraft().then(draft => {
      this.draft = draft;
      this._handleDraftUpdate(draft, this.status);
    }, () => {
      this.messageService.showError('Unable to load draft');
    });

    //Watch for changes on scope of the selected draft round from the pager
    this.deregisterWatcher = this.$scope.$watch((() => this.$scope.selectedDraftRound), () => {
      if ((angular.isDefined(this.draft) &&
        this.draft.draft_id === this.$routeParams.draft_id) &&
        this.draft.complete &&
        this.status.valid &&
        !this.status.locked) {
        this._loadCompletedData(this.$routeParams.draft_id);
      }
    });
  }

  $onDestroy() {
    if (angular.isDefined(this.deregisterDraftCounterSubscription)) {
      this.deregisterDraftCounterSubscription();
    }
    this.deregisterWatcher();
  }

  _handleDraftUpdate(draft, status) {
    if (draft.complete) {
      this.$scope.pagerItemTally = draft.draft_rounds * 10;
      this._loadCompletedData(draft.draft_id);
      return;
    }

    if (draft.setting_up) {
      this._loadSettingUpData(draft, status);
    } else if (draft.in_progress) {
      this._loadInProgressData(draft, status);
    }

    //This was moved to _handleDraftUpdate, we needn't register a subscription when the draft is "complete"
    this.deregisterDraftCounterSubscription = this.$scope.$on(this.subscriptionKeys.draftCounterHasChanged, (event, args) => {
      const {draft, status} = args;

      this.draft = draft;
      this.status = status;

      this._handleDraftUpdate(draft, status);
    });
  }

  _loadSettingUpData(draft, status) {
    const managersSuccess = data => {
      this.settingUpLoaded = true;
      this.$scope.managersLoading = false;
      this.$scope.managers = data;
    };

    const managersError = () => {
      this.settingUpLoaded = true;
      this.$scope.managersLoading = false;
      this.$scope.managersError = true;
      this.messageService.showError('Unable to load managers');
    };

    this.$scope.managersLoading = this.settingUpLoaded;
    this.$scope.commishManagersLoading = this.settingUpLoaded;
    this.$scope.managersError = false;

    if (status.valid && !status.locked) {
      if ((draft.commish_editable !== null) && (draft.commish_editable === false)) {
        this.api.Manager.getManagers({draft_id: draft.draft_id}, managersSuccess, managersError); // eslint-disable-line camelcase
      }

      if (this.settingUpLoaded === false && draft.commish_editable) {
        this.$timeout((() => this.$rootScope.$broadcast(this.subscriptionKeys.updateCommishManagers, {draft})), 250);
      }
    }
  }


  _loadInProgressData(draft, status) {
    const lastSuccess = data => {
      this.inProgressLoaded = true;
      this.$loading.finish('load_last_picks');
      this.$scope.lastLoading = false;
      this.$scope.lastFivePicks = data;
    };

    const nextSuccess = data => {
      this.inProgressLoaded = true;
      this.$loading.finish('load_next_picks');
      this.$scope.nextLoading = false;
      this.$scope.nextFivePicks = data;
    };

    const lastErrorHandler = () => {
      this.inProgressLoaded = true;
      this.$loading.finish('load_last_picks');
      this.$scope.lastLoading = false;
      this.$scope.lastError = true;
    };

    const nextErrorHandler = () => {
      this.inProgressLoaded = true;
      this.$loading.finish('load_next_picks');
      this.$scope.nextLoading = false;
      this.$scope.nextError = true;
    };

    this.$scope.lastLoading = this.inProgressLoaded;
    this.$scope.nextLoading = this.inProgressLoaded;

    if (this.inProgressLoaded === false) {
      this.$loading.start('load_last_picks');
      this.$loading.start('load_next_picks');
    }

    this.$scope.lastError = false;
    this.$scope.nextError = false;

    if (status.valid && !status.locked) {
      this.api.Pick.getLast({draft_id: draft.draftId, amount: 5}, lastSuccess, lastErrorHandler); // eslint-disable-line camelcase
      this.api.Pick.getNext({draft_id: draft.draftId, amount: 5}, nextSuccess, nextErrorHandler); // eslint-disable-line camelcase
    }
  }

  _loadCompletedData(draftId) {
    const roundSuccess = data => {
      this.roundPicks = data;
      this.roundLoading = false;
    };

    const errorHandler = () => {
      this.messageService.showError('Unable to load picks');
      this.roundError = true;
      this.roundLoading = false;
    };

    if (this.status.valid && !this.status.locked) {
      this.roundError = false;
      this.roundLoading = true;
      this.api.Pick.getSelectedByRound({draft_id: draftId, round: this.$scope.selectedDraftRound, sort_ascending: true}, roundSuccess, errorHandler); // eslint-disable-line camelcase
    }
  }
}
DraftIndexController.$inject = [
  '$scope',
  '$rootScope',
  '$routeParams',
  '$loading',
  '$timeout',
  'subscriptionKeys',
  'api',
  'messageService',
  'draftService',
];

angular.module('phpdraft.draft').component('phpdDraftIndex', {
  controller: DraftIndexController,
  templateUrl: 'app/features/draft/index/draftIndex.component.html',
});
