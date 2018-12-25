angular.module('phpdraft').config(($routeProvider, $locationProvider) => {
  $locationProvider
    .html5Mode(true)
    .hashPrefix('!');

  // $routeProvider.when('/draft/:draft_id/search', {
  //   controller: 'SearchController',
  //   controllerAs: 'searchCtrl',
  //   templateUrl: 'app/templates/picks/search.html'
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
