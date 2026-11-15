angular.module('phpdraft.authentication').config(($routeProvider, $locationProvider, sessionCheckedResolve) => {
  $locationProvider
    .html5Mode(true)
    .hashPrefix('!');

  $routeProvider.when('/login', {
    template: '<phpd-login></phpd-login>',
    resolve: sessionCheckedResolve,
  });

  $routeProvider.when('/lostPassword', {
    template: '<phpd-lost-password></phpd-lost-password>',
    resolve: sessionCheckedResolve,
  });

  $routeProvider.when('/resetPassword/:email?/:token?', {
    template: '<phpd-reset-password></phpd-reset-password>',
    resolve: sessionCheckedResolve,
  });

  $routeProvider.when('/register', {
    template: '<phpd-register></phpd-register>',
    resolve: sessionCheckedResolve,
  });

  $routeProvider.when('/verify/:email?/:token?', {
    template: '<phpd-verify-account></phpd-verify-account>',
    resolve: sessionCheckedResolve,
  });

  $routeProvider.when('/profile', {
    template: '<phpd-edit-profile></phpd-edit-profile>',
  });

  $routeProvider.when('/inviteSetup/:email?/:token?', {
    template: '<phpd-invite-setup></phpd-invite-setup>',
    resolve: sessionCheckedResolve,
  });
});
