angular.module('phpdraft.pick').config(($routeProvider, $locationProvider) => {
  $locationProvider
    .html5Mode(true)
    .hashPrefix('!');

  $routeProvider.when('/commish/draft/:draft_id/picks/add', {
    template: '<phpd-add-pick></phpd-add-pick>',
  });
});
