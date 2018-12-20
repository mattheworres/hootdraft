class DraftCreateController {
  constructor($scope,
    $loading,
    $window,
    $location,
    workingModalService,
    api,
    messageService,
    depthChartPositionService,
    errorService) {
    this.$scope = $scope;
    this.$loading = $loading;
    this.$window = $window;
    this.$location = $location;
    this.workingModalService = workingModalService;
    this.api = api;
    this.messageService = messageService;
    this.depthChartPositionService = depthChartPositionService;
    this.errorService = errorService;

    this.onDepthChartPositionChanged = this.onDepthChartPositionChanged.bind(this);
  }

  $onInit() {
    this.draft = {
      using_depth_charts: false,
      depthChartPositions: [],
    };

    this._loadDraftData();
  }

  _loadDraftData() {
    this.draftLoading = true;
    this.draftError = false;

    const draftInitializeSuccess = data => {
      angular.merge(this.draft, data);
      this.draftLoading = false;
    };

    const draftInitializeErrorHandler = () => {
      this.draftLoading = false;
      this.draftError = true;
      this.messageService.showError('Unable to load draft defaults');
    };

    this.api.Draft.getCreate({}, draftInitializeSuccess, draftInitializeErrorHandler);
  }

  createClicked() {
    if (!this.createFormIsInvalid()) {
      this._create();
    }
  }

  sportChanged() {
    const positionsSuccess = data => {
      const positionResetCallback = () => this.$loading.finish('load_data');

      if (angular.isUndefined(this.draft.depthChartPositions) || this.draft.depthChartPositions.length === 0) {
        this.depthChartPositionService.createPositionsBySport(this.draft, data.positions, positionResetCallback);
      } else {
        positionResetCallback();
      }

      this.hasNonStandardPositions = this.depthChartPositionService.calculateRoundsFromPositions(this.draft);
      this.depthChartsUnique = this.depthChartPositionService.getDepthChartPositionValidity(this.draft);
    };

    const positionsError = () => {
      this.$loading.finish('load_data');
      this.messageService.showError('Unable to load positions for the given draft sport');
    };

    if (this.draft.draft_sport && this.draft.draft_sport.length > 0) {
      this.$loading.start('load_data');
      this.api.DepthChartPosition.getPositions({draft_sport: this.draft.draft_sport}, positionsSuccess, positionsError);
    }
  }

  cancelClicked() {
    this.$window.history.back();
  }

  //Same handler for both add/remove of position AND change of "On/Off"
  onDepthChartPositionChanged(usingDepthCharts, depthChartPositions) {
    this.draft.using_depth_charts = usingDepthCharts;
    this.draft.depthChartPositions = depthChartPositions;
    this.hasNonStandardPositions = this.depthChartPositionService.calculateRoundsFromPositions(this.draft);
    this.depthChartsUnique = this.depthChartPositionService.getDepthChartPositionValidity(this.draft);

    if (this.draft.using_depth_charts && (this.draft.depthChartPositions.length === 0)) {
      this.sportChanged();
    }
  }

  createFormIsInvalid() {
    if (this.createInProgress || !this.form.$valid) {
      return true;
    }

    if (this.draft.using_depth_charts) {
      return !this.depthChartsUnique;
    }

    return false;
  }

  _create() {
    this.workingModalService.openModal();

    const createModel = {
      name: this.form.name.$viewValue,
      sport: this.draft.draft_sport,
      style: this.draft.draft_style,
      rounds: this.form.rounds.$viewValue,
      password: this.form.password.$viewValue,
      using_depth_charts: this.draft.using_depth_charts,
      depthChartPositions: this.draft.depthChartPositions,
    };

    this.createInProgress = true;

    this.messageService.closeToasts();

    const createSuccessHandler = response => {
      this.createInProgress = false;
      this.workingModalService.closeModal();

      this.form.$setPristine();

      this.messageService.showSuccess(`${response.draft.draft_name} created!`);
      this.$location.path(`/draft/${response.draft.draft_id}`);
    };

    const createFailureHandler = response => {
      this.createInProgress = false;
      this.workingModalService.closeModal();

      const createError = this.errorService.parseValidationErrorsFromResponse(response);

      this.messageService.showError(`${createError}`, 'Unable to create draft');
    };

    this.api.Draft.save(createModel, createSuccessHandler, createFailureHandler);
  }
}

DraftCreateController.$inject = [
  '$scope',
  '$loading',
  '$window',
  '$location',
  'workingModalService',
  'api',
  'messageService',
  'depthChartPositionService',
  'errorService',
];

angular.module('phpdraft.draft').component('phpdDraftCreate', {
  controller: DraftCreateController,
  templateUrl: 'app/features/draft/create/draftCreate.component.html',
});
