angular.module("app").directive 'pickDisplay', ->
    restrict: 'E'
    templateUrl: 'app/templates/directives/picks/pick_display.html'
    scope:
      draft: "="
      pick: "="
      first: "="
