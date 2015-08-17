angular.module("app").directive 'sectionError', ->
    restrict: 'E'
    templateUrl: 'app/templates/directives/shared/section_error.html'
    scope:
        showError: "="
        sectionName: "@sectionName"
