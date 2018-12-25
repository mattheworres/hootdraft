class StatsController {
  constructor($scope,
    $routeParams,
    subscriptionKeys,
    api,
    messageService,
    draftService,
    pathHelperService) {
    this.$scope = $scope;
    this.$routeParams = $routeParams;
    this.subscriptionKeys = subscriptionKeys;
    this.api = api;
    this.messageService = messageService;
    this.draftService = draftService;
    this.pathHelperService = pathHelperService;
  }

  $onInit() {
    this.statsGenerated = false;

    this.draftService.getDraft().then(draft => {
      this.draftStatus = this.draftService.getStatus();

      if (draft !== null && draft.setting_up === true || draft.in_progress === true) {
        this.pageError = true;
        this.pathHelperService.sendToPreviousPath();
        this.messageService.showWarning('Draft is still setting up');
      } else if (draft !== null && (draft.in_progress === true || draft.complete === true)) {
        this.draft = draft;

        this._loadStatsData(draft.draft_id);
      }
    }, () => {
      this.messageService.showError('Unable to load draft');
    });
  }

  _loadStatsData(draftId) {
    const statsSuccess = data => {
      this.statsLoading = false;
      this.stats = data.draft_statistics;
      this.statsGenerated = data.draft_statistics !== null;
    };

    const errorHandler = () => {
      this.statsLoading = false;
      this.statsError = true;
    };

    this.statsLoading = true;
    this.statsError = false;

    if (this.draftStatus.valid && !this.draftStatus.locked) {
      this.api.Draft.getStats({draft_id: draftId}, statsSuccess, errorHandler);
    }
  }
}

StatsController.$inject = [
  '$scope',
  '$routeParams',
  'subscriptionKeys',
  'api',
  'messageService',
  'draftService',
  'pathHelperService',
];

angular.module('phpdraft.draft').component('phpdStats', {
  controller: StatsController,
  templateUrl: 'app/features/draft/stats.component.html',
});
