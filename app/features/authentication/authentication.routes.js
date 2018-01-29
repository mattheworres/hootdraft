angular.module('phpdraft.authentication').config(($routeProvider, $locationProvider) => {
  $locationProvider
    .html5Mode(true)
    .hashPrefix('!');

  $routeProvider.when('/login', {
    template: '<login></login>',
  });
});
