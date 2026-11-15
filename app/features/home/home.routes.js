angular.module('phpdraft.home').config(($routeProvider, $locationProvider, sessionCheckedResolve) => {
  $locationProvider
    .html5Mode(true)
    .hashPrefix('!');

  $routeProvider.when('/', {
    template: '<phpd-home></phpd-home>',
    resolve: sessionCheckedResolve,
  });

  $routeProvider.when('/home', {
    template: '<phpd-home></phpd-home>',
    resolve: sessionCheckedResolve,
  });
});
