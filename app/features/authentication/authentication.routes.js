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

  $routeProvider.when('/resetPassword/:email?/:token?', {
    template: '<reset-password></reset-password>',
  });

  $routeProvider.when('/register', {
    template: '<register></register>',
  });

  $routeProvider.when('/verify/:email?/:token?', {
    template: '<verify-account></verify-account>',
  });
});
