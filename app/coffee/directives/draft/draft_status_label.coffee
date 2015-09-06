angular.module("app").directive 'draftStatusLabel', ->
    restrict: 'E'
    templateUrl: 'app/templates/directives/draft/draft_status_label.html'
    scope:
        draft: "="
