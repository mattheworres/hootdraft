class DepthChartsController {
  constructor($scope,
    $routeParams,
    $loading,
    $sessionStorage,
    lodash,
    subscriptionKeys,
    api,
    draftService,
    messageService,
    pathHelperService) {
    this.$scope = $scope;
    this.$routeParams = $routeParams;
    this.$loading = $loading;
    this.$sessionStorage = $sessionStorage;
    this.lodash = lodash;
    this.subscriptionKeys = subscriptionKeys;
    this.api = api;
    this.draftService = draftService;
    this.messageService = messageService;
    this.pathHelperService = pathHelperService;
  }

  $onInit() {
    this.depthChartLoading = true;
    this.managerChosen = false;
    this.moveInProgress = false;

    this.depthChartPositions = [];

    this._loadManagers();

    this.draftService.getDraft().then(draft => {
      this.draftStatus = this.draftService.getStatus();

      if ((draft !== null) && (draft.setting_up === true)) {
        this.pageError = true;
        this.pathHelperService.sendToPreviousPath();
        this.messageService.showWarning('Draft is still setting up');
      } else if ((draft !== null) && ((draft.in_progress === true) || (draft.complete === true))) {
        this.draft = draft;

        this.currentDraftCounter = draft.draft_counter;

        this.deregisterDraftCounterChangeWatch = this.$scope.$on(this.subscriptionKeys.draftCounterHasChanged, (event, args) => {
          const {draft, status} = args;

          this.onDraftCounterChanged(draft, status);
        }).bind(this);
      }
    }, () => {
      this.messageService.showError('Unable to load draft');
    });

    this.storedManager = this.$sessionStorage.depthChartManager;

    if (angular.isDefined(this.storedManager) && (parseInt(this.storedManager.draft_id, 10) === parseInt(this.$routeParams.draft_id, 10))) {
      this.selectedManager = this.storedManager;
    } else {
      delete this.$sessionStorage.depthChartManager;
    }

    this.deregisterSelectedManagerWatch = this.$scope.$watch('$ctrl.selectedManager', () => {
      if (angular.isUndefined(this.selectedManager)) {
        return;
      }

      this.managerChosen = parseInt(this.selectedManager.manager_id, 10) > 0;

      if (this.managerChosen) {
        this.$sessionStorage.depthChartManager = this.selectedManager;
        this._loadUpdatedData();
      }
    }, true);
  }

  $onDestroy() {
    this.deregisterDraftCounterChangeWatch();
    this.deregisterSelectedManagerWatch();
  }

  onDraftCounterChanged(draft, status) {
    if (angular.isDefined(this.draft)) {
      this.currentDraftCounter = this.draft.draft_counter;
      this.lodash.merge(this.draft, draft);
    } else {
      this.draft = draft;
    }

    this.lodash.merge(this.draftStatus, status);

    if (this.managerChosen && !this.moveInProgress) {
      this._loadUpdatedData();
      this.currentDraftCounter = draft.draft_counter;
    }
  }

  _loadManagers() {
    const managerSuccess = data => {
      this.managers = data;
      let containsValidManager = angular.isDefined(this.selectedManager);

      if (angular.isDefined(this.selectedManager)) {
        containsValidManager = this.lodash.findIndex(this.managers, {'manager_id': this.selectedManager.manager_id}) !== -1;
      }

      if (!this.managerChosen || !containsValidManager) {
        this.selectedManager = data[0];
        this.$sessionStorage.depthChartManager = this.selectedManager;
      }

      this.depthChartLoading = false;
    };

    const managersError = () => {
      this.messageService.showError('Unable to load managers');
      this.depthChartLoading = false;
    };

    this.api.Manager.getManagers({draft_id: this.$routeParams.draft_id}, managerSuccess, managersError);
  }

  enableDragging() {
    if (angular.isUndefined(this.draft)) {
      return false;
    }

    const draftInProgress = this.draft.in_progress;
    const draftCompleted = this.draft.complete;

    if (draftInProgress) {
      return true;
    }

    const hasEndTime = angular.isDefined(this.draft.draft_end_time);

    if (draftCompleted && hasEndTime) {
      const now = (new Date().getTime()) / 1000;
      //Replace dashes with slashes, fixes a weird date initialization bug only in Firefox:
      const draftEnd = (new Date(this.draft.draft_end_time.replace(/-/g,'/')).getTime()) / 1000; // eslint-disable-line comma-spacing
      const millisecondsElapsed = now - draftEnd;

      return millisecondsElapsed <= 600;
    }

    return false;
  }

  enableUnassignedTooltips(position) {
    return !this.moveInProgress && (position.position === 'Unassigned') && this.enableDragging();
  }

  _loadUpdatedData() {
    const depthChartSuccess = data => {
      this.moveInProgress = false;
      this.$loading.finish('loading_depth_chart');
      this.depthChartPositions = data.depthChartPositions;
    };

    const depthChartError = () => {
      this.moveInProgress = false;
      this.$loading.finish('loading_depth_chart');
      this.messageService.showError('Unable to load depth chart.');
    };

    this.moveInProgress = true;
    this.$loading.start('loading_depth_chart');
    this.api.DepthChartPosition.getDepthChart({draft_id: this.$routeParams.draft_id, manager_id: this.selectedManager.manager_id}, depthChartSuccess, depthChartError);
  }

  _updateDepthCharts() {
    if (angular.isUndefined(this.depthChartPositions) || (this.depthChartPositions.length === 0)) {
      return;
    }

    this.depthChartPositions.map(position => position.picks.forEach(pick => {
      if (pick.depth_chart_position_id !== position.depth_chart_position_id) {
        pick.depth_chart_position_id = position.depth_chart_position_id;

        const updateSuccess = updatedPick => {
          this.$loading.finish('loading_depth_chart');
          this.moveInProgress = false;
          pick.depth_chart_position_id = updatedPick.depth_chart_position_id;
        };

        const updateError = () => {
          this.$loading.finish('loading_depth_chart');
          this.messageService.showError('Unable to update depth chart.');
        };

        if (!this.moveInProgress) {
          this.moveInProgress = true;
          this.$loading.start('loading_depth_chart');
          this.api.DepthChartPosition.update({draft_id: this.$routeParams.draft_id, position_id: position.depth_chart_position_id, pick_id: pick.player_id}, updateSuccess, updateError);
        }
      }
    }));
  }

  positionStyle(position) {
    const widthCoefficient = position.position === 'Unassigned' ? 195 : 275;
    const calculatedWidth = (position.picks === null ? false : position.picks.length) ? position.picks.length * widthCoefficient : widthCoefficient;
    if ((position.picks.length === 0) || this.moveInProgress) {
      return '100%';
    }
    return `${calculatedWidth}px`;
  }

  positionDetailClass(position) {
    let className = 'position-default';

    if (position.position === 'Unassigned') {
      return className;
    }

    className += ' position-half';

    if (position.picks.length) {
      const positionPercentage = parseInt((position.picks.length / position.slots) * 100, 10);

      if ((positionPercentage >= 50) && (positionPercentage < 100)) {
        className += ' position-three-quarter';
      } else if (positionPercentage === 100) {
        className += ' position-full';
      } else if (positionPercentage > 100) {
        className += ' position-over-full';
      }
    }

    return className;
  }

  //Rather than relying on the $index value from ng-repeat, we need to manually find the array index
  //rather than the displayed array index Angular uses in order to remove the pick from the original
  //picks array:
  removePickFromPosition(positionPicks, pick) {
    const pickIndex = this.lodash.findIndex(positionPicks, {'player_id': pick.player_id});

    return positionPicks.splice(pickIndex, 1);
  }

  //DND Event Handlers
  dnd_dragstart() {
    this.moveInProgress = true;
  }

  dnd_dragend() {
    this.moveInProgress = false;
    this._updateDepthCharts();
  }
}

DepthChartsController.$inject = [
  '$scope',
  '$routeParams',
  '$loading',
  '$sessionStorage',
  'lodash',
  'subscriptionKeys',
  'api',
  'draftService',
  'messageService',
  'pathHelperService',
];

angular.module('phpdraft.draft').component('phpdDepthCharts', {
  controller: DepthChartsController,
  templateUrl: 'app/features/draft/depthCharts.component.html',
});
