angular.module("app").directive 'managerPicksDisplay', ->
    restrict: 'E'
    templateUrl: 'app/templates/directives/picks/manager_picks_display.html'
    scope:
      draft: "="
      managers: "="
      selectedManager: "="
      managerPicks: "="
