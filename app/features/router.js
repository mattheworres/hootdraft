angular.module('phpdraft').config(($routeProvider, $locationProvider) => {
  $locationProvider
    .html5Mode(true)
    .hashPrefix('!');

  // $routeProvider.when('/draft/:draft_id/stats', {
  //   controller: 'StatsController',
  //   controllerAs: 'statsCtrl',
  //   templateUrl: 'app/templates/draft/stats.html'
  // });


  // $routeProvider.when('/draft/:draft_id/picks/manager', {
  //   controller: 'ByManagerController',
  //   controllerAs: 'managerCtrl',
  //   templateUrl: 'app/templates/picks/by_manager.html'
  // });

  // $routeProvider.when('/draft/:draft_id/picks/round', {
  //   controller: 'ByRoundController',
  //   controllerAs: 'roundCtrl',
  //   templateUrl: 'app/templates/picks/by_round.html'
  // });

  // $routeProvider.when('/draft/:draft_id/search', {
  //   controller: 'SearchController',
  //   controllerAs: 'searchCtrl',
  //   templateUrl: 'app/templates/picks/search.html'
  // });


  // $routeProvider.when('/commish/draft/create', {
  //   controller: 'DraftCreateController',
  //   controllerAs: 'draftCreateCtrl',
  //   templateUrl: 'app/templates/commish/draft_create.html'
  // });

  // $routeProvider.when('/commish/draft/:draft_id/picks/edit', {
  //   controller: 'EditPickController',
  //   controllerAs: 'editPickCtrl',
  //   templateUrl: 'app/templates/commish/edit_pick.html'
  // });

  // $routeProvider.when('/commish/draft/:draft_id/trades/add', {
  //   controller: 'AddTradeController',
  //   controllerAs: 'addTradeCtrl',
  //   templateUrl: 'app/templates/commish/add_trade.html'
  // });


  // $routeProvider.when('/admin/users', {
  //   controller: 'UsersController',
  //   controllerAs: 'usersCtrl',
  //   templateUrl: 'app/templates/admin/users.html',
  //   adminOnly: true
  // });

  // $routeProvider.when('/admin/stats', {
  //   controller: 'RegenerateStatsController',
  //   controllerAs: 'regenerateCtrl',
  //   templateUrl: 'app/templates/admin/regenerate_stats.html',
  //   adminOnly: true
  // });
});
