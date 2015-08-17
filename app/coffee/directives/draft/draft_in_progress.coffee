angular.module("app").directive 'draftInProgress', ->

    restrict: 'E'
    transclude: true
    templateUrl: 'app/templates/directives/draft/draft_in_progress.html'
    scope:
        draft: "="
        draftValid: "="
        lastFivePicks: "="
        nextFivePicks: "="
