class TradesController {
  constructor($scope,
    $rootScope,
    $routeParams,
    lodash,
    subscriptionKeys,
    api,
    draftService,
    messageService,
    pathHelperService) {
    this.$scope = $scope;
    this.$rootScope = $rootScope;
    this.$routeParams = $routeParams;
    this.lodash = lodash;
    this.subscriptionKeys = subscriptionKeys;
    this.api = api;
    this.draftService = draftService;
    this.messageService = messageService;
    this.pathHelperService = pathHelperService;
  }

  $onInit() {
    this.currentDraftCounter = 0;

    this.draftService.getDraft().then(draft => {
      this.draftStatus = this.draftService.getStatus();

      if ((draft !== null) && (draft.setting_up === true)) {
        this.pageError = true;
        this.pathHelperService.sendToPreviousPath();
        this.messageService.showWarning('Draft is still setting up');
      } else if ((draft !== null) && ((draft.in_progress === true) || (draft.complete === true))) {
        this.draft = draft;

        this.currentDraftCounter = draft.draft_counter;

        this._loadTradeData(this.draft.draft_id);

        this.deregister = this.$scope.$on(this.subscriptionKeys.draftCounterHasChanged, (event, args) => {
          const {draft, status} = args;

          this.onDraftCounterChanged(draft, status);
        }).bind(this);
      }
    }, () => {
      this.messageService.showError('Unable to load draft');
    });
  }

  $onDestroy() {
    this.deregister();
  }

  onDraftCounterChanged(draft, status) {
    if (angular.isDefined(this.draft)) {
      this.lodash.merge(this.draft, draft);
      this.currentDraftCounter = this.draft.draft_counter;
    } else {
      this.draft = draft;
    }

    this.lodash.merge(this.draftStatus, status);

    this._loadTradeData(this.draft.draft_id);
  }

  _loadTradeData(draftId) {
    const tradesSuccess = data => {
      this.tradesLoading = false;
      this.trades = data;

      this.trades.forEach(trade => {
        trade.trade_time = new Date(trade.trade_time);
      });
    };

    const errorHandler = () => {
      this.tradesLoading = false;
      this.tradesError = true;
    };

    this.tradesLoading = true;
    this.tradesError = false;

    if (this.draftStatus.valid && !this.draftStatus.locked) {
      this.api.Trade.query({draft_id: draftId}, tradesSuccess, errorHandler);
    }
  }
}

TradesController.$inject = [
  '$scope',
  '$rootScope',
  '$routeParams',
  'lodash',
  'subscriptionKeys',
  'api',
  'draftService',
  'messageService',
  'pathHelperService',
];

angular.module('phpdraft.draft').component('phpdTrades', {
  controller: TradesController,
  templateUrl: 'app/features/draft/trades/trades.component.html',
});
