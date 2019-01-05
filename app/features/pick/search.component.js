class SearchController {
  constructor($scope,
    $routeParams,
    $loading,
    subscriptionKeys,
    messageService,
    api,
    draftService,
    pathHelperService,
    errorService) {
    this.$scope = $scope;
    this.$routeParams = $routeParams;
    this.$loading = $loading;
    this.subscriptionKeys = subscriptionKeys;
    this.messageService = messageService;
    this.api = api;
    this.draftService = draftService;
    this.pathHelperService = pathHelperService;
    this.errorService = errorService;

    this._loadSearchData = this._loadSearchData.bind(this);
  }

  $onInit() {
    this.keywords = '';
    this.team = '';
    this.position = '';
    this.sort = 'DESC';
    this.currentDraftCounter = 0;

    this.draftService.getDraft().then(draft => {
      this.draftStatus = this.draftService.getStatus();
      this.currentDraftCounter = draft.draft_counter;
      this.draft = draft;

      if (draft !== null && draft.setting_up) {
        this.pageError = true;
        this.pathHelperService.sendToPreviousPath();
        this.messageService.showWarning('Draft is still setting up');
      } else {
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

    if (this.hasSearchItems()) this._loadSearchData(draft.draft_id, false);
  }

  onSearchItemsChange() {
    if (this.hasSearchItems()) this._loadSearchData(this.draft.draft_id, false);
  }

  hasSearchItems() {
    return ((this.keywords === null ? 0 : this.keywords.length) > 0) ||
      ((this.team === null ? 0 : this.team.length) > 0) ||
      ((this.position === null ? 0 : this.position.length) > 0);
  }

  clearSearchCriteria() {
    this.keywords = '';
    this.position = '';
    this.team = '';
  }

  toggleSort() {
    if (this.sort === 'DESC') {
      this.sort = 'ASC';
    } else {
      this.sort = 'DESC';
    }

    this.onSearchItemsChange();
  }

  _loadSearchData(draft_id, onPageLoad) {
    const searchSuccess = data => {
      this.searchResults = data.player_results;
      this.searchLoading = false;
      this.$loading.finish('load_search_picks');
    };

    const errorHandler = () => {
      this.searchError = true;
      this.searchLoading = false;
      this.messageService.showError('Unable to search picks');
      this.$loading.finish('load_search_picks');
    };

    if (this.draftStatus.valid && !this.draftStatus.locked && this.hasSearchItems()) {
      this.searchError = false;
      this.searchLoading = onPageLoad;
      this.$loading.start('load_search_picks');
      this.api.Pick.search({draft_id, keywords: this.keywords, team: this.team, position: this.position, sort: this.sort}, searchSuccess, errorHandler);
    } else if (!this.hasSearchItems()) {
      if ((this.searchResults !== null) && (this.searchResults.length > 0)) {
        this.searchResults = [];
      }
    }
  }
}

SearchController.$inject = [
  '$scope',
  '$routeParams',
  '$loading',
  'subscriptionKeys',
  'messageService',
  'api',
  'draftService',
  'pathHelperService',
  'errorService',
];

angular.module('phpdraft.pick').component('phpdSearch', {
  controller: SearchController,
  templateUrl: 'app/features/pick/search.component.html',
});
