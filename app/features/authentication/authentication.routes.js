angular.module('phpdraft.authentication').config(($routeProvider, $locationProvider) => {
  $locationProvider
    .html5Mode(true)
    .hashPrefix('!');

  $routeProvider.when('/login', {
    template: '<phpd-login></phpd-login>',
  });

  $routeProvider.when('/lostPassword', {
    template: '<phpd-lost-password></phpd-lost-password>',
  });

  $routeProvider.when('/resetPassword/:email?/:token?', {
    template: '<phpd-reset-password></phpd-reset-password>',
  });

  $routeProvider.when('/register', {
    template: '<phpd-register></phpd-register>',
  });

  $routeProvider.when('/verify/:email?/:token?', {
    template: '<phpd-verify-account></phpd-verify-account>',
  });

  $routeProvider.when('/profile', {
    template: '<phpd-edit-profile></phpd-edit-profile>',
  });
});
