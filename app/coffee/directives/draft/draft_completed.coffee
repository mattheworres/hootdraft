angular.module("app").directive 'draftCompleted', ->

    restrict: 'E'
    transclude: true
    templateUrl: 'app/templates/directives/draft/draft_completed.html'
    scope:
        draft: "="
        draftValid: "="
        last10Picks: "="
