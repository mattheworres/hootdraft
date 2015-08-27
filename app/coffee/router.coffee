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

  $routeProvider.when '/by-commish',
    controller: 'ByCommishController'
    controllerAs: 'byCommishCtrl'
    templateUrl: 'app/templates/home/by_commish.html'
    reloadOnSearch: false

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

  $routeProvider.when '/draft/:draft_id',
    controller: 'DraftIndexController'
    controllerAs: 'draftCtrl'
    templateUrl: 'app/templates/draft/index.html'

  $routeProvider.when '/draft/:draft_id/picks/manager',
    controller: 'ByManagerController'
    controllerAs: 'managerCtrl'
    templateUrl: 'app/templates/picks/by_manager.html'

  $routeProvider.when '/draft/:draft_id/picks/round',
    controller: 'ByRoundController'
    controllerAs: 'roundCtrl'
    templateUrl: 'app/templates/picks/by_round.html'

  $routeProvider.when '/draft/:draft_id/trades',
    controller: 'TradesController'
    controllerAs: 'tradesCtrl'
    templateUrl: 'app/templates/draft/trades.html'

  $routeProvider.when '/draft/:draft_id/search',
    controller: 'SearchController'
    controllerAs: 'searchCtrl'
    templateUrl: 'app/templates/picks/search.html'

  $routeProvider.when '/draft/:draft_id/stats',
    controller: 'StatsController'
    controllerAs: 'statsCtrl'
    templateUrl: 'app/templates/draft/stats.html'