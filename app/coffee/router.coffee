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

  $routeProvider.when '/profile',
    controller: 'EditProfileController'
    controllerAs: 'editProfileCtrl'
    templateUrl: 'app/templates/authentication/edit_profile.html'

  $routeProvider.when '/draft/:draft_id',
    controller: 'DraftIndexController'
    controllerAs: 'draftCtrl'
    templateUrl: 'app/templates/draft/index.html'

  $routeProvider.when '/draft/:draft_id/board',
    controller: 'BoardController'
    controllerAs: 'boardCtrl'
    templateUrl: 'app/templates/draft/board.html'

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

  $routeProvider.when '/commish/draft/create',
    controller: 'DraftCreateController'
    controllerAs: 'draftCreateCtrl'
    templateUrl: 'app/templates/commish/draft_create.html'

  $routeProvider.when '/commish/draft/:draft_id/edit',
    controller: 'DraftEditController'
    controllerAs: 'draftEditCtrl'
    templateUrl: 'app/templates/commish/draft_edit.html'

  $routeProvider.when '/commish/draft/:draft_id/timers',
    controller: 'PickTimersController'
    controllerAs: 'pickTimerCtrl'
    templateUrl: 'app/templates/commish/pick_timers.html'

  $routeProvider.when '/commish/draft/:draft_id/picks/add',
    controller: 'AddPickController'
    controllerAs: 'addPickCtrl'
    templateUrl: 'app/templates/commish/add_pick.html'

  $routeProvider.when '/commish/draft/:draft_id/picks/edit',
    controller: 'EditPickController'
    controllerAs: 'editPickCtrl'
    templateUrl: 'app/templates/commish/edit_pick.html'

  $routeProvider.when '/commish/draft/:draft_id/trades/add',
    controller: 'AddTradeController'
    controllerAs: 'addTradeCtrl'
    templateUrl: 'app/templates/commish/add_trade.html'

  $routeProvider.when '/admin/users',
    #To test the adminOnly flag:
    controller: 'HomeController'
    controllerAs: 'homeCtrl'
    templateUrl: 'app/templates/home/home.html'
    #controller: 'AdminUsersController'
    #controllerAs: 'adminUserCtrl'
    #templateUrl: 'app/templates/admin/users.html'
    adminOnly: true
