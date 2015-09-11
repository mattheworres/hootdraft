angular.module("app").directive 'previousPicks', ->
    restrict: 'E'
    templateUrl: 'app/templates/directives/picks/previous_picks.html'
    scope:
      draft: "="
      previousPicks: "="
      previousLoading: "="
      previousError: "="