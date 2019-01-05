class DraftEditController {
  constructor($scope,
    $loading,
    $location,
    $window,
    subscriptionKeys,
    workingModalService,
    api,
    messageService,
    depthChartPositionService,
    draftService,
    errorService,
  ) {
    this.$scope = $scope;
    this.$loading = $loading;
    this.$location = $location;
    this.$window = $window;
    this.subscriptionKeys = subscriptionKeys;
    this.workingModalService = workingModalService;
    this.api = api;
    this.messageService = messageService;
    this.depthChartPositionService = depthChartPositionService;
    this.draftService = draftService;
    this.errorService = errorService;

    this.onDepthChartPositionChanged = this.onDepthChartPositionChanged.bind(this);
  }

  $onInit() {
    this.draftEdit = {
      using_depth_charts: false, // eslint-disable-line camelcase
      depthChartPositions: [],
    };
    this.hasNonStandardPositions = false;
    this.depthChartPositionIndex = -1;
    this.draftLoading = true;
    this.draftLoaded = false;
    this.sportChangeListenerRegistered = false;
    this.draftError = false;

    this.status = this.draftService.getStatus();

    this.draftService.getDraft().then(draft => {
      this.draft = draft;
      //this._handleDraftUpdate(draft, this.status);
      this._loadCommishDraft(this.draft.draft_id);
    }, () => {
      this.messageService.showError('Unable to load draft');
    });
  }

  _bindDraftSpecificListeners() {
    this.$scope.$watch('$ctrl.draftEdit.draft_sport', () => this.sportChanged());
  }

  _loadCommishDraft(draftId) {
    this.draftLoaded = true;

    const draftInitializeSuccess = data => {
      angular.merge(this.draftEdit, data);
      this.draftLoading = false;
      this._bindDraftSpecificListeners();
    };

    const draftInitializeErrorHandler = () => {
      this.draftLoading = false;
      this.draftError = true;
      this.messageService.showError('Unable to load draft');
    };

    this.api.Draft.commishGet({draft_id: draftId}, draftInitializeSuccess, draftInitializeErrorHandler);// eslint-disable-line camelcase
  }

  sportChanged() {
    const positionsSuccess = data => {
      const positionResetCallback = () => this.$loading.finish('load_data');

      if (angular.isUndefined(this.draftEdit.depthChartPositions) || this.draftEdit.depthChartPositions.length === 0) {
        this.depthChartPositionService.createPositionsBySport(this.draftEdit, data.positions, positionResetCallback);
      } else {
        positionResetCallback();
      }

      this.hasNonStandardPositions = this.depthChartPositionService.calculateRoundsFromPositions(this.draftEdit);
      this.depthChartsUnique = this.depthChartPositionService.getDepthChartPositionValidity(this.draftEdit);
    };

    const positionsError = () => {
      this.$loading.finish('load_data');
      this.messageService.showError('Unable to load positions for the given draft sport');
    };

    if (angular.isUndefined(this.draftEdit) || angular.isUndefined(this.draftEdit.draft_sport) || this.draftEdit.draft_sport.length === 0) {
      return;
    }

    this.$loading.start('load_data');
    this.api.DepthChartPosition.getPositions({draft_sport: this.draftEdit.draft_sport}, positionsSuccess, positionsError); // eslint-disable-line camelcase
  }

  editClicked() {
    if (!this.editFormIsInvalid()) {
      this._edit();
    }
  }

  cancelClicked() {
    this.$window.history.back();
  }

  //Same handler for both add/remove of position AND change of "On/Off"
  onDepthChartPositionChanged(usingDepthCharts, depthChartPositions) {
    this.draftEdit.using_depth_charts = usingDepthCharts; // eslint-disable-line camelcase
    this.draftEdit.depthChartPositions = depthChartPositions;
    this.hasNonStandardPositions = this.depthChartPositionService.calculateRoundsFromPositions(this.draftEdit);
    this.depthChartsUnique = this.depthChartPositionService.getDepthChartPositionValidity(this.draftEdit);

    if (this.draftEdit.using_depth_charts && (this.draftEdit.depthChartPositions.length === 0)) {
      this.sportChanged();
    }
  }

  editFormIsInvalid() {
    if (this.editInProgress || !this.form.$valid) {
      return true;
    }

    if (this.draftEdit.using_depth_charts) {
      return !this.depthChartsUnique;
    }

    return false;
  }

  _edit() {
    this.workingModalService.openModal();

    const editModel = {
      draft_id: this.draftEdit.draft_id, // eslint-disable-line camelcase
      name: this.draftEdit.draft_name,
      sport: this.draftEdit.draft_sport,
      style: this.draftEdit.draft_style,
      rounds: this.draftEdit.draft_rounds,
      password: this.draftEdit.draft_password,
      using_depth_charts: this.draftEdit.using_depth_charts, // eslint-disable-line camelcase
      depthChartPositions: this.draftEdit.depthChartPositions,
    };

    this.editInProgress = true;

    this.messageService.closeToasts();

    const editSuccessHandler = response => {
      this.editInProgress = false;
      this.workingModalService.closeModal();

      this.form.$setPristine();

      this.messageService.showSuccess(`${response.draft.draft_name} edited!`);

      this.draft = this.draftService.updateDraftInMemory(this.draftEdit);

      this.$location.path(`/draft/${this.draftEdit.draft_id}`);
    };

    const editFailureHandler = response => {
      this.editInProgress = false;
      this.workingModalService.closeModal();
      const registerError = this.errorService.parseValidationErrorsFromResponse(response);

      this.messageService.showError(`${registerError}`, 'Unable to edit draft');
    };

    this.api.Draft.update(editModel, editSuccessHandler, editFailureHandler);
  }
}

DraftEditController.$inject = [
  '$scope',
  '$loading',
  '$location',
  '$window',
  'subscriptionKeys',
  'workingModalService',
  'api',
  'messageService',
  'depthChartPositionService',
  'draftService',
  'errorService',
];

angular.module('phpdraft.draft').component('phpdDraftEdit', {
  controller: DraftEditController,
  templateUrl: 'app/features/draft/edit/draftEdit.component.html',
});
