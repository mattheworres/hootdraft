class PicksByManagerController {
  constructor($scope,
    $routeParams,
    $timeout,
    $loading,
    subscriptionKeys,
    messageService,
    api,
    draftService,
    pathHelperService,
    errorService) {
    this.$scope = $scope;
    this.$routeParams = $routeParams;
    this.$timeout = $timeout;
    this.$loading = $loading;
    this.subscriptionKeys = subscriptionKeys;
    this.messageService = messageService;
    this.api = api;
    this.draftService = draftService;
    this.pathHelperService = pathHelperService;
    this.errorService = errorService;
    this._loadManagerPicks = this._loadManagerPicks.bind(this);
    this._loadInProgressPicks = this._loadInProgressPicks.bind(this);
  }

  $onInit() {
    this.currentDraftCounter = 0;

    this._loadManagers();

    this.draftService.getDraft().then(draft => {
      this.draftStatus = this.draftService.getStatus();

      if (draft !== null && draft.setting_up) {
        this.$scope.pageError = true;
        this.pathHelperService.sendToPreviousPath();
        this.messageService.showWarning('Draft is still setting up');
      } else if (draft !== null && (draft.in_progress || draft.complete)) {
        this.draft = draft;
        this.currentDraftCounter = draft.draft_counter;

        if (draft.in_progress) {
          this._loadInProgressPicks(draft.draft_id, true);

          this.deregister = this.$scope.$on(this.subscriptionKeys.draftCounterHasChanged, (event, args) => {
            const {draft, status} = args;

            this.onDraftCounterChanged(draft, status);
          }).bind(this);
        }

        if (angular.isDefined(this.selectedManagers) && angular.isDefined(this.selectedManager.manager_id)) {
          this._loadManagerPicks(draft.draft_id, this.selectedManager.manager_id, true);
        }
      }
    }, () => {
      this.messageService.showError('Unable to load draft');
    });

    this.$scope.$watch('$ctrl.selectedManager', () => {
      if (angular.isDefined(this.selectedManager)) {
        this._loadManagerPicks(this.draft.draft_id, this.selectedManager.manager_id, true);
      }
    });
  }

  $onDestroy() {
    if (angular.isFunction(this.deregister)) {
      this.deregister();
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

    if (draft.in_progress) {
      this._loadInProgressPicks(draft.draft_id, false);
    }

    if (angular.isDefined(this.selectedManager.manager_id)) {
      this._loadManagerPicks(draft.draft_id, false);
    }
  }

  _loadManagers() {
    const managersSuccess = data => {
      this.managerError = false;
      this.managers = data;
      const first = data[0];
      this.selectedManager = first;
      this._loadManagerPicks(this.$routeParams.draft_id, this.selectedManager.manager_id, true);
    };

    const managersError = () => {
      this.managerError = true;
      this.messageService.showError('Unable to grab managers');
    };

    this.api.Manager.getManagers({draft_id: this.$routeParams.draft_id}, managersSuccess, managersError);
  }

  _loadManagerPicks(draft_id, selectedManagerId, showLoading) {
    const picksSuccess = data => {
      this.managerPicks = data;
      this.picksLoading = false;
    };

    const picksErrorHandler = () => {
      this.messageService.showError('Unable to load picks');
      this.picksError = true;
    };

    this.picksLoading = showLoading;
    this.picksError = false;
    this.api.Pick.getAllByManager({draft_id, manager_id: selectedManagerId, sort_ascending: true}, picksSuccess, picksErrorHandler);
  }

  _loadInProgressPicks(draft_id, onPageLoad) {
    const nextSuccess = data => {
      this.nextFivePicks = data;
      this.nextLoading = false;
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

PicksByManagerController.$inject = [
  '$scope',
  '$routeParams',
  '$timeout',
  '$loading',
  'subscriptionKeys',
  'messageService',
  'api',
  'draftService',
  'pathHelperService',
  'errorService',
];

angular.module('phpdraft.pick').component('phpdPicksByManager', {
  controller: PicksByManagerController,
  templateUrl: 'app/features/pick/lists/picksByManager.component.html',
});
