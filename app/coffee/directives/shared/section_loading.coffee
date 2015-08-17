angular.module("app").directive 'sectionLoading', ->
    restrict: 'E'
    templateUrl: 'app/templates/directives/shared/section_loading.html'
    scope:
        showLoading: "="
