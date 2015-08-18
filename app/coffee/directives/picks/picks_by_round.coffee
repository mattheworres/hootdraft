angular.module("app").directive 'picksByRound', ->
    restrict: 'E'
    templateUrl: 'app/templates/directives/picks/picks_by_round.html'
    scope:
      draft: "="
      selectedDraftRound: "="
      picks: "="
      pagerItemTally: "="
