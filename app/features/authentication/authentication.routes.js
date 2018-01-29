angular.module('phpdraft.authentication').config(($routeProvider, $locationProvider) => {
  $locationProvider
    .html5Mode(true)
    .hashPrefix('!');

  $routeProvider.when('/login', {
    template: '<login></login>',
  });

  $routeProvider.when('/lostPassword', {
    template: '<lost-password></lost-password>',
  });
});
