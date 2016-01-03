angular.module("app").directive 'draftInProgress', ->
    restrict: 'E'
    templateUrl: 'app/templates/directives/draft/draft_in_progress.html'
    scope:
        draft: "="
        draftValid: "="
        lastFivePicks: "="
        nextFivePicks: "="
        lastFiveLoading: "="
        nextFiveLoading: "="
