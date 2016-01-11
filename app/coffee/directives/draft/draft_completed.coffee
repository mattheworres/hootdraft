angular.module("app").directive 'draftCompleted', ->
    restrict: 'E'
    templateUrl: 'app/templates/directives/draft/draft_completed.html'
    scope:
        draft: "="
        draftValid: "="
        roundPicks: "="
        selectedDraftRound: "="
        pagerItemTally: "="
