angular.module('app').config ($routeProvider, $locationProvider) ->
    $locationProvider
        .html5Mode(true)
        .hashPrefix('!')

    $routeProvider.when '/',
        controller: 'HomeController'
        controllerAs: 'homeCtrl'
        templateUrl: 'app/templates/home/home.html'
