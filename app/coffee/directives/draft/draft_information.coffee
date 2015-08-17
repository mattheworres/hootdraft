angular.module("app").directive 'draftInformation', ->
    restrict: 'E'
    templateUrl: 'app/templates/directives/draft/draft_information.html'
    scope:
      draft: "="
