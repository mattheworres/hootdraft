angular.module("app").directive 'upcomingPicks', ->
    restrict: 'E'
    templateUrl: 'app/templates/directives/picks/upcoming_picks.html'
    scope:
      draft: "="
      nextPicks: "="
      nextLoading: "="
      nextError: "="