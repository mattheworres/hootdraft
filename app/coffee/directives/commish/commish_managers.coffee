angular.module("app").directive 'commishManagers', ->
    restrict: 'E'
    controller: 'CommishManagersController'
    controllerAs: 'commishManagersCtrl'
    templateUrl: 'app/templates/directives/commish/commish_managers.html'
    scope:
        editableManagers: '='
        draft: '='
