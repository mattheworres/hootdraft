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

  $routeProvider.when '/verify/:email?/:token?',
    controller: 'VerificationController'
    controllerAs: 'verificationCtrl'
    templateUrl: 'app/templates/authentication/verify.html'

  $routeProvider.when '/lostPassword',
    controller: 'LostPasswordController'
    controllerAs: 'lostPasswordCtrl'
    templateUrl: 'app/templates/authentication/lost.html'

  $routeProvider.when '/resetPassword/:email?/:token?',
    controller: 'ResetPasswordController'
    controllerAs: 'resetCtrl'
    templateUrl: 'app/templates/authentication/reset.html'