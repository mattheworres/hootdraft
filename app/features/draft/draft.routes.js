angular.module('phpdraft.draft').config(($routeProvider, $locationProvider) => {
  $locationProvider
    .html5Mode(true)
    .hashPrefix('!');

  $routeProvider.when('/draft/:draft_id/board', {
    template: '<phpd-board></phpd-board>',
  });
});
