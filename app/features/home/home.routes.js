angular.module('phpdraft.home').config(($routeProvider, $locationProvider) => {
  $locationProvider
    .html5Mode(true)
    .hashPrefix('!');

  $routeProvider.when('/', {
    template: '<home></home>',
  });

  $routeProvider.when('/home', {
    template: '<home></home>',
  });

  $routeProvider.when('/by-commish', {
    template: '<by-commish></by-commish>',
    reloadOnSearch: false,
  });
});
