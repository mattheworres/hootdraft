class DepthChartPositionsController {
  constructor(depthChartPositionService) {
    this.depthChartPositionService = depthChartPositionService;
  }

  getDepthChartChangeHandler() {
    if (angular.isDefined(this.changeHandler)) return this.changeHandler;

    this.changeHandler = this.onDepthChartPositionsChange();

    return this.changeHandler;
  }

  addDepthChartPosition() {
    if (this.editInProgress) return;

    const changeHandler = this.getDepthChartChangeHandler();

    changeHandler(
      this.usingDepthCharts === true,
      this.depthChartPositionService.addDepthChartPosition(this.depthChartPositions),
    );
  }

  deleteDepthChartPosition(index) {
    if (this.editInProgress || (index === -1)) return;

    const changeHandler = this.getDepthChartChangeHandler();

    changeHandler(
      this.usingDepthCharts === true,
      this.depthChartPositionService.deleteDepthChartPosition(this.depthChartPositions, index)
    );
  }

  //Mainly to update the nonstandard position flag
  onPositionChange() {
    if (this.editInProgress) return;

    const changeHandler = this.getDepthChartChangeHandler();
    const usingDepthCharts = this.usingDepthCharts === true;

    changeHandler(usingDepthCharts, usingDepthCharts ? this.depthChartPositions : []);
  }
}

DepthChartPositionsController.$inject = [
  'depthChartPositionService',
];

angular.module('phpdraft.draft').component('phpdDepthChartPositions', {
  controller: DepthChartPositionsController,
  templateUrl: 'app/features/draft/depthChartPositions.component.html',
  bindings: {
    usingDepthCharts: '<',
    depthChartPositions: '<',
    hasNonStandardPositions: '<',
    depthChartsUnique: '<',
    editInProgress: '<',
    onDepthChartPositionsChange: '&',
  },
});
