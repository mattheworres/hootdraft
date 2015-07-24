angular.module('app').config ($routeProvider, $locationProvider) ->
    $locationProvider
        .html5Mode(true)
        .hashPrefix('!')

    $routeProvider.when '/',
        controller: 'HomeController'
        templateUrl: 'app/templates/home/home.html'
