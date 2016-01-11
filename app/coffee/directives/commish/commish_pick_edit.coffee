angular.module("app").directive 'commishPickEdit', ->
    restrict: 'E'
    controller: 'CommishPickEditController'
    controllerAs: 'commishPickEditCtrl'
    templateUrl: 'app/templates/directives/commish/commish_pick_edit.html'
    scope:
      draft: "="
      currentPick: "="
      pristinePick: "="
      positions: "="
      teams: "="
      manualEntry: "="
      editInProgress: "="
      pickAction: "&"
      pickActionText: "@"
      pickIcon: "@"

