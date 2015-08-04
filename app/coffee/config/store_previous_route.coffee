angular.module("app").run ($rootScope, $sessionStorage) ->
  $rootScope.$on '$routeChangeSuccess', (event, current, previous) ->
    if previous? and previous.$$route? and previous.$$route.originalPath?
      $sessionStorage.previousRoute = previous.$$route.originalPath