angular.module('phpdraft.home').config(($routeProvider, $locationProvider) => {
  $locationProvider
    .html5Mode(true)
    .hashPrefix('!');

  $routeProvider.when('/', {
    template: '<phpd-home></phpd-home>',
  });

  $routeProvider.when('/home', {
    template: '<phpd-home></phpd-home>',
  });
});
