class DraftIndexController {
  constructor($scope, $rootScope, $routeParams, $loading,
    $timeout, subscriptionKeys, api, messageService, draftService,
    lodash) {
    this.$scope = $scope;
    this.$rootScope = $rootScope;
    this.$routeParams = $routeParams;
    this.$loading = $loading;
    this.$timeout = $timeout;
    this.subscriptionKeys = subscriptionKeys;
    this.api = api;
    this.messageService = messageService;
    this.draftService = draftService;
    this.lodash = lodash;

    this.onSelectedDraftRoundUpdated = this.onSelectedDraftRoundUpdated.bind(this);
  }

  $onInit() {
    this.settingUpLoaded = false;
    this.inProgressLoaded = false;
    this.completedLoaded = false;
    this.selectedDraftRound = 1;
    this.currentDraftCounter = 0;

    this.status = this.draftService.getStatus();

    this.draftService.getDraft().then(draft => {
      this.draft = draft;
      this._handleDraftUpdate(draft, this.status);
    }, () => {
      this.messageService.showError('Unable to load draft');
    });

    //Watch for changes on scope of the selected draft round from the pager
    this.deregisterWatcher = this.$scope.$watch((() => this.selectedDraftRound), () => {
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

  onSelectedDraftRoundUpdated(selectedDraftRound) {
    this.selectedDraftRound = selectedDraftRound;
  }

  _handleDraftUpdate(draft, status) {
    if (draft.complete) {
      this.$scope.pagerItemTally = draft.draft_rounds * 10;
      this._loadCompletedData(draft.draft_id);
      return;
    } else if (draft.setting_up) {
      this._loadSettingUpData(draft, status);
    } else if (draft.in_progress) {
      this._loadInProgressData(draft, status);
    }

    //This was moved to _handleDraftUpdate, we needn't register a subscription when the draft is "complete"
    this.deregisterDraftCounterSubscription = this.$scope.$on(this.subscriptionKeys.draftCounterHasChanged, (event, args) => {
      const {draft, status} = args;

      if (angular.isDefined(this.draft)) {
        this.lodash.merge(this.draft, draft);
        this.currentDraftCounter = this.draft.draft_counter;
      } else {
        this.draft = draft;
      }

      this.status = status;

      this._handleDraftUpdate(draft, status);
    });
  }

  _loadSettingUpData(draft, status) {
    const managersSuccess = data => {
      this.settingUpLoaded = true;
      this.managersLoading = false;
      this.managers = data;
    };

    const managersError = () => {
      this.settingUpLoaded = true;
      this.managersLoading = false;
      this.managersError = true;
      this.messageService.showError('Unable to load managers');
    };

    this.managersLoading = this.settingUpLoaded;
    this.commishManagersLoading = this.settingUpLoaded;
    this.managersError = false;

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
      this.lastLoading = false;
      this.lastFivePicks = data;
    };

    const nextSuccess = data => {
      this.inProgressLoaded = true;
      this.$loading.finish('load_next_picks');
      this.nextLoading = false;
      this.nextFivePicks = data;
    };

    const lastErrorHandler = () => {
      this.inProgressLoaded = true;
      this.$loading.finish('load_last_picks');
      this.lastLoading = false;
      this.lastError = true;
    };

    const nextErrorHandler = () => {
      this.inProgressLoaded = true;
      this.$loading.finish('load_next_picks');
      this.nextLoading = false;
      this.nextError = true;
    };

    this.lastLoading = this.inProgressLoaded;
    this.nextLoading = this.inProgressLoaded;

    if (this.inProgressLoaded === false) {
      this.$loading.start('load_last_picks');
      this.$loading.start('load_next_picks');
    }

    this.lastError = false;
    this.nextError = false;

    //Check the API route matching, or maybe if the draft object even exists here...
    if (status.valid && !status.locked) {
      this.api.Pick.getLast({draft_id: draft.draft_id, amount: 5}, lastSuccess, lastErrorHandler); // eslint-disable-line camelcase
      this.api.Pick.getNext({draft_id: draft.draft_id, amount: 5}, nextSuccess, nextErrorHandler); // eslint-disable-line camelcase
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
      this.api.Pick.getSelectedByRound({draft_id: draftId, round: this.selectedDraftRound, sort_ascending: true}, roundSuccess, errorHandler); // eslint-disable-line camelcase
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
  'lodash',
];

angular.module('phpdraft.draft').component('phpdDraftIndex', {
  controller: DraftIndexController,
  templateUrl: 'app/features/draft/index/draftIndex.component.html',
});
