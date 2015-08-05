angular.module('app').config ($routeProvider, $locationProvider) ->
  $locationProvider 
    .html5Mode(true)
    .hashPrefix('!')

  $routeProvider.when '/',
    controller: 'HomeController'
    controllerAs: 'homeCtrl'
    templateUrl: 'app/templates/home/home.html'

  $routeProvider.when '/home',
    controller: 'HomeController'
    controllerAs: 'homeCtrl'
    templateUrl: 'app/templates/home/home.html'

  $routeProvider.when '/login',
    controller: 'LoginController'
    controllerAs: 'loginCtrl'
    templateUrl: 'app/templates/authentication/login.html'

  $routeProvider.when '/register',
    controller: 'RegisterController'
    controllerAs: 'registerCtrl'
    templateUrl: 'app/templates/authentication/register.html'