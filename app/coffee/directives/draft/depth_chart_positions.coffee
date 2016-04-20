class DepthChartPositionDirectiveController extends BaseController
  @register 'DepthChartPositionDirectiveController'
  @inject 'depthChartPositionService'

  #Hack: I couldnt figure a way to resolve the $index from the directive template, to here
  #then to the original controller. So we set a property both listen on instead. Ugh.
  deletePosition: (displayIndex) ->
    @positionIndex = displayIndex

angular.module("app").directive 'depthChartPositions', ->
    restrict: 'E'
    templateUrl: 'app/templates/directives/draft/depth_chart_positions.html'
    scope:
        usingDepthCharts: "="
        depthChartPositions: "="
        hasNonStandardPositions: "="
        depthChartsUnique: "="
        positionIndex: "="
        addDepthChartPosition: "&"
    controller: 'DepthChartPositionDirectiveController'
    controllerAs: 'directiveCtrl'
    bindToController: true