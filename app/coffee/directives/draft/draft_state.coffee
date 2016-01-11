angular.module("app").directive 'draftState', ->

    restrict: 'E'
    transclude: true
    templateUrl: 'app/templates/directives/draft/draft_state.html'
    scope:
        ctrl: "=controller"
        commishName: "="
        draftError: "="
        draftLoading: "="
        draftLocked: "="
